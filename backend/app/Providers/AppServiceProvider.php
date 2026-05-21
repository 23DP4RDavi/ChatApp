<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        static $adminBootstrapApplied = false;

        if ($adminBootstrapApplied) {
            return;
        }

        $email = trim((string) env('ADMIN_BOOTSTRAP_EMAIL', ''));

        if ($email === '') {
            return;
        }

        try {
            if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'is_admin')) {
                return;
            }

            DB::table('users')
                ->where('email', $email)
                ->update(['is_admin' => true]);

            $adminBootstrapApplied = true;
        } catch (\Throwable $e) {
            // Ignore bootstrap promotion failures in production boot.
        }
    }
}
