<?php

namespace App\Services\ComercioExterior;

use App\Models\Legal\ComercioExterior\ApendiceParteCatalogo;
use App\Models\Legal\ComercioExterior\BomItem;
use App\Models\Legal\ComercioExterior\CatalogoRelacion;
use App\Models\Legal\ComercioExterior\ReglaAutomotriz;
use App\Models\Legal\ComercioExterior\ReglaOrigen;
use Illuminate\Support\Collection;

class CatalogoRelacionService
{
    public function resolveFinishedGood(string $fraction): ?array
    {
        $lookupKey = $this->normalizeLookupKey($fraction);
        if ($lookupKey === null) {
            return null;
        }

        $seccionAlias = CatalogoRelacion::query()
            ->with('seccionC')
            ->whereIn('relation_type', ['seccion_c_alias_canada', 'seccion_c_alias_eeuu', 'seccion_c_alias_mexico'])
            ->where('relation_key', $lookupKey)
            ->first();

        $seccionC      = $seccionAlias?->seccionC;
        $lookupMode    = $seccionC ? 'seccion_c' : 'directo';
        $ruleLookupKey = $seccionC ? $this->normalizeLookupKey($seccionC->fraccion_tmec) : $lookupKey;

        $regla      = $this->findReglaOrigen($ruleLookupKey);
        $automotriz = $regla
            ? $this->findReglaAutomotriz($regla, $lookupKey, $fraction)
            : $this->findDirectReglaAutomotriz($lookupKey, $fraction);

        if (! $regla && ! $automotriz) {
            return null;
        }

        return [
            'input_fraction'    => $fraction,
            'lookup_key'        => $lookupKey,
            'lookup_mode'       => $lookupMode,
            'regla_origen'      => $regla,
            'regla_automotriz'  => $automotriz,
            'seccion_c'         => $seccionC,
            'resolved_rule_text'=> $automotriz?->regla_texto ?? $regla?->regla_texto,
            'resolved_fraction' => $seccionC?->fraccion_tmec ?? $regla?->fraccion_arancelaria ?? $automotriz?->fraccion_arancelaria,
            'from_apendice'     => $automotriz !== null,
        ];
    }

    public function resolveBomItem(BomItem $item): array
    {
        $fg    = $this->resolveFinishedGood((string) $item->fraccion_arancelaria_fg);
        $partes = $this->findApendicePartes((string) $item->fraccion_arancelaria_rm);

        return [
            'fg'             => $fg,
            'apendice_partes'=> $partes->map(fn (ApendiceParteCatalogo $parte) => [
                'tabla'               => $parte->tabla,
                'tabla_codigo'        => $parte->tabla_codigo,
                'fraccion_arancelaria'=> $parte->fraccion_arancelaria,
                'descripcion'         => $parte->descripcion,
                'vcr_umbral_cn_pct'   => $parte->vcr_umbral_cn_pct,
                'vcr_umbral_vt_pct'   => $parte->vcr_umbral_vt_pct,
            ])->values()->all(),
        ];
    }

    public function findApendicePartes(string $fraction): Collection
    {
        $lookupKey = $this->normalizeLookupKey($fraction);
        if ($lookupKey === null) {
            return collect();
        }

        $byAlias = CatalogoRelacion::query()
            ->with('apendiceParte')
            ->where('relation_type', 'apendice_parte_alias')
            ->where('relation_key', $lookupKey)
            ->get()
            ->pluck('apendiceParte')
            ->filter();

        $byRange = ApendiceParteCatalogo::query()
            ->whereNotNull('fraccion_inicio_norm')
            ->where('fraccion_inicio_norm', '<=', $lookupKey)
            ->where('fraccion_fin_norm', '>=', $lookupKey)
            ->orderBy('tabla')
            ->get();

        return $byAlias
            ->concat($byRange)
            ->unique('id')
            ->sortBy('tabla')
            ->values();
    }

    public function estaEnTablasBC(string $fraction): bool
    {
        return $this->findApendicePartes($fraction)
            ->filter(fn (ApendiceParteCatalogo $p) => in_array($p->tabla_codigo, ['B', 'C']))
            ->isNotEmpty();
    }

