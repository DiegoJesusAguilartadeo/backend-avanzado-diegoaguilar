<?php

namespace App\Providers;

use App\Models\User;
use App\Repositories\EloquentProductRepository;
use App\Repositories\ProductRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProductRepositoryInterface::class,
            EloquentProductRepository::class
        );
    }

    public function boot(): void
    {
        // Gate unificada y explícita para administradores
        Gate::define('admin-access', function (?User $user) {
            return $user !== null && $user->role === 'admin';
        });
    }
}