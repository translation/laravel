<?php

namespace Armandsar\LaravelTranslationio;

use Gettext\Translations;

class POExtractor
{
    public function call($string)
    {
        $translations = Translations::fromPoString($string);

        $return = [];

        foreach ($translations as $translation) {
            $return[$translation->getContext()] = $translation->getTranslation();
        }

        return $return;
    }
}
