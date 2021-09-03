<?php

namespace DoubleThreeDigital\GuestEntries\Tests\Tags;

use DoubleThreeDigital\GuestEntries\Tags\GuestEntriesTag;
use DoubleThreeDigital\GuestEntries\Tests\TestCase;
use Illuminate\Container\EntryNotFoundException;
use Statamic\Exceptions\CollectionNotFoundException;
use Statamic\Facades\Antlers;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Tags\Tags;

class GuestEntriesTagTest extends TestCase
{
    /** @var Tags $tag */
    protected $tag;

    public function setUp(): void
    {
        parent::setUp();

        $this->tag = resolve(GuestEntriesTag::class)
            ->setParser(Antlers::parser())
            ->setContext([]);
    }

    /** @test */
    public function can_return_create_guest_entry_form()
    {
        Collection::make('guestbook')->save();

        $this->tag->setParameters([
            'collection' => 'guestbook',
        ]);

        $this->tag->setContent('
            <h2>Create Guestbook Entry</h2>

            <input name="name">
            <input name="email">
            <textarea name="comment"></textarea>

            <button type="submit">Submit</button>
        ');

        $usage = $this->tag->create();

        $this->assertStringContainsString('http://localhost/!/guest-entries/create', $usage);
        $this->assertStringContainsString('<input type="hidden" name="_token"', $usage);
        $this->assertStringContainsString('<input type="hidden" name="_collection" value="guestbook"', $usage);

        $this->assertStringContainsString('<h2>Create Guestbook Entry</h2>', $usage);
        $this->assertStringContainsString('<textarea name="comment"></textarea>', $usage);
    }

    /** @test */
    public function an_exception_is_thrown_when_attempting_to_return_create_guest_entry_form_if_no_collection_is_provided()
    {
        $this->expectException(\Exception::class);

        $this->tag->setParameters([
            'collection' => null,
        ]);

        $this->tag->setContent('
            <h2>Create Llalalalal Entry</h2>

            <input name="name">
            <input name="email">
            <textarea name="comment"></textarea>

            <button type="submit">Submit</button>
        ');

        $usage = $this->tag->create();
    }

    /** @test */
    public function an_exception_is_thrown_when_attempting_to_return_create_guest_entry_form_when_collection_does_not_exist()
    {
        $this->expectException(CollectionNotFoundException::class);

        $this->tag->setParameters([
            'collection' => 'lalalalallalalal',
        ]);

        $this->tag->setContent('
            <h2>Create Llalalalal Entry</h2>

            <input name="name">
            <input name="email">
            <textarea name="comment"></textarea>

            <button type="submit">Submit</button>
        ');

        $usage = $this->tag->create();
    }

    /** @test */
    public function can_return_update_guest_entry_form()
    {
        Collection::make('guestbook')->save();

        Entry::make()
            ->collection('guestbook')
            ->id('hello')
            ->slug('hello')
            ->data(['title' => 'Hello World'])
            ->save();

        $this->tag->setParameters([
            'collection' => 'guestbook',
            'id' => 'hello',
        ]);

        $this->tag->setContent('
            <h2>Update Guestbook Entry</h2>

            <input name="name">
            <input name="email">
            <textarea name="comment"></textarea>

            <button type="submit">Submit</button>
        ');

        $usage = $this->tag->update();

        $this->assertStringContainsString('http://localhost/!/guest-entries/update', $usage);
        $this->assertStringContainsString('<input type="hidden" name="_token"', $usage);
        $this->assertStringContainsString('<input type="hidden" name="_collection" value="guestbook"', $usage);
        $this->assertStringContainsString('<input type="hidden" name="_id" value="hello"', $usage);

        $this->assertStringContainsString('<h2>Update Guestbook Entry</h2>', $usage);
        $this->assertStringContainsString('<textarea name="comment"></textarea>', $usage);
    }

    /** @test */
    public function an_exception_is_thrown_when_attempting_to_return_update_guest_entry_form_if_no_collection_is_provided()
    {
        $this->expectException(\Exception::class);

        Collection::make('guestbook')->save();

        Entry::make()
            ->collection('guestbook')
            ->id('hello')
            ->slug('hello')
            ->data(['title' => 'Hello World'])
            ->save();

        $this->tag->setParameters([
            'id' => 'hello',
        ]);

        $this->tag->setContent('
            <h2>Update Guestbook Entry</h2>

            <input name="name">
            <input name="email">
            <textarea name="comment"></textarea>

            <button type="submit">Submit</button>
        ');

        $usage = $this->tag->update();
    }

    /** @test */
    public function an_exception_is_thrown_when_attempting_to_return_update_guest_entry_form_when_collection_does_not_exist()
    {
        $this->expectException(CollectionNotFoundException::class);

        Collection::make('guestbook')->save();

        Entry::make()
            ->collection('guestbook')
            ->id('hello')
            ->slug('hello')
            ->data(['title' => 'Hello World'])
            ->save();

        $this->tag->setParameters([
            'collection' => 'blah',
            'id' => 'hello',
        ]);

        $this->tag->setContent('
            <h2>Update Guestbook Entry</h2>

            <input name="name">
            <input name="email">
            <textarea name="comment"></textarea>

            <button type="submit">Submit</button>
        ');

        $usage = $this->tag->update();
    }

    /** @test */
    public function an_exception_is_thrown_when_attempting_to_return_update_guest_entry_form_if_no_entry_id_is_provided()
    {
        $this->expectException(\Exception::class);

        Collection::make('guestbook')->save();

        Entry::make()
            ->collection('guestbook')
            ->id('hello')
            ->slug('hello')
            ->data(['title' => 'Hello World'])
            ->save();

        $this->tag->setParameters([
            'collection' => 'guestbook',
        ]);

        $this->tag->setContent('
            <h2>Update Guestbook Entry</h2>

            <input name="name">
            <input name="email">
            <textarea name="comment"></textarea>

            <button type="submit">Submit</button>
        ');

        $usage = $this->tag->update();
    }

    /** @test */
    public function an_exception_is_thrown_when_attempting_to_return_update_guest_entry_form_when_entry_id_does_not_exist()
    {
        $this->expectException(EntryNotFoundException::class);

        Collection::make('guestbook')->save();

        Entry::make()
            ->collection('guestbook')
            ->id('hello')
            ->slug('hello')
            ->data(['title' => 'Hello World'])
            ->save();

        $this->tag->setParameters([
            'collection' => 'guestbook',
            'id' => 'blhabahahahah',
        ]);

        $this->tag->setContent('
            <h2>Update Guestbook Entry</h2>

            <input name="name">
            <input name="email">
            <textarea name="comment"></textarea>

            <button type="submit">Submit</button>
        ');

        $usage = $this->tag->update();
    }

    /** @test */
    public function can_return_delete_guest_entry_form()
    {
        Collection::make('guestbook')->save();

        Entry::make()
            ->collection('guestbook')
            ->id('hello')
            ->slug('hello')
            ->data(['title' => 'Hello World'])
            ->save();

        $this->tag->setParameters([
            'collection' => 'guestbook',
            'id' => 'hello',
        ]);

        $this->tag->setContent('
            <h2>Delete Guestbook Entry</h2>

            <button type="submit">DELETE</button>
        ');

        $usage = $this->tag->delete();

        $this->assertStringContainsString('http://localhost/!/guest-entries/delete', $usage);
        $this->assertStringContainsString('<input type="hidden" name="_token"', $usage);
        $this->assertStringContainsString('<input type="hidden" name="_collection" value="guestbook"', $usage);
        $this->assertStringContainsString('<input type="hidden" name="_id" value="hello"', $usage);

        $this->assertStringContainsString('<h2>Delete Guestbook Entry</h2>', $usage);
        $this->assertStringContainsString('<button type="submit">DELETE</button>', $usage);
    }

    /** @test */
    public function an_exception_is_thrown_when_attempting_to_return_delete_guest_entry_form_if_no_collection_is_provided()
    {
        $this->expectException(\Exception::class);

        Collection::make('guestbook')->save();

        Entry::make()
            ->collection('guestbook')
            ->id('hello')
            ->slug('hello')
            ->data(['title' => 'Hello World'])
            ->save();

        $this->tag->setParameters([
            'id' => 'hello',
        ]);

        $this->tag->setContent('
            <h2>Delete Guestbook Entry</h2>

            <button type="submit">DELETE</button>
        ');

        $usage = $this->tag->delete();
    }

    /** @test */
    public function an_exception_is_thrown_when_attempting_to_return_delete_guest_entry_form_when_collection_does_not_exist()
    {
        $this->expectException(CollectionNotFoundException::class);

        Collection::make('guestbook')->save();

        Entry::make()
            ->collection('guestbook')
            ->id('hello')
            ->slug('hello')
            ->data(['title' => 'Hello World'])
            ->save();

        $this->tag->setParameters([
            'collection' => 'blah',
            'id' => 'hello',
        ]);

        $this->tag->setContent('
            <h2>Delete Guestbook Entry</h2>

            <button type="submit">DELETE</button>
        ');

        $usage = $this->tag->delete();
    }

    /** @test */
    public function an_exception_is_thrown_when_attempting_to_return_delete_guest_entry_form_if_no_entry_id_is_provided()
    {
        $this->expectException(\Exception::class);

        Collection::make('guestbook')->save();

        Entry::make()
            ->collection('guestbook')
            ->id('hello')
            ->slug('hello')
            ->data(['title' => 'Hello World'])
            ->save();

        $this->tag->setParameters([
            'collection' => 'guestbook',
        ]);

        $this->tag->setContent('
            <h2>Delete Guestbook Entry</h2>

            <button type="submit">DELETE</button>
        ');

        $usage = $this->tag->delete();
    }

    /** @test */
    public function an_exception_is_thrown_when_attempting_to_return_delete_guest_entry_form_when_entry_id_does_not_exist()
    {
        $this->expectException(EntryNotFoundException::class);

        Collection::make('guestbook')->save();

        Entry::make()
            ->collection('guestbook')
            ->id('hello')
            ->slug('hello')
            ->data(['title' => 'Hello World'])
            ->save();

        $this->tag->setParameters([
            'collection' => 'guestbook',
            'id' => 'blhabahahahah',
        ]);

        $this->tag->setContent('
            <h2>Delete Guestbook Entry</h2>

            <button type="submit">DELETE</button>
        ');

        $usage = $this->tag->delete();
    }
}
