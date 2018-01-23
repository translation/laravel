<?php

return [
    'key' => env('TRANSLATIONIO_KEY'),

    'source_locale' => 'en',

    'target_locales' => [],

    /*
    |--------------------------------------------------------------------------
    | Directories to scan for Gettext
    |--------------------------------------------------------------------------
    |
    | Set directories to scan to find gettext strings (starting with __)
    |
    */
    'gettext_parse_paths' => ['app', 'resources'],

    /*
    |--------------------------------------------------------------------------
    | Where the Gettext translations are stored
    |--------------------------------------------------------------------------
    |
    | Full path is $storage/xx_XX/LC_MESSAGES/$domain.XX
    |
    */
    'gettext_locales_path' => 'resources/lang/gettext'
];
