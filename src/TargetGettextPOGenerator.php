<?php


namespace Armandsar\LaravelTranslationio;


use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Gettext\Extractors;
use Gettext\Generators;
use Gettext\Translations;

class TargetGettextPOGenerator
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
        Extractors\PhpCode::$options['functions'] = [
            'gettext' => 'gettext',
            '_' => 'gettext',
            '_i' => 'gettext',

            'ngettext' => 'ngettext',
            'n_' => 'ngettext',
            'n__' => 'ngettext',

            'pgettext' => 'pgettext',
            'p_' => 'pgettext',
            'p__' => 'pgettext',

            'npgettext' => 'npgettext',
            'np_' => 'npgettext',
            'np__' => 'npgettext',

            'noop' => 'noop',
            'noop_' => 'noop',
            'noop__' => 'noop',
        ];

        $translations = new Translations();
        $directories = $this->config['gettext_parse_paths'];

        foreach ($directories as $dir) {
            $dir = base_path($dir);

            if (!is_dir($dir)) {
                throw new Exception(__('Folder %s not exists. Gettext scan aborted.', $dir));
            }

            foreach ($this->scanDir($dir) as $file) {
                if (strstr($file, '.blade.php')) {
                    Extractors\Blade::fromFile($file, $translations);
                } elseif (strstr($file, '.php')) {
                    Extractors\PhpCode::fromFile($file, $translations);
                }
            }
        }

        $tmpDir = $this->tmpPath();
        $tmpFile = $tmpDir . DIRECTORY_SEPARATOR . 'app.po';

        if ( ! $this->filesystem->exists($tmpDir)) {
            $this->filesystem->makeDirectory($tmpDir, 0777, true);
        }

        $potDir = $this->gettextPath();
        $potFile = $potDir . DIRECTORY_SEPARATOR . 'app.pot';

        $translations->setHeader('Project-Id-Version', 'Name of application');
        $translations->setHeader('Report-Msgid-Bugs-To', 'contact@translation.io');
        $translations->setHeader("Plural-Forms", "nplurals=INTEGER; plural=EXPRESSION;");

        // Gérer ici le cas où des PO sont déjà existants
        // + laisser pot data dans le répertoire

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

    private function gettextPath()
    {
        return base_path($this->config['gettext_locales_path']);
    }
}
