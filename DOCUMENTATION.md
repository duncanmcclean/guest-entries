## Installation

1. Require Guest Entries as a Composer dependency:

    ```
    composer require duncanmcclean/guest-entries
    ```

2. Publish the addon's configuration file:

    ```
    php artisan vendor:publish --tag="guest-entries-config"
    ```

3. That's it installed! You can now [configure your forms](#forms) and [add them to your templates](#tags).

## Configuration

Guest Entries provides a configuration file that allows you to define the forms your users can submit. You will have published this configuration file during the installation process - it'll be located at `config/guest-entries.php`.

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Forms
    |--------------------------------------------------------------------------
    |
    | Configure the forms you'd like to use with the 'Guest Entries' addon.
    | Each form is referenced in your templates by its handle, like so:
    | {{ guest-entries:create form="submit_article" }}
    |
    */

    'forms' => [
        'submit_article' => [
            'action' => 'create',
            'collection' => 'articles',
            'fields' => ['title', 'content'],
        ],
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

    /*
    |--------------------------------------------------------------------------
    | Turnstile
    |--------------------------------------------------------------------------
    |
    | To protect your forms with Cloudflare Turnstile, add your site and
    | secret keys here, then enable `turnstile` on the forms you'd like
    | to protect.
    |
    */

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

];
```

### Forms

Every Guest Entries form needs to be configured here, where the key is the form's handle. You'll pass the handle to the `form` parameter on the [tags](#tags).

Since a form's settings live on the server, rather than in hidden inputs, they can't be tampered with by whoever is submitting the form.

```php
'forms' => [
    'submit_article' => [
        'action' => 'create',
        'collection' => 'articles',
        'blueprint' => 'article',
        'published' => false,
        'auth' => true,
        'request' => \App\Http\Requests\SubmitArticleRequest::class,
        'turnstile' => true,
        'fields' => ['title', 'content'],
    ],

    'edit_article' => [
        'action' => 'update',
        'collection' => 'articles',
        'auth' => true,
        'authorize' => 'author',
        'fields' => ['title', 'content'],
    ],

    'delete_article' => [
        'action' => 'delete',
        'collection' => 'articles',
        'authorize' => 'delete-article',
    ],
],
```

**`action` _required_**

What the form does: `create`, `update` or `delete`. A form can only be used with its matching tag.

**`collection` _required_**

The handle of the collection the form creates, updates or deletes entries in.

**`blueprint`**

The handle of the blueprint new entries should use. When not provided, the collection's default blueprint is used.

**`published`**

Whether entries should be published. When creating entries, this defaults to `false`. When updating entries, the publish state is left alone unless this is set.

**`auth`**

When `true`, only logged-in users are able to submit the form.

**`fields` _required for create & update forms_**

The fields the form is allowed to fill. Anything else in the request is ignored, including the `slug` and `date` inputs, unless they're listed here.

```php
'fields' => ['title', 'content', 'slug'],
```

Only list fields you're happy for anyone who can submit the form to set. Fields used for moderation, like a "featured" toggle or a "status" select, shouldn't be listed.

Any field listed here can be filled, even if it isn't in the blueprint, isn't visible, or is a reserved field like `author` or `template`. There are two exceptions:

* Fields with Antlers enabled (`antlers: true`) are never filled, since they'd let users run Antlers on your site.
* Inside Replicator and Grid fields, only sub-fields that are in the blueprint and visible are saved.

**`authorize` _required for update & delete forms_**

Who is allowed to update or delete an entry. Without it, every submission will be denied, and the `update` and `delete` tags won't render anything. It isn't used by create forms - use `auth` or a Form Request's `authorize()` method instead.

* `author` - only the entry's author can update or delete it. When an entry is created by a logged-in user, they'll be set as the entry's author, as long as the blueprint has an `author` field.
* Any other value is treated as the name of a [Gate](https://laravel.com/docs/authorization#gates) ability, which is passed the entry. If you'd like guests to be able to pass the gate, make sure the user parameter is nullable.

```php
Gate::define('delete-article', function (?User $user, Entry $entry) {
    return $user?->isSuper();
});
```

**`request`**

A Laravel Form Request used to validate (and authorize) submissions. Guest Entries doesn't apply the validation rules from your blueprint - those are for the Control Panel. Any validation you'd like on the front-end, like required fields, allowed options or the number of files, should live in a Form Request. You can pass in simply the name of the class or the FQNS (fully qualified namespace) - eg. `ArticleStoreRequest` vs `App\Http\Requests\ArticleStoreRequest`.

**`turnstile`**

When `true`, submissions must pass a [Cloudflare Turnstile](#turnstile) challenge.

### Enabling the Honeypot

You may also configure this addon's [Honeypot](#honeypot) feature. `false` will mean the feature is disabled. To enable, you may change this to a field name that will never be entered by a human (as it'll be hidden) but may be auto-filled by a robot.

## Tags

### Create Entry

```antlers
{{ guest-entries:create form="submit_article" }}
    <h2>Create article</h2>

    <input type="text" name="title">
    <textarea name="content"></textarea>

    <button type="submit">Create</button>
{{ /guest-entries:create }}
```

### Update Entry

```antlers
{{ guest-entries:update form="edit_article" id="article-id" }}
    <h2>Edit article</h2>

    <input type="text" name="title" value="{{ title | sanitize }}">
    <textarea name="content">{{ content | sanitize }}</textarea>

    <button type="submit">Update</button>
{{ /guest-entries:update }}
```

### Delete Entry

```antlers
{{ guest-entries:delete form="delete_article" id="article-id" }}
    <h2>Delete article</h2>
    <p>Are you 100% sure you want to get rid of this article? It'll be gone forever. Which if you didn't know - is a very long time!</p>

    <button type="submit">DELETE</button>
{{ /guest-entries:delete }}
```

### Parameters

When using any of the `guest-entries` tags, there's a few parameters available to you:

**`form` _required_**

Every tag will require you to pass in the `form` parameter, which should be the handle of a form you've configured in [`config/guest-entries.php`](#forms). Everything else about the form, like the collection, who can submit it and how it's validated, lives in the config.

**`id` _sometimes required_**

Both the `update` and `delete` tags require you to pass in the ID of the entry you want to work with. The entry must belong to the form's collection.

When the entry doesn't exist, or the current user isn't allowed to update or delete it (see [`authorize`](#forms)), the tag won't render anything. This stops the form leaking the entry's data to people who can't edit it.

**`redirect`**

You may specify a URL to redirect the user to once the Guest Entry form has been submitted. Only URLs on your site are allowed.

**`error_redirect`**

You may specify a URL to redirect the user to once the Guest Entry form has been submitted unsuccessfully - commonly due to a validation error. Only URLs on your site are allowed.

**Any other parameters**

Any other parameters you pass to the tag will be added as HTML attributes on the `<form>` element.

```antlers
{{ guest-entries:create form="submit_article" class="space-y-4" }}
```

### Fields

Only the fields listed in the form's [`fields`](#forms) option will be saved. Everything else in the request is ignored.

### Special Inputs

There's a few 'special' input fields that you can take advantage of:

**`slug`**

Pretty self-explanatory. Allows you to provide a slug for the created entry. When not provided, a slugified version of the `title` is used. If another entry is already using the slug, a number will be added to the end of it (eg. `my-article-1`).

The `slug` input is only used when it's listed in the form's `fields`. If another entry already uses the slug, a number will be added to the end (eg. `about-1`).

**`date`**

When using a [dated collection](https://statamic.dev/collections#dates), you may use a `date` input to specify the date of the entry. 

When a `date` input isn't provided, or it isn't listed in the form's `fields`, the current date will be used instead.

> **Note:** You should ensure that dates are converted into your application's timezone (normally `UTC`) *before* submitting the form. Learn more about [how Statamic handles timezones](https://statamic.dev/tips/timezones).

### Variables

If you're using the update/delete forms provided by Guest Entries, you will be able to use any of your entries data, in case you wish to fill `value` attributes on the input fields.

```antlers
{{ guest-entries:update form="edit_article" id="article-id" }}
    <h2>Edit article: {{ title | sanitize }}</h2>
    <p>Last updated: {{ updated_at }}</p>

    ...
{{ /guest-entries:update }}
```

### Errors

If you'd like to show any errors after a user has submitted the Guest Entries form, you can use the `{{ guest-entries:errors }}` tag, like shown below:

```antlers
{{ guest-entries:errors }}
    <li>{{ value }}</li>
{{ /guest-entries:errors }}
```

### Success

If you'd like to show a success message after a user has submitted the Guest Entries form, you can use the `{{ guest-entries:success }}` tag, like shown below:

```antlers
{{ if {guest-entries:success} }}
    Well done buddy!
{{ /if }}
```

### Security

Anything submitted through a Guest Entries form was written by someone you don't necessarily trust, so treat it like any other user input:

* **Escape it when you output it.** Antlers doesn't escape output by default, so use the [`sanitize`](https://statamic.dev/modifiers/sanitize) modifier on submitted values - including in `value` attributes, like in the examples above.
* **Markdown fields render HTML.** If guests can fill a Markdown field, enable its "Escape Markup" (`escape_markup`) option so `<script>` tags aren't rendered.
* **Validate with a Form Request.** Blueprint validation rules aren't applied to Guest Entries forms. Use the form's [`request`](#forms) option for required fields, allowed options, file types, the number of uploads, etc.
* **Only allow the fields you need.** See the form's [`fields`](#forms) option.
* **Don't statically cache update & delete forms.** The tags only render for users who can use them, so a cached page would show one user's form - and the entry's data - to everyone. Exclude these pages from [static caching](https://statamic.dev/static-caching).

### Using with Blade

If you prefer Blade, you can use Statamic's [Blade Components](https://statamic.dev/blade#using-antlers-blade-components) feature, which allows you to use Antlers tags in Blade.

```blade
<statamic:guest-entries:create form="submit_article">
    <input type="text" name="description" />
    <button>Update article</button>
