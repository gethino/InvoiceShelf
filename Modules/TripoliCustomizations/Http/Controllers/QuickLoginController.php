<?php

namespace Modules\TripoliCustomizations\Http\Controllers;

use App\Http\Controllers\V1\Admin\Auth\LoginController;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Modules\TripoliCustomizations\Http\Requests\QuickLoginRequest;
use Modules\TripoliCustomizations\Support\QuickLoginToken;

class QuickLoginController extends LoginController
{
    public function __invoke(
        QuickLoginRequest $request,
        QuickLoginToken $quickLoginToken,
    ): RedirectResponse|JsonResponse|Response {
        $payload = $quickLoginToken->resolve($request->string('token')->toString());
        $companyId = (int) Setting::getSetting('login_brand_company_id');

        if (Setting::getSetting('quick_login_enabled') === 'NO'
            || ! $payload
            || $payload['company_id'] !== $companyId) {
            $this->failAuthentication();
        }

        $user = User::query()
            ->whereKey($payload['user_id'])
            ->whereNotNull('email')
            ->whereNotNull('password')
            ->whereHas('companies', fn ($query) => $query->whereKey($companyId))
            ->first();

        if (! $user) {
            $this->failAuthentication();
        }

        $request->merge(['email' => $user->email]);

        return $this->login($request);
    }

    private function failAuthentication(): never
    {
        throw ValidationException::withMessages([
            'password' => [trans('auth.failed')],
        ]);
    }
}
