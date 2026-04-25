<?php

namespace Tio\Laravel;

use Gettext\Translator;

class GettextTranslator extends Translator
{
    protected function getTranslation($domain, $context, $original)
    {
        $context = $context ?? '';

        return parent::getTranslation($domain, $context, $original);
    }
}