</statamic:guest-entries:create>
```

### Fieldtypes

The Guest Entries addon will take the values you pass and save them as-is in the entry data. This means that you can use any fieldtype that Statamic supports, as long as you save the data in the correct format.

If you're not sure how a fieldtype is saved, re-create it in the Control Panel first and look at the data that gets saved. Some fieldtypes will save strings, while others will save complicated arrays, with lots of nested data.

#### Replicator

In the below example, `line_items` is the handle of the Replicator field, and `name` and `description` are the fields within that Replicator.

```html
<input class="mb-6" type="text" name="line_items[0][name]" />
<textarea name="line_items[0][description]"></textarea>

<input class="mb-6" type="text" name="line_items[1][name]" />
<textarea name="line_items[1][description]"></textarea>
```

#### Grid

In the below example, `line_items` is the handle of the Grid field, and `name` and `description` are the fields within the Grid.

```html
<input class="mb-6" type="text" name="line_items[0][name]" />
<textarea name="line_items[0][description]"></textarea>

<input class="mb-6" type="text" name="line_items[1][name]" />
<textarea name="line_items[1][description]"></textarea>
```

#### Assets

Before you can upload files using the `{{ guest-entries }}` tags, you will need to add the `files="true"` parameter to the tag:

```antlers
{{ guest-entries:create form="submit_article" files="true" }}
```

Then, you can add a file input to your form, like so:

```antlers
<input type="file" name="attachments[]" />
```

If you want to allow multiple files, simply add multiple inputs with the same name, like so:

```antlers
<input type="file" name="attachments[]" />
<input type="file" name="attachments[]" />
<input type="file" name="attachments[]" />
```

When the form is submitted, the files will be uploaded to the specified asset container and linked to the entry.

### Date

You can use a standard `<input type="date">` input for date fields.

```antlers
<input type="date" name="date" />
```

However, it's worth noting that the date should be submitted in your application's timezone (normally `UTC`). This means you may need to use JavaScript to convert the date from the user's local timezone to your app's timezone.

To learn more about how Statamic handles timezones, please review [the Statamic documentation](https://statamic.dev/tips/timezones).

## Honeypot

Guest Entries includes a simple Honeypot feature to help reduce spam via your front-end forms. Documentation around configuring can be seen under '[Configuration](#configuration)'.

Once you've enabled the Honeypot, ensure to add the field to your forms, like so:

```antlers
{{ guest-entries:create form="submit_article" }}
    <h2>Create article</h2>

    <input type="text" name="title">
    <textarea name="content"></textarea>

    <input type="hidden" name="zip_code" value=""> <!-- This is my honeypot -->

    <button type="submit">Create</button>
{{ /guest-entries:create }}
```

## Turnstile

Guest Entries can protect your forms with [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/), a CAPTCHA alternative.

1. Create a Turnstile widget in the Cloudflare dashboard, and add its keys to your `.env` file:

    ```
    TURNSTILE_SITE_KEY=your-site-key
    TURNSTILE_SECRET_KEY=your-secret-key
    ```

2. Enable `turnstile` on the forms you'd like to protect, in `config/guest-entries.php`:

    ```php
    'submit_article' => [
        'action' => 'create',
        'collection' => 'articles',
        'fields' => ['title', 'content'],
        'turnstile' => true,
    ],
    ```

3. Add the `{{ guest-entries:turnstile }}` tag to your form:

    ```antlers
    {{ guest-entries:create form="submit_article" }}
        <input type="text" name="title">
        <textarea name="content"></textarea>

        {{ guest-entries:turnstile }}

        <button type="submit">Create</button>
    {{ /guest-entries:create }}
    ```

Any parameters you pass to the tag are added to the widget as `data-*` attributes, so you can [configure it](https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/widget-configurations/):

```antlers
{{ guest-entries:turnstile theme="dark" size="compact" }}
```

When a submission fails the challenge, it'll be rejected with a validation error on the `cf-turnstile-response` input, which you can display with [`{{ guest-entries:errors }}`](#errors).

If you're submitting forms with JavaScript, make sure the `cf-turnstile-response` input added by the widget is included in the request.

## Events

If you need to trigger any custom code when a user does anything with Guest Entries, this addon provides some addons to let you do that.

### GuestEntryCreated

[`DuncanMcClean\GuestEntries\Events\GuestEntryCreated`](https://github.com/duncanmcclean/guest-entries/blob/main/src/Events/GuestEntryCreated.php)

This event is fired whenever an entry is created via `{{ guest-entries:create }}`.

```php
public function handle(GuestEntryCreated $event)
{
	$event->entry;
}
```

### GuestEntryUpdated

[`DuncanMcClean\GuestEntries\Events\GuestEntryUpdated`](https://github.com/duncanmcclean/guest-entries/blob/main/src/Events/GuestEntryUpdated.php)

This event is fired whenever an entry is updated via `{{ guest-entries:update }}`.

```php
public function handle(GuestEntryUpdated $event)
{
	$event->entry;
}
```

### GuestEntryDeleted

[`DuncanMcClean\GuestEntries\Events\GuestEntryDeleted`](https://github.com/duncanmcclean/guest-entries/blob/main/src/Events/GuestEntryDeleted.php)

This event is fired whenever an entry is updated via `{{ guest-entries:delete }}`.

```php
public function handle(GuestEntryDeleted $event)
{
	$event->entry;
}
```

## Upgrading from v5.x to v6.x

In v6, forms are configured in `config/guest-entries.php`, rather than with tag parameters and hidden inputs. It means a form's settings can't be tampered with by whoever is submitting it.

### Update the dependency

```
composer require duncanmcclean/guest-entries:^6.0
```

### Configure your forms

For every `{{ guest-entries:* }}` tag in your templates, add a form to the `forms` array in `config/guest-entries.php` and replace the tag's `collection` and `request` parameters with `form`:

```antlers
{{# Before #}}
{{ guest-entries:create collection="articles" request="ArticleStoreRequest" redirect="/thanks" }}

{{# After #}}
{{ guest-entries:create form="submit_article" redirect="/thanks" }}
```

```php
'forms' => [
    'submit_article' => [
        'action' => 'create',
        'collection' => 'articles',
        'request' => 'ArticleStoreRequest',
        'fields' => ['title', 'content'],
    ],
],
```

The `redirect`, `error_redirect`, `id` and `files` parameters stay on the tag. The tags will throw an exception if they're still passed `collection` or `request`.

The `collections` and `disable_form_parameter_validation` options have been removed. A collection can only be used by forms you've configured.

### List the fields each form can fill

Create and update forms now require a `fields` option, listing the fields the form can fill. Everything else in the request is ignored, including the `slug` and `date` inputs unless they're listed.

```php
'fields' => ['title', 'content'],
```

Fields with Antlers enabled are never filled.

### Decide who can update & delete entries

Update and delete forms now require an `authorize` option. Without it, every submission will be denied, and the tags won't render. Denied submissions return a `404`. Use `author` to only allow an entry's author, or the name of a Gate ability. See [Configuration](#forms).

You may also use the `auth` option to only allow logged-in users to submit a form.

### Move hidden inputs into the config

* The `published` input is no longer accepted. Use the form's `published` option instead.
* The `author` input is no longer accepted. When the blueprint has an `author` field, it's set to the logged-in user.

### Other changes

* Inside Replicator and Grid fields, only sub-fields that are in the blueprint and visible are saved.
* A submitted `slug` that's already taken gets a number added to the end, like generated slugs do.
* The `site` input can only pick one of the collection's sites.
* An existing file can only be attached if it's already on the entry, or inside the field's folder. For fields using a dynamic folder, that means the folder for this entry, author or slug.
* The update and delete tags render nothing when the current user isn't allowed to use them.
* Validation errors shown by `{{ guest-entries:errors }}` are now escaped.

### Redirects

The `redirect` and `error_redirect` parameters can only point to URLs on your site. External URLs are ignored.

### Routes

If you were submitting to the endpoints directly (eg. via JavaScript), they've changed:

| v5 | v6 |
| --- | --- |
| `POST /!/guest-entries/create` | `POST /!/guest-entries/{form}` |
| `POST /!/guest-entries/update` | `PATCH /!/guest-entries/{form}/{id}` |
| `DELETE /!/guest-entries/delete` | `DELETE /!/guest-entries/{form}/{id}` |

The `_collection`, `_id` and `_request` inputs are no longer used.

### Upgrading with AI

If you're using an AI coding agent, you can give it this prompt to do most of the upgrade for you:

```
Upgrade this site from Guest Entries v5 to v6. Read the upgrade guide first:
https://github.com/duncanmcclean/guest-entries/blob/6.x/DOCUMENTATION.md#upgrading-from-v5x-to-v6x

1. Find every `guest-entries:create`, `guest-entries:update` and `guest-entries:delete` tag in
   the templates (Antlers `{{ guest-entries:* }}` and Blade `<statamic:guest-entries:* />`), along with
   any JavaScript that posts to `/!/guest-entries/*`.
2. For each one, add a form to the `forms` array in `config/guest-entries.php`, using a descriptive
   snake_case handle. Move the `collection` and `request` parameters into the config, along with
   the value of any hidden `published` input. Replace them on the tag with `form="<handle>"`.
   Leave `redirect`, `error_redirect`, `id` and `files` on the tag.
   Add a `fields` option to create and update forms, listing the inputs the form submits.
3. Remove the `collections` and `disable_form_parameter_validation` options from the config.
4. Flag any inputs that look like moderation fields (eg. a "featured" toggle or "status" select)
   rather than adding them to `fields`, and any fields that have Antlers enabled.
5. Don't guess who can update or delete entries. For update and delete forms, leave `authorize` out
   and ask me, explaining the `author` and Gate options. Also ask whether each form should require
   users to be logged in (`auth`).

When you're done, summarise the forms you created, any fields that won't be saved, and any tags
you couldn't convert (eg. ones with dynamic parameters).
```
