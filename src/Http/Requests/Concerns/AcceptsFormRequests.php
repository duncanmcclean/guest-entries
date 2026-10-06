<?php

namespace DuncanMcClean\GuestEntries\Http\Requests\Concerns;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

trait AcceptsFormRequests
{
    public function buildFormRequest(string $formRequestClass, Request $request): ?FormRequest
    {
        $formRequest = match (true) {
            is_subclass_of($formRequestClass, FormRequest::class) => $formRequestClass,
            is_subclass_of($class = "App\\Http\\Requests\\$formRequestClass", FormRequest::class) => $class,
            default => null,
        };

        if ($formRequest) {
            $request = FormRequest::createFrom($request, new $formRequest);
            $request->setContainer(app())->setRedirector(app()->make(Redirector::class));

            return $request;
        }

        throw new \Exception("Unable to find Form Request [$formRequestClass]");
    }
}
