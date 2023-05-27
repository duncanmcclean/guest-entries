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
