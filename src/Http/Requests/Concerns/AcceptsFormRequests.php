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
            class_exists($class = $formRequestClass) => $class,
            class_exists($class = "App\\Http\\Requests\\$formRequestClass") => $class,
            default => null,
        };

        if ($formRequest) {
            $class = new $class;

            $request = FormRequest::createFrom($request, $class);
            $request->setContainer(app())->setRedirector(app()->make(Redirector::class));

            return $request;
        }

        throw new \Exception("Unable to find Form Request [$formRequestClass]");
    }
}
