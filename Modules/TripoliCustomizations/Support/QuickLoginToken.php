<?php

namespace Modules\TripoliCustomizations\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use JsonException;

class QuickLoginToken
{
    private const TOKEN_LIFETIME_HOURS = 12;

    public function issue(User $user, Company $company): string
    {
        return Crypt::encryptString(json_encode([
            'user_id' => $user->getKey(),
            'company_id' => $company->getKey(),
            'expires_at' => now()->addHours(self::TOKEN_LIFETIME_HOURS)->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{user_id: int, company_id: int}|null
     */
    public function resolve(string $token): ?array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            return null;
        }

        if (! is_array($payload)
            || ! is_int($payload['user_id'] ?? null)
            || ! is_int($payload['company_id'] ?? null)
            || ! is_int($payload['expires_at'] ?? null)
            || $payload['expires_at'] <= now()->timestamp) {
            return null;
        }

        return [
            'user_id' => $payload['user_id'],
            'company_id' => $payload['company_id'],
        ];
    }
}
