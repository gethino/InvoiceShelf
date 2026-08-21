<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Base64SquareImage implements ValidationRule
{
    public function __construct(private readonly int $maxBytes = 2097152) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $decoded = json_decode((string) $value);
        $encodedImage = is_object($decoded) ? ($decoded->data ?? null) : null;

        if (! is_string($encodedImage) || ! str_contains($encodedImage, ',')) {
            $fail("The {$attribute} must be a valid base64 image.");

            return;
        }

        $image = base64_decode(explode(',', $encodedImage, 2)[1], true);
        $dimensions = is_string($image) ? @getimagesizefromstring($image) : false;

        if (! is_string($image) || strlen($image) > $this->maxBytes) {
            $fail("The {$attribute} must not be larger than 2 MB.");

            return;
        }

        if ($dimensions === false || $dimensions[0] !== $dimensions[1]) {
            $fail("The {$attribute} must be a square image.");
        }
    }
}
