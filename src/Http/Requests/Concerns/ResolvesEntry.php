<?php

namespace DuncanMcClean\GuestEntries\Http\Requests\Concerns;

use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Entry;

trait ResolvesEntry
{
    private ?EntryContract $entry = null;

    public function entry(): EntryContract
    {
        return $this->entry ??= Entry::find($this->get('_id')) ?? abort(404);
    }

    public function entryBelongsToCollection(): bool
    {
        return $this->entry()->collectionHandle() === $this->get('_collection');
    }
}
