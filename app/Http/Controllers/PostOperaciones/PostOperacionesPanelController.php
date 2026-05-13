<?php

namespace App\Http\Controllers\PostOperaciones;

use App\Http\Controllers\Controller;

class PostOperacionesPanelController extends Controller
{
    public function dashboard()
    {
        return view('PostOperaciones.dashboard');
    }
}
