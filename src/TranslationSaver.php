<?php


namespace Armandsar\LaravelTranslationio;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\Translator;
use SplFileInfo;

class TranslationSaver
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
    }

    public function call($locale, $translationsDotted)
    {
        $translationsWithGroups = [];

        foreach ($translationsDotted as $key => $value) {
            if ($value !== '' && ! is_null($value)) {
                array_set($translationsWithGroups, $key, $value);
            }
        }

        foreach ($translationsWithGroups as $group => $translations) {
            $this->save($locale, $group, $translations);
        }
    }


    private function save($locale, $group, $translations)
    {
        $dir = $this->localePath($locale);

        if ( ! $this->filesystem->exists($dir)) {
            $this->filesystem->makeDirectory($dir);
        }

        $fileContent = <<<'EOT'
<?php
return {{translations}};
EOT;

        $fileContent = str_replace('{{translations}}', var_export($translations, true), $fileContent);

        $this->filesystem->put($dir . DIRECTORY_SEPARATOR . $group . '.php', $fileContent);
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
