<?php

use DuncanMcClean\GuestEntries\Tags\GuestEntriesTag;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Statamic\Exceptions\CollectionNotFoundException;
use Statamic\Facades\Antlers;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Statamic;
use Statamic\Tags\Tags;

use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertStringContainsString;

$tag = null;

beforeEach(function () use (&$tag) {
    Gate::define('manage-guestbook', fn (?Authenticatable $user) => true);

    Config::set('guest-entries.forms', [
        'create_guestbook' => ['action' => 'create', 'collection' => 'guestbook', 'fields' => ['name', 'email', 'comment']],
        'update_guestbook' => ['action' => 'update', 'collection' => 'guestbook', 'fields' => ['name', 'email', 'comment'], 'authorize' => 'manage-guestbook'],
        'delete_guestbook' => ['action' => 'delete', 'collection' => 'guestbook', 'authorize' => 'manage-guestbook'],
    ]);

    /** @var Tags */
    $tag = resolve(GuestEntriesTag::class)
        ->setParser(Antlers::parser())
        ->setContext([]);
});

it('returns create guest entry form', function () use (&$tag) {
    Collection::make('guestbook')->save();

    $tag->setParameters([
        'form' => 'create_guestbook',
    ]);

    $tag->setContent('
        <h2>Create Guestbook Entry</h2>

        <input name="name">
        <input name="email">
        <textarea name="comment"></textarea>

        <button type="submit">Submit</button>
    ');

    $usage = $tag->create();

    assertStringContainsString('action="http://localhost/!/guest-entries/create_guestbook"', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);

    assertStringContainsString('<h2>Create Guestbook Entry</h2>', $usage);
    assertStringContainsString('<textarea name="comment"></textarea>', $usage);
});

it('throws an exception when attempting to retrurn create guest entry form if no form is provided', function () use (&$tag) {
    $tag->setParameters([
        'form' => null,
    ]);

    $tag->setContent('
        <h2>Create Llalalalal Entry</h2>

        <input name="name">
        <input name="email">
        <textarea name="comment"></textarea>

        <button type="submit">Submit</button>
    ');

    $usage = $tag->create();
})->throws(Exception::class, 'The `form` parameter is required');

it('throws an exception when attempting to return create guest entry form when the form hasnt been configured', function () use (&$tag) {
    $tag->setParameters([
        'form' => 'not_configured',
    ]);

    $tag->setContent('
        <h2>Create Llalalalal Entry</h2>

        <input name="name">
        <input name="email">
        <textarea name="comment"></textarea>

        <button type="submit">Submit</button>
    ');

    $usage = $tag->create();
})->throws(Exception::class, "The [not_configured] form hasn't been configured.");

it('throws an exception when attempting to return create guest entry form with a form for a different action', function () use (&$tag) {
    Collection::make('guestbook')->save();

    $tag->setParameters([
        'form' => 'update_guestbook',
    ]);

    $tag->create();
})->throws(Exception::class, "The [update_guestbook] form can't be used with the `create` tag.");

it('throws an exception when attempting to return create guest entry form with v5 parameters', function (string $parameter) use (&$tag) {
    Collection::make('guestbook')->save();

    $tag->setParameters([
        'form' => 'create_guestbook',
        $parameter => 'something',
    ]);

    $tag->create();
})->with(['collection', 'request'])->throws(Exception::class, 'have been moved to the form');

it('throws an exception when attempting to return create guest entry form when the forms collection does not exist', function () use (&$tag) {
    Config::set('guest-entries.forms.create_guestbook.collection', 'does_not_exist');

    $tag->setParameters([
        'form' => 'create_guestbook',
    ]);

    $tag->create();
})->throws(CollectionNotFoundException::class);

it('returns create guest entry form with redirect and error_redirect hidden inputs', function () use (&$tag) {
    Collection::make('guestbook')->save();

    $tag->setParameters([
        'form' => 'create_guestbook',
        'redirect' => '/thank-you',
        'error_redirect' => '/error',
    ]);

    $usage = $tag->create();

    assertStringContainsString('action="http://localhost/!/guest-entries/create_guestbook"', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);
    assertStringContainsString('<input type="hidden" name="_redirect" value="/thank-you"', $usage);
    assertStringContainsString('<input type="hidden" name="_error_redirect" value="/error"', $usage);
});

it('returns create guest entry form with escaped hidden inputs', function () use (&$tag) {
    Collection::make('guestbook')->save();

    $tag->setParameters([
        'form' => 'create_guestbook',
        'redirect' => '"><script>alert(1)</script>',
    ]);

    $usage = $tag->create();

    assertStringContainsString('<input type="hidden" name="_redirect" value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $usage);
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
        'form' => 'update_guestbook',
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

    assertStringContainsString('action="http://localhost/!/guest-entries/update_guestbook/hello"', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);
    assertStringContainsString('<input type="hidden" name="_method" value="PATCH"', $usage);

    assertStringContainsString('<h2>Update Guestbook Entry</h2>', $usage);
    assertStringContainsString('<textarea name="comment"></textarea>', $usage);
});

it('throws an exception when attempting to return update guest entry create form if no form is provided', function () use (&$tag) {
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
})->throws(Exception::class, 'The `form` parameter is required');

it('throws an exception when attempting to return update guest entry form if no ID is provided', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'form' => 'not_configured',
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
})->throws(Exception::class, "The [not_configured] form hasn't been configured.");

it('throws an exception when attempting to return update guest entry form if no entry ID is provided', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'form' => 'update_guestbook',
    ]);

    $tag->setContent('
        <h2>Update Guestbook Entry</h2>

        <input name="name">
        <input name="email">
        <textarea name="comment"></textarea>

        <button type="submit">Submit</button>
    ');

    $usage = $tag->update();
})->throws(Exception::class, 'The `id` parameter is required');

it('returns nothing when attempting to return update guest entry form when entry ID does not exist', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'form' => 'update_guestbook',
        'id' => 'blhabahahahah',
    ]);

    $tag->setContent('
        <h2>Update Guestbook Entry</h2>

        <input name="name">
        <input name="email">
        <textarea name="comment"></textarea>

        <button type="submit">Submit</button>
    ');

    assertNull($tag->update());
});

