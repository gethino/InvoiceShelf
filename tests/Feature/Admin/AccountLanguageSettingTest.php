<?php

use App\Models\Company;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->user = User::query()->create([
        'name' => 'Account Language User',
        'email' => 'language@example.com',
        'password' => 'secret',
    ]);

    $company = Company::query()->create([
        'name' => 'Language Company',
        'owner_id' => $this->user->id,
        'slug' => 'language-company',
        'unique_hash' => 'language-company-hash',
    ]);

    $this->user->companies()->attach($company);
    $this->withHeaders(['company' => $company->id]);
    Sanctum::actingAs($this->user, ['*']);
});

test('authenticated user can save account language', function (string $language) {
    putJson('/api/v1/me/settings', [
        'settings' => [
            'language' => $language,
        ],
    ])->assertOk()->assertJson([
        'success' => true,
    ]);

    $this->assertDatabaseHas('user_settings', [
        'user_id' => $this->user->id,
        'key' => 'language',
        'value' => $language,
    ]);
})->with([
    'English' => 'en',
    'Arabic' => 'ar',
]);

test('account language buttons have localized labels', function () {
    $englishTranslations = json_decode(file_get_contents(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);
    $arabicTranslations = json_decode(file_get_contents(lang_path('ar.json')), true, flags: JSON_THROW_ON_ERROR);

    expect(data_get($englishTranslations, 'settings.account_settings.languages'))
        ->toBe([
            'english' => 'English',
            'arabic' => 'العربية',
        ])
        ->and(data_get($arabicTranslations, 'settings.account_settings.languages'))
        ->toBe([
            'english' => 'الإنجليزية',
            'arabic' => 'العربية',
        ]);
});
