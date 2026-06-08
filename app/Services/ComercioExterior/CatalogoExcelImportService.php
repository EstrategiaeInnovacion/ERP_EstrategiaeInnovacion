<?php

namespace App\Services\ComercioExterior;

use App\Models\Legal\ComercioExterior\ApendiceParteCatalogo;
use App\Models\Legal\ComercioExterior\CatalogoRelacion;
use App\Models\Legal\ComercioExterior\ReglaAutomotriz;
use App\Models\Legal\ComercioExterior\ReglaOrigen;
use App\Models\Legal\ComercioExterior\SeccionCFraccion;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CatalogoExcelImportService
{
    public function import(string $path): array
    {
        $spreadsheet = IOFactory::load($path);

        return DB::transaction(function () use ($spreadsheet) {
            CatalogoRelacion::query()->delete();
            ReglaAutomotriz::query()->delete();
            ApendiceParteCatalogo::query()->delete();
            SeccionCFraccion::query()->delete();
            ReglaOrigen::query()->delete();

            $result = [
                'reglas_origen'       => $this->importReglasOrigen($spreadsheet),
                'seccion_c'           => $this->importSeccionC($spreadsheet),
                'reglas_automotrices' => $this->importReglasAutomotrices($spreadsheet),
                'apendice_partes'     => $this->importApendicePartes($spreadsheet),
                'relaciones'          => 0,
            ];

            $result['relaciones'] = $this->buildRelations();

            $spreadsheet->disconnectWorksheets();

            return $result;
        });
    }

    private function importReglasOrigen(Spreadsheet $spreadsheet): int
    {
        $sheet = $spreadsheet->getSheetByName('Reglas de Origen');
        if (! $sheet) {
            return 0;
        }

        $rows = $sheet->toArray(null, true, true, false);
        array_shift($rows);

        $count = 0;
        foreach ($rows as $row) {
            $fraccion = trim((string) ($row[3] ?? ''));
            $regla    = trim((string) ($row[4] ?? ''));

            if ($fraccion === '' || $regla === '') {
                continue;
            }

            $refTexto    = trim((string) ($row[5] ?? ''));
            $reqApendice = ($refTexto !== '' && strtolower($refTexto) !== 'no aplica' && strtolower($refTexto) !== 'no');

            [$startNorm, $endNorm] = $this->normalizeNumericRange($fraccion);

            $payload = [
                'fraccion_inicio_norm'      => $startNorm,
                'fraccion_fin_norm'         => $endNorm,
                'descripcion'               => trim((string) ($row[2] ?? '')),
                'criterio'                  => null,
                'regla_texto'               => $regla,
                'capitulo'                  => $this->extractCapitulo($fraccion, (string) ($row[1] ?? '')),
                'requiere_apendice'         => $reqApendice,
                'referencia_apendice_texto' => $refTexto !== '' ? $refTexto : null,
                'nota_apendice'             => null,
                'vcr_porcentaje'            => $this->detectVcr($regla),
                'metodo_vcr'                => $this->detectMetodo($regla),
                'requiere_cambio_fraccion'  => $this->detectRequiereCC($regla),
                'nivel_cambio'              => $this->detectNivel($regla),
            ];

            $existing = ReglaOrigen::query()->where('fraccion_arancelaria', $fraccion)->first();
            if ($existing) {
                $existing->fill([
                    'fraccion_inicio_norm'      => $existing->fraccion_inicio_norm ?: $payload['fraccion_inicio_norm'],
                    'fraccion_fin_norm'         => $existing->fraccion_fin_norm ?: $payload['fraccion_fin_norm'],
                    'descripcion'               => $existing->descripcion ?: $payload['descripcion'],
                    'regla_texto'               => $this->mergeRuleTexts($existing->regla_texto, $payload['regla_texto']),
                    'capitulo'                  => $existing->capitulo ?: $payload['capitulo'],
                    'requiere_apendice'         => $existing->requiere_apendice || $payload['requiere_apendice'],
                    'referencia_apendice_texto' => $existing->referencia_apendice_texto ?: $payload['referencia_apendice_texto'],
                    'vcr_porcentaje'            => $existing->vcr_porcentaje ?: $payload['vcr_porcentaje'],
                    'metodo_vcr'                => $existing->metodo_vcr ?: $payload['metodo_vcr'],
                    'requiere_cambio_fraccion'  => $existing->requiere_cambio_fraccion || $payload['requiere_cambio_fraccion'],
                    'nivel_cambio'              => $this->pickMostSpecificLevel($existing->nivel_cambio, $payload['nivel_cambio']),
                ])->save();
            } else {
                ReglaOrigen::create(array_merge($payload, ['fraccion_arancelaria' => $fraccion]));
            }

            $count++;
        }

        return $count;
    }

    private function importSeccionC(Spreadsheet $spreadsheet): int
    {
        $sheet = $spreadsheet->getSheetByName('Apéndice – Sección C');
        if (! $sheet) {
            return 0;
        }

        $rows = $sheet->toArray(null, true, true, false);
        array_shift($rows);

        $count = 0;
        foreach ($rows as $row) {
            $fraccionTmec = trim((string) ($row[0] ?? ''));
            if ($fraccionTmec === '') {
                continue;
            }

            SeccionCFraccion::create([
                'fraccion_tmec'      => $fraccionTmec,
                'fraccion_tmec_norm' => $this->normalizeAlphaKey($fraccionTmec),
                'fraccion_canada'    => $this->nullableTrim((string) ($row[1] ?? '')),
                'fraccion_eeuu'      => $this->nullableTrim((string) ($row[2] ?? '')),
                'fraccion_mexico'    => $this->nullableTrim((string) ($row[3] ?? '')),
                'descripcion'        => $this->nullableTrim((string) ($row[4] ?? '')),
            ]);

            $count++;
        }

        return $count;
    }

    private function importReglasAutomotrices(Spreadsheet $spreadsheet): int
    {
        $sheet = $spreadsheet->getSheetByName('Apéndice Automotriz');
        if (! $sheet) {
            return 0;
        }

        $rows = $sheet->toArray(null, true, true, false);
        array_shift($rows);

        $count = 0;
        foreach ($rows as $row) {
            $fraccion     = trim((string) ($row[0] ?? ''));
            $tipoVehiculo = trim((string) ($row[1] ?? ''));
            $reglaTexto   = trim((string) ($row[9] ?? ''));

            if ($fraccion === '' || $tipoVehiculo === '') {
                continue;
            }

            [$startNorm, $endNorm] = $this->normalizePartRange($fraccion);

            $vcrUmbral = $row[6];
            $vcrUmbral = ($vcrUmbral !== null && $vcrUmbral !== '')
                ? (float) str_replace(',', '.', (string) $vcrUmbral)
                : null;

            ReglaAutomotriz::create([
                'fraccion_arancelaria' => $fraccion,
                'fraccion_inicio_norm' => $startNorm,
                'fraccion_fin_norm'    => $endNorm,
                'tipo_vehiculo_pt'     => $tipoVehiculo,
                'requiere_cc'         => $this->parseBool((string) ($row[2] ?? '')),
                'nivel_cc'             => $this->nullableTrim((string) ($row[3] ?? '')),
                'cc_excepcion_desde'   => $this->nullableTrim((string) ($row[4] ?? '')),
                'vcr_metodo'           => $this->nullableTrim((string) ($row[5] ?? '')),
                'vcr_umbral_pct'       => $vcrUmbral,
                'tabla_partes_ref'     => $this->nullableTrim((string) ($row[7] ?? '')),
                'articulo_apendice'    => $this->nullableTrim((string) ($row[8] ?? '')),
                'regla_texto'          => $reglaTexto !== '' ? $reglaTexto : ($fraccion . ' – ' . $tipoVehiculo),
                'referencia_nota'      => null,
            ]);

            $count++;
        }

        return $count;
    }

    private function importApendicePartes(Spreadsheet $spreadsheet): int
    {
        $sheet = $spreadsheet->getSheetByName('Apéndice – Tablas de Partes');
        if (! $sheet) {
            return 0;
        }

        $rows = $sheet->toArray(null, true, true, false);
        array_shift($rows);

        $count = 0;
        foreach ($rows as $row) {
            $tabla       = trim((string) ($row[0] ?? ''));
            $fraccion    = trim((string) ($row[1] ?? ''));
            $descripcion = trim((string) ($row[2] ?? ''));

            if ($tabla === '' || $descripcion === '') {
                continue;
            }

            [$startNorm, $endNorm] = $this->normalizeNumericRange($fraccion);

            $fraccionNorm = $this->nullableTrim((string) ($row[3] ?? ''));
            $tieneEx      = $this->parseBool((string) ($row[4] ?? ''));

            $cnPct = $row[5];
            $cnPct = ($cnPct !== null && $cnPct !== '')
                ? (float) str_replace(',', '.', (string) $cnPct)
                : null;

            $vtPct = $row[6];
            $vtPct = ($vtPct !== null && $vtPct !== '')
                ? (float) str_replace(',', '.', (string) $vtPct)
                : null;

            ApendiceParteCatalogo::create([
                'tabla'                => $tabla,
                'tabla_codigo'         => $this->extractTablaCode($tabla),
                'fraccion_arancelaria' => $fraccion ?: null,
                'fraccion_inicio_norm' => $startNorm,
                'fraccion_fin_norm'    => $endNorm,
                'fraccion_normalizada' => $fraccionNorm,
                'tiene_ex_prefix'      => $tieneEx,
                'vcr_umbral_cn_pct'    => $cnPct,
                'vcr_umbral_vt_pct'    => $vtPct,
                'descripcion'          => $descripcion,
            ]);

            $count++;
        }

        return $count;
    }

    private function buildRelations(): int
    {
        $relations = 0;

        $reglas       = ReglaOrigen::query()->get();
        $seccionC     = SeccionCFraccion::query()->get();
        $automotrices = ReglaAutomotriz::query()->get();

        foreach ($seccionC as $row) {
            foreach ($this->sectionCAliases($row) as $type => $aliases) {
                foreach ($aliases as $alias) {
                    CatalogoRelacion::create([
                        'relation_type'         => $type,
                        'relation_key'          => $alias,
                        'seccion_c_fraccion_id' => $row->id,
                    ]);
                    $relations++;
                }
            }

            $tmecDigits = $this->normalizeLookupKey($row->fraccion_tmec);
            foreach ($reglas as $regla) {
                if (! $this->matchesRange($tmecDigits, $regla->fraccion_inicio_norm, $regla->fraccion_fin_norm)) {
                    continue;
                }

                CatalogoRelacion::create([
                    'relation_type'         => 'seccion_c_to_regla',
                    'relation_key'          => $row->fraccion_tmec_norm,
                    'regla_origen_id'       => $regla->id,
                    'seccion_c_fraccion_id' => $row->id,
                ]);
                $relations++;
            }
        }

        foreach (ApendiceParteCatalogo::query()->get() as $parte) {
            foreach ($this->splitPartAliases($parte->fraccion_arancelaria) as $alias) {
                CatalogoRelacion::create([
                    'relation_type'    => 'apendice_parte_alias',
                    'relation_key'     => $alias,
                    'apendice_parte_id'=> $parte->id,
                ]);
                $relations++;
            }
        }

        foreach ($reglas->where('requiere_apendice', true) as $regla) {
            foreach ($automotrices as $automotriz) {
                if (! $this->rangesOverlap(
                    $regla->fraccion_inicio_norm,
                    $regla->fraccion_fin_norm,
                    $automotriz->fraccion_inicio_norm,
                    $automotriz->fraccion_fin_norm
                )) {
                    continue;
                }

                CatalogoRelacion::create([
                    'relation_type'      => 'regla_to_automotriz',
                    'relation_key'       => $regla->fraccion_inicio_norm,
                    'regla_origen_id'    => $regla->id,
                    'regla_automotriz_id'=> $automotriz->id,
                ]);
                $relations++;
            }
        }

        return $relations;
    }

    private function sectionCAliases(SeccionCFraccion $row): array
    {
        return [
            'seccion_c_alias_canada' => $this->splitAliases($row->fraccion_canada),
            'seccion_c_alias_eeuu'   => $this->splitAliases($row->fraccion_eeuu),
            'seccion_c_alias_mexico' => $this->splitAliases($row->fraccion_mexico),
        ];
    }

    private function splitAliases(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return collect(preg_split('/\s+/', trim($value)) ?: [])
            ->map(fn (string $alias) => $this->normalizeLookupKey($alias))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function splitPartAliases(?string $value): array
    {
        if (! $value) {
            return [];
        }

        $normalized = preg_replace('/\s+y\s+/iu', ',', $value);

        return collect(preg_split('/\s*,\s*/', (string) $normalized) ?: [])
            ->map(fn (string $token) => trim($token))
            ->filter()
            ->map(fn (string $token) => $this->normalizeLookupKey($token))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function parseBool(string $value): bool
    {
        return in_array(mb_strtolower(trim($value)), ['sí', 'si', 'yes', 'true', '1'], true);
    }

    private function detectVcr(string $regla): ?float
    {
        $patterns = [
            '/(\d+(?:[.,]\d+)?)\s*%.*?(?:vcr|valor\s+de\s+contenido)/iu',
            '/(?:vcr|valor\s+de\s+contenido)[^0-9%]*(\d+(?:[.,]\d+)?)\s*%/iu',
            '/no\s+(?:menor|inferior)\s+a\s+(\d+(?:[.,]\d+)?)\s*(?:por\s+ciento|%)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $regla, $matches)) {
                return (float) str_replace(',', '.', $matches[1]);
            }
        }

        return null;
    }

    private function detectMetodo(string $regla): ?string
    {
        if (preg_match('/costo\s+neto|CN\b/iu', $regla)) {
            return 'costo_neto';
        }

        if (preg_match('/valor\s+de\s+transacci[oó]n|VT\b/iu', $regla)) {
            return 'valor_transaccion';
        }

        return null;
    }

    private function detectNivel(string $regla): string
    {
        if (preg_match('/\bcap[íi]tulo\b/iu', $regla)) return 'capitulo';
        if (preg_match('/\bsubpartida\b/iu', $regla))   return 'subpartida';
        if (preg_match('/\bfracci[oó]n\b/iu', $regla))  return 'fraccion';

        return 'partida';
    }

    private function detectRequiereCC(string $regla): bool
    {
        return (bool) preg_match('/\bcambio\b.*?\b(cap[íi]tulo|partida|subpartida|fracci[oó]n)\b/iu', $regla);
    }

    private function extractCapitulo(string $fraccion, string $capitulo): ?int
    {
        $capNum = (int) preg_replace('/\D/', '', $capitulo);
        if ($capNum > 0) {
            return $capNum;
        }

        preg_match('/^(\d{2})/', preg_replace('/\D/', '', $fraccion), $matches);

        return isset($matches[1]) ? (int) $matches[1] : null;
    }

    private function extractTablaCode(string $tabla): ?string
    {
        if (preg_match('/Tabla\s+([A-Z](?:\.[0-9])?)/iu', $tabla, $matches)) {
            return strtoupper(str_replace('.', '', $matches[1]));
        }

        return null;
    }

    private function normalizeNumericRange(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [null, null];
        }

        preg_match_all('/[0-9A-Za-z]+(?:\.[0-9A-Za-z]+)*/u', $value, $matches);
        $tokens = $matches[0] ?? [];

        if ($tokens === []) {
            return [null, null];
        }

        $start = trim($tokens[0]);
        $end   = trim($tokens[count($tokens) - 1]);

        return [
            $this->normalizeRangeBound($start, false),
            $this->normalizeRangeBound($end, true),
        ];
    }

    private function normalizePartRange(?string $value): array
    {
        $aliases = $this->splitPartAliases($value);

        if (count($aliases) !== 1) {
            return [null, null];
        }

        return [$aliases[0], str_replace('0', '9', $aliases[0])];
    }

    private function normalizeRangeBound(string $value, bool $asEnd): ?string
    {
        $digits = preg_replace('/\D/', '', $value);
        if ($digits === '') {
            return null;
        }

        $padding = str_repeat($asEnd ? '9' : '0', max(0, 12 - strlen($digits)));

        return substr($digits . $padding, 0, 12);
    }

    private function normalizeLookupKey(?string $value): ?string
    {
        return $this->normalizeRangeBound((string) $value, false);
    }

    private function normalizeAlphaKey(?string $value): ?string
    {
        $normalized = preg_replace('/[^0-9a-z]/i', '', mb_strtolower(trim((string) $value)));

        return $normalized !== '' ? $normalized : null;
    }

    private function matchesRange(?string $lookup, ?string $start, ?string $end): bool
    {
        return $lookup !== null
            && $start !== null
            && $end !== null
            && strcmp($lookup, $start) >= 0
            && strcmp($lookup, $end) <= 0;
    }

    private function rangesOverlap(?string $startA, ?string $endA, ?string $startB, ?string $endB): bool
    {
        if ($startA === null || $endA === null || $startB === null || $endB === null) {
            return false;
        }

        return strcmp($startA, $endB) <= 0 && strcmp($endA, $startB) >= 0;
    }

    private function nullableTrim(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function mergeRuleTexts(string $current, string $incoming): string
    {
        $current  = trim($current);
        $incoming = trim($incoming);

        if ($current === '') return $incoming;
        if ($incoming === '' || str_contains($current, $incoming)) return $current;

        return $current . PHP_EOL . $incoming;
    }

    private function pickMostSpecificLevel(?string $current, ?string $incoming): ?string
    {
        $priority = ['capitulo' => 1, 'partida' => 2, 'subpartida' => 3, 'fraccion' => 4];

        $currentWeight  = $priority[$current] ?? 0;
        $incomingWeight = $priority[$incoming] ?? 0;

        return $incomingWeight > $currentWeight ? $incoming : $current;
    }
}
