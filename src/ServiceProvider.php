<?php

namespace DoubleThreeDigital\GuestEntries;

use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'actions' => __DIR__ . '/../routes/actions.php',
    ];

    protected $tags = [
        Tags\GuestEntriesTag::class,
    ];
}
