<?php

namespace DoubleThreeDigital\GuestEntries\Tags;

use Illuminate\Container\EntryNotFoundException;
use Statamic\Exceptions\CollectionNotFoundException;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Tags\Tags;

class GuestEntriesTag extends Tags
{
    use Concerns\FormBuilder;

    protected static $handle = 'guest-entries';

    // {{ guest-entries:create collection="name" }} <input type="hidden" name="title" value="Whatever..."> {{ /guest-entries }}
    public function create()
    {
        $collectionHandle = $this->params->get('collection');

        if (! $collectionHandle) {
            throw new \Exception("Guest Entries: The `collection` parameter is required when creating an entry.");
        }

        if (! Collection::handleExists($collectionHandle)) {
            throw new CollectionNotFoundException($collectionHandle);
        }

        return $this->createForm(route('statamic.guest-entries.store'));
    }

    public function update()
    {
        $entryId = $this->params->get('id');
        $collectionHandle = $this->params->get('collection');

        if (! $collectionHandle) {
            throw new \Exception("Guest Entries: The `collection` parameter is required when updating an entry.");
        }

        if (! $entryId) {
            throw new \Exception("Guest Entries: The `id` parameter is required when updating an entry.");
        }

        if (! Collection::handleExists($collectionHandle)) {
            throw new CollectionNotFoundException($collectionHandle);
        }

        if (! Entry::find($entryId)) {
            throw new EntryNotFoundException();
        }

        return $this->createForm(route('statamic.guest-entries.update'), Entry::find($entryId)->toAugmentedArray());
    }

    public function delete()
    {
        $entryId = $this->params->get('id');
        $collectionHandle = $this->params->get('collection');

        if (! $collectionHandle) {
            throw new \Exception("Guest Entries: The `collection` parameter is required when deleting an entry.");
        }

        if (! $entryId) {
            throw new \Exception("Guest Entries: The `id` parameter is required when deleting an entry.");
        }

        if (! Collection::handleExists($collectionHandle)) {
            throw new CollectionNotFoundException($collectionHandle);
        }

        if (! Entry::find($entryId)) {
            throw new EntryNotFoundException();
        }

        return $this->createForm(route('statamic.guest-entries.destroy'), Entry::find($entryId)->toAugmentedArray(), 'DELETE');
    }
}
