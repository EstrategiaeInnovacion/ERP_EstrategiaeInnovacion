<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Administracion\Cliente;
use Illuminate\Http\Request;

class ClientesReadonlyController extends Controller
{
    private array $panelMap = [
        'logistica'      => ['label' => 'Panel Logística',       'route' => 'logistica.index'],
        'legal'          => ['label' => 'Panel Legal',           'route' => 'legal.dashboard'],
        'auditoria'      => ['label' => 'Panel Auditoría',       'route' => 'auditoria.dashboard'],
        'postoperaciones'=> ['label' => 'Panel Post-Operaciones','route' => 'postoperaciones.dashboard'],
        'anexo24'        => ['label' => 'Panel Anexo 24',        'route' => 'anexo24.dashboard'],
    ];

    public function index(Request $request)
    {
        $routeName = $request->route()->getName() ?? '';
        $prefix    = explode('.', $routeName)[0];

        $panel      = $this->panelMap[$prefix]['label'] ?? 'Panel';
        $panelRoute = route($this->panelMap[$prefix]['route'] ?? 'welcome');

        $clientes = Cliente::with('perfil')->orderBy('nombre')->get();

        return view('shared.clientes-readonly', compact('clientes', 'panel', 'panelRoute'));
    }
}
