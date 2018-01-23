<?php


namespace Armandsar\LaravelTranslationio;

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
        $this->config = $application['config']['translationio'];
    }

    public function call($locale, $poContent)
    {
        $localeDir = $this->localePath($locale);
        $lcMessagesDir = $localeDir . DIRECTORY_SEPARATOR . 'LC_MESSAGES';
        $poFile = $localeDir . DIRECTORY_SEPARATOR . 'app.po';
        $moFile = $lcMessagesDir . DIRECTORY_SEPARATOR . 'app.mo';

        if ( ! $this->filesystem->exists($localeDir)) {
            $this->filesystem->makeDirectory($localeDir, 0777, true);
        }

        if ( ! $this->filesystem->exists($lcMessagesDir)) {
            $this->filesystem->makeDirectory($lcMessagesDir, 0777, true);
        }

        // Save to po file
        $this->filesystem->put($poFile, $poContent);

        // Save to mo files
        $translations = Translations::fromPoFile($poFile);
        $translations->toMoFile($moFile);
    }

    private function localePath($locale)
    {
        return $this->gettextPath() . DIRECTORY_SEPARATOR . $locale;
    }

    private function gettextPath()
    {
        return base_path($this->config['gettext_locales_path']);
    }
}
