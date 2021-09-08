<?php

namespace Tio\Laravel\Tests\Integration;

use Tio\Laravel\Facade;
use Tio\Laravel\TranslationIO;
use Tio\Laravel\Tests\TestCase;

class InitTest extends TestCase
{
    public function testItWorksWithError()
    {
        // NOTE: we blocked gettext/languages to <=2.6.0 to be able to keep French with 2 plurals (fow now).
        // It allows us to stay in sync with T.io while being compatible with previous PHP-VCR tests
        // ---
        // If one day gettext/languages is updated, please use another more stable language than French
        // and edit the plural rules in the pre-recorded PHP-VCR YAML files so that both regular composer
        // and `--prefer-lowest` pass
        app()['config']->set('translation.target_locales', ['fr-BE']);
        app()['config']->set('translation.key', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        app()['config']->set('translation.gettext_parse_paths', ['tests/fixtures/gettext']);

        $this->addTranslationFixture('fr-BE', [], 'auth', [
            'fields' => [
                'first_name' => 'Prénom'
            ]
        ]);

        $this->cassette('integration/init_with_bad_api_key.yml');

        try {
            $this->artisan('translation:init');
        }
        catch(\Throwable $e) {
            $this->assertEquals("Could not find any *active* project with this API key.", $e->getMessage());
        }
    }

    public function testItWorks()
    {
        app()['config']->set('translation.target_locales', ['fr-BE', 'lv', 'ru']);
        app()['config']->set('translation.key', 'b641be726cfc42a3a0e2daa7f6fdda5c');
        app()['config']->set('translation.gettext_parse_paths', ['tests/fixtures/gettext']);

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
Hello noop 1
Hello noop 2
Hello noop 3
Hello t
Hello t interpolation
Hello t complex interpolation
Hello plural n
Hello plural n interpolation
Hello plural n complex interpolation plural
Hello singular n
Hello singular n interpolation
Hello singular n complex interpolation singular
Hello plural n
Hello plural n interpolation
Hello plural n complex interpolation plural
Hello p
Hello p interpolation
Hello p complex interpolation
Hello plural np
Hello plural np interpolation
Hello plural np complex interpolation plural
Hello singular np
Hello singular np interpolation
Hello singular np complex interpolation singular
Hello plural np
Hello plural np interpolation
Hello plural np complex interpolation plural

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

    public function testItWorksWithExistingGettext()
    {
        app()['config']->set('translation.target_locales', ['fr-BE']);
        app()['config']->set('translation.key', '2953c30ba10244f185fd8edc8443efe1');
        app()['config']->set('translation.gettext_parse_paths', ['tests/fixtures/gettext']);

        $frBePOContent = <<<EOT
msgid ""
msgstr ""
"Project-Id-Version: Laravel\n"
"Report-Msgid-Bugs-To: contact@translation.io\n"
"Last-Translator: \n"
"Language-Team: French\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"POT-Creation-Date: 2018-03-06T11:45:02+00:00\n"
"PO-Revision-Date: 2018-03-06 12:45+0100\n"
"Language: fr_BE\n"
"Plural-Forms: nplurals=2; plural=n>1;\n"
"\n"

#: resources/views/welcome.blade.php:94
msgid "Hello t"
msgstr "Bonjour t"

#: resources/views/welcome.blade.php:95 resources/views/welcome.blade.php:96
msgid "Hello singular n"
msgid_plural "Hello plural n"
msgstr[0] "Bonjour singulier n"
msgstr[1] "Bonjour pluriel n"
EOT;

        $this->addTranslationPOFixture('fr-BE', $frBePOContent);

        $this->cassette('integration/init_with_po.yml');
        $this->artisan('translation:init');

        # Some already translated segments need to stay translated
        # Careful: in French, 0 and 1 are singulars, and in English, 0 is plural
        $expectedFrBeOutput = <<<EOT
Hello noop 1
Hello noop 2
Hello noop 3
Bonjour t
Hello t interpolation
Hello t complex interpolation
Bonjour singulier n
Hello plural n interpolation
Hello plural n complex interpolation plural
Bonjour singulier n
Hello singular n interpolation
Hello singular n complex interpolation singular
Bonjour pluriel n
Hello plural n interpolation
Hello plural n complex interpolation plural
Hello p
Hello p interpolation
Hello p complex interpolation
Hello plural np
Hello plural np interpolation
Hello plural np complex interpolation plural
Hello singular np
Hello singular np interpolation
Hello singular np complex interpolation singular
Hello plural np
Hello plural np interpolation
Hello plural np complex interpolation plural

EOT;

        $expectedEnOutput = <<<EOT
Hello noop 1
Hello noop 2
Hello noop 3
Hello t
Hello t interpolation
Hello t complex interpolation
Hello plural n
Hello plural n interpolation
Hello plural n complex interpolation plural
Hello singular n
Hello singular n interpolation
Hello singular n complex interpolation singular
Hello plural n
Hello plural n interpolation
Hello plural n complex interpolation plural
Hello p
Hello p interpolation
Hello p complex interpolation
Hello plural np
Hello plural np interpolation
Hello plural np complex interpolation plural
Hello singular np
Hello singular np interpolation
Hello singular np complex interpolation singular
Hello plural np
Hello plural np interpolation
Hello plural np complex interpolation plural

EOT;

        // Default behaviour is source language
        $this->assertEquals(
            $expectedEnOutput,
            $this->outputOfPhpFile('./tests/fixtures/gettext/example.php')
        );

        // Check that it's been translated to lv (response includes all translated sentences)
        Facade::setLocale('fr-BE');

        $this->assertEquals(
            $expectedFrBeOutput,
            $this->outputOfPhpFile('./tests/fixtures/gettext/example.php')
        );


        $poPath = app()['path.lang'] . DIRECTORY_SEPARATOR . 'gettext' . DIRECTORY_SEPARATOR . 'fr-BE' . DIRECTORY_SEPARATOR . 'app.po';

        $expectedFrBePoContent = <<<EOT
msgid ""
msgstr ""
"Project-Id-Version: Laravel\\n"
"Report-Msgid-Bugs-To: contact@translation.io\\n"
"Last-Translator: \\n"
"Language-Team: French\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"POT-Creation-Date: 2018-01-01T12:00:00+00:00\\n"
"PO-Revision-Date: 2018-03-06 15:42+0100\\n"
"Language: fr_BE\\n"
"Plural-Forms: nplurals=2; plural=n>1;\\n"

#: resources/views/welcome.blade.php:94
#: tests/fixtures/gettext/example.php:16
msgid "Hello t"
msgstr "Bonjour t"

#: resources/views/welcome.blade.php:95
#: resources/views/welcome.blade.php:96
#: tests/fixtures/gettext/example.php:32
msgid "Hello singular n"
msgid_plural "Hello plural n"
msgstr[0] "Bonjour singulier n"
msgstr[1] "Bonjour pluriel n"

#: tests/fixtures/gettext/example.php:5
msgid "Hello noop 1"
msgstr ""

#: tests/fixtures/gettext/example.php:6
msgid "Hello noop 2"
msgstr ""

#: tests/fixtures/gettext/example.php:7
msgid "Hello noop 3"
msgstr ""

#: tests/fixtures/gettext/example.php:19
msgid "Hello t %s"
msgstr ""

#: tests/fixtures/gettext/example.php:22
msgid "Hello t %name%"
msgstr ""

#: tests/fixtures/gettext/example.php:35
msgid "Hello singular n %s"
msgid_plural "Hello plural n %s"
msgstr[0] ""
msgstr[1] ""

#: tests/fixtures/gettext/example.php:38
msgid "Hello singular n %name1%"
msgid_plural "Hello plural n %name2%"
msgstr[0] ""
msgstr[1] ""

#: tests/fixtures/gettext/example.php:47
msgctxt "p context"
msgid "Hello p"
msgstr ""

#: tests/fixtures/gettext/example.php:50
msgctxt "p context"
msgid "Hello p %s"
msgstr ""

#: tests/fixtures/gettext/example.php:53
msgctxt "p context"
msgid "Hello p %name%"
msgstr ""

#: tests/fixtures/gettext/example.php:61
msgctxt "np context"
msgid "Hello singular np"
msgid_plural "Hello plural np"
msgstr[0] ""
msgstr[1] ""

#: tests/fixtures/gettext/example.php:64
msgctxt "np context"
msgid "Hello singular np %s"
msgid_plural "Hello plural np %s"
msgstr[0] ""
msgstr[1] ""

#: tests/fixtures/gettext/example.php:67
msgctxt "np context"
msgid "Hello singular np %name1%"
msgid_plural "Hello plural np %name2%"
msgstr[0] ""
msgstr[1] ""

EOT;

        $this->assertEquals(
            $this->filesystem->get($poPath),
            $expectedFrBePoContent
        );
    }

    public function testItWorksWithNoGettext()
    {
        app()['config']->set('translation.target_locales', ['fr-BE', 'lv', 'ru']);
        app()['config']->set('translation.key', '1bd053fe4148408cbd869101dcae9419');

        // a directory without Gettext
        app()['config']->set('translation.gettext_parse_paths', ['config']);

        $this->cassette('integration/init_with_no_gettext.yml');
        $this->artisan('translation:init');

        // => for PHPUnit >= 10
        if(method_exists($this, 'assertDirectoryDoesNotExist')) {
            $this->assertDirectoryDoesNotExist($this->gettextDir());
        }
        // => for PHPUnit < 10
        else {
            $this->assertDirectoryNotExists($this->gettextDir());
        }
    }

    public function testItWorksWithIgnoredKeyPrefixes()
    {
        app()['config']->set('translation.target_locales', ['fr']);
        app()['config']->set('translation.key', 'd91bc584bc51421d83fde31de5e5a31e');
        app()['config']->set('translation.gettext_parse_paths', []);
        app()['config']->set('translation.ignored_key_prefixes', ['greetings.hello']);

        $this->addTranslationFixture('en', [], 'greetings', [
            'hello' => 'Hello',
            'bye'   => 'Good bye'
        ]);

        $this->addTranslationFixture('fr', [], 'greetings', [
            'hello' => 'Bonjour',
            'bye'   => 'Au revoir'
        ]);

        # only contains "bye" key since "hello" was filtered out
        $this->cassette('integration/init_for_key_prefixes.yml');
        $this->artisan('translation:init');

        // => for PHPUnit >= 10
        if(method_exists($this, 'assertDirectoryDoesNotExist')) {
            $this->assertDirectoryDoesNotExist($this->localePath('fr/greetings.php'));
        }
        // => for PHPUnit < 10
        else {
            $this->assertDirectoryNotExists($this->localePath('fr/greetings.php'));
        }
    }
}
