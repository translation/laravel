<?php

namespace Tio\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Tio\Laravel\PrettyVarExport;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\Translator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

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

    private $config;

    public function __construct(
        Application $application,
        FileSystem $fileSystem,
        PrettyVarExport $prettyVarExport
    ) {
        $this->application = $application;
        $this->filesystem = $fileSystem;
        $this->prettyVarExport = $prettyVarExport;
        $this->config = $application['config']['translation'];
    }

    public function call($locale, $translationsDotted)
    {
        // Snapshot keys that belong to ignored prefixes before wiping the directory,
        // so they can be restored after the rebuild (they are never sent to translation.io
        // and therefore never appear in the API response).
        $ignoredKeysDotted = $this->snapshotIgnoredKeys($locale);

        // the content of the localePath will be recreated from scratch
        $this->filesystem->deleteDirectory($this->localePath($locale));

        $translationsWithGroups = [];

        foreach ($translationsDotted as $key => $value) {
            if ($value !== '' && ! is_null($value)) {
                Arr::set($translationsWithGroups, $key, $value);
            }
        }

        // Merge the ignored keys back in so that they survive the rebuild.
        foreach ($ignoredKeysDotted as $key => $value) {
            if ($value !== '' && ! is_null($value)) {
                Arr::set($translationsWithGroups, $key, $value);
            }
        }

        foreach ($translationsWithGroups as $group => $translations) {
            $this->save($locale, $group, $translations);
        }
    }

    /**
     * Read all translation files in the locale directory and collect only the
     * key/value pairs whose dotted key matches an ignored_key_prefix.  These
     * keys are never pushed to translation.io, so we must preserve them
     * ourselves across the delete-and-rebuild cycle.
     *
     * @param  string $locale
     * @return array  Flat dotted array of ignored key => value pairs.
     */
    private function snapshotIgnoredKeys($locale)
    {
        $ignoredPrefixes = $this->ignoredKeyPrefixes();

        if (empty($ignoredPrefixes)) {
            return [];
        }

        $path = $this->localePath($locale);

        if (! $this->filesystem->exists($path)) {
            return [];
        }

        $files = iterator_to_array(
            Finder::create()->files()->ignoreDotFiles(true)->in($path),
            false
        );

        $ignoredKeysDotted = [];

        foreach ($files as $file) {
            $group        = $file->getBasename('.' . $file->getExtension());
            $relativePath = $file->getRelativePath();

            // Build the dotted key prefix used inside this file:
            // subfolder/admin.php → group key prefix "subfolder/admin"
            $groupKeyPrefix = $relativePath !== ''
                ? $relativePath . '/' . $group
                : $group;

            // Normalise directory separators so Windows paths still match.
            $groupKeyPrefix = str_replace(DIRECTORY_SEPARATOR, '/', $groupKeyPrefix);

            $fileTranslations = Arr::dot(
                [$group => (array) require $file->getRealPath()]
            );

            foreach ($fileTranslations as $key => $value) {
                // Reconstruct the full dotted key as it would appear in
                // the extractor (subfolder prefix + dotted key).
                $fullKey = $relativePath !== ''
                    ? str_replace(DIRECTORY_SEPARATOR, '/', $relativePath) . '/' . $key
                    : $key;

                if ($this->isIgnoredKey($fullKey, $ignoredPrefixes)) {
                    $ignoredKeysDotted[$fullKey] = $value;
                }
            }
        }

        return $ignoredKeysDotted;
    }

    /**
     * Returns true when $key matches any of the given ignored prefixes,
     * using the same word-boundary regex as TranslationExtractor.
     */
    private function isIgnoredKey($key, array $ignoredPrefixes)
    {
        foreach ($ignoredPrefixes as $prefix) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '\b/', $key)) {
                return true;
            }
        }

        return false;
    }

    private function ignoredKeyPrefixes()
    {
        if (array_key_exists('ignored_key_prefixes', $this->config)) {
            return $this->config['ignored_key_prefixes'];
        }

        return [];
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

        // Leave the extra newline at the end
        $fileContent = <<<'EOT'
<?php

return {{translations}};

EOT;

        $prettyTranslationsExport = $this->prettyVarExport->call($translations, ['array-align' => true]);
        $fileContent = str_replace('{{translations}}', $prettyTranslationsExport, $fileContent);

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
