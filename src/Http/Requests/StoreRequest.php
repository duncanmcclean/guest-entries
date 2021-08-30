<?php

namespace DoubleThreeDigital\GuestEntries\Http\Requests;

use DoubleThreeDigital\GuestEntries\Rules\CollectionExists;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            '_collection'     => ['required', 'string',
                // new CollectionExists
            ],
            '_redirect'       => ['nullable', 'string'],
            '_error_redirect' => ['nullable', 'string'],
            '_request'        => ['nullable', 'string'],
        ];
    }
}
