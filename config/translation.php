<?php

return [
    'key' => env('TRANSLATIONIO_KEY'),

    'source_locale' => 'en',

    'target_locales' => [],

    /*
    |--------------------------------------------------------------------------
    | # Ignored PHP key prefixes.
    |--------------------------------------------------------------------------
    |
    | Ex:  * 'auth' for the whole auth.php file
    |      * 'validation.size' for subtree of "size" key in validation.php file
    |
    */
    'ignored_key_prefixes' => [],

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
    \ PO file is here: $gettext_locales_path/xx_XX/app.po
    | MO file is here: $gettext_locales_path/xx_XX/LC_MESSAGES/app.mo
    |
    */
    'gettext_locales_path' => 'resources/lang/gettext'
];
