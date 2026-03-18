<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDemoUser
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (!Auth::check()) {
            $user = User::first() ?? User::create([
                'name' => 'Demo User',
                'email' => 'demo@example.com',
                'password' => Hash::make('password'),
            ]);
            Auth::login($user);
            $request->session()->regenerate();
        }

        return $next($request);
    }
}
