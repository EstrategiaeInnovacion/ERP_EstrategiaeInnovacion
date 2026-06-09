<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\Logistica\CampoPersonalizado;
use App\Models\Logistica\CampoValor;
use App\Models\Logistica\Cliente;
use App\Models\Logistica\MatrizSeguimiento;
use Illuminate\Http\Request;

class CampoPersonalizadoController extends Controller
{
    // ── Helper: verifica que el usuario autenticado es coordinador de logística ──
    private function esCoordinador(): bool
    {
        $user     = auth()->user();
        $empleado = $user ? Empleado::where('correo', $user->email)->first() : null;

        if (! $empleado || ! $empleado->es_coordinador) {
            return false;
        }

        $area     = mb_strtolower($empleado->area     ?? '', 'UTF-8');
        $posicion = mb_strtolower($empleado->posicion ?? '', 'UTF-8');

        foreach (['logística', 'logistica', 'sistemas', 'dirección', 'direccion'] as $p) {
            if (str_contains($area, $p) || str_contains($posicion, $p)) {
                return true;
            }
        }

        return false;
    }

    // ── GET /logistica/campos/cliente/{cliente} ──────────────────────────────
    // Devuelve los campos definidos para ese cliente (JSON)
    public function indexPorCliente(Cliente $cliente)
    {
        if (! $this->esCoordinador()) {
            abort(403);
        }

        $campos = CampoPersonalizado::where('cliente_id', $cliente->id)
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['id', 'nombre', 'tipo', 'es_obligatorio', 'orden']);

        return response()->json($campos);
    }

    // ── POST /logistica/campos/cliente/{cliente} ─────────────────────────────
    // Crea un nuevo campo para el cliente
    public function store(Request $request, Cliente $cliente)
    {
        if (! $this->esCoordinador()) {
            abort(403);
        }

        $data = $request->validate([
            'nombre'         => 'required|string|max:120',
            'tipo'           => 'required|in:texto,fecha',
            'es_obligatorio' => 'boolean',
        ]);

        $maxOrden = CampoPersonalizado::where('cliente_id', $cliente->id)->max('orden') ?? 0;

        $campo = CampoPersonalizado::create([
            'cliente_id'     => $cliente->id,
            'nombre'         => $data['nombre'],
            'tipo'           => $data['tipo'],
            'es_obligatorio' => $data['es_obligatorio'] ?? false,
            'orden'          => $maxOrden + 1,
            'created_by'     => auth()->id(),
        ]);

        return response()->json($campo, 201);
    }

    // ── DELETE /logistica/campos/{campo} ────────────────────────────────────
    public function destroy(CampoPersonalizado $campo)
    {
        if (! $this->esCoordinador()) {
            abort(403);
        }

        $campo->delete();

        return response()->json(['ok' => true]);
    }

    // ── GET /logistica/matriz-seguimiento/{seguimiento}/campos ────────────────
    // Devuelve definiciones + valores ya guardados para una operación
    public function getValores(MatrizSeguimiento $seguimiento)
    {

        // Buscar el cliente en el catálogo por nombre exacto (proveedor_cliente)
        $cliente = Cliente::where('cliente', $seguimiento->proveedor_cliente)->first();

        if (! $cliente) {
            return response()->json(['campos' => []]);
        }

        $campos = CampoPersonalizado::where('cliente_id', $cliente->id)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        // Valores ya guardados para esta operación
        $valoresGuardados = CampoValor::where('matriz_seguimiento_id', $seguimiento->id)
            ->whereIn('campo_id', $campos->pluck('id'))
            ->get()
            ->keyBy('campo_id');

        $resultado = $campos->map(fn($c) => [
            'id'             => $c->id,
            'nombre'         => $c->nombre,
            'tipo'           => $c->tipo,
            'es_obligatorio' => $c->es_obligatorio,
            'valor'          => $valoresGuardados->get($c->id)?->valor ?? '',
        ]);

        return response()->json([
            'campos'      => $resultado,
            'ref_interna' => $seguimiento->ref_interna,
            'cliente'     => $seguimiento->proveedor_cliente,
        ]);
    }

    // ── POST /logistica/matriz-seguimiento/{seguimiento}/campos ──────────────
    // Guarda / actualiza los valores de los campos para una operación
    public function saveValores(Request $request, MatrizSeguimiento $seguimiento)
    {
        $cliente = Cliente::where('cliente', $seguimiento->proveedor_cliente)->first();
        if (! $cliente) {
            return response()->json(['error' => 'Cliente no encontrado'], 422);
        }

        $valores = $request->input('valores', []); // ['campo_id' => 'valor', ...]

        // Validar campos obligatorios
        $campos = CampoPersonalizado::where('cliente_id', $cliente->id)
            ->orderBy('orden')
            ->get();

        foreach ($campos as $campo) {
            if ($campo->es_obligatorio && empty($valores[$campo->id])) {
                return response()->json([
                    'error' => "El campo \"{$campo->nombre}\" es obligatorio.",
                ], 422);
            }
        }

        // Upsert valores
        foreach ($campos as $campo) {
            CampoValor::updateOrCreate(
                ['campo_id' => $campo->id, 'matriz_seguimiento_id' => $seguimiento->id],
                ['valor'    => $valores[$campo->id] ?? null]
            );
        }

        return response()->json(['ok' => true]);
    }
}
