<?php

namespace Tio\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\Translator;
use Illuminate\Support\Arr;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class TranslationExtractor
{
    /**
     * @var Application
     */
    private $application;
    /**
     * @var Filesystem
     */
    private $fileSystem;
    /**
     * @var Translator
     */
    private $translator;

    private $config;

    public function __construct(
        Application $application,
        FileSystem $fileSystem,
        Translator $translator
    )
    {
        $this->application = $application;
        $this->fileSystem = $fileSystem;
        $this->translator = $translator;
        $this->config = $application['config']['translation'];
    }

    public function call($locale)
    {
        $path = $this->localePath($locale);

        if ( ! $this->fileSystem->exists($path)) {
            return [];
        }

        $files = iterator_to_array(
            Finder::create()->files()->ignoreDotFiles(true)->in($path),
            false
        );

        sort($files);

        $translations = collect($files)->map(function (SplFileInfo $file) use ($locale) {
            $group = $file->getBasename('.' . $file->getExtension());
            $relativePath = $file->getRelativePath();

            $data = Arr::dot([
                $group => $this->translator->getLoader()->load($locale, $relativePath . DIRECTORY_SEPARATOR . $group)
            ]);

            $data = $this->addSubfolderToKeys($relativePath, $data);

            return collect($data)->filter();
        })->collapse()->toArray();

        $translations = $this->rejectIgnoredKeyPrefixes($translations);

        return $translations;
    }

    // if subfolder translation file, add $relativePath/ in front of each key
    // (https://laravel.io/forum/02-23-2015-localization-load-files-from-subdirectories-at-resourceslanglocale)
    private function addSubfolderToKeys($relativePath, $data)
    {
        if ($relativePath != '') {
            $newData = [];

            foreach ($data as $key => $value) {
                $newData[$relativePath . '/' . $key] = $value;
            }

            return $newData;
        }
        else {
            return $data;
        }
    }

    public function rejectIgnoredKeyPrefixes($translations)
    {
        $newTranslations = [];

        foreach ($translations as $key => $value) {
            if ( ! $this->ignoredKey($key)) {
                $newTranslations[$key] = $value;
            }
        }

        return $newTranslations;
    }

    private function ignoredKey($key)
    {
        $result = false;

        foreach ($this->ignoredKeyPrefixes() as $ignoredKeyPrefix) {
            if (preg_match("/^" . preg_quote($ignoredKeyPrefix, '/') . "\b/", $key)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    private function ignoredKeyPrefixes() {
      if (array_key_exists('ignored_key_prefixes', $this->config)) {
          return $this->config['ignored_key_prefixes'];
      }
      else {
          return [];
      }
    }

    private function localePath($locale)
    {
        return $this->path() . DIRECTORY_SEPARATOR . $locale;
    }

    private function path()
    {
        return $this->application['path.lang'];
    }
}
