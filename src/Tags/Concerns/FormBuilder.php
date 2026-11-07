<?php

namespace DuncanMcClean\GuestEntries\Tags\Concerns;

use Statamic\Tags\Concerns\RendersForms;

trait FormBuilder
{
    use RendersForms;

    protected static $knownParams = ['form', 'id', 'redirect', 'error_redirect'];

    protected function createForm(string $action, array $data = [], string $method = 'POST'): string|array
    {
        $params = array_filter([
            'redirect' => $this->params->get('redirect'),
            'error_redirect' => $this->params->get('error_redirect'),
        ]);

        if (! $this->canParseContents()) {
            $attrs = $this->formAttrs($action, $method, static::$knownParams);
            $params = $this->formParams($method, $params);

            return array_merge([
                'attrs' => $attrs,
                'attrs_html' => $this->renderAttributes($attrs),
                'params' => $this->formMetaPrefix($params),
                'params_html' => $this->formMetaFields($params),
            ], $data);
        }

        $html = $this->formOpen($action, $method, static::$knownParams);

        $html .= $this->formMetaFields($params);

        $html .= $this->parse($this->sessionData($data));

        $html .= $this->formClose();

        return $html;
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
