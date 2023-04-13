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

    private $config;

    public function __construct(
        Application $application,
        FileSystem $fileSystem
    ) {
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
            $translationsPerJsonPaths = [];

            foreach ($jsonTranslations as $jsonTranslation) {
                if ($jsonTranslation->getContext() == $this->jsonStringContext()) {
                    // Default JSON path
                    $jsonPath = $this->application['path.lang'];
                }
                else {
                    // Custom JSON path (added with `$loader->addJsonPath()`)
                    $jsonPath = explode(']', explode('[', $jsonTranslation->getContext(), 2)[1])[0];
                    $jsonPath = $this->application->basePath($jsonPath);
                }

                // Ignore empty translations (empty is like no string)
                if (strlen($jsonTranslation->getTranslation()) > 0) {
                    $translationsPerJsonPaths[$jsonPath][$jsonTranslation->getOriginal()] = $jsonTranslation->getTranslation();
                }
            }

            foreach ($translationsPerJsonPaths as $jsonPath => $translations) {
                $jsonFile = $jsonPath . DIRECTORY_SEPARATOR . $locale . '.json';
                file_put_contents($jsonFile, json_encode($translations, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }
        }
    }

    private function loadTranslationsFromPoContent($poContent)
    {
        // Create Temporary path (to create po files and be able to load them in memory)
        $tmpDir = $this->tmpPath();
        $tmpFile = $tmpDir . DIRECTORY_SEPARATOR . 'app.po';
        $this->filesystem->makeDirectory($tmpDir, 0777, true, true);

        $this->filesystem->put($tmpFile, $poContent);

        $translations = Translations::fromPoFile($tmpFile);

        $this->filesystem->delete($tmpFile);

        return $translations;
    }

    private function extractGettextTranslations($translations)
    {
        $gettextTranslations = clone $translations;
        $keysToRemove = [];

        // only keep non-JSON translations
        foreach ($gettextTranslations as $key => $gettextTranslation) {
            $context = $gettextTranslation->getContext();

            if ($this->startsWith($context, $this->jsonStringContext())) {
                $keysToRemove[] = $key;
            }
        }

        foreach ($keysToRemove as $keyToRemove) {
            unset($gettextTranslations[$keyToRemove]);
        }

        return $gettextTranslations;
    }

    private function extractJsonTranslations($translations)
    {
        $jsonTranslations = clone $translations;
        $keysToRemove = [];

        // only keep JSON translations
        foreach ($jsonTranslations as $key => $jsonTranslation) {
            $context = $jsonTranslation->getContext();

            if (! $this->startsWith($context, $this->jsonStringContext())) {
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
            return base_path($this->config['gettext_locales_path']);
        }
        else {
            // Default values if not present in config file
            return $this->application['path.lang'] . DIRECTORY_SEPARATOR . 'gettext';
        }
    }

    private function tmpPath()
    {
        return $this->storagePath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'tmp';
    }

    private function storagePath()
    {
        return $this->application['path.storage'];
    }

    private function jsonStringContext()
    {
        return 'Extracted from JSON file';
    }

    // Because "Str::starsWith()" is only Laravel 5.7+
    // https://stackoverflow.com/a/7168986/1243212
    function startsWith($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}
