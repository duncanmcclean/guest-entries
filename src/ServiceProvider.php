<?php

namespace DoubleThreeDigital\GuestEntries;

use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'actions' => __DIR__.'/../routes/actions.php',
    ];

    protected $tags = [
        Tags\GuestEntriesTag::class,
    ];

    public function boot()
    {
        parent::boot();

        $this->mergeConfigFrom(__DIR__.'/../config/guest-entries.php', 'guest-entries');

        $this->publishes([
            __DIR__.'/../config/guest-entries.php' => config_path('guest-entries.php'),
        ], 'guest-entries-config');
    }
}
