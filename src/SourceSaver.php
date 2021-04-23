<?php

namespace Tio\Laravel;

use Tio\Laravel\PrettyVarExport;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class SourceSaver
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

    public function call($sourceEdit, $sourceLocale)
    {
        $key = $sourceEdit['key'];

        $dir = $this->localePath($sourceLocale);

        // Adapt $group and $dir if the key contains subfolders:
        // https://laravel.io/forum/02-23-2015-localization-load-files-from-subdirectories-at-resourceslanglocale
        if (Str::contains($key, '/')) {
            $subFolders = explode('/', $key);
            array_pop($subFolders);
            $dir = join(DIRECTORY_SEPARATOR, array_merge([$dir], $subFolders));
        }

        $this->filesystem->makeDirectory($dir, 0777, true, true);

        $group = $this->group($sourceEdit['key']);

        $groupFile = $dir . DIRECTORY_SEPARATOR . $group . '.php';

        if ($this->filesystem->exists($groupFile)) {
            $translations = $this->filesystem->getRequire($groupFile);

            $translations = $this->applySourceEditInTranslations($translations, $sourceEdit);

            if (class_exists('Themsaid\Langman\Manager')) {
                $manager = new \Themsaid\Langman\Manager(new Filesystem, '', [], []);
                $manager->writeFile($groupFile, $translations);
            } else {
                $fileContent = <<<'EOT'
<?php
return {{translations}};
EOT;

                $prettyTranslationsExport = $this->prettyVarExport->call($translations, ['array-align' => true]);
                $fileContent = str_replace('{{translations}}', $prettyTranslationsExport, $fileContent);

                $this->filesystem->put($groupFile, $fileContent);
            }
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

    private function group($key)
    {
        $foldersAndGroup = explode('.', $key)[0];

        if (Str::contains($foldersAndGroup, '/')) {
            $parts = explode('/', $foldersAndGroup);
            return array_pop($parts);
        }
        else {
            return $foldersAndGroup;
        }
    }

    private function keys($key)
    {
        $keyParts = explode('.', $key);
        array_shift($keyParts); // remove group part
        return $keyParts;
    }

    private function applySourceEditInTranslations($translations, $sourceEdit)
    {
        $keys = $this->keys($sourceEdit['key']);
        $oldText = $sourceEdit['old_text'];
        $newText = $sourceEdit['new_text'];

        $current = &$translations;

        for ($i = 0; $i < count($keys) - 1; $i++) {
            $key = $keys[$i];
            $current = &$current[$key];
        }

        if (isset($current[$keys[count($keys) - 1]]) && $current[$keys[count($keys) - 1]] == $oldText) {
            $current[$keys[count($keys) - 1]] = $newText;
        }

        return $translations;
    }
}
