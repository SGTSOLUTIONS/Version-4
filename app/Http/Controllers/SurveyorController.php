<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SurveyorController extends Controller
{
     public function dashboard()
    {
        return view('main.surveyor.dashboard');
    }
}
