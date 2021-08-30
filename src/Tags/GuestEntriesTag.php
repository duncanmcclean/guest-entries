<?php

namespace DoubleThreeDigital\GuestEntries\Tags;

use Statamic\Tags\Tags;

class GuestEntriesTag extends Tags
{
    use Concerns\FormBuilder;

    protected static $handle = 'guest-entries';

    // {{ guest-entries:create collection="name" }} <input type="hidden" name="title" value="Whatever..."> {{ /guest-entries }}
    public function create()
    {
        return $this->createForm(route('statamic.guest-entries.store'));
    }

    public function update()
    {
        return $this->createForm(route('statamic.guest-entries.update'));
    }

    public function delete()
    {
        return $this->createForm(route('statamic.guest-entries.destroy'), [], 'DELETE');
    }
}
