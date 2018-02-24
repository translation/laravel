<?php

namespace Armandsar\LaravelTranslationio\Tests\Integration;

use Armandsar\LaravelTranslationio\TranslationIO;
use Armandsar\LaravelTranslationio\Tests\TestCase;

class InitTest extends TestCase
{
    public function testItWorks()
    {
        app()['config']->set('translationio.target_locales', ['lv', 'ru']);
        app()['config']->set('translationio.key', 'a355468ad9ea4b1a9c793f352dd0c654');

        $this->addTranslationFixture('lv', [], 'auth', [
            'password' => 'Parole'
        ]);

        $this->addTranslationFixture('ru', [], 'auth', [
            'password' => 'Пароль',
            'error' => 'Ошибка'
        ]);

        $this->addTranslationFixture('en', [], 'auth', [
            'password' => 'Password',
            'email' => 'Email',
            'fields' => [
                'first_name' => 'First name',
                'last_name' => 'Last name',
            ]
        ]);

        $this->addTranslationFixture('en', ['subfolder'], 'forms', [
            'title' => 'Title'
        ]);

        $this->cassette('integration/init.yml');
        $this->artisan('translation:init');

        $expectedEnglishOutput = <<<EOT
Hello noop
Hello noop_
Hello noop__
Hello gettext
Hello _
Hello i_ interpolation
Hello i__ interpolation
Hello i__ complex interpolation
Hello plural ngettext
Hello singular n_
Hello plural n__ interpolation
Hello plural n__ complex interpolation plural
Hello pgettext
Hello p_
Hello p__
Hello p__ complex interpolation
Hello npgettext singular
Hello singular np_
Hello plural np__
Hello plural np__ complex interpolation plural
EOT;

        $this->assertEquals(
          $this->outputOfPhpFile('./tests/fixtures/gettext/example.php'),
          $expectedEnglishOutput
        );

        // Check that they are not translated yet
        $t = new TranslationIO(config('translationio'));
        $t->setLocale('lv');

        $this->assertEquals(
          $this->outputOfPhpFile('./tests/fixtures/gettext/example.php'),
          $expectedEnglishOutput
        );
    }
}
