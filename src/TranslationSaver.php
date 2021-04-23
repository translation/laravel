<?php

namespace Tio\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Tio\Laravel\PrettyVarExport;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\Translator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
    /**
     * @var PrettyVarExport
     */
    private $prettyVarExport;

    public function __construct(
        Application $application,
        FileSystem $fileSystem,
        PrettyVarExport $prettyVarExport
    )
    {
        $this->application = $application;
        $this->filesystem = $fileSystem;
        $this->prettyVarExport = $prettyVarExport;
    }

    public function call($locale, $translationsDotted)
    {
        # the content of the localePath will be recreated from scratch
        $this->filesystem->deleteDirectory($this->localePath($locale));

        $translationsWithGroups = [];

        foreach ($translationsDotted as $key => $value) {
            if ($value !== '' && ! is_null($value)) {
                Arr::set($translationsWithGroups, $key, $value);
            }
        }

        foreach ($translationsWithGroups as $group => $translations) {
            $this->save($locale, $group, $translations);
        }
    }


    private function save($locale, $group, $translations)
    {
        $dir = $this->localePath($locale);

        // Adapt $group and $dir if key contains subfolders:
        // https://laravel.io/forum/02-23-2015-localization-load-files-from-subdirectories-at-resourceslanglocale
        if (Str::contains($group, '/')) {
            $subFolders = explode('/', $group);
            $group = array_pop($subFolders);
            $dir = join(DIRECTORY_SEPARATOR, array_merge([$dir], $subFolders));
        }

        $this->filesystem->makeDirectory($dir, 0777, true, true);

        if (class_exists('Themsaid\Langman\Manager')) {
            $manager = new \Themsaid\Langman\Manager(new Filesystem, '', [], []);
            $manager->writeFile($dir . DIRECTORY_SEPARATOR . $group . '.php', $translations);
        } else {
            $fileContent = <<<'EOT'
<?php
return {{translations}};
EOT;

            $prettyTranslationsExport = $this->prettyVarExport->call($translations, ['array-align' => true]);
            $fileContent = str_replace('{{translations}}', $prettyTranslationsExport, $fileContent);

            $this->filesystem->put($dir . DIRECTORY_SEPARATOR . $group . '.php', $fileContent);
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
