<?php

namespace Armandsar\LaravelTranslationio\Tests\Integration;

use Armandsar\LaravelTranslationio\Facade;
use Armandsar\LaravelTranslationio\TranslationIO;
use Armandsar\LaravelTranslationio\Tests\TestCase;

class InitTest extends TestCase
{
    public function testItWorks()
    {
        app()['config']->set('translationio.target_locales', ['fr-BE', 'lv', 'ru']);
        app()['config']->set('translationio.key', 'b641be726cfc42a3a0e2daa7f6fdda5c');

        $this->addTranslationFixture('fr-BE', [], 'auth', [
            'fields' => [
                'first_name' => 'Prénom'
            ]
        ]);

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
Hello noop__ 1
Hello noop__ 2
Hello noop__ 3
Hello t__
Hello t__ interpolation
Hello t__ complex interpolation
Hello plural n__
Hello plural n__ interpolation
Hello plural n__ complex interpolation plural
Hello singular n__
Hello singular n__ interpolation
Hello singular n__ complex interpolation singular
Hello plural n__
Hello plural n__ interpolation
Hello plural n__ complex interpolation plural
Hello p__
Hello p__ interpolation
Hello p__ complex interpolation
Hello plural np__
Hello plural np__ interpolation
Hello plural np__ complex interpolation plural
Hello singular np__
Hello singular np__ interpolation
Hello singular np__ complex interpolation singular
Hello plural np__
Hello plural np__ interpolation
Hello plural np__ complex interpolation plural

EOT;

        $this->assertEquals(
            $this->outputOfPhpFile('./tests/fixtures/gettext/example.php'),
            $expectedEnglishOutput
        );

        // Check that they are not translated yet
        Facade::setLocale('lv');

        $this->assertEquals(
            $this->outputOfPhpFile('./tests/fixtures/gettext/example.php'),
            $expectedEnglishOutput
        );
    }
}
