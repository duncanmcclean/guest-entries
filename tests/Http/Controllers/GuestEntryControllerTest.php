<?php

use DoubleThreeDigital\GuestEntries\Events\GuestEntryCreated;
use DoubleThreeDigital\GuestEntries\Events\GuestEntryDeleted;
use DoubleThreeDigital\GuestEntries\Events\GuestEntryUpdated;
use DoubleThreeDigital\GuestEntries\Tests\Fixtures\FirstCustomStoreRequest;
use DoubleThreeDigital\GuestEntries\Tests\Fixtures\FirstCustomUpdateRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Spatie\TestTime\TestTime;
use Statamic\Events\EntrySaved;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Stache\Stache;

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
});

it('can_store_entry_where_slug_is_generated_from_title', function () {
    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => 'comments',
            'title' => 'This is fantastic',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'comments');
    $this->assertSame($entry->get('title'), 'This is fantastic');
    $this->assertSame($entry->slug(), 'this-is-fantastic');
});

it('can_store_entry_with_custom_form_request', function () {
    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => 'comments',
            '_request' => FirstCustomStoreRequest::class,
            'title' => 'This is great',
            'slug' => 'this-is-great',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('description');
});

it('cant_store_entry_if_collection_has_not_been_whitelisted', function () {
    Collection::make('smth')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => 'smth',
            'title' => 'Whatever',
            'slug' => 'whatever',
        ])
        ->assertForbidden();

    $entry = Entry::all()->last();

    $this->assertNull($entry);
});

