<?php

namespace Tio\Laravel;

use Gettext\Translator;

class GettextTranslator extends Translator
{
    public function __construct()
    {
        $this->defaultDomain('');
    }

    protected function getTranslation($domain, $context, $original)
    {
        $domain  = $domain ?? '';
        $context = $context ?? '';

        return parent::getTranslation($domain, $context, $original);
    }

    protected function getPluralIndex($domain, $n, $fallback)
    {
        $domain = $domain ?? '';

        return parent::getPluralIndex($domain, $n, $fallback);
    }
}
