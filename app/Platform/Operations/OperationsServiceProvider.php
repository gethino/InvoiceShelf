<?php

namespace App\Platform\Operations;

use App\Platform\Operations\Application\RuntimeConfigurationService;
use App\Platform\Operations\Console\ResetApp;
use App\Platform\Operations\Console\UpdateCommand;
use App\Platform\Operations\Policies\OperationsAccessPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class OperationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RuntimeConfigurationService::class);
    }

    public function boot(RuntimeConfigurationService $runtimeConfiguration): void
    {
        Gate::define('manage settings', [OperationsAccessPolicy::class, 'manage']);
        Gate::define('manage update app', [OperationsAccessPolicy::class, 'manage']);

        $this->commands([
            ResetApp::class,
            UpdateCommand::class,
        ]);

        $runtimeConfiguration->apply();
    }
}
