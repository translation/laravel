<?php

namespace Tio\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Gettext\Extractors;
use Gettext\Translation;
use Gettext\Translations;
use Gettext\Merge;

class GettextPOGenerator
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
     * @var TranslatorContract
     */
    private $translator;

    private $config;

    public function __construct(Application $application, Filesystem $filesystem, TranslatorContract $translator)
    {
        $this->application = $application;
        $this->filesystem = $filesystem;
        $this->translator = $translator;
        $this->config = $application['config']['translation'];
    }

    public function call($targets)
    {
        $po_data = $this->scan($targets);

        return $po_data;
    }

    // https://github.com/eusonlito/laravel-Gettext/blob/170c284c9feb8cb6073149791bed02889d820b90/src/Eusonlito/LaravelGettext/Gettext.php#L73
    private function scan($targets)
    {
        $this->gettextFunctionsToExtract();

        $translations = new Translations();
        $directories = $this->gettextParsePaths();

        // Extract GetText strings from project
        foreach ($directories as $dir) {
            if (! is_dir($dir)) {
                throw new \Exception('Folder "' . $dir . '" does not exist. Gettext scan aborted.');
            }

            foreach ($this->scanDir($dir) as $file) {
                if (strstr($file, '.blade.php')) {
                    Extractors\Blade::fromFile($file, $translations);
                } elseif (strstr($file, '.php')) {
                    Extractors\PhpCode::fromFile($file, $translations);
                }
            }
        }

        // Extract strings from all language JSON files
        $this->addStringsFromJsonFiles($translations);

        // Create Temporary path (create po files to load them in memory)
        $tmpDir = $this->tmpPath();
        $tmpFile = $tmpDir . DIRECTORY_SEPARATOR . 'app.po';
        $this->filesystem->makeDirectory($tmpDir, 0777, true, true);

        // po(t) headers
        $this->setPoHeaders($translations);

        // Create pot data
        $translations->toPoFile($tmpFile);
        $poLocales = [
            'pot_data' => $this->filesystem->get($tmpFile)
        ];

        // Create po data for each language
        foreach ($targets as $target) {
            $targetTranslations = clone $translations;
            $targetTranslations->setLanguage($target);

            $targetTranslations = $this->mergeWithExistingTargetPoFile($targetTranslations, $target);
            $targetTranslations = $this->mergeWithExistingStringsFromTargetJsonFile($targetTranslations, $target);

            $targetTranslations->toPoFile($tmpFile);
            $poLocales[$target] = $this->filesystem->get($tmpFile);
        }

        $this->filesystem->delete($tmpFile);

        return $poLocales;
    }

    private function gettextFunctionsToExtract()
    {
        Extractors\PhpCode::$options['functions'] = [
            'noop' => 'noop',
            't'    => 'gettext',
            'n'    => 'ngettext',
            'p'    => 'pgettext',
            'np'   => 'npgettext'
        ];
    }

    private function scanDir($dir)
    {
        $directory = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);
        $files = array();

        foreach ($iterator as $fileinfo) {
            $name = $fileinfo->getPathname();

            if (! strpos($name, '/.')) {
                $files[] = $name;
            }
        }

        return $files;
    }

    private function tmpPath()
    {
        return $this->storagePath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'tmp';
    }

    private function storagePath()
    {
        return $this->application['path.storage'];
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

    private function gettextParsePaths()
    {
        if (array_key_exists('gettext_parse_paths', $this->config)) {
            return $this->config['gettext_parse_paths'];
        }
        else {
            // Default values if not present in config file
            return ['app', 'resources'];
        }
    }

    private function jsonFiles()
    {
        $files = [];

        foreach ($this->jsonPaths() as $path) {
            foreach (glob($path . DIRECTORY_SEPARATOR . '*.json') as $filename) {
                $files[] = $filename;
            }
        }

        return $files;
    }

    private function jsonPaths()
    {
        $paths = [$this->application['path.lang']];

        // The Translator interface doesn't require the use of 'loaders' so we'll
        // check to make sure we have a Laravel Translator instance to be safe
        if ($this->translator instanceof Translator) {
            $loader = $this->translator->getLoader();

            if ($loader instanceof FileLoader) {
                // $loader->jsonPaths() getter doesn't exist before Laravel 8 so we use this trick to access the protected value.
                // cf. https://stackoverflow.com/a/3475714/1243212
                $rp = new \ReflectionProperty('Illuminate\Translation\FileLoader', 'jsonPaths');
                $rp->setAccessible(true);
                $jsonPaths = $rp->getValue($loader);

                foreach ($jsonPaths as $path) {
                    $paths[] = $path;
                }
            }
        }

        return $paths;
    }

    private function addStringsFromJsonFiles($translations)
    {
        $sourceStrings = [];

        // Load each JSON file to get source strings
        foreach ($this->JsonFiles() as $jsonFile) {
            if ($this->validJsonFile($jsonFile)) {
                $jsonTranslations = json_decode(file_get_contents($jsonFile), true);
                $jsonPath = dirname($jsonFile);

                foreach ($jsonTranslations as $key => $value) {
                    $sourceStrings[$key] = $jsonPath;
                }
            }
        }

        // Insert them in $translations with a special context
        foreach ($sourceStrings as $sourceString => $jsonPath) {
            $translations->insert($this->jsonStringContext($jsonPath), $sourceString, '');
        }
    }

    private function setPoHeaders($translations)
    {
        $translations->setHeader('Project-Id-Version', $this->appName());
        $translations->setHeader('Report-Msgid-Bugs-To', 'contact@translation.io');
        $translations->setHeader("Plural-Forms", "nplurals=INTEGER; plural=EXPRESSION;");

        // Only for testing (for VCR)
        if ($this->application->environment('testing')) {
            $translations->setHeader('POT-Creation-Date', '2018-01-01T12:00:00+00:00');
            $translations->setHeader("PO-Revision-Date",  "2018-01-02T12:00:00+00:00");
        }
    }

    private function mergeWithExistingTargetPoFile($translations, $target)
    {
        $gettextPath = $this->gettextLocalesPath();
        $poPath = $gettextPath . DIRECTORY_SEPARATOR . $target . DIRECTORY_SEPARATOR . 'app.po';

        if ($this->filesystem->exists($poPath)) {
            $existingTargetTranslations = Translations::fromPoFile($poPath);

            $translations->mergeWith(
                $existingTargetTranslations,
                Merge::ADD || Merge::HEADERS_ADD || Merge::COMMENTS_OURS ||
                Merge::EXTRACTED_COMMENTS_OURS || Merge::FLAGS_OURS || Merge::REFERENCES_OURS
            );
        }

        return $translations;
    }

    private function mergeWithExistingStringsFromTargetJsonFile($translations, $target)
    {
        $targetTranslations = new Translations();
        $targetTranslations->setLanguage($target);

        $jsonFiles = collect($this->jsonFiles());

        $targetJsonFiles = $jsonFiles->filter(
            function ($jsonFile) use ($target) {
                return $this->endsWith($jsonFile, $target . '.json');
            }
        );

        foreach ($targetJsonFiles as $targetJsonFile) {
            if ($this->filesystem->exists($targetJsonFile) && $this->validJsonFile($targetJsonFile)) {
                $targetJsonTranslations = json_decode(file_get_contents($targetJsonFile), true);

                foreach ($targetJsonTranslations as $key => $value) {
                    $jsonPath = dirname($targetJsonFile);
                    $translation = new Translation($this->jsonStringContext($jsonPath), $key, '');
                    $translation->setTranslation($value);
                    $targetTranslations[] = $translation;
                }

                $translations->mergeWith(
                    $targetTranslations,
                    Merge::ADD || Merge::HEADERS_ADD || Merge::COMMENTS_OURS ||
                    Merge::EXTRACTED_COMMENTS_OURS || Merge::FLAGS_OURS || Merge::REFERENCES_OURS
                );
            }
        }

        return $translations;
    }

    private function jsonStringContext($path)
    {
        if ($path == $this->application['path.lang']) {
            // Default JSON path
            return 'Extracted from JSON file';
        }
        else {
            // Custom JSON path (added with `$loader->addJsonPath()`)
            $relativePath = substr($path, strpos($path, $this->application->basePath() . '/') + strlen($this->application->basePath()) + 1);
            return 'Extracted from JSON file [' . $relativePath . ']';
        }
    }

    private function appName()
    {
        $appName = 'Laravel';

        if (config('app.name') != '') {
            $appName = config('app.name');
        }

        return $appName;
    }

    // Because "Str::endsWith()" is only Laravel 5.7+
    // https://stackoverflow.com/a/10473026/1243212
    private function endsWith($haystack, $needle)
    {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }

    // To explain to the user why the JSON file is not valid
    // Based on https://stackoverflow.com/a/15198925/1243212
    private function validJsonFile($fileName)
    {
        $result = json_decode(file_get_contents($fileName), true);

        switch (json_last_error()) {
        case JSON_ERROR_NONE:
            $error = ''; // JSON is valid
            break;
        case JSON_ERROR_DEPTH:
            $error = 'The maximum stack depth has been exceeded.';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $error = 'Invalid or malformed JSON.';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $error = 'Control character error, possibly incorrectly encoded.';
            break;
        case JSON_ERROR_SYNTAX:
            $error = 'Syntax error, malformed JSON.';
            break;
            // PHP >= 5.3.3
        case JSON_ERROR_UTF8:
            $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
            break;
            // PHP >= 5.5.0
        case JSON_ERROR_RECURSION:
            $error = 'One or more recursive references in the value to be encoded.';
            break;
            // PHP >= 5.5.0
        case JSON_ERROR_INF_OR_NAN:
            $error = 'One or more NAN or INF values in the value to be encoded.';
            break;
        case JSON_ERROR_UNSUPPORTED_TYPE:
            $error = 'A value of a type that cannot be encoded was given.';
            break;
        default:
            $error = 'Unknown JSON error occured.';
            break;
        }

        if ($error !== '') {
            exit('Error: ' . $error . "\nYour JSON file \"" . $fileName . '" is not valid.' . "\nPlease fix it and try again, or reach contact@translation.io if you need help.");
        }
        else {
            return true; // Json is valid
        }
    }
}
