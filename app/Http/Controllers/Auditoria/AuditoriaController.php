<?php

namespace App\Http\Controllers\Auditoria;

use App\Http\Controllers\Controller;

class AuditoriaController extends Controller
{
    public function dashboard()
    {
        return view('Auditoria.dashboard');
    }
}
