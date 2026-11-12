<?php

namespace DuncanMcClean\GuestEntries\Http\Requests\Concerns;

use DuncanMcClean\GuestEntries\Rules\Turnstile;

trait ValidatesTurnstile
{
    private function turnstileRules(): array
    {
        if (! $this->form()->turnstile()) {
            return [];
        }

        return ['cf-turnstile-response' => [new Turnstile]];
    }
}
