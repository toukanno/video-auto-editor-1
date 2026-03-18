<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::guessPolicyNamesUsing(fn (string $modelClass) => 'App\\Policies\\'.class_basename($modelClass).'Policy');

        if (app()->environment(['local', 'testing']) && ! Auth::check() && ! app()->runningInConsole()) {
            $user = User::first() ?? User::create([
                'name' => 'Local Demo User',
                'email' => 'demo@example.com',
                'password' => 'password',
            ]);

            Auth::login($user);
        }
    }
}
