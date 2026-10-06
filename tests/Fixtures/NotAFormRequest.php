<?php

namespace DuncanMcClean\GuestEntries\Tests\Fixtures;

class NotAFormRequest
{
    public static bool $instantiated = false;

    public function __construct()
    {
        static::$instantiated = true;
    }
}
