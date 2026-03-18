<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDemoUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            $user = User::firstOrCreate(
                ['email' => 'demo@example.com'],
                ['name' => 'Demo User', 'password' => 'password']
            );

            Auth::login($user);
        }

        return $next($request);
    }
}
