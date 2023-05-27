---
title: Configuration
---

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

    /*
    |--------------------------------------------------------------------------
    | Form Parameter Validation
    |--------------------------------------------------------------------------
    |
    | Guest Entries will encrypt & validate form parameters to prevent them
    | from being tampered with. You may disable this if you wish.
    |
    */

    'disable_form_parameter_validation' => false,

];
```

### Collections

To enable a collection, create an entry in the array, where the key is the handle of the collection and where the value is `true`/`false`, depending on if you wish to enable/disable it.

### Honeypot

You may also configure this addon's [Honeypot](#honeypot) feature. `false` will mean the feature is disabled. To enable, you may change this to a field name that will never be entered by a human (as it'll be hidden) but may be auto-filled by a robot.

### Form Parameter Validation

Guest Entries will automatically encrypt the values of hidden form parameters, like `_redirect` and `_collection` when using the `{{ guest-entry }}` tag. The values are later decrypted by a middleware when the form is submitted.

This measure aims to prevent users from tampering with the form parameters. However, if you wish to disable this functionality, set `disable_form_parameter_validation` to `true`.
