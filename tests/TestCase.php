<?php

namespace DuncanMcClean\GuestEntries\Tests;

use DuncanMcClean\GuestEntries\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        // We need to do this until https://github.com/statamic/cms/pull/13396
        // has been merged and tagged.
        \Statamic\Facades\CP\Nav::shouldReceive('clearCachedUrls')->zeroOrMoreTimes();
        $this->addToAssertionCount(-1); // Dont want to assert this
    }
}
