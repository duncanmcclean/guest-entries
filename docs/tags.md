---
title: Tags
---

## Create Entry

```antlers
{{ guest-entries:create form="submit_article" }}
    <h2>Create article</h2>

    <input type="text" name="title">
    <textarea name="content"></textarea>

    <button type="submit">Create</button>
{{ /guest-entries:create }}
```

## Update Entry

```antlers
{{ guest-entries:update form="edit_article" id="article-id" }}
    <h2>Edit article</h2>

    <input type="text" name="title" value="{{ title | sanitize }}">
    <textarea name="content">{{ content | sanitize }}</textarea>

    <button type="submit">Update</button>
{{ /guest-entries:update }}
```

## Delete Entry

```antlers
{{ guest-entries:delete form="delete_article" id="article-id" }}
    <h2>Delete article</h2>
    <p>Are you 100% sure you want to get rid of this article? It'll be gone forever. Which if you didn't know - is a very long time!</p>

    <button type="submit">DELETE</button>
{{ /guest-entries:delete }}
```

## Parameters

When using any of the `guest-entries` tags, there's a few parameters available to you:

**`form` _required_**

Every tag will require you to pass in the `form` parameter, which should be the handle of a form you've configured in [`config/guest-entries.php`](/configuration#forms). Everything else about the form, like the collection, who can submit it and how it's validated, lives in the config.

**`id` _sometimes required_**

Both the `update` and `delete` tags require you to pass in the ID of the entry you want to work with. The entry must belong to the form's collection.

When the entry doesn't exist, or the current user isn't allowed to update or delete it (see [`authorize`](/configuration#forms)), the tag won't render anything. This stops the form leaking the entry's data to people who can't edit it.

**`redirect`**

You may specify a URL to redirect the user to once the Guest Entry form has been submitted. Only URLs on your site are allowed.

**`error_redirect`**

You may specify a URL to redirect the user to once the Guest Entry form has been submitted unsuccessfully - commonly due to a validation error. Only URLs on your site are allowed.

## Fields

Only the fields listed in the form's [`fields`](/configuration#forms) option will be saved. Everything else in the request is ignored.

## Special Inputs

There's a few 'special' input fields that you can take advantage of:

**`slug`**

Pretty self-explanatory. Allows you to provide a slug for the created entry. When not provided, a slugified version of the `title` is used. If another entry is already using the slug, a number will be added to the end of it (eg. `my-article-1`).

The `slug` input is only used when it's listed in the form's `fields`. If another entry already uses the slug, a number will be added to the end (eg. `about-1`).

**`date`**

When using a [dated collection](https://statamic.dev/collections#dates), you may use a `date` input to specify the date of the entry. 

When a `date` input isn't provided, or it isn't listed in the form's `fields`, the current date will be used instead.

> **Note:** You should ensure that dates are converted into your application's timezone (normally `UTC`) *before* submitting the form. Learn more about [how Statamic handles timezones](https://statamic.dev/tips/timezones).

## Variables

If you're using the update/delete forms provided by Guest Entries, you will be able to use any of your entries data, in case you wish to fill `value` attributes on the input fields.

```antlers
{{ guest-entries:update form="edit_article" id="article-id" }}
    <h2>Edit article: {{ title | sanitize }}</h2>
    <p>Last updated: {{ updated_at }}</p>

    ...
{{ /guest-entries:update }}
```

## Errors

If you'd like to show any errors after a user has submitted the Guest Entries form, you can use the `{{ guest-entries:errors }}` tag, like shown below:

```antlers
{{ guest-entries:errors }}
    <li>{{ value }}</li>
{{ /guest-entries:errors }}
```

## Success

If you'd like to show a success message after a user has submitted the Guest Entries form, you can use the `{{ guest-entries:success }}` tag, like shown below:

```antlers
{{ if {guest-entries:success} }}
    Well done buddy!
{{ /if }}
```

## Security

Anything submitted through a Guest Entries form was written by someone you don't necessarily trust, so treat it like any other user input:

* **Escape it when you output it.** Antlers doesn't escape output by default, so use the [`sanitize`](https://statamic.dev/modifiers/sanitize) modifier on submitted values - including in `value` attributes, like in the examples above.
* **Markdown fields render HTML.** If guests can fill a Markdown field, enable its "Escape Markup" (`escape_markup`) option so `<script>` tags aren't rendered.
* **Validate with a Form Request.** Blueprint validation rules aren't applied to Guest Entries forms. Use the form's [`request`](/configuration#forms) option for required fields, allowed options, file types, the number of uploads, etc.
* **Only allow the fields you need.** See the form's [`fields`](/configuration#forms) option.
* **Don't statically cache update & delete forms.** The tags only render for users who can use them, so a cached page would show one user's form - and the entry's data - to everyone. Exclude these pages from [static caching](https://statamic.dev/static-caching).

## Using with Blade

If you prefer Blade, you can use Statamic's [Blade Components](https://statamic.dev/blade#using-antlers-blade-components) feature, which allows you to use Antlers tags in Blade.

```blade
<statamic:guest-entries:create form="submit_article">
    <input type="text" name="description" />
    <button>Update article</button>
</statamic:guest-entries:create>
```

## Fieldtypes

The Guest Entries addon will take the values you pass and save them as-is in the entry data. This means that you can use any fieldtype that Statamic supports, as long as you save the data in the correct format.

If you're not sure how a fieldtype is saved, re-create it in the Control Panel first and look at the data that gets saved. Some fieldtypes will save strings, while others will save complicated arrays, with lots of nested data.

### Replicator

In the below example, `line_items` is the handle of the Replicator field, and `name` and `description` are the fields within that Replicator.

```html
<input class="mb-6" type="text" name="line_items[0][name]" />
<textarea name="line_items[0][description]"></textarea>

<input class="mb-6" type="text" name="line_items[1][name]" />
<textarea name="line_items[1][description]"></textarea>
```

### Grid

In the below example, `line_items` is the handle of the Grid field, and `name` and `description` are the fields within the Grid.

```html
<input class="mb-6" type="text" name="line_items[0][name]" />
<textarea name="line_items[0][description]"></textarea>

<input class="mb-6" type="text" name="line_items[1][name]" />
<textarea name="line_items[1][description]"></textarea>
```

### Assets

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

## Date

You can use a standard `<input type="date">` input for date fields.

```antlers
<input type="date" name="date" />
```

However, it's worth noting that the date should be submitted in your application's timezone (normally `UTC`). This means you may need to use JavaScript to convert the date from the user's local timezone to your app's timezone.

To learn more about how Statamic handles timezones, please review [the Statamic documentation](https://statamic.dev/tips/timezones).
