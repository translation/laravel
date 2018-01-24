<?php

namespace Armandsar\LaravelTranslationio\Tests;

use Armandsar\LaravelTranslationio\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use VCR\VCR;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function setUp()
    {
        parent::setUp();
        $this->filesystem = app(Filesystem::class);
        $this->cleanLanguages();
    }

    protected function addTranslationFixture($locale, $directories, $group, $translations)
    {
        $localeDir = $this->localePath($locale);

        $dir = join(DIRECTORY_SEPARATOR, array_merge([$localeDir], $directories));

        if ( ! $this->filesystem->exists($dir)) {
            $this->filesystem->makeDirectory($dir, 0777, true);
        }

        $fileContent = <<<'EOT'
<?php
return {{translations}};
EOT;

        $fileContent = str_replace('{{translations}}', var_export($translations, true), $fileContent);

        $this->filesystem->put($dir . DIRECTORY_SEPARATOR . $group . '.php', $fileContent);
    }

    protected function localePath($locale)
    {
        return app()['path.lang'] . DIRECTORY_SEPARATOR . $locale;
    }

    protected function cassette($file)
    {
        VCR::insertCassette($file);
    }

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    private function cleanLanguages()
    {
        $this->filesystem->deleteDirectory(app()['path.lang']);
        $this->filesystem->makeDirectory(app()['path.lang']);
    }

}
