<?php


namespace Armandsar\LaravelTranslationio;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\Translator;
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

    public function __construct(
        Application $application,
        FileSystem $fileSystem,
        Translator $translator
    )
    {
        $this->application = $application;
        $this->fileSystem = $fileSystem;
        $this->translator = $translator;
    }

    public function call($locale)
    {
        $path = $this->localePath($locale);

        if ( ! $this->fileSystem->exists($path)) {
            return [];
        }

        $files = iterator_to_array(
            Finder::create()->files()->ignoreDotFiles(true)->in($path)->depth(0),
            false
        );

        return collect($files)->map(function (SplFileInfo $file) use ($locale) {
            $group = $file->getBasename('.' . $file->getExtension());
            $data = array_dot([
                $group => $this->translator->getLoader()->load($locale, $group)
            ]);

            $data = collect($data);

            return $data->filter();
        })->collapse()->toArray();
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