    public function findTablaInsumo(string $fraccionInsumo, string $tipoVehiculo): ?array
    {
        $partes = $this->findApendicePartes($fraccionInsumo);
        if ($partes->isEmpty()) {
            return null;
        }

        $prioridad = match ($tipoVehiculo) {
            'Vehículo de Pasajeros', 'Camión Ligero', 'Pasajero/Camión Ligero' => ['A1', 'A2', 'B', 'C'],
            'Camión Pesado'                                                      => ['D', 'E'],
            'Fuera carretera'                                                    => ['F'],
            default                                                              => ['F', 'B', 'C', 'D', 'E'],
        };

        foreach ($prioridad as $codigo) {
            $parte = $partes->firstWhere('tabla_codigo', $codigo);
            if ($parte) {
                return [
                    'tabla'         => $parte->tabla,
                    'tabla_codigo'  => $parte->tabla_codigo,
                    'vcr_umbral_cn' => $parte->vcr_umbral_cn_pct,
                    'vcr_umbral_vt' => $parte->vcr_umbral_vt_pct,
                ];
            }
        }

        $primera = $partes->first();

        return [
            'tabla'         => $primera->tabla,
            'tabla_codigo'  => $primera->tabla_codigo,
            'vcr_umbral_cn' => $primera->vcr_umbral_cn_pct,
            'vcr_umbral_vt' => $primera->vcr_umbral_vt_pct,
        ];
    }

    public function getTipoVehiculo(string $fraction): string
    {
        $d = preg_replace('/\D/', '', $fraction);
        $s = substr($d . str_repeat('0', 6), 0, 6);

        if ($s >= '870321' && $s <= '870390') return 'Vehículo de Pasajeros';
        if (in_array($s, ['870421', '870431']))  return 'Camión Ligero';
        if (in_array(substr($s, 0, 6), ['870120', '870422', '870423', '870432', '870490', '870600'])) return 'Camión Pesado';

        return 'Cualquier otro';
    }

    private function selectByTipoVehiculo(Collection $rows, string $fraction): ?ReglaAutomotriz
    {
        $tipo = $this->getTipoVehiculo($fraction);

        return $rows->firstWhere('tipo_vehiculo_pt', $tipo)
            ?? ($this->isPasajeroOLigero($tipo)
                ? $rows->firstWhere('tipo_vehiculo_pt', 'Pasajero/Camión Ligero')
                : null)
            ?? ($this->isPasajeroLigeroPesado($tipo)
                ? $rows->firstWhere('tipo_vehiculo_pt', 'Pasajero/Ligero/Pesado')
                : null)
            ?? $rows->firstWhere('tipo_vehiculo_pt', 'Todos')
            ?? $rows->firstWhere('tipo_vehiculo_pt', 'Cualquier otro')
            ?? $rows->first();
    }

    private function isPasajeroOLigero(string $tipo): bool
    {
        return in_array($tipo, ['Vehículo de Pasajeros', 'Camión Ligero']);
    }

    private function isPasajeroLigeroPesado(string $tipo): bool
    {
        return in_array($tipo, ['Vehículo de Pasajeros', 'Camión Ligero', 'Camión Pesado']);
    }

    private function findReglaOrigen(?string $lookupKey): ?ReglaOrigen
    {
        if ($lookupKey === null) {
            return null;
        }

        return ReglaOrigen::query()
            ->whereNotNull('fraccion_inicio_norm')
            ->where('fraccion_inicio_norm', '<=', $lookupKey)
            ->where('fraccion_fin_norm', '>=', $lookupKey)
            ->get()
            ->sortByDesc(fn (ReglaOrigen $regla) => $this->fractionSpecificity($regla->fraccion_arancelaria))
            ->first();
    }

    private function findReglaAutomotriz(ReglaOrigen $regla, string $lookupKey, string $ptFraction): ?ReglaAutomotriz
    {
        if (! $regla->requiere_apendice) {
            return null;
        }

        return $this->findDirectReglaAutomotriz($lookupKey, $ptFraction);
    }

    private function findDirectReglaAutomotriz(string $lookupKey, string $ptFraction = ''): ?ReglaAutomotriz
    {
        $rows = ReglaAutomotriz::query()
            ->whereNotNull('fraccion_inicio_norm')
            ->where('fraccion_inicio_norm', '<=', $lookupKey)
            ->where('fraccion_fin_norm', '>=', $lookupKey)
            ->get()
            ->sortByDesc(fn (ReglaAutomotriz $item) => $this->fractionSpecificity($item->fraccion_arancelaria));

        if ($rows->isEmpty()) {
            return null;
        }

        if ($rows->count() > 1 && $ptFraction !== '') {
            return $this->selectByTipoVehiculo($rows, $ptFraction);
        }

        return $rows->first();
    }

    private function normalizeLookupKey(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', trim((string) $value));
        if ($digits === '') {
            return null;
        }

        return substr($digits . str_repeat('0', max(0, 12 - strlen($digits))), 0, 12);
    }

    private function fractionSpecificity(?string $value): int
    {
        $firstToken = trim((string) strtok((string) $value, '-'));

        return strlen(preg_replace('/\D/', '', $firstToken));
    }
}
