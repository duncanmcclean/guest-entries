<?php

namespace DuncanMcClean\GuestEntries\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    use Concerns\AcceptsFormRequests,
        Concerns\HandlesRedirects,
        Concerns\ResolvesEntry,
        Concerns\ResolvesForm;

    private string $action = 'update';

    public function authorize()
    {
        return $this->form()->authorize($this->entry())
            && $this->formRequestAuthorizes();
    }

    public function rules()
    {
        return $this->formRequest()?->rules() ?? [];
    }

    public function messages()
    {
        return $this->formRequest()?->messages() ?? [];
    }
}
