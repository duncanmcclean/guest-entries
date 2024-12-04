---
title: Tags
---

## Create Entry

```antlers
{{ guest-entries:create collection="articles" }}
    <h2>Create article</h2>

    <input type="text" name="title">
    <textarea name="content"></textarea>

    <button type="submit">Create</button>
{{ /guest-entries:create }}
```

## Update Entry

```antlers
{{ guest-entries:update collection="articles" id="article-id" }}
    <h2>Edit article</h2>

    <input type="text" name="title" value="{{ title }}">
    <textarea name="content">{{ content }}</textarea>

    <button type="submit">Update</button>
{{ /guest-entries:update }}
```

## Delete Entry

```antlers
{{ guest-entries:delete collection="articles" id="article-id" }}
    <h2>Delete article</h2>
    <p>Are you 100% sure you want to get rid of this article? It'll be gone forever. Which if you didn't know - is a very long time!</p>

    <button type="submit">DELETE</button>
{{ /guest-entries:delete }}
```

## Parameters

When using any of the `guest-entries` tags, there's a few parameters available to you:

**`collection` _required_**

Every tag will require you to pass in the `collection` parameter, which should be the handle of the collection you want to deal with.

**`id` _sometimes required_**

Both the `update` and `delete` tags require you to pass in the ID of the entry you want to work with.

**`redirect`**

You may specify a URL to redirect the user to once the Guest Entry form has been submitted.

**`error_redirect`**

You may specify a URL to redirect the user to once the Guest Entry form has been submitted unsuccessfully - commonly due to a validation error.

**`request`**

You may specify a Laravel Form Request to be used for validation of the form. You can pass in simply the name of the class or the FQNS (fully qualified namespace) - eg. `ArticleStoreRequest` vs `App\Http\Requests\ArticleStoreRequest`

## Special Inputs

There's a few 'special' input fields that you can take advantage of:

**`slug`**

Pretty self-explanatory. Allows you to provide a slug for the created entry. When not provided, a slugified version of the `title` is used.

**`date`**

When using a dated collection, a `date` will be allowed. When not provided, the current date is used.

**`published`**

Pretty self-explanatory. Allows you to control the publish state of the created entry. You should provide either a `1` or `true` to publish an entry.

When not provided, the entry will be set to unpublished.

## Variables

If you're using the update/delete forms provided by Guest Entries, you will be able to use any of your entries data, in case you wish to fill `value` attributes on the input fields.

```antlers
{{ guest-entries:update collection="articles" id="article-id" }}
    <h2>Edit article: {{ title }}</h2>
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

## Using with Blade

If you prefer Blade, you can use Statamic's [Blade Components](https://statamic.dev/blade#using-antlers-blade-components) feature, which allows you to use Antlers tags in Blade.

```blade
<statamic:guest-entries:create collection="blog">
    <input type="text" name="description" />
    <button>Update article</button>
</statamic:guest-entries:create>
```