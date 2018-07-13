# [Translation.io](https://translation.io/laravel) client for Laravel 5

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/translation/laravel/master.svg?style=flat-square)](https://travis-ci.org/translation/laravel)
[![Test Coverage](https://api.codeclimate.com/v1/badges/552e1ddc3f3f604d4908/test_coverage)](https://codeclimate.com/github/translation/laravel/test_coverage)
[![Maintainability](https://api.codeclimate.com/v1/badges/552e1ddc3f3f604d4908/maintainability)](https://codeclimate.com/github/translation/laravel/maintainability)

Add this package to translate your application with
[Laravel](#laravel-localization-php-keyvalues) or [GetText](#gettext) syntaxes.

Keep it synchronized with your translators on [Translation.io](https://translation.io/laravel).

[Technical Demo](https://translation.io/videos/laravel.mp4) (2.5min)

[![Image](https://translation.io/assets/gifs/translation-fe6fc5848766fb926e6ca06943ca6c03cc387fa539d9b37d0e375a42e858c70e.gif)](https://translation.io/laravel)

Need help? [contact@translation.io](mailto:contact@translation.io)

Table of contents
=================

 * [Translation syntaxes](#translation-syntaxes)
   * [Laravel Localization (PHP key/values)](#laravel-localization-php-keyvalues)
   * [GetText](#gettext)
 * [Installation](#installation)
 * [Usage](#usage)
   * [Sync](#sync)
   * [Sync and Show Purgeable](#sync-and-show-purgeable)
   * [Sync and Purge](#sync-and-purge)
 * [Manage Languages](#manage-languages)
   * [Add or Remove Language](#add-or-remove-language)
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
trans('inbox.title');

// Regular with sublevel key
trans('inbox.menu.title');

// Pluralization
trans_choice('inbox.message', $n);

// Interpolation
trans('inbox.hello', ['name' => $user->name]);
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

Note that `__` can also be used instead of `trans`.

### GetText

This package adds the GetText support for Laravel. We [strongly suggest](https://translation.io/blog/gettext-is-better-than-rails-i18n)
that you use GetText to translate your applications since it allows a simpler and more maintainable syntax.

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
t('%city1% is bigger than %city2%', [ '%city1%' => 'NYC', '%city2%' => 'BXL' ]);
```

You don't need another file with source text or translations, everything will
be synchronized from Translation.io, and stored on PO/MO files.

## Installation

 1. Add the package via Composer:

```bash
$ composer require tio/laravel
```

If you are on lower Laravel version that 5.5
(or choose not to use package auto discovery) add this to service providers:

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
[documentation](https://translation.io/blog/adding-target-languages) about that.

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

### Custom Languages

You may want to add a custom language that is derived from an existing language.
It's useful if you want to change some translations for another instance of the
application or for a specific customer.

The structure of a custom language is : existing language code + "-" + custom text.

Examples: `en-microsoft` or `fr-be-custom`.

Custom languages can be added and used like any other language.

## Change the current locale

### Globally

The easiest way to change the current locale is with the `set.locale` Middleware.

```php
// in routes/web.php

Route::middleware('set.locale')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });
});
```

It will automatically set the locale extracted from the user's browser `HTTP_ACCEPT_LANGUAGE` value, and keep it in the session between requests.

Update the current locale by redirecting the user to https://yourdomain.com?locale=fr or even https://yourdomain.com/fr if you prefixed your routes like this:

```php
// in routes/web.php

Route::prefix('{locale?}')->middleware('set.locale')->group(function() {
    Route::get('/', function () {
        return view('welcome');
    });
});
```

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
