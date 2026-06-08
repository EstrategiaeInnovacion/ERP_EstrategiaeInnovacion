<?php

namespace App\Services\ComercioExterior;

class ExcelRulesService
{
    public function __construct(private CatalogoRelacionService $catalogoRelacionService)
    {
    }

    public function findRule(string $fraction): ?array
    {
        if ($fraction === '' || $fraction === '—') {
            return null;
        }

        $resolved = $this->catalogoRelacionService->resolveFinishedGood($fraction);
        if (! $resolved) {
            return null;
        }

        $automotriz  = $resolved['regla_automotriz'];
        $reglaOrigen = $resolved['regla_origen'];

        return [
            'fraccion'                  => $resolved['resolved_fraction'],
            'regla_texto'               => $resolved['resolved_rule_text'],
            'referencia_apendice'       => (bool) $reglaOrigen?->requiere_apendice,
            'referencia_apendice_texto' => $reglaOrigen?->referencia_apendice_texto,
            'from_apendice'             => $resolved['from_apendice'],
            'source_lookup'             => $resolved['lookup_mode'],
            'seccion_c_codigo'          => $resolved['seccion_c']?->fraccion_tmec,
            'seccion_c_descripcion'     => $resolved['seccion_c']?->descripcion,
            'tipo_vehiculo_pt'          => $automotriz?->tipo_vehiculo_pt,
            'requiere_cc'               => $automotriz?->requiere_cc,
            'nivel_cc'                  => $automotriz?->nivel_cc,
            'cc_excepcion_desde'        => $automotriz?->cc_excepcion_desde,
            'vcr_metodo'                => $automotriz?->vcr_metodo,
            'vcr_umbral_pct'            => $automotriz?->vcr_umbral_pct,
            'tabla_partes_ref'          => $automotriz?->tabla_partes_ref,
            'articulo_apendice'         => $automotriz?->articulo_apendice,
        ];
    }
}