it('returns nothing when attempting to return update guest entry form when entry belongs to a different collection', function () use (&$tag) {
    Collection::make('guestbook')->save();
    Collection::make('pages')->save();

    Entry::make()
        ->collection('pages')
        ->id('home')
        ->data(['title' => 'Home'])
        ->save();

    $tag->setParameters([
        'form' => 'update_guestbook',
        'id' => 'home',
    ]);

    assertNull($tag->update());
});

it('returns nothing when the user isnt allowed to update the entry', function () use (&$tag) {
    Config::set('guest-entries.forms.update_guestbook.authorize', 'author');

    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->data(['title' => 'Hello World', 'comment' => 'Private draft', 'author' => 'someone-else'])
        ->save();

    $tag->setParameters([
        'form' => 'update_guestbook',
        'id' => 'hello',
    ]);

    $tag->setContent('<textarea name="comment">{{ comment }}</textarea>');

    assertNull($tag->update());
});

it('throws an exception when attempting to return update guest entry form when the form has no fields', function () use (&$tag) {
    Config::set('guest-entries.forms.update_guestbook.fields', null);

    Collection::make('guestbook')->save();

    Entry::make()->collection('guestbook')->id('hello')->data(['title' => 'Hello World'])->save();

    $tag->setParameters([
        'form' => 'update_guestbook',
        'id' => 'hello',
    ]);

    $tag->update();
})->throws(Exception::class, 'The [update_guestbook] form is missing `fields`.');

it('returns update guest entry form and entry values can be used', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World', 'comment' => 'Something can go here'])
        ->save();

    $tag->setParameters([
        'form' => 'update_guestbook',
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

    assertStringContainsString('action="http://localhost/!/guest-entries/update_guestbook/hello"', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);

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
        'form' => 'update_guestbook',
        'id' => 'hello',
        'redirect' => '/thank-you',
        'error_redirect' => '/error',
    ]);

    $usage = $tag->update();

    assertStringContainsString('action="http://localhost/!/guest-entries/update_guestbook/hello"', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);
    assertStringContainsString('<input type="hidden" name="_redirect" value="/thank-you"', $usage);
    assertStringContainsString('<input type="hidden" name="_error_redirect" value="/error"', $usage);
});

it('can fetch update guest entry form data', function () {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $form = Statamic::tag('guest-entries:update')->params([
        'form' => 'update_guestbook',
        'id' => 'hello',
    ])->fetch();

    assertStringContainsString('action="http://localhost/!/guest-entries/update_guestbook/hello"', $form['attrs_html']);
    assertStringContainsString('<input type="hidden" name="_token"', $form['params_html']);
    assertStringContainsString('Hello World', $form['title']);
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
        'form' => 'delete_guestbook',
        'id' => 'hello',
    ]);

    $tag->setContent('
        <h2>Delete Guestbook Entry</h2>

        <button type="submit">DELETE</button>
    ');

    $usage = $tag->delete();

    assertStringContainsString('action="http://localhost/!/guest-entries/delete_guestbook/hello"', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);

    assertStringContainsString('<h2>Delete Guestbook Entry</h2>', $usage);
    assertStringContainsString('<button type="submit">DELETE</button>', $usage);
});

