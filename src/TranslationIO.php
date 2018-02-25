<?php

namespace Armandsar\LaravelTranslationio;

use Illuminate\Support\Facades\App;
use Gettext\GettextTranslator;

class TranslationIO
{
    private $locale;
    private $config = array();

    public function __construct($config = null)
    {
        $this->config = $config ?? config('translationio');
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;

        $this->setLaravelLocale($locale);
        $this->setServerLocale($locale . '.UTF-8');
        $this->loadGettextForLocale($locale . '.UTF-8');
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function getTranslator()
    {
        return $this->translator;
    }

    # Define Laravel locale: __("") with keys
    private function setLaravelLocale($locale)
    {
        App::setLocale($locale); # LaravelApp::setLocale(substr($locale, 0, 2));
    }

    private function setServerLocale($localeWithEncoding)
    {
        # IMPORTANT: locale must be installed in server!
        # sudo locale-gen es_ES.UTF-8
        # sudo update-locale
        putenv('LANG='.$localeWithEncoding);
        putenv('LANGUAGE='.$localeWithEncoding);
        putenv('LC_MESSAGES='.$localeWithEncoding);
        putenv('LC_PAPER='.$localeWithEncoding);
        putenv('LC_TIME='.$localeWithEncoding);
        putenv('LC_MONETARY='.$localeWithEncoding);

        if (defined('LC_MESSAGES')) {
            setlocale(LC_MESSAGES, $localeWithEncoding);
        }
        if (defined('LC_COLLATE')) {
            setlocale(LC_COLLATE, $localeWithEncoding);
        }
        if (defined('LC_TIME')) {
            setlocale(LC_TIME, $localeWithEncoding);
        }
        if (defined('LC_MONETARY')) {
            setlocale(LC_MONETARY, $localeWithEncoding);
        }
        if (!defined('LC_MESSAGES') && !defined('LC_COLLATE') && !defined('LC_TIME') && !defined('LC_MONETARY')) {
            setlocale(LC_ALL, $localeWithEncoding);
        }
    }

    private function loadGettextForLocale($localeWithEncoding)
    {
        $translator = new GettextTranslator();

        $translator->setLanguage($localeWithEncoding);
        $translator->loadDomain('app', base_path($this->config['gettext_locales_path']));

        bind_textdomain_codeset('app', 'UTF-8');

        $this->translator = $translator;
    }
}
