<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Session\Session;
use Illuminate\Support\Facades\Http;

class QuestionController extends Controller
{

    public function generate()
    {
        $session = new Session();
        $token = $session->get('access_token');
        $id = $session->get('id');
        return view('question.generate', compact('token', 'id', 'session'));
    }
    public function index($code, $guid)
    {
        $session = new Session();
        $token = $session->get('access_token');
        $responseTopic = Http::withHeaders([
            'Authorization' => "Bearer " . $token,
            'Content-Type' => "application/json"
        ])->get(env("URL_API", "http://example.com") . '/api/v1/topic/' . $guid);
        $topic = json_decode($responseTopic, true);
        $name = $topic['data']['name'];
        $total_weight = $topic['data']['totalWeight'];
        return view('question.index', compact('token', 'guid', 'code', 'name', 'total_weight', 'session'));
    }
}
