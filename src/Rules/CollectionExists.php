<?php

namespace DoubleThreeDigital\GuestEntries\Rules;

use Illuminate\Validation\Rule;
use Statamic\Facades\Collection;

class CollectionExists extends Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return Collection::find($value) !== null;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Could not find collection :attribute.';
    }
}
