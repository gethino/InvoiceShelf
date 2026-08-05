<?php

use App\Platform\Operations\Http\Middleware\CronJobMiddleware;
use App\Platform\Operations\Http\Middleware\EnsureNotContainerized;
use App\Platform\Operations\OperationsServiceProvider;
use App\Platform\Storage\Application\FileDiskService;
use App\Platform\Storage\Contracts\StorageConfigurator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

test('the operations platform owns runtime configuration commands and authorization', function () {
    expect(app()->getProviders(OperationsServiceProvider::class))->toHaveCount(1)
        ->and(app(StorageConfigurator::class))->toBeInstanceOf(FileDiskService::class)
        ->and(Gate::has('manage settings'))->toBeTrue()
        ->and(Gate::has('manage update app'))->toBeTrue()
        ->and(Artisan::all())->toHaveKeys(['core:update', 'reset:app']);

    expect(class_exists('App\\Providers\\AppConfigProvider'))->toBeFalse()
        ->and(class_exists('App\\Support\\Update\\Updater'))->toBeFalse()
        ->and(class_exists('App\\Console\\Commands\\UpdateCommand'))->toBeFalse()
        ->and(class_exists('App\\Console\\Commands\\ResetApp'))->toBeFalse()
        ->and(class_exists('App\\Http\\Controllers\\AppVersionController'))->toBeFalse()
        ->and(class_exists('App\\Http\\Controllers\\Admin\\UpdateController'))->toBeFalse()
        ->and(class_exists('App\\Http\\Controllers\\Admin\\Settings\\SettingsController'))->toBeFalse()
        ->and(class_exists('App\\Http\\Controllers\\Webhook\\CronJobController'))->toBeFalse();
});

test('the operations platform preserves its public routes and middleware', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => preg_match(
            '#^api/(?:v1/(?:app/version|settings$|check/update$|update/)|cron$)#',
            $route->uri(),
        ) === 1)
        ->keyBy(fn ($route): string => implode('|', $route->methods()).' '.$route->uri());

    expect($routes->keys()->sort()->values()->all())->toBe(collect([
        'GET|HEAD api/cron',
        'GET|HEAD api/v1/app/version',
        'GET|HEAD api/v1/check/update',
        'GET|HEAD api/v1/settings',
        'POST api/v1/settings',
        'POST api/v1/update/clean',
        'POST api/v1/update/copy',
        'POST api/v1/update/delete',
        'POST api/v1/update/download',
        'POST api/v1/update/finish',
        'POST api/v1/update/migrate',
        'POST api/v1/update/unzip',
    ])->sort()->values()->all());

    foreach ($routes as $route) {
        expect($route->getActionName())->toStartWith('App\\Platform\\Operations\\Http\\');
    }

    foreach (['GET|HEAD api/v1/settings', 'POST api/v1/settings'] as $key) {
        expect($routes->get($key)->gatherMiddleware())->toContain('auth:sanctum', 'company', 'bouncer');
    }

    foreach ($routes->filter(fn ($route) => str_contains($route->uri(), 'update')) as $route) {
        expect($route->gatherMiddleware())->toContain('auth:sanctum', 'company', 'not-containerized');
    }

    expect($routes->get('GET|HEAD api/cron')->gatherMiddleware())->toContain('cron-job')
        ->and(app('router')->getMiddleware()['cron-job'] ?? null)->toBe(CronJobMiddleware::class)
        ->and(app('router')->getMiddleware()['not-containerized'] ?? null)->toBe(EnsureNotContainerized::class);
});
