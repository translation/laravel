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

            file_put_contents($this->jsonPath($locale), json_encode($jsonArray, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
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
        $keysToRemove = [];

        // only keep non-JSON translations
        foreach ($gettextTranslations as $key => $gettextTranslation) {
            if ($gettextTranslation->getContext() == $this->jsonStringContext()) {
                $keysToRemove[] = $key;
            }
        }

        foreach ($keysToRemove as $keyToRemove) {
            unset($gettextTranslations[$keyToRemove]);
        }

        return $gettextTranslations;
    }

    private function extractJsonTranslations($translations) {
        $jsonTranslations = clone $translations;
        $keysToRemove = [];

        // only keep JSON translations
        foreach ($jsonTranslations as $key => $jsonTranslation) {
            if ($jsonTranslation->getContext() != $this->jsonStringContext()) {
                $keysToRemove[] = $key;
            }
        }

        foreach ($keysToRemove as $keyToRemove) {
            unset($jsonTranslations[$keyToRemove]);
        }

        return $jsonTranslations;
    }

    private function localePath($locale)
    {
        return $this->gettextLocalesPath() . DIRECTORY_SEPARATOR . $locale;
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
