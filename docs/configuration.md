---
title: Configuration
---

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

];
```

### Forms

Every Guest Entries form needs to be configured here, where the key is the form's handle. You'll pass the handle to the `form` parameter on the [tags](/tags).

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

### Honeypot

You may also configure this addon's [Honeypot](/honeypot) feature. `false` will mean the feature is disabled. To enable, you may change this to a field name that will never be entered by a human (as it'll be hidden) but may be auto-filled by a robot.
