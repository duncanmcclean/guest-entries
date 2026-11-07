<?php

namespace DuncanMcClean\GuestEntries\Tags\Concerns;

use Statamic\Tags\Concerns\RendersForms;

trait FormBuilder
{
    use RendersForms;

    protected static $knownParams = ['collection', 'id', 'redirect', 'error_redirect', 'request'];

    protected function createForm(string $action, array $data = [], string $method = 'POST', array $knownParams = []): string|array
    {
        $url = route("statamic.guest-entries.{$action}");
        $knownParams = array_merge(static::$knownParams, $knownParams);

        if (! $this->canParseContents()) {
            $attrs = $this->formAttrs($url, $method, $knownParams);
            $params = $this->formParams($method, $this->hiddenParams($action));

            return array_merge([
                'attrs' => $attrs,
                'attrs_html' => $this->renderAttributes($attrs),
                'params' => $this->formMetaPrefix($params),
                'params_html' => $this->formMetaFields($params),
            ], $data);
        }

        $html = $this->formOpen($url, $method, static::$knownParams);

        $html .= $this->formMetaFields($this->hiddenParams($action));

        $html .= $this->parse($this->sessionData($data));

        $html .= $this->formClose();

        return $html;
    }

    private function hiddenParams(string $action): array
    {
        $params = [
            'collection' => $this->params->get('collection'),
            'id' => $this->params->get('id'),
            'redirect' => $this->params->get('redirect'),
            'error_redirect' => $this->params->get('error_redirect'),
            'request' => $this->params->get('request'),
        ];

        if (config('guest-entries.disable_form_parameter_validation')) {
            return array_map(fn ($value) => $value ?? 'Empty', $params);
        }

        return ['params' => encrypt(['action' => $action, ...$params])];
    }

    protected function sessionData($data = [])
    {
        if ($errors = $this->errors()) {
            $data['errors'] = $errors;
        }

        return $data;
    }

    protected function params(): array
    {
        return collect(static::$knownParams)->map(function ($param, $ignore) {
            if ($redirect = $this->get($param)) {
                return $params[$param] = $redirect;
            }
        })->filter()
            ->values()
            ->all();
    }

    /**
     * @return bool|string
     */
    public function errors()
    {
        if (! $this->hasErrors()) {
            return false;
        }

        $errors = [];

        foreach (session('errors')->getBag('guest-entries')->all() as $error) {
            $errors[]['value'] = e($error);
        }

        return ($this->content === '')    // If this is a single tag...
            ? ! empty($errors)             // just output a boolean.
            : $errors;  // Otherwise, parse the content loop.
    }

    /**
     * Does this form have errors?
     */
    protected function hasErrors(): bool
    {
        return (session()->has('errors'))
            ? session('errors')->hasBag('guest-entries')
            : false;
    }

    /**
     * Get the errorBag from session.
     *
     * @return object
     */
    protected function getErrorBag()
    {
        if ($this->hasErrors()) {
            return session('errors')->getBag('guest-entries');
        }
    }
}
