<?php

namespace DuncanMcClean\GuestEntries\Http\Middleware;

use Closure;
use DuncanMcClean\GuestEntries\Exceptions\InvalidFormParametersException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Statamic\Facades\Site;

class EnsureFormParametersArriveIntact
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @return Response
     *
     * @throws InvalidFormParametersException
     */
    public function handle($request, Closure $next)
    {
        $params = config('guest-entries.disable_form_parameter_validation')
            ? $this->unencryptedParams($request)
            : $this->encryptedParams($request);

        $params = collect(['collection', 'id', 'redirect', 'error_redirect', 'request'])
            ->mapWithKeys(fn (string $key) => ["_{$key}" => $params[$key] ?? null])
            ->map(fn ($value) => $value === 'Empty' ? null : $value);

        // The query string takes priority over the request body in $request->get(),
        // so we need to remove any parameters smuggled in through the query string.
        $params->keys()->each(fn (string $key) => $request->query->remove($key));

        $request->merge($params->all());

        return $next($request);
    }

    private function encryptedParams(Request $request): array
    {
        $payload = $request->input('_params');

        if (! is_string($payload)) {
            throw new InvalidFormParametersException;
        }

        try {
            $params = decrypt($payload);
        } catch (DecryptException) {
            throw new InvalidFormParametersException;
        }

        if (! is_array($params) || ($params['action'] ?? null) !== $request->route()->getActionMethod()) {
            throw new InvalidFormParametersException;
        }

        return $params;
    }

    private function unencryptedParams(Request $request): array
    {
        return [
            'collection' => $request->input('_collection'),
            'id' => $request->input('_id'),
            'redirect' => $this->internalUrl($request->input('_redirect'), $request),
            'error_redirect' => $this->internalUrl($request->input('_error_redirect'), $request) ?? $request->header('referer') ?? '/',
            'request' => $request->input('_request'),
        ];
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
