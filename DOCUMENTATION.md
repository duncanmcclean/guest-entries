## Configuration

Guest Entries provides a configuration file that allows you to define the collections you wish for entries to be created/updated within. You will have published this configuration file during the installation process - it'll be located at `config/guest-entries.php`.

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Collections
    |--------------------------------------------------------------------------
    |
    | Configure which collections you'd like to be created/updated with
    | the 'Guest Entries' addon.
    |
    */

    'collections' => [
        'pages' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Honeypot
    |--------------------------------------------------------------------------
    |
    | If you'd like to enable the honeypot, specify the name of the input
    | you'd like to use.
    |
    */

    'honeypot' => false,

];
```

To enable a collection, create an entry in the array, where the key is the handle of the collection and where the value is `true`/`false`, depending on if you wish to enable/disable it.

You may also configure this addon's [Honeypot](#honeypot) feature. `false` will mean the feature is disabled. To enable, you may change this to a field name that will never be entered by a human (as it'll be hidden) but may be auto-filled by a robot.

## Tags

### Create Entry

```antlers
{{ guest-entries:create collection="articles" }}
    <h2>Create article</h2>

    <input type="text" name="title">
    <textarea name="content"></textarea>

    <button type="submit">Create</button>
{{ /guest-entries:create }}
```

### Update Entry

```antlers
{{ guest-entries:update collection="articles" id="article-id" }}
    <h2>Edit article</h2>

    <input type="text" name="title" value="{{ title }}">
    <textarea name="content">{{ content }}</textarea>

    <button type="submit">Update</button>
{{ /guest-entries:update }}
```

### Delete Entry

```antlers
{{ guest-entries:delete collection="articles" id="article-id" }}
    <h2>Delete article</h2>
    <p>Are you 100% sure you want to get rid of this article? It'll be gone forever. Which if you didn't know - is a very long time!</p>

    <button type="submit">DELETE</button>
{{ /guest-entries:delete }}
```

### Parameters

When using any of the `guest-entries` tags, there's a few parameters available to you:

**`collection` *required***

Every tag will require you to pass in the `collection` parameter, which should be the handle of the collection you want to deal with.

**`id` *sometimes required***

Both the `update` and `delete` tags require you to pass in the ID of the entry you want to work with.

**`redirect`**

You may specify a URL to redirect the user to once the Guest Entry form has been submitted.

**`error_redirect`**

You may specify a URL to redirect the user to once the Guest Entry form has been submitted unsuccessfully - commonly due to a validation error.

**`request`**

You may specify a Laravel Form Request to be used for validation of the form. You can pass in simply the name of the class or the FQNS (fully qualified namespace) - eg. `ArticleStoreRequest` vs `App\Http\Requests\ArticleStoreRequest`

### Variables

If you're using the update/delete forms provided by Guest Entries, you will be able to use any of your entries data, in case you wish to fill `value` attributes on the input fields.

```antlers
{{ guest-entries:update collection="articles" id="article-id" }}
    <h2>Edit article: {{ title }}</h2>
    <p>Last updated: {{ updated_at }}</p>

    ...
{{ /guest-entries:update }}
```

## Honeypot

Guest Entries includes a simple Honeypot feature to help reduce spam via your front-end forms. Documentation around configuring can be seen under '[Configuration](#configuration)'.

Once you've enabled the Honeypot, ensure to add the field to your forms, like so:

```antlers
{{ guest-entries:create collection="articles" }}
    <h2>Create article</h2>

    <input type="text" name="title">
    <textarea name="content"></textarea>

    <input type="hidden" name="zip_code" value=""> <!-- This is my honeypot -->

    <button type="submit">Create</button>
{{ /guest-entries:create }}
```

## Errors

If you'd like to show any errors after a user has submitted the Guest Entries form, you can use the `{{ guest-entries:errors }}` tag, like shown below:

```antlers
{{ guest-entries:errors }}
    <li>{{ value }}</li>
{{ /guest-entries:errors }}
```

## Events

If you need to trigger any custom code when a user does anything with Guest Entries, this addon provides some addons to let you do that.

### GuestEntryCreated

[`DoubleThreeDigital\GuestEntries\Events\GuestEntryCreated`](https://github.com/doublethreedigital/guest-entries/blob/main/src/Events/GuestEntryCreated.php)

This event is fired whenever an entry is created via `{{ guest-entries:create }}`.

```php
public function handle(GuestEntryCreated $event)
{
	$event->entry;
}
```

### GuestEntryUpdated

[`DoubleThreeDigital\GuestEntries\Events\GuestEntryUpdated`](https://github.com/doublethreedigital/guest-entries/blob/main/src/Events/GuestEntryUpdated.php)

This event is fired whenever an entry is updated via `{{ guest-entries:update }}`.

```php
public function handle(GuestEntryUpdated $event)
{
	$event->entry;
}
```

### GuestEntryDeleted

[`DoubleThreeDigital\GuestEntries\Events\GuestEntryDeleted`](https://github.com/doublethreedigital/guest-entries/blob/main/src/Events/GuestEntryDeleted.php)

This event is fired whenever an entry is updated via `{{ guest-entries:delete }}`.

```php
public function handle(GuestEntryDeleted $event)
{
	$event->entry;
}
```

## File Uploads

Sometimes you may want your users to be able to upload files to your entries. Guest Entries makes this easy for you.

First things first, ensure you have an assets field on the entry blueprint, let's use `attachment` as an example. Make sure the Assets field has an asset container specified.

Then in your Guest Entries form, add `files="true"` as a parameter to the form and create a file input.

```antlers
{{ guest-entries:create collection="articles" files="true" }}
    <h2>Create article</h2>

    <input type="text" name="title">
    <textarea name="content"></textarea>

    <input type="file" name="attachments[]">

    <button type="submit">Create</button>
{{ /guest-entries:create }}
```

If you need to upload multiple files, just use multiple inputs, like so:

```antlers
{{ guest-entries:create collection="articles" files="true" }}
    <!-- ... -->

    <input type="file" name="attachments[]">
    <input type="file" name="attachments[]">
    <input type="file" name="attachments[]">

    <!-- ... -->
{{ /guest-entries:create }}
```

When the form is submitted, the file will be uploaded to the specified asset container and will be linked to the entry.
