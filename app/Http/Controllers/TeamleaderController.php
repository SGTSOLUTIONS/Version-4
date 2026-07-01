<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TeamleaderController extends Controller
{
    public function dashboard()
    {
        return view('main.teamleader.dashboard');
    }
}
