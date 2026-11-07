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

        // 'submit_article' => [
        //     'action' => 'create',
        //     'collection' => 'articles',
        //     'fields' => ['title', 'content'],
        // ],

        // 'edit_article' => [
        //     'action' => 'update',
        //     'collection' => 'articles',
        //     'fields' => ['title', 'content'],
        //     'auth' => true,
        //     'authorize' => 'author',
        // ],

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
