<?php

namespace DuncanMcClean\GuestEntries\Tests\Fixtures;

use Illuminate\Foundation\Http\FormRequest;

class FirstCustomUpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'record_label' => ['required', 'string', 'max:2'],
        ];
    }
}
