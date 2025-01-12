<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Session\Session;

class SessionTimeoutMiddleware
{
    protected $timeout = 60; // 30 menit (dalam detik)

    public function handle($request, Closure $next)
    {
        $session = new Session();
        if ($session->get('access_token')) {
            $token = $session->get('access_token');
            $id = $session->get('id');
            $lastActivity = session('last_activity');
            if ($lastActivity && (time() - $lastActivity > $this->timeout)) {
                Http::withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json',
                ])->post(env("URL_API", "http://example.com") . '/api/v1/auth/logout');
                Http::get(route('session.clear'));
                session()->flush(); // Hapus sesi
                return redirect()->route('login')->with('message', 'You have been logged out due to inactivity.');
            }
            session(['last_activity' => time()]); // Update waktu aktivitas terakhir
        }
        return $next($request);
    }
}
