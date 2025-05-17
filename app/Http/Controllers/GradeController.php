<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Illuminate\Support\Facades\Http;


class GradeController extends Controller
{
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
        return view('grade.index', compact('token', 'name', 'code', 'guid', 'session'));
    }

    public function detail($code, $guid, $userId)
    {
        $session = new Session();
        $token = $session->get('access_token');
        $responseTopic = Http::withHeaders([
            'Authorization' => "Bearer " . $token,
            'Content-Type' => "application/json"
        ])->get(env("URL_API", "http://example.com") . '/api/v1/topic/' . $guid);
        $topic = json_decode($responseTopic, true);
        $name = $topic['data']['name'];
        return view('grade.detail', compact('token', 'code', 'name', 'userId', 'guid', 'session'));
    }

    public function evaluation($code, $guid, $userId)
    {
        $session = new Session();
        $token = $session->get('access_token');
        $responseTopic = Http::withHeaders([
            'Authorization' => "Bearer " . $token,
            'Content-Type' => "application/json"
        ])->get(env("URL_API", "http://example.com") . '/api/v1/topic/' . $guid);
        $topic = json_decode($responseTopic, true);
        $name = $topic['data']['name'];
        return view('grade.evaluation', compact('token', 'code', 'name', 'userId', 'guid', 'session'));
    }
}

