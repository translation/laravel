# [Translation.io](https://translation.io) client for Laravel 5

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/armandsar/laravel-translationio/master.svg?style=flat-square)](https://travis-ci.org/armandsar/laravel-translationio)
[![Total Downloads](https://img.shields.io/packagist/dt/armandsar/laravel-translationio.svg?style=flat-square)](https://packagist.org/packages/armandsar/laravel-translationio)

Add this package to translate your application with
[Laravel](#laravel-localization-php-keyvalues) or [GetText](#gettext) syntaxes.

Keep it synchronized with your translators on [Translation.io](https://translation.io).

[![Image](https://translation.io/interface.png)](https://translation.io)

Need help? [contact@translation.io](mailto:contact@translation.io)

Table of contents
=================

 * [Installation](#installation)
 * [Translation syntaxes](#translation-syntaxes)
   * [Laravel Localization (PHP key/values)](#laravel-localization-php-keyvalues)
   * [GetText](#gettext)
 * [Usage](#usage)
   * [Sync](#sync)
   * [Sync and Show Purgeable](#sync-and-show-purgeable)
   * [Sync and Purge](#sync-and-purge)
 * [List of clients for Translation.io](#list-of-clients-for-translationio)
   * [Ruby on Rails (Ruby)](#ruby-on-rails-ruby)
   * [Laravel (PHP)](#laravel-php)
   * [React and React-Intl (JavaScript)](#react-and-react-intl-javascript)
 * [Testing](#testing)
 * [Contributing](#contributing)
 * [License](#license)

## Installation

 1. Add the package via Composer:

```bash
$ composer require armandsar/laravel-translationio
```

If you are on lower Laravel version that 5.5
(or choose not to use package auto discovery) add this to service providers:

```php
\Armandsar\LaravelTranslationio\ServiceProvider::class
```

 2. Create a new translation project [from the UI](https://translation.io).
 3. Copy the initializer into your Laravel app (`config/translationio.php`) or execute `php artisan vendor:publish`.

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

 4. Add the API key (TRANSLATIONIO_KEY) in your `.env` file.

 5. Initialize your project and push existing translations to Translation.io with:

```bash
$ php artisan translation:init
```

If you later need to add/remove target languages, please read our
[documentation](https://translation.io/blog/adding-target-languages) about that.

## Translation syntaxes

#### Laravel Localization (PHP key/values)

The [default Laravel method to localize](https://laravel.com/docs/master/localization#using-short-keys).

```php
// Regular
__('inbox.title');

// Regular with sublevel key
__('inbox.menu.title');

// Pluralization
trans_choice('inbox.message', $n);

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

#### GetText

This package adds the GetText support for Laravel. We [strongly suggest](https://translation.io/blog/gettext-is-better-than-rails-i18n)
that you use GetText to translate your applications since it allows a simpler and more maintainable syntax.

```php
// Regular
t__("Text to be translated");

// Pluralization
n__("Singular text", "Plural text", $number);

// Regular with context
p__("context", "Text to be translated");

// Pluralization with context
np__("context", "Singular text", "Plural text", $number);

// Simple Interpolations (works with n__, p__ and np__ too)
t__('Hello %s', $user->name);

// Complex Interpolations (works with n__, p__ and np__ too)
t__('%city1% is bigger than %city2%', [ '%city1%' => 'NYC', '%city2%' => 'BXL' ]);
```

## Usage

#### Sync

To send new translatable keys/strings and get new translations from Translation.io, simply run:

```bash
$ php artisan translation:sync
```

#### Sync and Show Purgeable

If you need to find out what are the unused keys/strings from Translation.io, using the current branch as reference:

```bash
$ php artisan translation:sync_and_show_purgeable
```

As the name says, this operation will also perform a sync at the same time.

#### Sync and Purge

If you need to remove unused keys/strings from Translation.io, using the current branch as reference:

```bash
$ php artisan translation:sync_and_purge
```

As the name says, this operation will also perform a sync at the same time.

Warning: all keys that are not present in the current branch will be **permanently deleted from Translation.io**.

## List of clients for Translation.io

These implementations were usually started by contributors for their own projects.
Some of them are officially supported by [Translation.io](https://translation.io)
and some are not yet supported. However, they are quite well documented.

Thanks a lot to these contributors for their hard work!

If you want to create a new client for your favorite language or framework, feel
free to reach us on [contact@translation.io](mailto:contact@translation.io) and
we'll assist you with the workflow logic and some API documentations.

#### Ruby on Rails (Ruby)

**Officially Supported**

 * GitHub: https://github.com/aurels/translation-gem
 * RubyGems: https://rubygems.org/gems/translation/

Credits: [@aurels](https://github.com/aurels), [@michaelhoste](https://github.com/michaelhoste)

#### Laravel (PHP)

**Officially Supported**

 * GitHub: https://github.com/armandsar/laravel-translationio
 * Packagist: https://packagist.org/packages/armandsar/laravel-translationio

Credits: [@armandsar](https://github.com/armandsar), [@michaelhoste](https://github.com/michaelhoste)

#### React and React-Intl (JavaScript)

**Not Officially Supported**

 * GitHub: https://github.com/deecewan/translation-io
 * NPM: https://www.npmjs.com/package/translation-io

Credits: [@deecewan](https://github.com/deecewan)

## Testing

To run the specs:

```bash
$ phpunit
```

## Contributing

Please read the [CONTRIBUTING](CONTRIBUTING.md) file.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
