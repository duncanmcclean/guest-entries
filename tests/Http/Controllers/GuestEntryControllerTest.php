<?php

use DuncanMcClean\GuestEntries\Events\GuestEntryCreated;
use DuncanMcClean\GuestEntries\Events\GuestEntryDeleted;
use DuncanMcClean\GuestEntries\Events\GuestEntryUpdated;
use DuncanMcClean\GuestEntries\Tests\Fixtures\FirstCustomStoreRequest;
use DuncanMcClean\GuestEntries\Tests\Fixtures\FirstCustomUpdateRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\TestTime\TestTime;
use Statamic\Events\EntrySaved;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

use function PHPUnit\Framework\assertCount;

beforeEach(function () {
    File::deleteDirectory(app('stache')->store('entries')->directory());

    $this->app['config']->set('guest-entries.collections', [
        'comments' => true,
        'albums' => true,
    ]);
});

it('can store entry', function () {
    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');
});

it('can store entry where slug is generated from title', function () {
    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is fantastic',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is fantastic');
    $this->assertSame($entry->slug(), 'this-is-fantastic');
});

it('can store entry when collection has title format', function () {
    Collection::make('comments')->titleFormats(['default' => 'BLAH {{ name }}'])->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'name' => 'So, I was sitting there and somebody came up to me and I asked them something.',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'BLAH So, I was sitting there and somebody came up to me and I asked them something.');
    $this->assertSame($entry->slug(), 'blah-so-i-was-sitting-there-and-somebody-came-up-to-me-and-i-asked-them-something');
});

it('can store entry with custom form request', function () {
    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            '_request' => encrypt(FirstCustomStoreRequest::class),
            'title' => 'This is great',
            'slug' => 'this-is-great',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('description');
});

it('cant store entry if collection has not been whitelisted', function () {
    Collection::make('smth')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('smth'),
            'title' => 'Whatever',
            'slug' => 'whatever',
        ])
        ->assertForbidden();

    $entry = Entry::all()->last();

    $this->assertNull($entry);
});

it('can store entry and user is redirected', function () {
    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            '_redirect' => encrypt('/bobs-your-uncle'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
        ])
        ->assertRedirect('/bobs-your-uncle');

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');
});

