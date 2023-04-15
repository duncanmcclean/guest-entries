<?php

namespace DuncanMcClean\GuestEntries\Rules;

use Illuminate\Validation\Rule;
use Statamic\Facades\Entry;

class EntryExists extends Rule
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
        return Entry::find($value) !== null;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Could not find entry :attribute.';
    }
}
