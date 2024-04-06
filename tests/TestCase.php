<?php

namespace DuncanMcClean\GuestEntries\Tests;

use DuncanMcClean\GuestEntries\ServiceProvider;
use Statamic\Extend\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
