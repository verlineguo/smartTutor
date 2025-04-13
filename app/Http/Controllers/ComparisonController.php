<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class ComparisonController extends Controller
{
    public function index()
    {
        $session = new Session();
        $token = $session->get('access_token');
        $id = $session->get('id');
        return view('comparison.index', compact('token', 'id', 'session'));
    }

    
}
