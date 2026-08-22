<?php

use App\Models\Company;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\User;
use Modules\TripoliCustomizations\Support\QuickLoginToken;

use function Pest\Laravel\postJson;

beforeEach(function () {
    Currency::query()->create([
        'id' => 1,
        'name' => 'US Dollar',
        'code' => 'USD',
        'symbol' => '$',
        'precision' => 2,
        'thousand_separator' => ',',
        'decimal_separator' => '.',
    ]);

    $this->user = User::query()->create([
        'name' => 'Quick User',
        'email' => 'quick@example.com',
        'role' => 'super admin',
        'password' => 'secret',
        'currency_id' => 1,
    ]);

    $this->company = Company::factory()->create([
        'owner_id' => $this->user->id,
        'name' => 'Quick Login Company',
    ]);
    $this->user->companies()->attach($this->company);

    Setting::setSetting('login_brand_company_id', (string) $this->company->id);
});

test('login page exposes company users without email or raw ids', function () {
    $this->user->addMediaFromBase64(quickLoginAvatarPng())
        ->usingFileName('avatar.png')
        ->toMediaCollection('admin_avatar');

    $avatar = $this->user->fresh()->avatar;
    Setting::setSetting('profile_complete', 'COMPLETED');

    $this->get('/login')
        ->assertOk()
        ->assertSee('Quick User', false)
        ->assertSee(json_encode($avatar, JSON_THROW_ON_ERROR), false)
        ->assertSee('"quick_login_enabled":true', false)
        ->assertDontSee('quick@example.com', false)
        ->assertDontSee('"user_id":'.$this->user->id, false);
});

test('company user can authenticate with quick login token and password', function () {
    $token = app(QuickLoginToken::class)->issue($this->user, $this->company);

    postJson('/quick-login', [
        'token' => $token,
        'password' => 'secret',
    ])->assertNoContent();

    $this->assertAuthenticatedAs($this->user);
});

test('quick login rejects invalid credentials and stale membership', function () {
    $token = app(QuickLoginToken::class)->issue($this->user, $this->company);

    postJson('/quick-login', [
        'token' => $token,
        'password' => 'wrong-password',
    ])->assertUnprocessable();

    $this->user->companies()->detach($this->company);

    postJson('/quick-login', [
        'token' => $token,
        'password' => 'secret',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');

    $this->assertGuest();
});

test('quick login rejects expired and wrong-company tokens', function () {
    $token = app(QuickLoginToken::class)->issue($this->user, $this->company);
    $otherCompany = Company::factory()->create(['owner_id' => $this->user->id]);
    $wrongCompanyToken = app(QuickLoginToken::class)->issue($this->user, $otherCompany);

    postJson('/quick-login', [
        'token' => $wrongCompanyToken,
        'password' => 'secret',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');

    $this->travel(13)->hours();

    postJson('/quick-login', [
        'token' => $token,
        'password' => 'secret',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
});

test('quick login can be disabled', function () {
    Setting::setSetting('quick_login_enabled', 'NO');
    Setting::setSetting('profile_complete', 'COMPLETED');
    $token = app(QuickLoginToken::class)->issue($this->user, $this->company);

    $this->get('/login')
        ->assertOk()
        ->assertSee('"quick_login_enabled":false', false)
        ->assertSee('"quick_login_users":[]', false);

    postJson('/quick-login', [
        'token' => $token,
        'password' => 'secret',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
});

test('quick login keeps standard login throttling', function () {
    $token = app(QuickLoginToken::class)->issue($this->user, $this->company);

    foreach (range(1, 5) as $attempt) {
        postJson('/quick-login', [
            'token' => $token,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    postJson('/quick-login', [
        'token' => $token,
        'password' => 'wrong-password',
    ])->assertTooManyRequests();
});

function quickLoginAvatarPng(): string
{
    $image = imagecreatetruecolor(2, 2);

    ob_start();
    imagepng($image);
    $contents = ob_get_clean();
    imagedestroy($image);

    return 'data:image/png;base64,'.base64_encode($contents);
}
