# Translation.io client for Laravel 5

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/armandsar/laravel-translationio/master.svg?style=flat-square)](https://travis-ci.org/armandsar/laravel-translationio)
[![Total Downloads](https://img.shields.io/packagist/dt/armandsar/laravel-translationio.svg?style=flat-square)](https://packagist.org/packages/armandsar/laravel-translationio)

Add this package to translate your application with [Translation.io](http://translation.io).

## Install

Via Composer

``` bash
$ composer require armandsar/laravel-translationio --dev
```

If you are on lower Laravel version that 5.5
(or choose not to use package auto discovery) add this to service providers:

```php
\Armandsar\LaravelTranslationio\ServiceProvider::class
```

## Publish config

``` bash
$ php artisan vendor:publish
```

## Configure

Set api key, source and targets locales in published config file

## Usage

Initialize your project with translation.io and push existing translations run

``` bash
$ php artisan translation:init
```

Update translations

``` bash
$ php artisan translation:sync
```

## Testing

``` bash
$ phpunit
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
