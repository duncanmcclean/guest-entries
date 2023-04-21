<?php

use DoubleThreeDigital\GuestEntries\Tags\GuestEntriesTag;
use Illuminate\Container\EntryNotFoundException;
use function PHPUnit\Framework\assertStringContainsString;
use Statamic\Exceptions\CollectionNotFoundException;
use Statamic\Facades\Antlers;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Tags\Tags;

$tag = null;

beforeEach(function () use (&$tag) {
    /** @var Tags */
    $tag = resolve(GuestEntriesTag::class)
        ->setParser(Antlers::parser())
        ->setContext([]);
});

it('returns create guest entry form', function () use (&$tag) {
    Collection::make('guestbook')->save();

    $tag->setParameters([
        'collection' => 'guestbook',
    ]);

    $tag->setContent('
        <h2>Create Guestbook Entry</h2>

        <input name="name">
        <input name="email">
        <textarea name="comment"></textarea>

        <button type="submit">Submit</button>
    ');

    $usage = $tag->create();

    assertStringContainsString('http://localhost/!/guest-entries/create', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);
    assertStringContainsString('<input type="hidden" name="_collection" value="guestbook"', $usage);

    assertStringContainsString('<h2>Create Guestbook Entry</h2>', $usage);
    assertStringContainsString('<textarea name="comment"></textarea>', $usage);
});

it('throws an exception when attempting to retrurn create guest entry form if no collection is provided', function () use (&$tag) {
    $tag->setParameters([
        'collection' => null,
    ]);

    $tag->setContent('
        <h2>Create Llalalalal Entry</h2>

        <input name="name">
        <input name="email">
        <textarea name="comment"></textarea>

        <button type="submit">Submit</button>
    ');

    $usage = $tag->create();
})->throws(\Exception::class);

it('throws an exception when attempting to return create guest entry form when collection does not exist', function () use (&$tag) {
    $tag->setParameters([
        'collection' => 'lalalalallalalal',
    ]);

    $tag->setContent('
        <h2>Create Llalalalal Entry</h2>

        <input name="name">
        <input name="email">
        <textarea name="comment"></textarea>

        <button type="submit">Submit</button>
    ');

    $usage = $tag->create();
})->throws(CollectionNotFoundException::class);

it('returns create guest entry form with redirect and error_redirect hidden inputs', function () use (&$tag) {
    Collection::make('guestbook')->save();

    $tag->setParameters([
        'collection' => 'guestbook',
        'redirect' => '/thank-you',
        'error_redirect' => '/error',
    ]);

    $usage = $tag->create();

    assertStringContainsString('http://localhost/!/guest-entries/create', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);
    assertStringContainsString('<input type="hidden" name="_collection" value="guestbook"', $usage);
    assertStringContainsString('<input type="hidden" name="_redirect" value="/thank-you"', $usage);
    assertStringContainsString('<input type="hidden" name="_error_redirect" value="/error"', $usage);
});

it('returns update guest entry form', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'collection' => 'guestbook',
        'id' => 'hello',
    ]);

    $tag->setContent('
        <h2>Update Guestbook Entry</h2>

        <input name="name">
        <input name="email">
        <textarea name="comment"></textarea>

        <button type="submit">Submit</button>
    ');

    $usage = $tag->update();

    assertStringContainsString('http://localhost/!/guest-entries/update', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);
    assertStringContainsString('<input type="hidden" name="_collection" value="guestbook"', $usage);
    assertStringContainsString('<input type="hidden" name="_id" value="hello"', $usage);

    assertStringContainsString('<h2>Update Guestbook Entry</h2>', $usage);
    assertStringContainsString('<textarea name="comment"></textarea>', $usage);
});

it('throws an exception when attempting to return update guest entry create form if no collection is provided', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'id' => 'hello',
    ]);

    $tag->setContent('
        <h2>Update Guestbook Entry</h2>

        <input name="name">
        <input name="email">
        <textarea name="comment"></textarea>

        <button type="submit">Submit</button>
    ');

    $usage = $tag->update();
})->throws(\Exception::class);

it('throws an exception when attempting to return update guest entry form if no ID is provided', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'collection' => 'blah',
        'id' => 'hello',
    ]);

    $tag->setContent('
        <h2>Update Guestbook Entry</h2>

        <input name="name">
        <input name="email">
        <textarea name="comment"></textarea>

        <button type="submit">Submit</button>
    ');

    $usage = $tag->update();
})->throws(CollectionNotFoundException::class);

it('throws an exception when attempting to return update guest entry form if no entry ID is provided', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'collection' => 'guestbook',
    ]);

    $tag->setContent('
        <h2>Update Guestbook Entry</h2>

        <input name="name">
        <input name="email">
        <textarea name="comment"></textarea>

        <button type="submit">Submit</button>
    ');

    $usage = $tag->update();
})->throws(\Exception::class);

it('throws an exception when attempting to return update guest entry form when entry ID does not exist', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'collection' => 'guestbook',
        'id' => 'blhabahahahah',
    ]);

    $tag->setContent('
        <h2>Update Guestbook Entry</h2>

        <input name="name">
        <input name="email">
        <textarea name="comment"></textarea>

        <button type="submit">Submit</button>
    ');

    $usage = $tag->update();
})->throws(EntryNotFoundException::class);

