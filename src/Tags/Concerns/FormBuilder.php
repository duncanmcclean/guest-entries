<?php

namespace DuncanMcClean\GuestEntries\Tags\Concerns;

use Statamic\Tags\Concerns\RendersForms;

trait FormBuilder
{
    use RendersForms;

    protected static $knownParams = ['collection', 'id', 'redirect', 'error_redirect', 'request'];

    protected function createForm(string $action, array $data = [], string $method = 'POST', array $knownParams = []): string|array
    {
        $knownParams = array_merge(static::$knownParams, $knownParams);

        if (! $this->canParseContents()) {
            $attrs = $this->formAttrs($action, $method, $knownParams);

            $params = $this->formParams($method, [
                'collection' => $this->collectionValue(),
                'id' => $this->idValue(),
                'redirect' => $this->redirectValue(),
                'error_redirect' => $this->errorRedirectValue(),
                'request' => $this->requestValue(),
            ]);

            return array_merge([
                'attrs' => $attrs,
                'attrs_html' => $this->renderAttributes($attrs),
                'params' => $this->formMetaPrefix($params),
                'params_html' => $this->formMetaFields($params),
            ], $data);
        }

        $html = $this->formOpen($action, $method, static::$knownParams);

        $html .= $this->collectionField();
        $html .= $this->idField();
        $html .= $this->redirectField();
        $html .= $this->errorRedirectField();
        $html .= $this->requestField();

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

    protected function collectionValue()
    {
        $collection = $this->params->get('collection') ?? 'Empty';

        return config('guest-entries.disable_form_parameter_validation')
            ? $collection
            : encrypt($collection);
    }

    protected function idValue()
    {
        $id = $this->params->get('id') ?? 'Empty';

        return config('guest-entries.disable_form_parameter_validation')
            ? $id
            : encrypt($id);
    }

    protected function redirectValue()
    {
        $redirect = $this->params->get('redirect') ?? 'Empty';

        return config('guest-entries.disable_form_parameter_validation')
            ? $redirect
            : encrypt($redirect);
    }

    protected function errorRedirectValue()
    {
        $errorRedirect = $this->params->get('error_redirect') ?? 'Empty';

        return config('guest-entries.disable_form_parameter_validation')
            ? $errorRedirect
            : encrypt($errorRedirect);
    }

    protected function requestValue()
    {
        $request = $this->params->get('request') ?? 'Empty';

        return config('guest-entries.disable_form_parameter_validation')
            ? $request
            : encrypt($request);
    }

    protected function collectionField()
    {
        return '<input type="hidden" name="_collection" value="'.$this->collectionValue().'" />';
    }

    protected function idField()
    {
        return '<input type="hidden" name="_id" value="'.$this->idValue().'" />';
    }

    protected function redirectField()
    {
        return '<input type="hidden" name="_redirect" value="'.$this->redirectValue().'" />';
    }

    protected function errorRedirectField()
    {
        return '<input type="hidden" name="_error_redirect" value="'.$this->errorRedirectValue().'" />';
    }

    protected function requestField()
    {
        return '<input type="hidden" name="_request" value="'.$this->requestValue().'" />';
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
            $errors[]['value'] = $error;
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
