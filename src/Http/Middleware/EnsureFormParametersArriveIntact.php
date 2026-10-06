<?php

namespace DuncanMcClean\GuestEntries\Http\Middleware;

use Closure;
use DuncanMcClean\GuestEntries\Exceptions\InvalidFormParametersException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Statamic\Facades\Site;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureFormParametersArriveIntact
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @return Response
     *
     * @throws AccessDeniedHttpException
     */
    public function handle($request, Closure $next)
    {
        // In a test environment, we don't want to worry about having to pass in form
        // parameters. So, before this test, we'll set some fallbacks for the params
        // if they're not set in the request.
        if (app()->environment('testing')) {
            $request->merge([
                '_redirect' => $request->has('_redirect')
                    ? $request->get('_redirect')
                    : encrypt($request->header('referer') ?? '/'),
                '_error_redirect' => $request->has('_error_redirect')
                    ? $request->get('_error_redirect')
                    : encrypt($request->header('referer') ?? '/'),
                '_request' => $request->has('_request')
                    ? $request->get('_request')
                    : encrypt('Empty'),
                '_collection' => $request->has('_collection')
                    ? $request->get('_collection')
                    : encrypt('Empty'),
                '_id' => $request->has('_id')
                    ? $request->get('_id')
                    : encrypt('Empty'),
            ]);
        }

        // If the validation of form parameters is disabled, we want to take the
        // user's input and encrypt it, so it can be used later in this middleware.
        if (config('guest-entries.disable_form_parameter_validation')) {
            $request->merge([
                '_request' => encrypt($request->get('_request') ?? $request->header('referer') ?? '/'),
                '_error_redirect' => encrypt($this->internalUrl($request->get('_error_redirect'), $request) ?? $request->header('referer') ?? '/'),
                '_redirect' => encrypt($this->internalUrl($request->get('_redirect'), $request) ?? 'Empty'),
                '_collection' => encrypt($request->get('_collection') ?? 'Empty'),
                '_id' => encrypt($request->get('_id') ?? 'Empty'),
            ]);
        }

        try {
            $redirectParam = decrypt($request->get('_redirect'));
            $errorRedirectParam = decrypt($request->get('_error_redirect'));
            $requestParam = decrypt($request->get('_request'));
            $collectionParam = decrypt($request->get('_collection'));
            $idParam = decrypt($request->get('_id'));
        } catch (DecryptException $e) {
            throw new InvalidFormParametersException;
        }

        if (! $redirectParam || ! $errorRedirectParam || ! $requestParam) {
            throw new InvalidFormParametersException;
        }

        $request->merge([
            '_redirect' => $redirectParam === 'Empty' ? null : $redirectParam,
            '_error_redirect' => $errorRedirectParam === 'Empty' ? null : $errorRedirectParam,
            '_request' => $requestParam === 'Empty' ? null : $requestParam,
            '_collection' => $collectionParam === 'Empty' ? null : $collectionParam,
            '_id' => $idParam === 'Empty' ? null : $idParam,
        ]);

        return $next($request);
    }

    private function internalUrl(mixed $url, Request $request): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $normalizedUrl = str_replace('\\', '/', preg_replace('/[\x00-\x20]/', '', $url));

        if (Str::startsWith($normalizedUrl, '//')) {
            return null;
        }

        $scheme = parse_url($normalizedUrl, PHP_URL_SCHEME);
        $host = parse_url($normalizedUrl, PHP_URL_HOST);

        if (is_null($scheme) && is_null($host)) {
            return $url;
        }

        $internalHosts = Site::all()
            ->map(fn ($site) => parse_url($site->absoluteUrl(), PHP_URL_HOST))
            ->push($request->getHost());

        if (in_array($scheme, ['http', 'https']) && $internalHosts->contains($host)) {
            return $url;
        }

        return null;
    }
}
