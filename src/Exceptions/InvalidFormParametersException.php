<?php

namespace DuncanMcClean\GuestEntries\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidFormParametersException extends HttpException
{
    public function __construct()
    {
        parent::__construct(422, 'Invalid form parameters.');
    }
}