it('returns update guest entry form and entry values can be used', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World', 'comment' => 'Something can go here'])
        ->save();

    $tag->setParameters([
        'collection' => 'guestbook',
        'id' => 'hello',
    ]);

    $tag->setContent('
        <h2>Update Guestbook Entry: {{ title }}</h2>

        <input name="name">
        <input name="email">
        <textarea name="comment">Something can go here</textarea>

        <button type="submit">Submit</button>
    ');

    $usage = $tag->update();

    assertStringContainsString('http://localhost/!/guest-entries/update', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);
    assertStringContainsString('<input type="hidden" name="_collection" value="guestbook"', $usage);
    assertStringContainsString('<input type="hidden" name="_id" value="hello"', $usage);

    assertStringContainsString('<h2>Update Guestbook Entry: Hello World</h2>', $usage);
    assertStringContainsString('<textarea name="comment">Something can go here</textarea>', $usage);
});

it('returns update guest entry form with redirect and error_redirect hidden inputs', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'collection' => 'guestbook',
        'id' => 'hello',
        'redirect' => '/thank-you',
        'error_redirect' => '/error',
    ]);

    $usage = $tag->update();

    assertStringContainsString('http://localhost/!/guest-entries/update', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);
    assertStringContainsString('<input type="hidden" name="_collection" value="guestbook"', $usage);
    assertStringContainsString('<input type="hidden" name="_id" value="hello"', $usage);
    assertStringContainsString('<input type="hidden" name="_redirect" value="/thank-you"', $usage);
    assertStringContainsString('<input type="hidden" name="_error_redirect" value="/error"', $usage);
});

it('returns delete guest entry form', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'collection' => 'guestbook',
        'id' => 'hello',
    ]);

    $tag->setContent('
        <h2>Delete Guestbook Entry</h2>

        <button type="submit">DELETE</button>
    ');

    $usage = $tag->delete();

    assertStringContainsString('http://localhost/!/guest-entries/delete', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);
    assertStringContainsString('<input type="hidden" name="_collection" value="guestbook"', $usage);
    assertStringContainsString('<input type="hidden" name="_id" value="hello"', $usage);

    assertStringContainsString('<h2>Delete Guestbook Entry</h2>', $usage);
    assertStringContainsString('<button type="submit">DELETE</button>', $usage);
});

it('throws an exception when attempting to return delete guest entry form if no collection is provided', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'id' => 'hello',
    ]);

    $tag->setContent('
        <h2>Delete Guestbook Entry</h2>

        <button type="submit">DELETE</button>
    ');

    $usage = $tag->delete();
})->throws(\Exception::class);

it('throws an exception when attempting to return delete guest entry form when collection does not exist', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'collection' => 'blah',
        'id' => 'hello',
    ]);

    $tag->setContent('
        <h2>Delete Guestbook Entry</h2>

        <button type="submit">DELETE</button>
    ');

    $usage = $tag->delete();
})->throws(CollectionNotFoundException::class);

it('throws an exception when attempting to return delete guest entry form if no entry ID is provided', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'collection' => 'guestbook',
    ]);

    $tag->setContent('
        <h2>Delete Guestbook Entry</h2>

        <button type="submit">DELETE</button>
    ');

    $usage = $tag->delete();
})->throws(\Exception::class);

it('throws an exception when attempting to return delete guest entry form when entry ID does not exist', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'collection' => 'guestbook',
        'id' => 'blhabahahahah',
    ]);

    $tag->setContent('
        <h2>Delete Guestbook Entry</h2>

        <button type="submit">DELETE</button>
    ');

    $usage = $tag->delete();
})->throws(EntryNotFoundException::class);

it('returns delete guest entry form and entry values can be used', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'collection' => 'guestbook',
        'id' => 'hello',
    ]);

    $tag->setContent('
        <h2>Delete Guestbook Entry: {{ title }}</h2>

        <button type="submit">DELETE</button>
    ');

    $usage = $tag->delete();

    assertStringContainsString('http://localhost/!/guest-entries/delete', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);
    assertStringContainsString('<input type="hidden" name="_collection" value="guestbook"', $usage);
    assertStringContainsString('<input type="hidden" name="_id" value="hello"', $usage);

    assertStringContainsString('<h2>Delete Guestbook Entry: Hello World</h2>', $usage);
    assertStringContainsString('<button type="submit">DELETE</button>', $usage);
});

it('returns delete guest entry form with redirect and error_redirect hidden inputs', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'collection' => 'guestbook',
        'id' => 'hello',
        'redirect' => '/thank-you',
        'error_redirect' => '/error',
    ]);

    $usage = $tag->delete();

    assertStringContainsString('http://localhost/!/guest-entries/delete', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);
    assertStringContainsString('<input type="hidden" name="_collection" value="guestbook"', $usage);
    assertStringContainsString('<input type="hidden" name="_id" value="hello"', $usage);
    assertStringContainsString('<input type="hidden" name="_redirect" value="/thank-you"', $usage);
    assertStringContainsString('<input type="hidden" name="_error_redirect" value="/error"', $usage);
});
