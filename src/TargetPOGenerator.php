<?php

namespace Tio\Laravel;

use Gettext\Generators\Po;
use Gettext\Translation;
use Gettext\Translations;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;

class TargetPOGenerator
{
    /**
     * @var Application
     */
    private $application;
    /**
     * @var TranslationExtractor
     */
    private $extractor;

    /**
     * @var Collection
     */
    private $sourceEntries;

    /**
     * @var Collection
     */
    private $sourceEntryKeys;

    public function __construct(Application $application, TranslationExtractor $extractor)
    {
        $this->application = $application;
        $this->extractor = $extractor;
    }

    public function call($source, $targets)
    {
        $this->sourceEntries = collect($this->extractor->call($source));
        $this->sourceEntryKeys = $this->sourceEntries->keys();

        $targetEntries = $this->targetEntries($targets)->map(function ($localeEntries) {
            $validEntries = [];

            # previously used filter($key, $value) but not compatible with Laravel 5.1
            collect($localeEntries)->each(function ($value, $key) use (&$validEntries) {
                if ($this->sourceEntryKeys->contains($key)) {
                    $validEntries[$key] = $value;
                }
            });

            $validEntries = collect($validEntries);

            // Source keys that are not translated are created with empty translation
            $this->sourceEntryKeys->diff($validEntries->keys()->all())
                ->each(function ($key) use (&$validEntries) {
                    $validEntries->put($key, "");
                });

            return $validEntries->all();
        });

        return $targetEntries->map(function ($entries) {
            return $this->poData($entries);
        })->all();
    }

    private function poData($entries)
    {
        $translations = new Translations();
        $translations->deleteHeaders();

        foreach ($entries as $key => $value) {
            $original = $this->sourceEntries->get($key);
            $translation = new Translation($key, $original);
            $translation->setTranslation($value);
            $translations[] = $translation;
        }

        $po = Po::toString($translations);

        return $po;
    }

    private function targetEntries($targets)
    {
        return collect($targets)
            ->keyBy(function ($locale) {
                return $locale;
            })->map(function ($locale) {
                return $this->extractor->call($locale);
            });
    }
}
