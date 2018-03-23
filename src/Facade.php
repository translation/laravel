<?php

namespace Tio\Laravel;

class Facade extends \Illuminate\Support\Facades\Facade
{

    protected static function getFacadeAccessor()
    {
        return TranslationIO::class;
    }
}
