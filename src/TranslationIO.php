<?php

namespace Tio\Laravel;

use Illuminate\Support\Facades\App;
use Gettext\Translator;
use Gettext\Translations;
use Illuminate\Filesystem\Filesystem;

class TranslationIO
{
    private $locale;
    private $config = array();

    public static $current;

    public function __construct($config = null)
    {
        $this->config = $config ?? config('translation');
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;

        $this->setLaravelLocale($locale);
        $this->loadGettextForLocale($locale);
    }

    public function getLocale()
    {
        return $this->locale;
    }

    # Define Laravel locale: __("") with keys
    private function setLaravelLocale($locale)
    {
        App::setLocale($locale); # LaravelApp::setLocale(substr($locale, 0, 2));
    }

    private function loadGettextForLocale($locale)
    {
        $translator = new Translator();
        $moPath = $this->moPath($locale);

        if (file_exists($moPath)) {
            $translations = Translations::fromMoFile($moPath);
            $translator->loadTranslations($translations);
        }

        self::$current = $translator;

        static::includeFunctions();
    }

    # MO file is ~2 times faster than PO and PHP files
    private function moPath($locale)
    {
        return $this->gettextLocalesPath() . '/'. $locale .'/LC_MESSAGES/app.mo';
    }

    private function gettextLocalesPath()
    {
        if (array_key_exists('gettext_locales_path', $this->config)) {
            $gettextLocalesPath = $this->config['gettext_locales_path'];
        }
        else {
            // Default values if not present in config file
            $gettextLocalesPath = 'resources/lang/gettext';
        }

        return base_path($gettextLocalesPath);
    }

    public static function includeFunctions()
    {
        include_once __DIR__.'/Helpers/translator_functions.php';
    }
}
