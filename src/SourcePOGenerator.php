<?php

namespace Tio\Laravel;

use Gettext\Generators\Po;
use Gettext\Translation;
use Gettext\Translations;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;

class SourcePOGenerator
{
    /**
     * @var Application
     */
    private $application;
    /**
     * @var TranslationExtractor
     */
    private $extractor;

    public function __construct(Application $application, TranslationExtractor $extractor)
    {
        $this->application = $application;
        $this->extractor = $extractor;
    }

    public function call($locale)
    {
        return $this->poData($this->extractor->call($locale));
    }

    private function poData($entries)
    {
        $translations = new Translations();
        $translations->deleteHeaders();

        foreach ($entries as $key => $value) {
            $translations[] = new Translation($key, $value);
        }

        $po = Po::toString($translations);

        return $po;
    }
}
