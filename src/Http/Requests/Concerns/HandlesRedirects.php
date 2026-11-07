<?php

namespace DuncanMcClean\GuestEntries\Http\Requests\Concerns;

use Statamic\Facades\URL;

trait HandlesRedirects
{
    public function redirectUrl(): ?string
    {
        return $this->internalUrl($this->input('_redirect'));
    }

    protected function getRedirectUrl()
    {
        return $this->internalUrl($this->input('_error_redirect')) ?? parent::getRedirectUrl();
    }

    private function internalUrl(mixed $url): ?string
    {
        return is_string($url) && ! URL::isExternalToApplication($url) ? $url : null;
    }
}
