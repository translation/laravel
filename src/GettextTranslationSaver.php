<?php

namespace Tio\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Gettext\Translations;

class GettextTranslationSaver
{
    /**
     * @var Application
     */
    private $application;
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        Application $application,
        FileSystem $fileSystem
    )
    {
        $this->application = $application;
        $this->filesystem = $fileSystem;
        $this->config = $application['config']['translation'];
    }

    public function call($locale, $poContent)
    {
        $localeDir = $this->localePath($locale);
        $lcMessagesDir = $localeDir . DIRECTORY_SEPARATOR . 'LC_MESSAGES';
        $poFile = $localeDir . DIRECTORY_SEPARATOR . 'app.po';
        $moFile = $lcMessagesDir . DIRECTORY_SEPARATOR . 'app.mo';

        $translations = $this->loadTranslationsFromPoContent($poContent);
        $gettextTranslations = $this->extractGettextTranslations($translations);
        $jsonTranslations = $this->extractJsonTranslations($translations);

        // Save PO files
        if (count($gettextTranslations) > 0) {
            $this->filesystem->makeDirectory($localeDir, 0777, true, true);
            $this->filesystem->makeDirectory($lcMessagesDir, 0777, true, true);

            // Save to po file
            $gettextTranslations->toPoFile($poFile);

            // Save to mo files
            $gettextTranslations->toMoFile($moFile);
        }

        // Save JSON files
        if (count($jsonTranslations) > 0) {
            $jsonArray = [];

            foreach ($jsonTranslations as $jsonTranslation) {
                $jsonArray[$jsonTranslation->getOriginal()] = $jsonTranslation->getTranslation();
            }

            file_put_contents($this->jsonPath($locale), json_encode($jsonArray, JSON_PRETTY_PRINT));
        }
    }

    private function loadTranslationsFromPoContent($poContent) {
        // Create Temporary path (to create po files and be able to load them in memory)
        $tmpDir = $this->tmpPath();
        $tmpFile = $tmpDir . DIRECTORY_SEPARATOR . 'app.po';
        $this->filesystem->makeDirectory($tmpDir, 0777, true, true);

        $this->filesystem->put($tmpFile, $poContent);

        $translations = Translations::fromPoFile($tmpFile);

        $this->filesystem->delete($tmpFile);

        return $translations;
    }

    private function extractGettextTranslations($translations) {
        $gettextTranslations = clone $translations;
        $gettextTranslationsCollection = collect($gettextTranslations);

        // only keep non-JSON translations
        $gettextTranslationsCollection = $gettextTranslationsCollection->filter(function ($gettextTranslation) {
            return $gettextTranslation->getContext() != $this->jsonStringContext();
        });

        $ts = new Translations();

        foreach ($gettextTranslationsCollection->all() as $t) {
            $ts[] = $t;
        }

        return $ts;
    }

    private function extractJsonTranslations($translations) {
        $jsonTranslations = clone $translations;
        $jsonTranslationsCollection = collect($jsonTranslations);

        // only keep JSON translations
        $jsonTranslationsCollection = $jsonTranslationsCollection->filter(function ($jsonTranslation) {
            return $jsonTranslation->getContext() == $this->jsonStringContext();
        });

        $ts = new Translations();

        foreach ($jsonTranslationsCollection->all() as $t) {
            $ts[] = $t;
        }

        return $ts;
    }

    private function localePath($locale)
    {
        return $this->gettextPath() . DIRECTORY_SEPARATOR . $locale;
    }

    private function gettextPath()
    {
        return base_path($this->config['gettext_locales_path']);
    }

    private function jsonPath($locale)
    {
        return $this->application['path.lang'] . DIRECTORY_SEPARATOR . $locale . '.json';
    }

    private function tmpPath()
    {
        return $this->storagePath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'tmp';
    }

    private function storagePath()
    {
        return $this->application['path.storage'];
    }

    private function jsonStringContext() {
        return 'Extracted from JSON file';
    }
}
