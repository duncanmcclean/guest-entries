<?php

namespace DoubleThreeDigital\GuestEntries\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Statamic\Contracts\Entries\Entry;

class GuestEntryCreated
{
    use Dispatchable, InteractsWithSockets;

    public $entry;

    public function __construct(Entry $entry)
    {
        $this->entry = $entry;
    }
}
