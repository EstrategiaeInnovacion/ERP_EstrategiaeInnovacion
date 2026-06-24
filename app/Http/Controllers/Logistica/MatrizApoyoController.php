<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\Logistica\Aduana;
use App\Models\Logistica\Cliente;
use App\Models\Logistica\MatrizApoyoAgente;
use App\Models\Logistica\MatrizApoyoArrastre;
use App\Models\Logistica\MatrizApoyoForwarder;
use App\Models\Logistica\MatrizApoyoNaviera;
use Illuminate\Http\Request;

class MatrizApoyoController extends Controller
{
    public function index()
    {
        $agentes                      = MatrizApoyoAgente::orderBy('agente_aduanal')->orderBy('responsabilidad')->get();
        $responsabilidades            = MatrizApoyoAgente::RESPONSABILIDADES;
        $forwarders                   = MatrizApoyoForwarder::orderBy('cliente')->orderBy('responsabilidad')->get();
        $responsabilidadesForwarder   = MatrizApoyoForwarder::RESPONSABILIDADES;
        $navieras                     = MatrizApoyoNaviera::orderBy('cliente')->orderBy('responsabilidad')->get();
        $responsabilidadesNaviera     = MatrizApoyoNaviera::RESPONSABILIDADES;
        $arrastres                    = MatrizApoyoArrastre::orderBy('cliente')->orderBy('responsabilidad')->get();
        $responsabilidadesArrastre    = MatrizApoyoArrastre::RESPONSABILIDADES;

        $clientes = Cliente::orderBy('cliente')->pluck('cliente');
        $aduanas  = Aduana::orderBy('aduana')->orderBy('seccion')->get(['aduana', 'seccion', 'denominacion']);

        $empleadoActual = auth()->user()
            ? auth()->user()->empleado
            : null;

        $misClientes = $empleadoActual
            ? Cliente::where('ejecutivo_asignado_id', $empleadoActual->id)
                ->pluck('cliente')
                ->map(fn($c) => mb_strtolower($c))
                ->values()
                ->toArray()
            : [];

        return view('Logistica.matriz-apoyo', compact(
            'agentes', 'responsabilidades',
            'forwarders', 'responsabilidadesForwarder',
            'navieras', 'responsabilidadesNaviera',
            'arrastres', 'responsabilidadesArrastre',
            'clientes', 'aduanas', 'misClientes'
        ));
    }

    public function calificaciones()
    {
        $agentes    = MatrizApoyoAgente::whereNotNull('calificacion')
                        ->orderByDesc('calificacion')->orderBy('agente_aduanal')->get();
        $forwarders = MatrizApoyoForwarder::whereNotNull('calificacion')
                        ->orderByDesc('calificacion')->orderBy('razon_social')->get();
        $navieras   = MatrizApoyoNaviera::whereNotNull('calificacion')
                        ->orderByDesc('calificacion')->orderBy('razon_social')->get();
        $arrastres  = MatrizApoyoArrastre::whereNotNull('calificacion')
                        ->orderByDesc('calificacion')->orderBy('razon_social')->get();

        return view('Logistica.matriz-calificaciones', compact('agentes', 'forwarders', 'navieras', 'arrastres'));
    }

    public function storeAgente(Request $request)
    {
        $data = $request->validate([
            'cliente'            => 'nullable|string|max:255',
            'aduana'             => 'nullable|string|max:255',
            'agente_aduanal'     => 'required|string|max:255',
            'razon_social'       => 'nullable|string|max:255',
            'patente'            => 'nullable|string|max:50',
            'calificacion'       => 'nullable|integer|min:1|max:5',
            'responsabilidad'    => 'required|string|max:120',
            'nombre'             => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|email|max:255',
            'telefono'           => 'nullable|string|max:50',
            'comentarios'        => 'nullable|string',
        ]);

        MatrizApoyoAgente::create($data);

        return response()->json(['success' => true]);
    }

    public function updateAgente(Request $request, MatrizApoyoAgente $agente)
    {
        $data = $request->validate([
            'cliente'            => 'nullable|string|max:255',
            'aduana'             => 'nullable|string|max:255',
            'agente_aduanal'     => 'required|string|max:255',
            'razon_social'       => 'nullable|string|max:255',
            'patente'            => 'nullable|string|max:50',
            'calificacion'       => 'nullable|integer|min:1|max:5',
            'responsabilidad'    => 'required|string|max:120',
            'nombre'             => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|email|max:255',
            'telefono'           => 'nullable|string|max:50',
            'comentarios'        => 'nullable|string',
        ]);

        $agente->update($data);

        return response()->json(['success' => true]);
    }

