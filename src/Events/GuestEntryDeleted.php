<?php

namespace DuncanMcClean\GuestEntries\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Statamic\Contracts\Entries\Entry;

class GuestEntryDeleted
{
    use Dispatchable, InteractsWithSockets;

    public $entry;

    public function __construct(Entry $entry)
    {
        $this->entry = $entry;
    }
}
