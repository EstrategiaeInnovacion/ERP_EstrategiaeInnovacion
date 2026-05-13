<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Administracion\Cliente;

class AdministracionController extends Controller
{
    public function dashboard()
    {
        $totalClientes = Cliente::count();

        return view('Administracion.dashboard', compact('totalClientes'));
    }
}
