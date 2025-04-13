<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Session\Session;

class EvaluationController extends Controller
{
    public function index($questionGuid, $userAnswerGuid)
    {
        $session = new Session();
        $token = $session->get('access_token');
        $id = $session->get('id');
        $role = $session->get('role');

      


    return view('evaluation.index', compact('questionGuid', 'userAnswerGuid', 'token', 'id', 'role', 'session'));
    }
}
