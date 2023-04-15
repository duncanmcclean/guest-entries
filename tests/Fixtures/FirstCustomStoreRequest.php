<?php

namespace DuncanMcClean\GuestEntries\Tests\Fixtures;

use Illuminate\Foundation\Http\FormRequest;

class FirstCustomStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'description' => ['required', 'string'],
        ];
    }
}