it('can_store_entry_and_user_is_redirected', function () {
    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => 'comments',
            '_redirect' => '/bobs-your-uncle',
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

it('can_store_entry_and_ensure_ignored_parameters_are_not_saved', function () {
    Collection::make('comments')->save();

        $this
            ->post(route('statamic.guest-entries.store'), [
                '_collection' => 'comments',
                '_redirect' => '/whatever',
                '_error_redirect' => '/whatever-else',
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

it('can_store_entry_and_ensure_updated_at_is_set', function () {
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
    $this->assertNotNull($entry->get('updated_at'));
});

it('can_store_entry_where_collection_is_date_ordered_and_ensure_date_is_saved', function () {
    TestTime::freeze('Y-m-d H:i', '2021-10-10 11:11');

    Collection::make('comments')->dated(true)->save();

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

    $this->assertStringContainsString('2021-10-10-1111.this-is-great.md', $entry->path());
});

it('can_store_entry_where_collection_is_not_date_ordered_and_ensure_date_is_saved', function () {
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
            '_collection' => 'comments',
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

it('can_store_entry_and_ensure_honeypot_works_if_value_is_empty', function () {
    Config::set('guest-entries.honeypot', 'postcode');

    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => 'comments',
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

it('can_store_entry_and_ensure_honeypot_works_if_value_is_not_empty', function () {
    Config::set('guest-entries.honeypot', 'postcode');

    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => 'comments',
            'title' => 'This is great',
            'slug' => 'this-is-great3',
            'postcode' => 'A12 34B',
        ])
        ->assertRedirect();

    $entry = Entry::all()->last();

    $this->assertNull($entry);
});

it('can_store_entry_and_ensure_entry_is_unpublished_by_default', function () {
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
    $this->assertFalse($entry->published());
});

it('can_store_entry_and_ensure_published_status_is_updated', function () {
    Collection::make('comments')->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => 'comments',
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

it('can_store_entry_and_ensure_events_and_dispatched', function () {
    Event::fake();

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

    Event::assertDispatchedTimes(GuestEntryCreated::class, 1);
});

it('can_store_entry_and_date_is_saved_as_part_of_file_name_if_dated_collection', function () {
    Collection::make('comments')->dated(true)->save();

    $this
        ->post(route('statamic.guest-entries.store'), [
            '_collection' => 'comments',
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

it('can_store_entry_and_ensure_file_can_be_uploaded', function () {
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
            '_collection' => 'comments',
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

it('can_store_entry_and_ensure_multiple_files_can_be_uploaded', function () {
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
                '_collection' => 'comments',
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

it('can_store_entry_and_ensure_date_is_in_same_format_defined_in_blueprint', function () {
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
            '_collection' => 'comments',
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

it('can_store_entry_and_ensure_created_in_correct_site_by_request_payload', function () {
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
            '_collection' => 'comments',
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

it('can_store_entry_and_ensure_created_in_correct_site_by_referer', function () {
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
    $this->assertSame($entry->locale(), 'two');
});

it('can_store_entry_and_ensure_created_in_correct_site_by_current_site_fallback', function () {
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
    $this->assertSame($entry->locale(), 'two');
});

it('can_store_entry_and_ensure_entry_is_only_saved_once', function () {
    Event::fake();

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

    Event::assertDispatchedTimes(EntrySaved::class, 1);
});

it('can_store_entry_with_replicator_field', function () {
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
            '_collection' => 'comments',
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

it('can_store_entry_with_replicator_field_and_an_assets_field_inside_the_replicator', function () {
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
            '_collection' => 'comments',
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

it('can_update_entry', function () {
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
});

it('can_update_entry_with_custom_form_request', function () {
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
            '_request' => FirstCustomUpdateRequest::class,
            'record_label' => 'Unknown',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('record_label');
});

it('cant_update_entry_if_collection_has_not_been_whitelisted', function () {
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
            '_collection' => 'hahahahaha',
            '_id' => 'hahahahaha-idee',
            'title' => 'Something',
        ])
        ->assertForbidden();

    $entry = Entry::find('hahahahaha-idee');

    $this->assertNotNull($entry);
    $this->assertSame($entry->collectionHandle(), 'hahahahaha');
    $this->assertSame($entry->get('title'), 'Smth'); // Has not changed
    $this->assertSame($entry->slug(), 'smth');
});

it('can_update_entry_and_user_is_redirected', function () {
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
            '_redirect' => '/good-good-night',
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

it('can_update_entry_and_ensure_required_parameters_are_not_saved', function () {
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
            '_redirect' => '/something',
            '_error_redirect' => '/something-else',
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

it('can_update_entry_and_ensure_updated_at_is_set', function () {
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

    $this->assertNotNull($entry->get('updated_at'));
    $this->assertNotSame($entry->get('updated_at'), 12345);
});

it('can_update_entry_and_ensure_published_status_is_updated', function () {
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
    $this->assertTrue($entry->published());
});

it('can_update_entry_and_ensure_events_are_dispatched', function () {
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

    Event::assertDispatchedTimes(GuestEntryUpdated::class, 1);
});

it('can_update_entry_and_date_is_saved_as_part_of_file_name_if_dated_collection', function () {
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
            '_collection' => 'albums',
            '_id' => 'allo-mate-idee',
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

it('can_update_entry_and_ensure_date_is_in_same_format_as_defined_in_blueprint', function () {
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
            '_collection' => 'albums',
            '_id' => 'allo-mate-idee',
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

it('can_update_entry_and_ensure_file_can_be_uploaded', function () {
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
            '_collection' => 'albums',
            '_id' => 'allo-mate-idee',
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

it('can_update_entry_and_ensure_multiple_files_can_be_uploaded', function () {
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
            '_collection' => 'albums',
            '_id' => 'allo-mate-idee',
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

it('can_update_entry_with_revisions_enabled', function () {
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
            '_collection' => 'albums',
            '_id' => 'allo-mate-idee',
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

it('can_update_entry_and_date_and_ensure_date_is_saved_normally_if_not_dated_collection', function () {
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
            '_collection' => 'albums',
            '_id' => 'allo-mate-idee',
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

it('can_update_entry_and_ensure_entry_is_only_saved_once', function () {
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

    Event::assertDispatchedTimes(EntrySaved::class, 2);
});

it('can_update_entry_with_replicator_field', function () {
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

    $this->assertIsArray($entry->get('things'));
    $this->assertCount(2, $entry->get('things'));
});

it('can_update_entry_with_replicator_field_and_an_assets_field_inside_the_replicator', function () {
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
            '_collection' => 'albums',
            '_id' => 'allo-mate-idee',
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

it('can_destroy_entry', function () {
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
});

it('cant_destroy_entry_if_collection_has_not_been_whitelisted', function () {
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
            '_collection' => 'blahblah',
            '_id' => 'arg',
        ])
        ->assertForbidden();

    $entry = Entry::find('arg');

    $this->assertNotNull($entry);
});

it('can_destroy_entry_if_collection_has_not_been_whitelisted_and_user_is_redirected', function () {
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
            '_redirect' => '/allo-mate',
        ])
        ->assertRedirect('/allo-mate');

    $entry = Entry::find('allo-mate-idee');

    $this->assertNull($entry);
});

it('can_destroy_entry_and_ensure_events_are_dispatched', function () {
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
            '_collection' => 'albums',
            '_id' => 'allo-mate-idee',
        ])
        ->assertRedirect();

    $entry = Entry::find('allo-mate-idee');

    $this->assertNull($entry);

    Event::assertDispatchedTimes(GuestEntryDeleted::class, 1);
});
