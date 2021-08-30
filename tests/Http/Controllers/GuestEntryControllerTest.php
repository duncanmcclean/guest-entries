<?php

namespace DoubleThreeDigital\GuestEntries\Tests\Http\Controllers;

use DoubleThreeDigital\GuestEntries\Tests\TestCase;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;

class GuestEntryControllerTest extends TestCase
{
    /** @test */
    public function can_store_entry()
    {
        Collection::make('comments')->save();

        $this
            ->post(route('statamic.guest-entries.store'), [
                '_collection' => 'comments',
                'title' => 'This is great',
                'slug' => 'this-is-great',
            ])
            ->assertRedirect();

        $entry = Entry::all()->last();

        $this->assertNotNull($entry);
        $this->assertSame($entry->collectionHandle(), 'comments');
        $this->assertSame($entry->get('title'), 'This is great');
        $this->assertSame($entry->slug(), 'this-is-great');
    }

    /** @test */
    public function can_update_entry()
    {
        Collection::make('albums')->save();

        Entry::make()
            ->id('allo-mate-idee')
            ->collection('albums')
            ->slug('allo-mate')
            ->data([
                'title' => 'Allo Mate!',
                'artist' => 'Guvna B',
            ])
            ->save();

        $this
            ->post(route('statamic.guest-entries.update'), [
                '_collection' => 'albums',
                '_id' => 'allo-mate-idee',
                'record_label' => 'Unknown',
            ])
            ->assertRedirect();

        $entry = Entry::find('allo-mate-idee');

        $this->assertNotNull($entry);
        $this->assertSame($entry->collectionHandle(), 'albums');
        $this->assertSame($entry->get('title'), 'Allo Mate!');
        $this->assertSame($entry->get('record_label'), 'Unknown');
        $this->assertSame($entry->slug(), 'allo-mate');
    }

    /** @test */
    public function can_destroy_entry()
    {
        Collection::make('albums')->save();

        Entry::make()
            ->id('allo-mate-idee')
            ->collection('albums')
            ->slug('allo-mate')
            ->data([
                'title' => 'Allo Mate!',
                'artist' => 'Guvna B',
            ])
            ->save();

        $this
            ->delete(route('statamic.guest-entries.destroy'), [
                '_collection' => 'albums',
                '_id' => 'allo-mate-idee',
            ])
            ->assertRedirect();

        $entry = Entry::find('allo-mate-idee');

        $this->assertNull($entry);
    }
}
