<?php

namespace Armandsar\LaravelTranslationio;


use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Gettext\Extractors;
use Gettext\Translations;

class GettextPOGenerator
{
    /**
     * @var Application
     */
    private $application;
    /**
     * @var FileSystem
     */
    private $filesystem;

    private $config;

    public function __construct(Application $application, FileSystem $filesystem)
    {
        $this->application = $application;
        $this->filesystem = $filesystem;
        $this->config = $application['config']['translationio'];
    }

    public function call($source, $targets)
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

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                throw new \Exception('Folder "' . $dir . '" doest not exists. Gettext scan aborted.');
            }

            foreach ($this->scanDir($dir) as $file) {
                if (strstr($file, '.blade.php')) {
                    Extractors\Blade::fromFile($file, $translations);
                } elseif (strstr($file, '.php')) {
                    Extractors\PhpCode::fromFile($file, $translations);
                }
            }
        }

        # Create Temporary path (create po files to load them in memory)
        $tmpDir = $this->tmpPath();
        $tmpFile = $tmpDir . DIRECTORY_SEPARATOR . 'app.po';

        if ( ! $this->filesystem->exists($tmpDir)) {
            $this->filesystem->makeDirectory($tmpDir, 0777, true);
        }

        # Create pot file (will be kept in app structure)
        $potDir = $this->gettextLocalesPath();
        $potFile = $potDir . DIRECTORY_SEPARATOR . 'app.pot';

        if ( ! $this->filesystem->exists($potDir)) {
            $this->filesystem->makeDirectory($potDir, 0777, true);
        }

        # po(t) headers
        $this->setPoHeaders($translations);

        # If we want to load existing PO files on first init, do it here.

        // if File.exist?(po_path)
        //   GetText::Tools::MsgMerge.run(po_path, @pot_path, '-o', po_path, '--no-fuzzy-matching', '--no-obsolete-entries')
        // else
        //   FileUtils.mkdir_p(File.dirname(po_path))
        //   GetText::Tools::MsgInit.run('-i', @pot_path, '-o', po_path, '-l', target_locale, '--no-translator')
        // end

        # create pot data
        $translations->toPoFile($potFile);
        $poLocales = [
            'pot_data' => $this->filesystem->get($potFile)
        ];

        # create po data for each language
        foreach ($targets as $target) {
            $translations->setLanguage($target);
            $translations->toPoFile($tmpFile);
            $poLocales[$target] = $this->filesystem->get($tmpFile);
        }

        $this->filesystem->delete($tmpFile);

        return $poLocales;
    }

    private function gettextFunctionsToExtract()
    {
        Extractors\PhpCode::$options['functions'] = [
            'noop' => 'noop',
            'noop_' => 'noop',
            'noop__' => 'noop',

            'gettext' => 'gettext',
            '_' => 'gettext',
            'i_' => 'gettext',
            'i__' => 'gettext',

            'ngettext' => 'ngettext',
            'n_' => 'ngettext',
            'n__' => 'ngettext',

            'pgettext' => 'pgettext',
            'p_' => 'pgettext',
            'p__' => 'pgettext',

            'npgettext' => 'npgettext',
            'np_' => 'npgettext',
            'np__' => 'npgettext',
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
        return base_path($this->config['gettext_locales_path']);
    }

    private function gettextParsePaths()
    {
        if ($this->application->environment('testing')) {
            return ['tests/fixtures/gettext'];
        }
        else {
            return $this->config['gettext_parse_paths'];
        }
    }

    private function setPoHeaders($translations) {
      $translations->setHeader('Project-Id-Version', 'Name of application');
      $translations->setHeader('Report-Msgid-Bugs-To', 'contact@translation.io');
      $translations->setHeader("Plural-Forms", "nplurals=INTEGER; plural=EXPRESSION;");

      // only for testing (for VCR)
      if ($this->application->environment('testing')) {
        $translations->setHeader('POT-Creation-Date', '2018-01-01T12:00:00+00:00');
        $translations->setHeader("PO-Revision-Date",  "2018-01-02T12:00:00+00:00");
      }
    }
}
