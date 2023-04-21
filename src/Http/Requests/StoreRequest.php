<?php

namespace DuncanMcClean\GuestEntries\Http\Requests;

use DuncanMcClean\GuestEntries\Rules\CollectionExists;
use Illuminate\Foundation\Http\FormRequest;
use Statamic\Facades\Collection;

class StoreRequest extends FormRequest
{
    use Concerns\AcceptsFormRequests,
        Concerns\WhitelistedCollections,
        Concerns\HandleFailedValidation;

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
            '_redirect' => ['nullable', 'string'],
            '_error_redirect' => ['nullable', 'string'],
            '_request' => ['nullable', 'string'],
            'slug' => [
                Collection::find($this->get('_collection'))->autoGeneratesTitles()
                    ? null
                    : 'required_without:title',
            ],
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
