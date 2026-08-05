<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Platform\Persistence\ModelIdentityMap;
use Illuminate\Database\Eloquent\Relations\Relation;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Role;

test('first-party and bouncer models use stable morph aliases', function () {
    expect(ModelIdentityMap::aliases())
        ->toHaveKey('company', Company::class)
        ->toHaveKey('customer', Customer::class)
        ->toHaveKey('invoice', Invoice::class)
        ->toHaveKey('user', User::class)
        ->toHaveKey('bouncer_ability', Ability::class)
        ->toHaveKey('bouncer_role', Role::class);

    expect((new Invoice)->getMorphClass())->toBe('invoice')
        ->and((new User)->getMorphClass())->toBe('user')
        ->and((new Role)->getMorphClass())->toBe('bouncer_role')
        ->and(Relation::getMorphedModel('customer'))->toBe(Customer::class);
});

test('every first-party model has a stable identity', function () {
    $models = collect(glob(app_path('Models/*.php')))
        ->map(fn (string $path): string => 'App\\Models\\'.pathinfo($path, PATHINFO_FILENAME));

    $mappedModels = collect(ModelIdentityMap::aliases())->values();

    expect($models->diff($mappedModels)->values()->all())->toBe([]);
});

test('database aliases do not leak through the existing v1 discriminator', function () {
    expect(ModelIdentityMap::publicType('invoice'))->toBe('App\\Models\\Invoice')
        ->and(ModelIdentityMap::publicType('Modules\\Example\\Models\\Record'))
        ->toBe('Modules\\Example\\Models\\Record');
});
