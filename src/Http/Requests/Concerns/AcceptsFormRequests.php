<?php

namespace DuncanMcClean\GuestEntries\Http\Requests\Concerns;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Redirector;

trait AcceptsFormRequests
{
    private ?FormRequest $formRequest = null;

    public function formRequest(): ?FormRequest
    {
        if (! $formRequestClass = $this->form()->formRequest()) {
            return null;
        }

        return $this->formRequest ??= $this->buildFormRequest($formRequestClass);
    }

    private function buildFormRequest(string $formRequestClass): FormRequest
    {
        $formRequest = match (true) {
            is_subclass_of($formRequestClass, FormRequest::class) => $formRequestClass,
            is_subclass_of($class = "App\\Http\\Requests\\$formRequestClass", FormRequest::class) => $class,
            default => throw new \Exception("Unable to find Form Request [$formRequestClass]"),
        };

        return FormRequest::createFrom($this, new $formRequest)
            ->setContainer(app())
            ->setRedirector(app()->make(Redirector::class));
    }

    private function formRequestAuthorizes(): bool
    {
        $formRequest = $this->formRequest();

        return ! $formRequest || ! method_exists($formRequest, 'authorize') || $formRequest->authorize();
    }
}
