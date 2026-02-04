<?php

namespace App\Services\Logistica;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OperacionFilterService
{
    /**
     * Aplica todos los filtros recibidos en el Request al Query Builder.
     */
    public function apply(Builder $query, Request $request): Builder
    {
        // 1. Filtrar por Cliente
        if ($request->filled('cliente') && $request->cliente !== 'todos') {
            $query->where('cliente', $request->cliente);
        }

        // 2. Filtrar por Ejecutivo
        if ($request->filled('ejecutivo') && $request->ejecutivo !== 'todos') {
            // Buscamos por coincidencia de nombre
            $query->where('ejecutivo', 'like', '%' . $request->ejecutivo . '%');
        }

        // 3. Filtrar por Status
        if ($request->filled('status') && $request->status !== 'todos') {
            $query->where(function($q) use ($request) {
                $q->where('status_manual', $request->status)
                  ->orWhere('status_calculado', $request->status);
            });
        }

        // 4. Filtros de Fechas (Creación)
        if ($request->filled('fecha_creacion_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_creacion_desde);
        }
        if ($request->filled('fecha_creacion_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_creacion_hasta);
        }

        // 5. Filtros de Fechas (Embarque)
        if ($request->filled('fecha_embarque_desde')) {
            $query->whereDate('fecha_embarque', '>=', $request->fecha_embarque_desde);
        }
        if ($request->filled('fecha_embarque_hasta')) {
            $query->whereDate('fecha_embarque', '<=', $request->fecha_embarque_hasta);
        }

        // 6. Búsqueda General (Search box)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('operacion', 'like', "%{$search}%")
                  ->orWhere('no_pedimento', 'like', "%{$search}%")
                  ->orWhere('referencia_cliente', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}