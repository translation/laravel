<?php


namespace Armandsar\LaravelTranslationio;

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
            $valid = collect(array_flip(array_filter(array_flip($localeEntries), function ($key) {
                return $this->sourceEntryKeys->contains($key);
            })));

            $this->diffKeys($this->sourceEntryKeys->all(), $valid->keys()->all())
                ->each(function ($key) use (&$valid) {
                    $valid->put($key, "");
                });

            return $valid->all();
        });

        return $targetEntries->map(function ($entries) {
            return $this->poData($entries);
        })->all();
    }

    private function diffKeys($arr1, $arr2)
    {
        return collect(array_diff_key($arr1, $arr2));
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
