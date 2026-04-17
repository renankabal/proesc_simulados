<?php

namespace App\Providers;

use App\Domain\Prova\Models\Prova;
use App\Http\Policies\ProvaPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Prova::class, ProvaPolicy::class);
    }
}
