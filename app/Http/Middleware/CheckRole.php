<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Illuminate\Support\Facades\Http;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {

        if (empty($roles)) {
            abort(403, 'Unauthorized action.');
        }
        if ($request->route('code')) {
            $code = $request->route('code');
        } else {
            $code = '';
        }
        $session = new Session();
        $role_name = $session->get('role_name');
        $token = $session->get('access_token');
        $id = $session->get('id');
        if (in_array('assistant', $roles) && $code == '') {
            $response = Http::withHeaders([
                'Authorization' => "Bearer " . $token,
                'Content-Type' => "application/json"
            ])->post(
                env("URL_API", "http://example.com") . '/api/v1/user/check/role',
                [
                    'user_id' => $id,
                ]
            );
            if ($response['data']) {
                return $next($request);
            }
        } elseif (in_array('assistant', $roles) && $code) {
            $response = Http::withHeaders([
                'Authorization' => "Bearer " . $token,
                'Content-Type' => "application/json"
            ])->post(
                env("URL_API", "http://example.com") . '/api/v1/assistant/check',
                [
                    'user_id' => $id,
                    'course_code' => $code
                ]
            );
            if ($response['data']) {
                return $next($request);
            }
        }


        if (in_array($role_name, $roles)) {
            return $next($request);
        } else {
            abort(403, 'Unauthorized action.');
        }
    }
}
