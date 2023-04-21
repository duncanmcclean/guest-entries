<?php

namespace DuncanMcClean\GuestEntries\Http\Requests;

use DuncanMcClean\GuestEntries\Rules\CollectionExists;
use DuncanMcClean\GuestEntries\Rules\EntryExists;
use Illuminate\Foundation\Http\FormRequest;

class DestroyRequest extends FormRequest
{
    use Concerns\WhitelistedCollections,
        Concerns\HandleFailedValidation;

    public function authorize()
    {
        return $this->collectionIsWhitelisted($this->get('_collection'));
    }

    public function rules()
    {
        return [
            '_collection' => ['required', 'string',
                // new CollectionExists
            ],
            '_id' => ['required', 'string',
                // new EntryExists
            ],
            '_redirect' => ['nullable', 'string'],
            '_error_redirect' => ['nullable', 'string'],
            '_request' => ['nullable', 'string'],
        ];
    }
}
