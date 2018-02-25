<?php


namespace Armandsar\LaravelTranslationio;


class Facade extends \Illuminate\Support\Facades\Facade
{

    protected static function getFacadeAccessor()
    {
        return TranslationIO::class;
    }
}