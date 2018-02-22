<?php

return [
    'key' => env('TRANSLATIONIO_KEY'),

    'source_locale' => 'en',

    'target_locales' => [],

    /*
    |--------------------------------------------------------------------------
    | Directories to scan for Gettext strings
    |--------------------------------------------------------------------------
    |
    */
    'gettext_parse_paths' => ['app', 'resources'],

    /*
    |--------------------------------------------------------------------------
    | Where the Gettext translations are stored
    |--------------------------------------------------------------------------
    |
    | Full path is $gettext_locales_path/xx_XX/LC_MESSAGES/app.XX
    |
    */
    'gettext_locales_path' => 'resources/lang/gettext'
];
