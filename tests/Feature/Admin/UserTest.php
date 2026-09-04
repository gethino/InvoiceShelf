<?php

use App\Http\Controllers\V1\Admin\Users\UsersController;
use App\Http\Requests\UserRequest;
use App\Models\Company;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Silber\Bouncer\BouncerFacade;
use Silber\Bouncer\Database\Role;

use function Pest\Faker\fake;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);

    $user = User::where('role', 'super admin')->first();

    $this->withHeaders([
        'company' => $user->companies()->first()->id,
    ]);

    Sanctum::actingAs(
        $user,
        ['*']
    );
});

getJson('/api/v1/users')->assertOk();

test('store user using a form request', function () {
    $this->assertActionUsesFormRequest(
        UsersController::class,
        'store',
        UserRequest::class
    );
});

// test('store user', function () {
//     $data = [
//         'name' => fake()->name,
//         'email' => fake()->unique()->safeEmail,
//         'phone' => fake()->phoneNumber,
//         'password' => fake()->password
//     ];

//     postJson('/api/v1/users', $data)->assertOk();

//     $this->assertDatabaseHas('users', [
//         'name' => $data['name'],
//         'email' => $data['email'],
//         'phone' => $data['phone'],
//     ]);
// });

test('get user belonging to the current company', function () {
    $companyId = User::where('role', 'super admin')->first()->companies()->first()->id;
    $user = User::factory()->create();
    $user->companies()->attach($companyId);

    getJson("/api/v1/users/{$user->id}")->assertOk();
});

test('cannot view a user belonging to another company', function () {
    $user = User::factory()->create();
    $user->companies()->attach(Company::factory()->create()->id);

    getJson("/api/v1/users/{$user->id}")->assertForbidden();
});

test('cannot update a user belonging to another company', function () {
    $companyId = User::where('role', 'super admin')->first()->companies()->first()->id;
    $user = User::factory()->create();
    $user->companies()->attach(Company::factory()->create()->id);

    putJson("/api/v1/users/{$user->id}", [
        'name' => 'Hacked',
        'email' => 'pwned@attacker.test',
        'companies' => [['id' => $companyId, 'role' => 'super admin']],
    ])->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'pwned@attacker.test']);
});

test('update user using a form request', function () {
    $this->assertActionUsesFormRequest(
        UsersController::class,
        'update',
        UserRequest::class
    );
});

// test('update user', function () {
//     $user = User::factory()->create();

//     $data = [
//         'name' => fake()->name,
//         'email' => fake()->unique()->safeEmail,
//         'phone' => fake()->phoneNumber,
//         'password' => fake()->password
//     ];

//     putJson("/api/v1/users/{$user->id}", $data)->assertOk();

//     $this->assertDatabaseHas('users', [
//         'name' => $data['name'],
//         'email' => $data['email'],
//         'phone' => $data['phone'],
//     ]);
// });

test('deletes a user belonging to the current company', function () {
    $companyId = User::where('role', 'super admin')->first()->companies()->first()->id;
    $user = User::factory()->create();
    $user->companies()->attach($companyId);

    postJson('/api/v1/users/delete', ['users' => [$user->id]])->assertOk();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('cannot bulk delete a user belonging to another company', function () {
    $user = User::factory()->create();
    $user->companies()->attach(Company::factory()->create()->id);

    postJson('/api/v1/users/delete', ['users' => [$user->id]])->assertOk();

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

function assignCompanyRole(User $user, Company $company, string $role): void
{
    BouncerFacade::scope()->to($company->id);
    Role::query()->firstOrCreate(['name' => $role]);
    $user->assign($role);
}

function ensureCompanyRoleExists(Company $company, string $role): void
{
    BouncerFacade::scope()->to($company->id);
    Role::query()->firstOrCreate(['name' => $role]);
}

function signInAsCompanyManager(Company $company): User
{
    $manager = User::factory()->create(['role' => 'manager']);
    $manager->companies()->attach($company->id);
    assignCompanyRole($manager, $company, 'manager');

    test()->withHeaders(['company' => $company->id]);
    Sanctum::actingAs($manager, ['*']);

    return $manager;
}

test('manager can list and create users in the active company', function () {
    $company = User::where('role', 'super admin')->first()->companies()->first();
    signInAsCompanyManager($company);
    ensureCompanyRoleExists($company, 'staff');

    getJson('/api/v1/users')->assertOk();

    $response = postJson('/api/v1/users', [
        'name' => 'Company Staff',
        'email' => 'company-staff@example.com',
        'password' => 'password123',
        'companies' => [[
            'id' => $company->id,
            'role' => 'staff',
        ]],
    ])->assertCreated();

    $createdUser = User::query()->findOrFail($response->json('data.id'));

    expect($createdUser->hasCompany($company->id))->toBeTrue()
        ->and($createdUser->hasRoleInCompany('staff', $company->id))->toBeTrue();
});

test('manager can edit roles but cannot change company membership', function () {
    $owner = User::where('role', 'super admin')->first();
    $company = $owner->companies()->first();
    $otherCompany = Company::factory()->create(['owner_id' => $owner->id]);
    signInAsCompanyManager($company);

    $target = User::factory()->create(['role' => 'staff']);
    $target->companies()->attach($company->id);
    assignCompanyRole($target, $company, 'staff');

    putJson("/api/v1/users/{$target->id}", [
        'name' => 'Promoted User',
        'email' => $target->email,
        'companies' => [['id' => $company->id, 'role' => 'manager']],
    ])->assertOk();

    expect($target->fresh()->hasRoleInCompany('manager', $company->id))->toBeTrue();

    putJson("/api/v1/users/{$target->id}", [
        'name' => 'Moved User',
        'email' => $target->email,
        'companies' => [['id' => $otherCompany->id, 'role' => 'staff']],
    ])->assertForbidden();

    expect($target->fresh()->hasCompany($company->id))->toBeTrue()
        ->and($target->fresh()->hasCompany($otherCompany->id))->toBeFalse();
});

test('manager cannot assign super admin edit owner or delete users', function () {
    $owner = User::where('role', 'super admin')->first();
    $company = $owner->companies()->first();
    signInAsCompanyManager($company);

    $target = User::factory()->create(['role' => 'staff']);
    $target->companies()->attach($company->id);
    assignCompanyRole($target, $company, 'staff');

    putJson("/api/v1/users/{$target->id}", [
        'name' => $target->name,
        'email' => $target->email,
        'companies' => [['id' => $company->id, 'role' => 'super admin']],
    ])->assertForbidden();

    getJson("/api/v1/users/{$owner->id}")->assertForbidden();
    postJson('/api/v1/users/delete', ['users' => [$target->id]])->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $target->id]);
});

test('only owners and multi-company managers can switch companies', function () {
    $owner = User::where('role', 'super admin')->first();
    $company = $owner->companies()->first();
    $otherCompany = Company::factory()->create(['owner_id' => $owner->id]);
    $owner->companies()->attach($otherCompany->id);

    expect($owner->canSwitchCompanies())->toBeTrue()
        ->and($owner->canCreateCompany())->toBeTrue();

    $manager = signInAsCompanyManager($company);
    expect($manager->canSwitchCompanies())->toBeFalse();

    $manager->companies()->attach($otherCompany->id);
    expect($manager->canSwitchCompanies())->toBeTrue()
        ->and($manager->canCreateCompany())->toBeFalse();

    $staff = User::factory()->create(['role' => 'staff']);
    $staff->companies()->attach([$company->id, $otherCompany->id]);
    assignCompanyRole($staff, $company, 'staff');

    expect($staff->canSwitchCompanies())->toBeFalse();
});
