<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Session\Session;

class ChatbotController extends Controller
{
    public function index()
    {
        $session = new Session();
        $token = $session->get('access_token');
        $id = $session->get('id');
        $role = $session->get('role_name');

        return view('chatbot.index', compact('token', 'id', 'role', 'session'));
    }
}
