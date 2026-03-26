<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LegalController extends Controller
{
    public function dashboard()
    {
        return view('Legal.dashboard');
    }
}