    public function destroyAgente(MatrizApoyoAgente $agente)
    {
        $agente->delete();

        return response()->json(['success' => true]);
    }

    // ── FORWARDERS ────────────────────────────────────────

    public function storeForwarder(Request $request)
    {
        $data = $request->validate([
            'cliente'            => 'required|string|max:255',
            'aduana'             => 'nullable|string|max:255',
            'razon_social'       => 'nullable|string|max:255',
            'calificacion'       => 'nullable|integer|min:1|max:5',
            'responsabilidad'    => 'required|string|max:120',
            'nombre'             => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|email|max:255',
            'telefono'           => 'nullable|string|max:50',
            'comentarios'        => 'nullable|string',
        ]);

        MatrizApoyoForwarder::create($data);

        return response()->json(['success' => true]);
    }

    public function updateForwarder(Request $request, MatrizApoyoForwarder $forwarder)
    {
        $data = $request->validate([
            'cliente'            => 'required|string|max:255',
            'aduana'             => 'nullable|string|max:255',
            'razon_social'       => 'nullable|string|max:255',
            'calificacion'       => 'nullable|integer|min:1|max:5',
            'responsabilidad'    => 'required|string|max:120',
            'nombre'             => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|email|max:255',
            'telefono'           => 'nullable|string|max:50',
            'comentarios'        => 'nullable|string',
        ]);

        $forwarder->update($data);

        return response()->json(['success' => true]);
    }

    public function destroyForwarder(MatrizApoyoForwarder $forwarder)
    {
        $forwarder->delete();

        return response()->json(['success' => true]);
    }

    // ── NAVIERAS ──────────────────────────────────────────

    public function storeNaviera(Request $request)
    {
        $data = $request->validate([
            'cliente'            => 'nullable|string|max:255',
            'aduana'             => 'nullable|string|max:255',
            'razon_social'       => 'nullable|string|max:255',
            'calificacion'       => 'nullable|integer|min:1|max:5',
            'responsabilidad'    => 'required|string|max:120',
            'nombre'             => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|email|max:255',
            'telefono'           => 'nullable|string|max:50',
            'comentarios'        => 'nullable|string',
        ]);
        MatrizApoyoNaviera::create($data);
        return response()->json(['success' => true]);
    }

    public function updateNaviera(Request $request, MatrizApoyoNaviera $naviera)
    {
        $data = $request->validate([
            'cliente'            => 'nullable|string|max:255',
            'aduana'             => 'nullable|string|max:255',
            'razon_social'       => 'nullable|string|max:255',
            'calificacion'       => 'nullable|integer|min:1|max:5',
            'responsabilidad'    => 'required|string|max:120',
            'nombre'             => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|email|max:255',
            'telefono'           => 'nullable|string|max:50',
            'comentarios'        => 'nullable|string',
        ]);
        $naviera->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyNaviera(MatrizApoyoNaviera $naviera)
    {
        $naviera->delete();
        return response()->json(['success' => true]);
    }

    // ── ARRASTRES ─────────────────────────────────────────

    public function storeArrastre(Request $request)
    {
        $data = $request->validate([
            'cliente'            => 'nullable|string|max:255',
            'aduana'             => 'nullable|string|max:255',
            'razon_social'       => 'nullable|string|max:255',
            'calificacion'       => 'nullable|integer|min:1|max:5',
            'responsabilidad'    => 'required|string|max:120',
            'nombre'             => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|email|max:255',
            'telefono'           => 'nullable|string|max:50',
            'comentarios'        => 'nullable|string',
        ]);
        MatrizApoyoArrastre::create($data);
        return response()->json(['success' => true]);
    }

    public function updateArrastre(Request $request, MatrizApoyoArrastre $arrastre)
    {
        $data = $request->validate([
            'cliente'            => 'nullable|string|max:255',
            'aduana'             => 'nullable|string|max:255',
            'razon_social'       => 'nullable|string|max:255',
            'calificacion'       => 'nullable|integer|min:1|max:5',
            'responsabilidad'    => 'required|string|max:120',
            'nombre'             => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|email|max:255',
            'telefono'           => 'nullable|string|max:50',
            'comentarios'        => 'nullable|string',
        ]);
        $arrastre->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyArrastre(MatrizApoyoArrastre $arrastre)
    {
        $arrastre->delete();
        return response()->json(['success' => true]);
    }
}
