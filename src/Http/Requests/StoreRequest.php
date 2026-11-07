<?php

namespace DuncanMcClean\GuestEntries\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    use Concerns\AcceptsFormRequests,
        Concerns\HandlesRedirects,
        Concerns\ResolvesForm;

    private string $action = 'create';

    public function authorize()
    {
        return $this->form()->authorize()
            && $this->formRequestAuthorizes();
    }

    public function rules()
    {
        if ($formRequest = $this->formRequest()) {
            return $formRequest->rules();
        }

        return [
            'slug' => [
                $this->form()->collection()->autoGeneratesTitles()
                    ? null
                    : 'required_without:title',
            ],
        ];
    }

    public function messages()
    {
        return $this->formRequest()?->messages() ?? [];
    }
}
