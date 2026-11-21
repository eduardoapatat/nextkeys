<?php

namespace App\Concerns;

use Illuminate\Support\Str;

/**
 * Form requests that read an email compare it in lowercase and without
 * surrounding spaces: Postgres compares strings case-sensitively, so
 * `Ana@x.cl` and `ana@x.cl` would otherwise be two accounts.
 */
trait NormalizesEmail
{
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (is_string($email)) {
            $this->merge(['email' => Str::lower(trim($email))]);
        }
    }
}
