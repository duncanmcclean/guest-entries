<?php

namespace DuncanMcClean\GuestEntries\Http\Requests;

use DuncanMcClean\GuestEntries\Rules\CollectionExists;
use DuncanMcClean\GuestEntries\Rules\EntryExists;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    use Concerns\AcceptsFormRequests,
        Concerns\HandleFailedValidation,
        Concerns\WhitelistedCollections;

    public function authorize()
    {
        return $this->collectionIsWhitelisted($this->get('_collection'));
    }

    public function rules()
    {
        $rules = [
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

        if ($formRequest = $this->get('_request')) {
            $rules = array_merge($this->buildFormRequest($formRequest, $this)->rules());
        }

        return $rules;
    }

    public function messages()
    {
        if ($formRequest = $this->get('_request')) {
            return $this->buildFormRequest($formRequest, $this)->messages();
        }

        return [];
    }
}
