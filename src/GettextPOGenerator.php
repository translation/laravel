<?php

namespace Tio\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
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
            if (!is_dir($dir)) {
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

            if (!strpos($name, '/.')) {
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
            $gettextLocalesPath = $this->config['gettext_locales_path'];
        }
        else {
            // Default values if not present in config file
            $gettextLocalesPath = 'resources/lang/gettext';
        }

        return base_path($gettextLocalesPath);
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
	        foreach (glob($path.DIRECTORY_SEPARATOR.'*.json') as $filename) {
		        $files[] = $filename;
	        }
        }

        return $files;
    }
    
    private function jsonPaths()
    {
	    $paths = $this->application['path.lang'];
	    
	    // The Translator interface doesn't require the use of 'loaders' so we'll
	    // check to make sure we have a Laravel Translator instance to be safe 
	    if ($this->translator instanceof Translator) {
		    $loader = $this->translator->getLoader();
		    if ($loader instanceof FileLoader) {
			    foreach ($loader->jsonPaths() as $path) {
				    $paths[] = $path;
			    }
		    }
	    }
	    
	    return $paths;
    }

    private function path()
    {
        return $this->application['path.lang'];
    }

    private function addStringsFromJsonFiles($translations)
    {
        $sourceStrings = [];

        // Load each JSON file to get source strings
        foreach ($this->JsonFiles() as $jsonFile) {
            $jsonTranslations = json_decode(file_get_contents($jsonFile), true);

            foreach ($jsonTranslations as $key => $value) {
                $sourceStrings[$key] = $jsonFile;
            }
        }

        // Insert them in $translations with a special context
        foreach ($sourceStrings as $sourceString => $sourceFile) {
            $translations->insert($this->jsonStringContext($sourceFile), $sourceString, '');
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
        $poPath      = $gettextPath . DIRECTORY_SEPARATOR . $target . DIRECTORY_SEPARATOR . 'app.po';

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

        $targetJsonFile = $this->application['path.lang'] . DIRECTORY_SEPARATOR . $target . '.json';

        if ($this->filesystem->exists($targetJsonFile)) {
            $targetJsonTranslations = json_decode(file_get_contents($targetJsonFile), true);

            foreach ($targetJsonTranslations as $key => $value) {
                $translation = new Translation($this->jsonStringContext($targetJsonFile), $key, '');
                $translation->setTranslation($value);
                $targetTranslations[] = $translation;
            }

            $translations->mergeWith(
                $targetTranslations,
                Merge::ADD || Merge::HEADERS_ADD || Merge::COMMENTS_OURS ||
                Merge::EXTRACTED_COMMENTS_OURS || Merge::FLAGS_OURS || Merge::REFERENCES_OURS
            );
        }

        return $translations;
    }

    private function jsonStringContext($sourceFile)
    {
    	$sourceFile = Str::of($sourceFile);
    	
	    return $sourceFile->contains($this->application['path.lang'])
			? 'Extracted from JSON file'
		    : 'Extracted from JSON file ['.$sourceFile->after($this->application->basePath()).']';
    }

    private function appName()
    {
        $appName = 'Laravel';

        if (config('app.name') != '') {
            $appName = config('app.name');
        }

        return $appName;
    }
}
