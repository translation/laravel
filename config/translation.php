<?php

return [
    'key' => env('TRANSLATIONIO_KEY'),

    'source_locale' => 'en',

    'target_locales' => [],

    'skip_php_translations' => false,

    /*
    |--------------------------------------------------------------------------
    | # Ignored PHP key prefixes.
    |--------------------------------------------------------------------------
    |
    | Ex: * 'validation':        ignore the whole validation.php file.
    |     * 'validation.custom': ignore the "custom" subtree in validation.php file.
    |     * 'subfolder/more':    ignore the whole subfolder/more.php file.
    |
    */
    'ignored_key_prefixes' => [],

    /*
    |--------------------------------------------------------------------------
    | Directories to scan for Gettext strings.
    |--------------------------------------------------------------------------
    |
    */
    'gettext_parse_paths' => ['app', 'resources'],

    /*
    |--------------------------------------------------------------------------
    | Where the Gettext translations are stored.
    |--------------------------------------------------------------------------
    |
    | PO file is here: $gettext_locales_path/xx_XX/app.po
    | MO file is here: $gettext_locales_path/xx_XX/LC_MESSAGES/app.mo
    |
    | For Laravel >= 9, default path should be changed to 'lang/gettext'.
    |
    */
    'gettext_locales_path' => 'resources/lang/gettext'
];
