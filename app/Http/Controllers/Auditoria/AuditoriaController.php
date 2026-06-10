<?php

namespace App\Http\Controllers\Auditoria;

use App\Http\Controllers\Controller;

class AuditoriaController extends Controller
{
    public function dashboard(\Illuminate\Http\Request $request)
    {
        return app(AuditoriaProyectoController::class)->index($request);
    }
}
