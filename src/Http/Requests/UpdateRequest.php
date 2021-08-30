<?php

namespace DoubleThreeDigital\GuestEntries\Http\Requests;

use DoubleThreeDigital\GuestEntries\Rules\CollectionExists;
use DoubleThreeDigital\GuestEntries\Rules\EntryExists;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    use Concerns\WhitelistedCollections;

    public function authorize()
    {
        return $this->collectionIsWhitelisted($this->get('_collection'));
    }

    public function rules()
    {
        return [
            '_collection'     => ['required', 'string',
                // new CollectionExists
            ],
            '_id'             => ['required', 'string',
                // new EntryExists
            ],
            '_redirect'       => ['nullable', 'string'],
            '_error_redirect' => ['nullable', 'string'],
            '_request'        => ['nullable', 'string'],
        ];
    }
}