it('throws an exception when attempting to return delete guest entry form if no form is provided', function () use (&$tag) {
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
})->throws(Exception::class, 'The `form` parameter is required');

it('throws an exception when attempting to return delete guest entry form when the form hasnt been configured', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'form' => 'not_configured',
        'id' => 'hello',
    ]);

    $tag->setContent('
        <h2>Delete Guestbook Entry</h2>

        <button type="submit">DELETE</button>
    ');

    $usage = $tag->delete();
})->throws(Exception::class, "The [not_configured] form hasn't been configured.");

it('throws an exception when attempting to return delete guest entry form if no entry ID is provided', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'form' => 'delete_guestbook',
    ]);

    $tag->setContent('
        <h2>Delete Guestbook Entry</h2>

        <button type="submit">DELETE</button>
    ');

    $usage = $tag->delete();
})->throws(Exception::class, 'The `id` parameter is required');

it('returns nothing when attempting to return delete guest entry form when entry ID does not exist', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'form' => 'delete_guestbook',
        'id' => 'blhabahahahah',
    ]);

    $tag->setContent('
        <h2>Delete Guestbook Entry</h2>

        <button type="submit">DELETE</button>
    ');

    assertNull($tag->delete());
});

it('returns nothing when the user isnt allowed to delete the entry', function () use (&$tag) {
    Config::set('guest-entries.forms.delete_guestbook.authorize', null);

    Collection::make('guestbook')->save();

    Entry::make()->collection('guestbook')->id('hello')->data(['title' => 'Hello World'])->save();

    $tag->setParameters([
        'form' => 'delete_guestbook',
        'id' => 'hello',
    ]);

    $tag->setContent('<p>Delete {{ title }}?</p>');

    assertNull($tag->delete());
});

it('returns delete guest entry form and entry values can be used', function () use (&$tag) {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $tag->setParameters([
        'form' => 'delete_guestbook',
        'id' => 'hello',
    ]);

    $tag->setContent('
        <h2>Delete Guestbook Entry: {{ title }}</h2>

        <button type="submit">DELETE</button>
    ');

    $usage = $tag->delete();

    assertStringContainsString('action="http://localhost/!/guest-entries/delete_guestbook/hello"', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);

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
        'form' => 'delete_guestbook',
        'id' => 'hello',
        'redirect' => '/thank-you',
        'error_redirect' => '/error',
    ]);

    $usage = $tag->delete();

    assertStringContainsString('action="http://localhost/!/guest-entries/delete_guestbook/hello"', $usage);
    assertStringContainsString('<input type="hidden" name="_token"', $usage);
    assertStringContainsString('<input type="hidden" name="_redirect" value="/thank-you"', $usage);
    assertStringContainsString('<input type="hidden" name="_error_redirect" value="/error"', $usage);
});

it('returns turnstile widget', function () use (&$tag) {
    Config::set('guest-entries.turnstile.site_key', 'turnstile-site-key');

    $tag->setParameters(['theme' => 'dark']);

    expect($tag->turnstile())->toBe(
        '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>'
        .'<div class="cf-turnstile" data-sitekey="turnstile-site-key" data-theme="dark"></div>'
    );
});

it('throws an exception when attempting to return turnstile widget without a site key', function () use (&$tag) {
    $tag->turnstile();
})->throws(Exception::class, 'Please set the `turnstile.site_key` config option');

it('escapes errors', function () use (&$tag) {
    session()->put('errors', (new ViewErrorBag)->put('default', new MessageBag([
        'blocks' => 'The selected blocks.<img src=x onerror=alert(1)>.type is invalid.',
    ])));

    expect($tag->errors())->toBe([
        ['value' => 'The selected blocks.&lt;img src=x onerror=alert(1)&gt;.type is invalid.'],
    ]);
});

it('can fetch delete guest entry form data', function () {
    Collection::make('guestbook')->save();

    Entry::make()
        ->collection('guestbook')
        ->id('hello')
        ->slug('hello')
        ->data(['title' => 'Hello World'])
        ->save();

    $form = Statamic::tag('guest-entries:delete')->params([
        'form' => 'delete_guestbook',
        'id' => 'hello',
    ])->fetch();

    assertStringContainsString('action="http://localhost/!/guest-entries/delete_guestbook/hello"', $form['attrs_html']);
    assertStringContainsString('<input type="hidden" name="_token"', $form['params_html']);
    assertStringContainsString('Hello World', $form['title']);
});
