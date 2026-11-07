---
title: Upgrade Guide
---

## Upgrading from v5 to v6

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

Update and delete forms now require an `authorize` option. Without it, every submission will be denied, and the tags won't render. Denied submissions return a `404`. Use `author` to only allow an entry's author, or the name of a Gate ability. See [Configuration](/configuration#forms).

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
https://github.com/duncanmcclean/guest-entries/blob/6.x/docs/upgrade-guide.md

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
