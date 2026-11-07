<?php

namespace DuncanMcClean\GuestEntries\Http\Requests\Concerns;

use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Entry;

trait ResolvesEntry
{
    private ?EntryContract $entry = null;

    public function entry(): EntryContract
    {
        return $this->entry ??= $this->findEntry();
    }

    private function findEntry(): EntryContract
    {
        $entry = Entry::find($this->route('id'));

        abort_unless($entry?->collectionHandle() === $this->form()->collection()->handle(), 404);

        return $entry;
    }

    protected function failedAuthorization()
    {
        abort(404);
    }
}
