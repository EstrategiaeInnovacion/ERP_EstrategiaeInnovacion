<?php

namespace App\Services\ComercioExterior;

use App\Models\Legal\ComercioExterior\BomItem;
use App\Models\Legal\ComercioExterior\ReglaOrigen;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrigenCalculatorService
{
    private const PAISES_USMCA = [
        'MX', 'MEX', 'MEXICO', 'MÉXICO',
        'US', 'USA', 'EEUU', 'EUA', 'ESTADOS UNIDOS', 'UNITED STATES',
        'CA', 'CAN', 'CANADA', 'CANADÁ',
    ];

    private array $bomItemsCache = [];

    public function analizarItem(BomItem $item): array
    {
        $resultado = [
            'fraccion_fg'              => $item->fraccion_arancelaria_fg,
            'fraccion_rm'              => $item->fraccion_arancelaria_rm,
            'regla_encontrada'         => false,
            'regla_id'                 => null,
            'criterio'                 => null,
            'califica_originario'      => null,
            'regla_de_origen_texto'    => null,
            'presenta_cambio_fraccion' => null,
            'cumple_demas_requisitos'  => null,
            'requiere_apendice'        => false,
            'vcr_calculado'            => null,
            'vcr_minimo'               => null,
            'vcr_cumple'               => null,
            'errores'                  => [],
            'advertencias'             => [],
        ];

        $catalogo = $this->consultarReglaOrigen($item->fraccion_arancelaria_fg);
        $regla    = $catalogo ? $this->buildResolvedRule($catalogo) : null;

        if (! $regla) {
            $resultado['errores'][] = "No se encontró regla de origen para fracción {$item->fraccion_arancelaria_fg}";
            $this->persistirResultado($item, $resultado);

            return $resultado;
        }

        $resultado['regla_encontrada']      = true;
        $resultado['regla_id']              = $catalogo['regla_origen']?->id;
        $resultado['regla_de_origen_texto'] = $regla->regla_texto;
        $resultado['requiere_apendice']     = (bool) $regla->requiere_apendice;
        $resultado['catalogo_lookup_mode']  = $catalogo['lookup_mode'];
        $resultado['catalogo_fuente']       = $catalogo['from_apendice']
            ? 'Apéndice Automotriz'
            : ($catalogo['seccion_c'] ? 'Sección C + Reglas de Origen' : 'Reglas de Origen');
        $resultado['seccion_c_codigo']      = $catalogo['seccion_c']?->fraccion_tmec;
        $resultado['seccion_c_descripcion'] = $catalogo['seccion_c']?->descripcion;
        $resultado['partes_apendice']       = app(CatalogoRelacionService::class)
            ->findApendicePartes((string) $item->fraccion_arancelaria_rm)
            ->map(fn ($parte) => [
                'tabla'                => $parte->tabla,
                'tabla_codigo'         => $parte->tabla_codigo,
                'fraccion_arancelaria' => $parte->fraccion_arancelaria,
                'descripcion'          => $parte->descripcion,
            ])
            ->values()
            ->all();

        if ($regla->requiere_apendice) {
            $resultado['advertencias'][] = 'Esta fracción requiere verificar las disposiciones del Apéndice Automotriz.';
        }

        if (! empty($resultado['partes_apendice'])) {
            $resultado['advertencias'][] = 'El insumo aparece en Apéndice – Tablas de Partes y requiere revisión complementaria.';
        }

        $criterio              = $regla->criterio ?? $this->inferirCriterio($regla);
        $resultado['criterio'] = $criterio;

        $detalleCriterio = match ($criterio) {
            'A'     => $this->aplicarCriterioA($item, $regla),
            'B'     => $this->aplicarCriterioBOC($item, $regla, 'B'),
            'C'     => $this->aplicarCriterioBOC($item, $regla, 'C'),
            'D'     => $this->aplicarCriterioD($item, $regla),
            default => $this->aplicarReglaGenerica($item, $regla),
        };

        $resultado = array_merge($resultado, $detalleCriterio);

        $allBomItems             = $this->getBomItems($item->bom_id);
        $tieneNoOrig             = $allBomItems->some(
            fn ($i) => ! in_array(strtoupper(trim((string) $i->pais_de_origen)), self::PAISES_USMCA)
        );
        $resultado['criterio']   = $tieneNoOrig ? 'B' : 'C';

        if ($resultado['escape_tabla_bc'] ?? false) {
            $resultado['advertencias'][] = 'Condición de Escape (Art. 4.5): fracción del PT en Tabla B/C — VCR no alcanzado, califica originario solo por CC.';
        }

        $this->persistirResultado($item, $resultado);

        return $resultado;
    }

    public function analizarBom(int $bomId): array
    {
        $items = BomItem::where('bom_id', $bomId)
            ->whereNotNull('fraccion_arancelaria_fg')
            ->where('fraccion_arancelaria_fg', '!=', '')
            ->get();

        $resultados = [];
        foreach ($items as $item) {
            $resultados[$item->id] = $this->analizarItem($item);
        }

        $total       = count($resultados);
        $originarios = collect($resultados)->filter(fn ($r) => $r['califica_originario'] === 'Sí')->count();

        return [
            'items'   => $resultados,
            'summary' => [
                'total'                 => $total,
                'originarios'           => $originarios,
                'no_originarios'        => $total - $originarios,
                'porcentaje_originario' => $total > 0 ? round($originarios / $total * 100, 2) : 0,
            ],
        ];
    }

    public function aplicarCriterioA(BomItem $item, ReglaOrigen $regla): array
    {
        $pais         = strtoupper(trim($item->pais_de_origen ?? ''));
        $esOriginario = in_array($pais, self::PAISES_USMCA);

        return [
            'califica_originario'      => $esOriginario ? 'Sí' : 'No',
            'presenta_cambio_fraccion' => 'N/A',
            'cumple_demas_requisitos'  => $esOriginario ? 'Sí' : 'No',
        ];
    }

    public function aplicarCriterioBOC(BomItem $item, ReglaOrigen $regla, string $criterio): array
    {
        $resultado = [];

        if ($criterio === 'B') {
            $cambioCumple = $this->verificarCambioFraccion(
                $item->fraccion_arancelaria_fg ?? '',
                $item->fraccion_arancelaria_rm ?? '',
                $regla->nivel_cambio ?? 'partida'
            );
            $resultado['presenta_cambio_fraccion'] = $cambioCumple ? 'Sí' : 'No';
            $cumple = $cambioCumple;

            if ($regla->vcr_porcentaje) {
                $vcrResult = $this->calcularVCR($item, $regla);
                $resultado['vcr_calculado'] = $vcrResult['vcr'];
                $resultado['vcr_minimo']    = $vcrResult['minimo'];
                $resultado['vcr_cumple']    = $vcrResult['cumple'];
                $cumple                     = $cambioCumple && $vcrResult['cumple'];

                if (! $cumple && $cambioCumple && ! $vcrResult['cumple']) {
                    $fraccionFG = (string) ($item->fraccion_arancelaria_fg ?? '');
                    if ($fraccionFG !== '' && app(CatalogoRelacionService::class)->estaEnTablasBC($fraccionFG)) {
                        $cumple                        = true;
                        $resultado['escape_tabla_bc'] = true;
                    }
                }
            }

            $resultado['califica_originario']     = $cumple ? 'Sí' : 'No';
            $resultado['cumple_demas_requisitos'] = $cumple ? 'Sí' : 'No';

        } else {
            $resultado['presenta_cambio_fraccion'] = 'N/A';
            $vcrResult                             = $this->calcularVCR($item, $regla);
            $resultado['vcr_calculado']            = $vcrResult['vcr'];
            $resultado['vcr_minimo']               = $vcrResult['minimo'];
            $resultado['vcr_cumple']               = $vcrResult['cumple'];
            $cumple                                = $vcrResult['cumple'];

            $resultado['califica_originario']     = $cumple ? 'Sí' : 'No';
            $resultado['cumple_demas_requisitos'] = $cumple ? 'Sí' : 'No';
        }

        return $resultado;
    }

    public function aplicarCriterioD(BomItem $item, ReglaOrigen $regla): array
    {
        $vcrResult = $this->calcularVCR($item, $regla);

        return [
            'presenta_cambio_fraccion' => 'N/A',
            'vcr_calculado'            => $vcrResult['vcr'],
            'vcr_minimo'               => $vcrResult['minimo'],
            'vcr_cumple'               => $vcrResult['cumple'],
            'califica_originario'      => $vcrResult['cumple'] ? 'Sí' : 'No',
            'cumple_demas_requisitos'  => $vcrResult['cumple'] ? 'Sí' : 'No',
        ];
    }

    public function aplicarReglaGenerica(BomItem $item, ReglaOrigen $regla): array
    {
        $resultado    = [];
        $cambioCumple = true;

        if ($regla->requiere_cambio_fraccion) {
            $cambioCumple = $this->verificarCambioFraccion(
                $item->fraccion_arancelaria_fg ?? '',
                $item->fraccion_arancelaria_rm ?? '',
                $regla->nivel_cambio ?? 'partida'
            );
            $resultado['presenta_cambio_fraccion'] = $cambioCumple ? 'Sí' : 'No';
        } else {
            $resultado['presenta_cambio_fraccion'] = 'N/A';
        }

        if ($regla->vcr_porcentaje) {
            $vcrResult = $this->calcularVCR($item, $regla);
            $resultado['vcr_calculado'] = $vcrResult['vcr'];
            $resultado['vcr_minimo']    = $vcrResult['minimo'];
            $resultado['vcr_cumple']    = $vcrResult['cumple'];
            $cumple                     = $cambioCumple && $vcrResult['cumple'];

            if (! $cumple && $cambioCumple && ! $vcrResult['cumple']) {
                $fraccionFG = (string) ($item->fraccion_arancelaria_fg ?? '');
                if ($fraccionFG !== '' && app(CatalogoRelacionService::class)->estaEnTablasBC($fraccionFG)) {
                    $cumple                        = true;
                    $resultado['escape_tabla_bc'] = true;
                }
            }
        } else {
            $cumple = $cambioCumple;
        }

        $resultado['califica_originario']     = $cumple ? 'Sí' : 'No';
        $resultado['cumple_demas_requisitos'] = $cumple ? 'Sí' : 'No';

        return $resultado;
    }

    public function calcularVCR(BomItem $item, ReglaOrigen $regla): array
    {
        $minimo = (float) ($regla->vcr_porcentaje ?? $this->obtenerVCRMinimoPorAnio(now()->year));
        $metodo = $regla->metodo_vcr ?? 'CN';

        $cn = (float) ($item->precio_final_usd ?? 0);
        if ($cn <= 0) {
            $cn = (float) ($this->getBomItems($item->bom_id)
                ->first(fn ($i) => (float) ($i->precio_final_usd ?? 0) > 0)
                ?->precio_final_usd ?? 0);
        }

        if ($cn <= 0) {
            return [
                'vcr'    => null,
                'minimo' => $minimo,
                'cumple' => false,
                'metodo' => $metodo,
                'error'  => 'precio_final_usd es cero o nulo; no se puede calcular VCR',
            ];
        }

        $vmno = $this->getBomItems($item->bom_id)
            ->filter(fn ($i) => ! in_array(strtoupper(trim((string) $i->pais_de_origen)), self::PAISES_USMCA))
            ->sum(fn ($i) => (float) ($i->costo_total_usd ?? 0));

        $vcr = (($cn - $vmno) / $cn) * 100;
        $vcr = round($vcr, 4);

        return [
            'vcr'    => $vcr,
            'minimo' => $minimo,
            'cumple' => $vcr >= $minimo,
            'metodo' => $metodo,
        ];
    }

    public function verificarCambioFraccion(string $fraccionFG, string $fraccionRM, string $nivel = 'partida'): bool
    {
        if (! $fraccionFG || ! $fraccionRM) {
            return false;
        }

        $fg = preg_replace('/\D/', '', $fraccionFG);
        $rm = preg_replace('/\D/', '', $fraccionRM);

        return match ($nivel) {
            'capitulo'   => substr($fg, 0, 2) !== substr($rm, 0, 2),
            'partida'    => substr($fg, 0, 4) !== substr($rm, 0, 4),
            'subpartida' => substr($fg, 0, 6) !== substr($rm, 0, 6),
            'fraccion'   => $fg !== $rm,
            default      => substr($fg, 0, 4) !== substr($rm, 0, 4),
        };
    }

    public function consultarReglaOrigen(string $fraccion): ?array
    {
        if (! $fraccion) {
            return null;
        }

        return app(CatalogoRelacionService::class)->resolveFinishedGood($fraccion);
    }

    public function obtenerVCRMinimoPorAnio(int $anio = 0): float
    {
        if ($anio === 0) {
            $anio = now()->year;
        }

        return Cache::remember("vcr_minimo_{$anio}", 3600, function () use ($anio) {
            $param = DB::table('parametros_sistema_ce')
                ->where('activo', 1)
                ->where('clave', 'like', 'vcr_porcentaje_%')
                ->where(function ($q) use ($anio) {
                    $q->whereNull('anio_vigencia')
                      ->orWhere('anio_vigencia', '<=', $anio);
                })
                ->orderByDesc('anio_vigencia')
                ->first();

            return $param ? (float) $param->valor_decimal : 62.5;
        });
    }

    private function inferirCriterio(ReglaOrigen $regla): string
    {
        $texto = $regla->regla_texto ?? '';

        if (preg_match('/enteramente obtenida|completamente obtenida/iu', $texto)) {
            return 'A';
        }

        if (isset($regla->requiere_cambio_fraccion)) {
            if ($regla->requiere_cambio_fraccion) {
                return 'B';
            }

            return $regla->vcr_porcentaje ? 'C' : 'B';
        }

        $noRequiereCC = (bool) preg_match('/no\s+se\s+requiere\s+cambio\s+de\s+clasificaci/iu', $texto);
        $exigeCC      = ! $noRequiereCC && (bool) preg_match('/\bcambio\b/iu', $texto);
        $tieneVcr     = (bool) preg_match('/valor\s+de\s+contenido\s+regional|no\s+menor\s+a.{1,60}por\s+ciento|\bvcr\b/iu', $texto);

        if ($exigeCC)  return 'B';
        if ($tieneVcr) return 'C';

        return 'B';
    }

    private function persistirResultado(BomItem $item, array $resultado): void
    {
        $item->update([
            'califica_originario'      => $resultado['califica_originario'],
            'presenta_cambio_fraccion' => $resultado['presenta_cambio_fraccion'],
            'cumple_demas_requisitos'  => $resultado['cumple_demas_requisitos'],
            'criterio_de_origen'       => $resultado['criterio'],
            'regla_de_origen'          => $resultado['regla_de_origen_texto'] ?: null,
            'regla_origen_id'          => $resultado['regla_id'],
            'analisis_detalle'         => $resultado,
            'analisis_en'              => now(),
        ]);
    }

    private function buildResolvedRule(array $catalogo): ReglaOrigen
    {
        $reglaBase = $catalogo['regla_origen'];

        if (! $reglaBase && ($catalogo['regla_automotriz'] ?? null)) {
            $auto  = $catalogo['regla_automotriz'];
            $texto = $auto->regla_texto;

            $synthetic                           = new ReglaOrigen();
            $synthetic->fraccion_arancelaria     = $auto->fraccion_arancelaria;
            $synthetic->regla_texto              = $texto;
            $synthetic->requiere_apendice        = true;
            $synthetic->vcr_porcentaje           = $auto->vcr_umbral_pct    ?? $this->extractVcrFromText($texto);
            $synthetic->metodo_vcr               = $auto->vcr_metodo        ? $this->mapMetodoVcr($auto->vcr_metodo)  : $this->extractMetodoFromText($texto);
            $synthetic->requiere_cambio_fraccion = $auto->requiere_cc       ?? $this->extractRequiresCcFromText($texto);
            $synthetic->nivel_cambio             = $auto->nivel_cc          ? $this->mapNivelCc($auto->nivel_cc)      : $this->extractNivelFromText($texto);
            $synthetic->criterio                 = $this->inferirCriterio($synthetic);

            return $synthetic;
        }

        if (! $catalogo['from_apendice'] || ! $catalogo['regla_automotriz']) {
            return $reglaBase;
        }

        $auto  = $catalogo['regla_automotriz'];
        $regla = $reglaBase->replicate();
        $texto = $auto->regla_texto;

        $regla->id                           = $reglaBase->id;
        $regla->regla_texto                  = $texto;
        $regla->vcr_porcentaje               = $auto->vcr_umbral_pct    ?? $this->extractVcrFromText($texto) ?? $reglaBase->vcr_porcentaje;
        $regla->metodo_vcr                   = $auto->vcr_metodo        ? $this->mapMetodoVcr($auto->vcr_metodo)  : ($this->extractMetodoFromText($texto) ?? $reglaBase->metodo_vcr);
        $regla->requiere_cambio_fraccion     = $auto->requiere_cc       ?? $this->extractRequiresCcFromText($texto);
        $regla->nivel_cambio                 = $auto->nivel_cc          ? $this->mapNivelCc($auto->nivel_cc)      : $this->extractNivelFromText($texto);
        $regla->criterio                     = $reglaBase->criterio     ?? $this->inferirCriterio($regla);

        return $regla;
    }

    private function mapNivelCc(?string $nivel): string
    {
        return match (strtolower(trim((string) $nivel))) {
            'capítulo', 'capitulo' => 'capitulo',
            'subpartida'           => 'subpartida',
            'fracción', 'fraccion' => 'fraccion',
            default                => 'partida',
        };
    }

    private function mapMetodoVcr(?string $metodo): ?string
    {
        return match (strtolower(trim((string) $metodo))) {
            'costo neto', 'cn'                                    => 'costo_neto',
            'valor de transacción', 'valor de transaccion', 'vt' => 'valor_transaccion',
            default                                               => null,
        };
    }

    private function extractVcrFromText(string $texto): ?float
    {
        if (preg_match('/no\s+(?:menor|inferior)\s+a\s+(\d+(?:[.,]\d+)?)\s*(?:por\s+ciento|%)/iu', $texto, $matches)) {
            return (float) str_replace(',', '.', $matches[1]);
        }

        return null;
    }

    private function extractMetodoFromText(string $texto): ?string
    {
        if (preg_match('/costo\s+neto|CN\b/iu', $texto))                    return 'costo_neto';
        if (preg_match('/valor\s+de\s+transacci[oó]n|VT\b/iu', $texto))    return 'valor_transaccion';

        return null;
    }

    private function extractRequiresCcFromText(string $texto): bool
    {
        return (bool) preg_match('/\bcambio\b.*?\b(cap[íi]tulo|partida|subpartida|fracci[oó]n)\b/iu', $texto);
    }

    private function extractNivelFromText(string $texto): string
    {
        if (preg_match('/\bcap[íi]tulo\b/iu', $texto))  return 'capitulo';
        if (preg_match('/\bsubpartida\b/iu', $texto))   return 'subpartida';
        if (preg_match('/\bfracci[oó]n\b/iu', $texto))  return 'fraccion';

        return 'partida';
    }

    private function getBomItems(int $bomId): Collection
    {
        if (! array_key_exists($bomId, $this->bomItemsCache)) {
            $this->bomItemsCache[$bomId] = BomItem::where('bom_id', $bomId)->get();
        }

        return $this->bomItemsCache[$bomId];
    }
}
