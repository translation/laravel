# [Translation.io](https://translation.io/laravel) client for Laravel 5

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/translation/laravel/master.svg?style=flat-square)](https://travis-ci.org/translation/laravel)
[![Test Coverage](https://api.codeclimate.com/v1/badges/552e1ddc3f3f604d4908/test_coverage)](https://codeclimate.com/github/translation/laravel/test_coverage)
[![Maintainability](https://api.codeclimate.com/v1/badges/552e1ddc3f3f604d4908/maintainability)](https://codeclimate.com/github/translation/laravel/maintainability)

Add this package to localize your Laravel application.

Use the official Laravel syntax (with [PHP](#laravel-localization-php-keyvalues) or [JSON](#laravel-localization-json-source-text) files),
or use the [GetText](#gettext) syntax.

Write only the source text, and keep it synchronized with your translators on [Translation.io](https://translation.io/laravel).

<a href="https://translation.io/laravel">
  <img width="720px" alt="Translation.io interface" src="https://translation.io/gifs/translation.gif">
</a>

[Technical Demo](https://translation.io/videos/laravel.mp4) (2.5min)

Need help? [contact@translation.io](mailto:contact@translation.io)

Table of contents
=================

 * [Translation syntaxes](#translation-syntaxes)
   * [Laravel Localization (PHP key/values)](#laravel-localization-php-keyvalues)
   * [Laravel Localization (JSON source text)](#laravel-localization-json-source-text)
   * [GetText](#gettext)
 * [Installation](#installation)
 * [Usage](#usage)
   * [Sync](#sync)
   * [Sync and Show Purgeable](#sync-and-show-purgeable)
   * [Sync and Purge](#sync-and-purge)
 * [Manage Languages](#manage-languages)
   * [Add or Remove Language](#add-or-remove-language)
   * [Edit Language](#edit-language)
   * [Custom Languages](#custom-languages)
 * [Change the current locale](#change-the-current-locale)
   * [Globally](#globally)
   * [Locally](#locally)
 * [Testing](#testing)
 * [Contributing](#contributing)
 * [List of clients for Translation.io](#list-of-clients-for-translationio)
   * [Ruby on Rails (Ruby)](#ruby-on-rails-ruby)
   * [Laravel (PHP)](#laravel-php)
   * [React and React-Intl (JavaScript)](#react-and-react-intl-javascript)
   * [Others](#others)
 * [License](#license)

## Translation syntaxes

### Laravel Localization (PHP key/values)

The [default Laravel method to localize](https://laravel.com/docs/master/localization#using-short-keys).

```php
// Regular
__('inbox.title');

// Regular with sublevel key
__('inbox.menu.title');

// Pluralization
trans_choice('inbox.message', $number);

// Interpolation
__('inbox.hello', ['name' => $user->name]);
```

With the PHP file `resources/lang/en/inbox.php`:

```php
return [
    'title' => 'Title to be translated',
    'hello' => 'Hello :name',
    'messages' => 'One message|Many messages',
    'menu' => [
        'title' => 'Title of menu'
    ]
];
```

Note that `trans` can also be used instead of `__`.

### Laravel Localization (JSON source text)

[A new feature](https://laravel.com/docs/5.6/localization#using-translation-strings-as-keys) of Laravel 5.4
is the possibility to use `__` with the source text (and not only with keys like in the previous section).

These translations are stored into JSON files located in the `resources/lang/` directory.

```php
// Regular
__("Text to be translated");

// Pluralization
trans_choice(__('One message|Many messages'), $number);

// Interpolation
__('Hello :name', ['name' => $user->name]);
```

With the JSON file `resources/lang/en.json`:

```json
{
    "Text to be translated": "",
    "One message|Many messages": "",
    "Hello :name": ""
}
```

To spend less time dealing with multiple JSON files, we advise to only edit
the original language (usually `en.json`) to add new strings, and leave the
translations empty.

During a [sync](#sync), This package will automatically create and fill the JSON files
of the target languages.

### GetText

This package adds the GetText support to Laravel. We [strongly suggest](https://translation.io/blog/gettext-is-better-than-rails-i18n)
that you use GetText to localize your application since it allows an easier and more complete syntax.

Also, you won't need to create and manage any PHP or JSON file since your code will be
automatically scanned for any string to translate.

```php
// Regular
t("Text to be translated");

// Pluralization
n("Singular text", "Plural text", $number);

// Regular with context
p("context", "Text to be translated");

// Pluralization with context
np("context", "Singular text", "Plural text", $number);

// Simple Interpolations (works with n, p and np too)
t('Hello %s', $user->name);

// Complex Interpolations (works with n, p and np too)
t(':city1 is bigger than :city2', [ ':city1' => 'NYC', ':city2' => 'BXL' ]);
```

## Installation

 1. Add the package via Composer:

```bash
$ composer require tio/laravel
```

If you are on a Laravel version lower than 5.5
(or choose not to use package auto discovery) add this to service providers (`config/app.php`):

```php
\Tio\Laravel\ServiceProvider::class
```

 2. Create a new translation project [from the UI](https://translation.io/laravel).
 3. Copy the initializer into your Laravel app (`config/translation.php`) or execute `php artisan vendor:publish`.

The initializer looks like this:

```php
return [
    'key' => env('TRANSLATIONIO_KEY'),
    'source_locale' => 'en',
    'target_locales' => ['fr', 'nl', 'de', 'es'],
    'gettext_parse_paths' => ['app', 'resources'],     // Where the GetText strings will be scanned
    'gettext_locales_path' => 'resources/lang/gettext' // Where the GetText translations will be stored
];
```

 4. Add the API key (`TRANSLATIONIO_KEY`) in your `.env` file.

 5. Initialize your project and push existing translations to Translation.io with:

```bash
$ php artisan translation:init
```

If you later need to add/remove target languages, please read our
[this section](#add-or-remove-language) about that.

## Usage

### Sync

To send new translatable keys/strings and get new translations from Translation.io, simply run:

```bash
$ php artisan translation:sync
```

### Sync and Show Purgeable

If you need to find out what are the unused keys/strings from Translation.io, using the current branch as reference:

```bash
$ php artisan translation:sync_and_show_purgeable
```

As the name says, this operation will also perform a sync at the same time.

### Sync and Purge

If you need to remove unused keys/strings from Translation.io, using the current branch as reference:

```bash
$ php artisan translation:sync_and_purge
```

As the name says, this operation will also perform a sync at the same time.

Warning: all keys that are not present in the current branch will be **permanently deleted from Translation.io**.

## Manage Languages

### Add or Remove Language

You can add or remove a language by updating `'target_locales' => []` in your
`config/translation.php` file, and executing `php artisan translation:sync`.

If you want to add a new language with existing translations (ex. if you already have
a translated PHP file in your `lang` folder), you will need to create a new project on
Translation.io and run `php artisan translation:init` for them to appear.

### Edit Language

To edit existing languages while keeping their translations (e.g. changing from `en` to `en-US`).

 1. Create a new project on Translation.io with the correct languages.
 2. Adapt `config/translation.php` (new API key and languages)
 3. Adapt directory language names in `resources/lang` (optional: adapt GetText `.po` headers)
 4. Execute `php artisan translation:init` and check that everything went fine.
 5. Invite your collaborators in the new project.
 6. Remove the old project.

Since you created a new project, the translation history and tags will unfortunately be lost.

### Custom Languages

You may want to add a custom language that is derived from an existing language.
It's useful if you want to change some translations for another instance of the
application or for a specific customer.

The structure of a custom language is : existing language code + "-" + custom text.

Examples: `en-microsoft` or `fr-BE-custom`.

Custom languages can be added and used like any other language.

## Change the current locale

### Globally

The easiest way to change the current locale is with the `set.locale` Middleware.

```php
// in routes/web.php

// Solution 1: Apply the locale selection to root
Route::get('/', function () {
    return view('welcome');
})->middleware('set.locale');

// Solution 2: Apply the locale selection to many routes
Route::middleware('set.locale')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });
});

// Solution 3: prefix your routes with the locale and apply it
Route::prefix('{locale?}')->middleware('set.locale')->group(function() {
    Route::get('/', function () {
        return view('welcome');
    });
});
```

First time the user will connect, it will automatically set the locale extracted from the browser `HTTP_ACCEPT_LANGUAGE` value, and keep it in the session between requests.

Update the user locale by redirecting to https://yourdomain.com?locale=fr or https://yourdomain.com/fr (Solution 3)

The `set.locale` Middleware code is [here](https://github.com/translation/laravel/blob/master/src/Middleware/SetLocaleMiddleware.php), feel free to adapt it with your own locale management.

### Locally

Change the current locale with:

```php
use Tio\Laravel\Facade as Translation;

Translation::setLocale('fr');
```

## Testing

To run the specs:

```bash
$ phpunit
```

## Contributing

Please read the [CONTRIBUTING](CONTRIBUTING.md) file.

## List of clients for Translation.io

These implementations were usually started by contributors for their own projects.
Some of them are officially supported by [Translation.io](https://translation.io)
and some are not yet supported. However, they are quite well documented.

Thanks a lot to these contributors for their hard work!

### Ruby on Rails (Ruby)

Officially Supported on [https://translation.io/rails](https://translation.io/rails)

 * GitHub: https://github.com/translation/rails
 * RubyGems: https://rubygems.org/gems/translation/

Credits: [@aurels](https://github.com/aurels), [@michaelhoste](https://github.com/michaelhoste)

### Laravel (PHP)

Officially Supported on [https://translation.io/laravel](https://translation.io/laravel)

 * GitHub: https://github.com/translation/laravel
 * Packagist: https://packagist.org/packages/tio/laravel

Credits: [@armandsar](https://github.com/armandsar), [@michaelhoste](https://github.com/michaelhoste)

### React and React-Intl (JavaScript)

 * GitHub: https://github.com/deecewan/translation-io
 * NPM: https://www.npmjs.com/package/translation-io

Credits: [@deecewan](https://github.com/deecewan)

### Others

If you want to create a new client for your favorite language or framework, feel
free to reach us on [contact@translation.io](mailto:contact@translation.io) and
we'll assist you with the workflow logic and send you API docs.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