it('can store entry and ensure ignored parameters are not saved', function () {
    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            '_redirect' => encrypt('/whatever'),
            '_error_redirect' => encrypt('/whatever-else'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');

    $this->assertNull($entry->get('_collection'));
    $this->assertNull($entry->get('_redirect'));
    $this->assertNull($entry->get('_error_redirect'));
});

it('can store entry and ensure updated at is set', function () {
    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');
    $this->assertNotNull($entry->get('updated_at'));
});

it('can store entry where collection is date ordered and ensure date is saved', function () {
    TestTime::freeze('Y-m-d H:i', '2021-10-10 11:11');

    Collection::make('comments')->dated(true)->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');

    $this->assertStringContainsString('2021-10-10.this-is-great.md', $entry->path());
});

it('can store entry where collection is not date ordered and ensure date is saved', function () {
    TestTime::freeze('Y-m-d H:i', '2021-10-10 11:11');

    Blueprint::make('comments')
        ->setNamespace('collections.comments')
        ->setContents([
            'title' => 'Comments',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'date',
                            'field' => [
                                'mode' => 'single',
                                'time_enabled' => false,
                                'time_required' => false,
                                'earliest_date' => '1900-01-01',
                                'format' => 'Y-m-d',
                                'full_width' => false,
                                'inline' => false,
                                'columns' => 1,
                                'rows' => 1,
                                'display' => 'Date',
                                'type' => 'date',
                                'icon' => 'date',
                                'listable' => 'hidden',
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

    Collection::make('comments')->dated(false)->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
            'date' => '2021-12-25',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->get('date'), '2021-12-25');
    $this->assertSame($entry->slug(), 'this-is-great');

    $this->assertStringContainsString('this-is-great.md', $entry->path());
});

it('can store entry and ensure honeypot works if value is empty', function () {
    Config::set('guest-entries.honeypot', 'postcode');

    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great2',
            'postcode' => '',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great2');
});

it('can store entry and ensure honeypot works if value is not empty', function () {
    Config::set('guest-entries.honeypot', 'postcode');

    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great3',
            'postcode' => 'A12 34B',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNull($entry);
});

it('can store entry and ensure entry is unpublished by default', function () {
    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');
    $this->assertFalse($entry->published());
});

it('can store entry and ensure published status is updated', function () {
    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
            'published' => '1',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');
    $this->assertTrue($entry->published());
});

it('can store entry and ensure events and dispatched', function () {
    Event::fake();

    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');

    Event::assertDispatchedTimes(GuestEntryCreated::class, 1);
});

it('can store entry and date is saved as part of file name if dated collection', function () {
    Collection::make('comments')->dated(true)->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
            'date' => '2021-06-06',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');

    $this->assertStringContainsString('2021-06-06.this-is-great.md', $entry->path());
});

it('can store entry and ensure file can be uploaded', function () {
    AssetContainer::make('assets')->disk('local')->save();

    Blueprint::make('comments')
        ->setNamespace('collections.comments')
        ->setContents([
            'title' => 'Comments',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'attachment',
                            'field' => [
                                'mode' => 'list',
                                'container' => 'assets',
                                'restrict' => false,
                                'allow_uploads' => true,
                                'show_filename' => true,
                                'display' => 'Attachment',
                                'type' => 'assets',
                                'icon' => 'assets',
                                'listable' => 'hidden',
                                'max_items' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
            'attachment' => UploadedFile::fake()->create('foobar.png'),
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');

    $this->assertNotNull($entry->get('attachment'));
    $this->assertIsString($entry->get('attachment'));
});

it('can store entry and ensure uploaded SVG file is sanitized', function () {
    AssetContainer::make('assets')->disk('local')->save();

    Blueprint::make('comments')
        ->setNamespace('collections.comments')
        ->setContents([
            'title' => 'Comments',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'attachment',
                            'field' => [
                                'mode' => 'list',
                                'container' => 'assets',
                                'restrict' => false,
                                'allow_uploads' => true,
                                'show_filename' => true,
                                'display' => 'Attachment',
                                'type' => 'assets',
                                'icon' => 'assets',
                                'listable' => 'hidden',
                                'max_items' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
            'attachment' => UploadedFile::fake()->createWithContent('foobar.svg', '<?xml version="1.0" encoding="UTF-8" standalone="no"?><svg xmlns="http://www.w3.org/2000/svg" width="500" height="500"><script type="text/javascript">alert(`Bad stuff could go in here.`);</script></svg>'),
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');

    $this->assertNotNull($entry->get('attachment'));
    $this->assertIsString($entry->get('attachment'));

    $file = Storage::disk('local')->get($entry->get('attachment'));

    $this->assertStringNotContainsString('<script', $file);
    $this->assertStringNotContainsString('Bad stuff could go in here.', $file);
    $this->assertStringNotContainsString('</script>', $file);
});

it('cant store an entry when uploading a PHP file', function () {
    AssetContainer::make('assets')->disk('local')->save();

    Blueprint::make('comments')
        ->setNamespace('collections.comments')
        ->setContents([
            'title' => 'Comments',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'attachment',
                            'field' => [
                                'mode' => 'list',
                                'container' => 'assets',
                                'restrict' => false,
                                'allow_uploads' => true,
                                'show_filename' => true,
                                'display' => 'Attachment',
                                'type' => 'assets',
                                'icon' => 'assets',
                                'listable' => 'hidden',
                                'max_items' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
            'attachment' => UploadedFile::fake()->image('foobar.php'),
        ])
        ->assertSessionHasErrors('attachment');

    assertCount(0, Entry::all());

    Storage::disk('local')->assertMissing('assets/foobar.php');
});

it('can store entry and ensure multiple files can be uploaded', function () {
    AssetContainer::make('assets')->disk('local')->save();

    Blueprint::make('comments')
        ->setNamespace('collections.comments')
        ->setContents([
            'title' => 'Comments',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'attachments',
                            'field' => [
                                'mode' => 'list',
                                'container' => 'assets',
                                'restrict' => false,
                                'allow_uploads' => true,
                                'show_filename' => true,
                                'display' => 'Attachment',
                                'type' => 'assets',
                                'icon' => 'assets',
                                'listable' => 'hidden',
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

    Collection::make('comments')->save();

    $this->withoutExceptionHandling();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
            'attachments' => [
                UploadedFile::fake()->create('foobar.png'),
                UploadedFile::fake()->create('barfoo.jpg'),
            ],
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');

    $this->assertNotNull($entry->get('attachments'));
    $this->assertIsArray($entry->get('attachments'));
    $this->assertSame(count($entry->get('attachments')), 2);
});

it('can store entry with one uploaded file and one existing file', function () {
    AssetContainer::make('assets')->disk('local')->save();

    Asset::make()->container('assets')->path('blah-blah-blah.png')->save();

    Blueprint::make('comments')
        ->setNamespace('collections.comments')
        ->setContents([
            'title' => 'Comments',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'attachments',
                            'field' => [
                                'mode' => 'list',
                                'container' => 'assets',
                                'restrict' => false,
                                'allow_uploads' => true,
                                'show_filename' => true,
                                'display' => 'Attachment',
                                'type' => 'assets',
                                'icon' => 'assets',
                                'listable' => 'hidden',
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

    Collection::make('comments')->save();

    $this->withoutExceptionHandling();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
            'attachments' => [
                UploadedFile::fake()->create('foobar.png'),
                'blah-blah-blah.png',
            ],
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');

    $this->assertNotNull($entry->get('attachments'));
    $this->assertIsArray($entry->get('attachments'));
    $this->assertSame(count($entry->get('attachments')), 2);

    $this->assertStringContainsString('-foobar.png', $entry->get('attachments')[0]);
    $this->assertStringContainsString('blah-blah-blah.png', $entry->get('attachments')[1]);
});

it('can store entry and ensure date is in same format defined in blueprint', function () {
    Blueprint::make('comments')
        ->setNamespace('collections.comments')
        ->setContents([
            'title' => 'Comments',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'date',
                            'field' => [
                                'mode' => 'single',
                                'time_enabled' => false,
                                'time_required' => false,
                                'earliest_date' => '1900-01-01',
                                'format' => 'Y',
                                'full_width' => false,
                                'inline' => false,
                                'columns' => 1,
                                'rows' => 1,
                                'display' => 'Date',
                                'type' => 'date',
                                'icon' => 'date',
                                'listable' => 'hidden',
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

    Collection::make('comments')->dated(false)->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
            'date' => '2009-06-06',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->get('date'), '2009');
    $this->assertSame($entry->slug(), 'this-is-great');

    $this->assertStringContainsString('this-is-great.md', $entry->path());
});

it('can store entry and ensure created in correct site by request payload', function () {
    Config::set('statamic.editions.pro', true);

    Config::set('statamic.sites.sites', [
        'one' => [
            'name' => config('app.name'),
            'locale' => 'en_US',
            'url' => '/one',
        ],
        'two' => [
            'name' => config('app.name'),
            'locale' => 'en_US',
            'url' => '/two',
        ],
    ]);

    Site::setConfig(config('statamic.sites'));

    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
            'site' => 'one',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');
    $this->assertSame($entry->locale(), 'one');
});

it('can store entry and ensure created in correct site by referer', function () {
    Config::set('statamic.editions.pro', true);

    Config::set('statamic.sites.sites', [
        'one' => [
            'name' => config('app.name'),
            'locale' => 'en_US',
            'url' => '/one',
        ],
        'two' => [
            'name' => config('app.name'),
            'locale' => 'en_US',
            'url' => '/two',
        ],
    ]);

    Site::setConfig(config('statamic.sites'));

    Collection::make('comments')->save();

    $this
        ->from('/two/something')
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');
    $this->assertSame($entry->locale(), 'two');
});

it('can store entry and ensure created in correct site by current site fallback', function () {
    Config::set('statamic.editions.pro', true);

    Config::set('statamic.sites.sites', [
        'one' => [
            'name' => config('app.name'),
            'locale' => 'en_US',
            'url' => '/one',
        ],
        'two' => [
            'name' => config('app.name'),
            'locale' => 'en_US',
            'url' => '/two',
        ],
    ]);

    Site::setConfig(config('statamic.sites'));
    Site::setCurrent('two');

    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');
    $this->assertSame($entry->locale(), 'two');
});

it('can store entry and ensure entry is only saved once', function () {
    Event::fake();

    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');

    Event::assertDispatchedTimes(EntrySaved::class, 1);
});

it('can store entry with replicator field', function () {
    Blueprint::make('comments')
        ->setNamespace('collections.comments')
        ->setContents([
            'title' => 'Comments',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'things',
                            'field' => [
                                'sets' => [
                                    'thing' => [
                                        'display' => 'Thing',
                                        'fields' => [
                                            [
                                                'handle' => 'link',
                                                'field' => [
                                                    'type' => 'text',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'type' => 'replicator',
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
            'things' => [
                [
                    'text' => 'Woop die whoop!',
                ],
                [
                    'text' => 'I have a Blue Peter badge!',
                ],
            ],
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');

    $this->assertIsArray($entry->get('things'));
    $this->assertCount(2, $entry->get('things'));
});

it('can store entry with replicator field and an assets field inside the replicator', function () {
    AssetContainer::make('assets')->disk('local')->save();

    Blueprint::make('comments')
        ->setNamespace('collections.comments')
        ->setContents([
            'title' => 'Comments',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'things',
                            'field' => [
                                'sets' => [
                                    'thing' => [
                                        'display' => 'Thing',
                                        'fields' => [
                                            [
                                                'handle' => 'link',
                                                'field' => [
                                                    'type' => 'text',
                                                ],
                                            ],
                                            [
                                                'handle' => 'document',
                                                'field' => [
                                                    'mode' => 'list',
                                                    'container' => 'assets',
                                                    'restrict' => false,
                                                    'allow_uploads' => true,
                                                    'show_filename' => true,
                                                    'display' => 'Document',
                                                    'type' => 'assets',
                                                    'icon' => 'assets',
                                                    'listable' => 'hidden',
                                                    'max_items' => 1,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'type' => 'replicator',
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

    Collection::make('comments')->save();

    $this->withoutExceptionHandling();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => encrypt('comments'),
            'title' => 'This is great',
            'slug' => 'this-is-great',
            'things' => [
                [
                    'text' => 'Woop die whoop!',
                ],
                [
                    'document' => UploadedFile::fake()->create('document.pdf', 100),
                ],
            ],
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is great');
    $this->assertSame($entry->slug(), 'this-is-great');

    $this->assertIsArray($entry->get('things'));
    $this->assertCount(2, $entry->get('things'));

    $this->assertIsString($entry->get('things')[0]['text']);
    $this->assertIsString($entry->get('things')[1]['document']);
});

it('can update entry', function () {
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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->slug(), 'allo-mate');
});

it('can update entry if collection has title format', function () {
    Collection::make('albums')->titleFormats(['default' => '{{ artist }} - {{ name }}'])->save();

    Entry::make()
        ->id('allo-mate-idee')
        ->collection('albums')
        ->slug('allo-mate')
        ->data([
            'title' => 'Guvna B - Allo Mate!',
            'name' => 'Allo Mate!',
            'artist' => 'Guvna B',
        ])
        ->save();

    $this
        ->post(route('statamic.guest-entries.update'), [
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
            'name' => 'Allo Mate',
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Guvna B - Allo Mate');
});

it('can update entry with custom form request', function () {
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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            '_request' => encrypt(FirstCustomUpdateRequest::class),
            'record_label' => 'Unknown',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('record_label');
});

it('cant update entry if collection has not been whitelisted', function () {
    Collection::make('hahahahaha')->save();

    Entry::make()
        ->id('hahahahaha-idee')
        ->collection('hahahahaha')
        ->slug('smth')
        ->data([
            'title' => 'Smth',
        ])
        ->save();

    $this
        ->post(route('statamic.guest-entries.update'), [
            '_collection' => encrypt('hahahahaha'),
            '_id' => encrypt('hahahahaha-idee'),
            'title' => 'Something',
        ])
        ->assertForbidden();

    $entry = Entry::find('hahahahaha-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'hahahahaha');
    $this->assertSame($entry->get('title'), 'Smth'); // Has not changed
    $this->assertSame($entry->slug(), 'smth');
});

it('can update entry and user is redirected', function () {
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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            '_redirect' => encrypt('/good-good-night'),
            'record_label' => 'Unknown',
        ])
        ->assertRedirect('/good-good-night');

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->slug(), 'allo-mate');
});

it('can update entry and ensure required parameters are notsaved', function () {
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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            '_redirect' => encrypt('/something'),
            '_error_redirect' => encrypt('/something-else'),
            'record_label' => 'Unknown',
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->slug(), 'allo-mate');

    $this->assertNull($entry->get('_collection'));
    $this->assertNull($entry->get('_id'));
    $this->assertNull($entry->get('_redirect'));
    $this->assertNull($entry->get('_error_redirect'));
});

it('can update entry and ensure updated at is set', function () {
    Collection::make('albums')->save();

    Entry::make()
        ->id('allo-mate-idee')
        ->collection('albums')
        ->slug('allo-mate')
        ->data([
            'title' => 'Allo Mate!',
            'artist' => 'Guvna B',
            'updated_at' => 12345,
        ])
        ->save();

    $this
        ->post(route('statamic.guest-entries.update'), [
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->slug(), 'allo-mate');

    $this->assertNotNull($entry->get('updated_at'));
    $this->assertNotSame($entry->get('updated_at'), 12345);
});

it('can update entry and ensure published status is updated', function () {
    Collection::make('albums')->save();

    Entry::make()
        ->id('allo-mate-idee')
        ->collection('albums')
        ->slug('allo-mate')
        ->data([
            'title' => 'Allo Mate!',
            'artist' => 'Guvna B',
            'published' => '1',
        ])
        ->save();

    $this
        ->post(route('statamic.guest-entries.update'), [
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->slug(), 'allo-mate');
    $this->assertTrue($entry->published());
});

it('can update entry and ensure events are dispatched', function () {
    Event::fake();

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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->slug(), 'allo-mate');

    Event::assertDispatchedTimes(GuestEntryUpdated::class, 1);
});

it('can update entry and date is saved as part of file name if dated collection', function () {
    Collection::make('albums')->dated(true)->save();

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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
            'date' => '2021-09-09',
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->slug(), 'allo-mate');

    $this->assertStringContainsString('2021-09-09.allo-mate.md', $entry->path());
});

it('can update entry and ensure date is in same format as defined in blueprint', function () {
    Blueprint::make('albums')
        ->setNamespace('collections.albums')
        ->setContents([
            'title' => 'Albums',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'date',
                            'field' => [
                                'mode' => 'single',
                                'time_enabled' => false,
                                'time_required' => false,
                                'earliest_date' => '1900-01-01',
                                'format' => 'Y',
                                'full_width' => false,
                                'inline' => false,
                                'columns' => 1,
                                'rows' => 1,
                                'display' => 'Date',
                                'type' => 'date',
                                'icon' => 'date',
                                'listable' => 'hidden',
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

    Collection::make('albums')->dated(false)->save();

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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
            'date' => '2021-09-09',
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->get('date'), '2021');
    $this->assertSame($entry->slug(), 'allo-mate');

    $this->assertStringContainsString('allo-mate.md', $entry->path());
});

it('can update entry and ensure file can be uploaded', function () {
    AssetContainer::make('assets')->disk('local')->save();

    Blueprint::make('albums')
        ->setNamespace('collections.albums')
        ->setContents([
            'title' => 'Albums',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'artist',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'record_label',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'attachment',
                            'field' => [
                                'mode' => 'list',
                                'container' => 'assets',
                                'restrict' => false,
                                'allow_uploads' => true,
                                'show_filename' => true,
                                'display' => 'Attachment',
                                'type' => 'assets',
                                'icon' => 'assets',
                                'listable' => 'hidden',
                                'max_items' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
            'attachment' => UploadedFile::fake()->image('something.jpg'),
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->slug(), 'allo-mate');

    $this->assertNotNull($entry->get('attachment'));
    $this->assertIsString($entry->get('attachment'));
});

it('can update entry and ensure uploaded SVG file is sanitized', function () {
    AssetContainer::make('assets')->disk('local')->save();

    Blueprint::make('albums')
        ->setNamespace('collections.albums')
        ->setContents([
            'title' => 'Albums',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'artist',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'record_label',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'attachment',
                            'field' => [
                                'mode' => 'list',
                                'container' => 'assets',
                                'restrict' => false,
                                'allow_uploads' => true,
                                'show_filename' => true,
                                'display' => 'Attachment',
                                'type' => 'assets',
                                'icon' => 'assets',
                                'listable' => 'hidden',
                                'max_items' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
            'attachment' => UploadedFile::fake()->createWithContent('foobar.svg', '<?xml version="1.0" encoding="UTF-8" standalone="no"?><svg xmlns="http://www.w3.org/2000/svg" width="500" height="500"><script type="text/javascript">alert(`Bad stuff could go in here.`);</script></svg>'),
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->slug(), 'allo-mate');

    $this->assertNotNull($entry->get('attachment'));
    $this->assertIsString($entry->get('attachment'));

    $file = Storage::disk('local')->get($entry->get('attachment'));

    $this->assertStringNotContainsString('<script', $file);
    $this->assertStringNotContainsString('Bad stuff could go in here.', $file);
    $this->assertStringNotContainsString('</script>', $file);
});

it('cant update entry when uploading a PHP file', function () {
    AssetContainer::make('assets')->disk('local')->save();

    Blueprint::make('albums')
        ->setNamespace('collections.albums')
        ->setContents([
            'title' => 'Albums',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'artist',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'record_label',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'attachment',
                            'field' => [
                                'mode' => 'list',
                                'container' => 'assets',
                                'restrict' => false,
                                'allow_uploads' => true,
                                'show_filename' => true,
                                'display' => 'Attachment',
                                'type' => 'assets',
                                'icon' => 'assets',
                                'listable' => 'hidden',
                                'max_items' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'attachment' => UploadedFile::fake()->image('something.php'),
        ])
        ->assertSessionHasErrors('attachment');

    $entry = Entry::find('allo-mate-idee');

    $this->assertNull($entry->get('attachment'));

    Storage::disk('local')->assertMissing('something.php');
});

it('can update entry and ensure multiple files can be uploaded', function () {
    AssetContainer::make('assets')->disk('local')->save();

    Blueprint::make('albums')
        ->setNamespace('collections.albums')
        ->setContents([
            'title' => 'Albums',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'artist',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'record_label',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'attachments',
                            'field' => [
                                'mode' => 'list',
                                'container' => 'assets',
                                'restrict' => false,
                                'allow_uploads' => true,
                                'show_filename' => true,
                                'display' => 'Attachment',
                                'type' => 'assets',
                                'icon' => 'assets',
                                'listable' => 'hidden',
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
            'attachments' => [
                UploadedFile::fake()->create('foobar.png'),
                UploadedFile::fake()->create('barfoo.jpg'),
            ],
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->slug(), 'allo-mate');

    $this->assertNotNull($entry->get('attachments'));
    $this->assertIsArray($entry->get('attachments'));
    $this->assertSame(count($entry->get('attachments')), 2);
});

it('can update entry with one uploaded file and one existing file', function () {
    AssetContainer::make('assets')->disk('local')->save();

    Asset::make()->container('assets')->path('blah-blah-blah.png')->save();

    Blueprint::make('albums')
        ->setNamespace('collections.albums')
        ->setContents([
            'title' => 'Albums',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'artist',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'record_label',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'attachments',
                            'field' => [
                                'mode' => 'list',
                                'container' => 'assets',
                                'restrict' => false,
                                'allow_uploads' => true,
                                'show_filename' => true,
                                'display' => 'Attachment',
                                'type' => 'assets',
                                'icon' => 'assets',
                                'listable' => 'hidden',
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
            'attachments' => [
                UploadedFile::fake()->create('foobar.png'),
                'blah-blah-blah.png',
            ],
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->slug(), 'allo-mate');

    $this->assertNotNull($entry->get('attachments'));
    $this->assertIsArray($entry->get('attachments'));
    $this->assertSame(count($entry->get('attachments')), 2);

    $this->assertStringContainsString('-foobar.png', $entry->get('attachments')[0]);
    $this->assertStringContainsString('blah-blah-blah.png', $entry->get('attachments')[1]);
});

it('can update entry with revisions enabled', function () {
    Config::set('statamic.editions.pro', true);
    Config::set('statamic.revisions.enabled', true);

    Collection::make('albums')->revisionsEnabled(true)->save();

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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');
    $workingCopy = $entry->workingCopy();

    $this->assertNotNull($entry);
    $this->assertTrue($entry->revisionsEnabled());
    $this->assertTrue($entry->hasWorkingCopy());
    $this->assertSame($entry->collectionHandle(), 'albums');

    $this->assertSame($workingCopy->message(), 'Guest Entry Updated');
    $this->assertSame($workingCopy->action(), 'revision');
    $this->assertSame($workingCopy->attributes(), [
        'title' => 'Allo Mate!',
        'slug' => 'allo-mate',
        'published' => true,
        'data' => [
            'title' => 'Allo Mate!',
            'artist' => 'Guvna B',
            'record_label' => 'Unknown',
        ],
    ]);
});

it('can update entry and date and ensure date is saved normally if not dated collection', function () {
    Collection::make('albums')->dated(false)->save();

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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
            'date' => '2021-09-09',
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->get('date'), '2021-09-09');
    $this->assertSame($entry->slug(), 'allo-mate');

    $this->assertStringContainsString('allo-mate.md', $entry->path());
});

it('can update entry and ensure entry is only saved once', function () {
    Event::fake();

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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->slug(), 'allo-mate');

    Event::assertDispatchedTimes(EntrySaved::class, 2);
});

it('can update entry with replicator field', function () {
    Blueprint::make('albums')
        ->setNamespace('collections.albums')
        ->setContents([
            'title' => 'Albums',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'artist',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'record_label',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'things',
                            'field' => [
                                'sets' => [
                                    'thing' => [
                                        'display' => 'Thing',
                                        'fields' => [
                                            [
                                                'handle' => 'link',
                                                'field' => [
                                                    'type' => 'text',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'type' => 'replicator',
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

    Collection::make('albums')->save();

    Entry::make()
        ->id('allo-mate-idee')
        ->collection('albums')
        ->slug('allo-mate')
        ->data([
            'title' => 'Allo Mate!',
            'artist' => 'Guvna B',
            'things' => [
                [
                    'text' => 'Woop die whoop!',
                ],
                [
                    'text' => 'I have a Blue Peter badge!',
                ],
            ],
        ])
        ->save();

    $this
        ->post(route('statamic.guest-entries.update'), [
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->slug(), 'allo-mate');

    $this->assertIsArray($entry->get('things'));
    $this->assertCount(2, $entry->get('things'));
});

it('can update entry with replicator field and an assets field inside the replicator', function () {
    Blueprint::make('albums')
        ->setNamespace('collections.albums')
        ->setContents([
            'title' => 'Albums',
            'sections' => [
                'main' => [
                    'display' => 'main',
                    'fields' => [
                        [
                            'handle' => 'title',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'artist',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'slug',
                            'field' => [
                                'type' => 'slug',
                            ],
                        ],
                        [
                            'handle' => 'record_label',
                            'field' => [
                                'type' => 'text',
                            ],
                        ],
                        [
                            'handle' => 'things',
                            'field' => [
                                'sets' => [
                                    'thing' => [
                                        'display' => 'Thing',
                                        'fields' => [
                                            [
                                                'handle' => 'link',
                                                'field' => [
                                                    'type' => 'text',
                                                ],
                                            ],
                                            [
                                                'handle' => 'document',
                                                'field' => [
                                                    'mode' => 'list',
                                                    'container' => 'assets',
                                                    'restrict' => false,
                                                    'allow_uploads' => true,
                                                    'show_filename' => true,
                                                    'display' => 'Document',
                                                    'type' => 'assets',
                                                    'icon' => 'assets',
                                                    'listable' => 'hidden',
                                                    'max_items' => 1,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'type' => 'replicator',
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->save();

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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            'record_label' => 'Unknown',
            'things' => [
                [
                    'text' => 'Woop die whoop!',
                ],
                [
                    'document' => UploadedFile::fake()->create('document.pdf', 100),
                ],
            ],
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'albums');
    $this->assertSame($entry->get('title'), 'Allo Mate!');
    $this->assertSame($entry->get('record_label'), 'Unknown');
    $this->assertSame($entry->slug(), 'allo-mate');

    $this->assertIsArray($entry->get('things'));
    $this->assertCount(2, $entry->get('things'));

    $this->assertIsString($entry->get('things')[0]['text']);
    $this->assertIsString($entry->get('things')[1]['document']);
});

it('can destroy entry', function () {
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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNull($entry);
});

it('cant destroy entry if collection has not been whitelisted', function () {
    Collection::make('blahblah')->save();

    Entry::make()
        ->id('arg')
        ->collection('blahblah')
        ->slug('arg')
        ->data([
            'title' => 'Arrrrgg!',
        ])
        ->save();

    $this
        ->delete(route('statamic.guest-entries.destroy'), [
            '_collection' => encrypt('blahblah'),
            '_id' => encrypt('arg'),
        ])
        ->assertForbidden();

    $entry = Entry::find('arg');

    $this->assertNotNull($entry);
});

it('can destroy entry if collection has not been whitelisted and user is redirected', function () {
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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
            '_redirect' => encrypt('/allo-mate'),
        ])
        ->assertRedirect('/allo-mate');

    $entry = Entry::find('allo-mate-idee');

    $this->assertNull($entry);
});

it('can destroy entry and ensure events are dispatched', function () {
    Event::fake();

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
            '_collection' => encrypt('albums'),
            '_id' => encrypt('allo-mate-idee'),
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNull($entry);

    Event::assertDispatchedTimes(GuestEntryDeleted::class, 1);
});
