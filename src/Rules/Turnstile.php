<?php

namespace DuncanMcClean\GuestEntries\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Turnstile implements ValidationRule
{
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '' || ! $this->verify($value)) {
            $fail(__('Please verify that you are human.'));
        }
    }

    private function verify(string $token): bool
    {
        $secretKey = config('guest-entries.turnstile.secret_key')
            ?? throw new \Exception('Guest Entries: Please set the `turnstile.secret_key` config option before enabling Turnstile on a form.');

        return Http::asForm()
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secretKey,
                'response' => $token,
            ])
            ->json('success') === true;
    }
}
