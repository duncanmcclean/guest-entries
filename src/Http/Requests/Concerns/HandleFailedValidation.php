<?php

namespace DuncanMcClean\GuestEntries\Http\Requests\Concerns;

trait HandleFailedValidation
{
    /**
     * Get the URL to redirect to on a validation error.
     *
     * @return string
     */
    protected function getRedirectUrl()
    {
        if ($this->has('_error_redirect')) {
            return $this->get('_error_redirect');
        }

        return parent::getRedirectUrl();
    }
}
