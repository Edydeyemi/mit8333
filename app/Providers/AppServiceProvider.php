<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
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
        // Use Bootstrap 5 pagination styling across the application
        Paginator::useBootstrapFive();

        // Gate definition: super_admin has omnipotent access; all others verified against role permissions
        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->role === 'super_admin' || $user->hasRole('super_admin')) {
                return true;
            }

            if ($user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}
