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
        app()['config']->set('translationio.gettext_parse_paths', ['tests/fixtures/gettext']);

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

    public function testItWorksWithExistingGettext()
    {
        app()['config']->set('translationio.target_locales', ['fr-BE']);
        app()['config']->set('translationio.key', '2953c30ba10244f185fd8edc8443efe1');
        app()['config']->set('translationio.gettext_parse_paths', ['tests/fixtures/gettext']);

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
msgid "Hello t__"
msgstr "Bonjour t__"

#: resources/views/welcome.blade.php:95 resources/views/welcome.blade.php:96
msgid "Hello singular n__"
msgid_plural "Hello plural n__"
msgstr[0] "Bonjour singulier n__"
msgstr[1] "Bonjour pluriel n__"
EOT;

        $this->addTranslationPOFixture('fr-BE', $frBePOContent);

        $this->cassette('integration/init_with_po.yml');
        $this->artisan('translation:init');

        # Some already translated segments need to stay translated
        # Careful: in French, 0 and 1 are singulars, and in English, 0 is plural
        $expectedFrBeOutput = <<<EOT
Hello noop__ 1
Hello noop__ 2
Hello noop__ 3
Bonjour t__
Hello t__ interpolation
Hello t__ complex interpolation
Bonjour singulier n__
Hello plural n__ interpolation
Hello plural n__ complex interpolation plural
Bonjour singulier n__
Hello singular n__ interpolation
Hello singular n__ complex interpolation singular
Bonjour pluriel n__
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

        $expectedEnOutput = <<<EOT
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
"\\n"

#: resources/views/welcome.blade.php:94 tests/fixtures/gettext/example.php:16
msgid "Hello t__"
msgstr "Bonjour t__"

#: resources/views/welcome.blade.php:95 resources/views/welcome.blade.php:96
#: tests/fixtures/gettext/example.php:32
msgid "Hello singular n__"
msgid_plural "Hello plural n__"
msgstr[0] "Bonjour singulier n__"
msgstr[1] "Bonjour pluriel n__"

#: tests/fixtures/gettext/example.php:5
msgid "Hello noop__ 1"
msgstr ""

#: tests/fixtures/gettext/example.php:6
msgid "Hello noop__ 2"
msgstr ""

#: tests/fixtures/gettext/example.php:7
msgid "Hello noop__ 3"
msgstr ""

#: tests/fixtures/gettext/example.php:19
msgid "Hello t__ %s"
msgstr ""

#: tests/fixtures/gettext/example.php:22
msgid "Hello t__ %name%"
msgstr ""

#: tests/fixtures/gettext/example.php:35
msgid "Hello singular n__ %s"
msgid_plural "Hello plural n__ %s"
msgstr[0] ""
msgstr[1] ""

#: tests/fixtures/gettext/example.php:38
msgid "Hello singular n__ %name1%"
msgid_plural "Hello plural n__ %name2%"
msgstr[0] ""
msgstr[1] ""

#: tests/fixtures/gettext/example.php:47
msgctxt "p__ context"
msgid "Hello p__"
msgstr ""

#: tests/fixtures/gettext/example.php:50
msgctxt "p__ context"
msgid "Hello p__ %s"
msgstr ""

#: tests/fixtures/gettext/example.php:53
msgctxt "p__ context"
msgid "Hello p__ %name%"
msgstr ""

#: tests/fixtures/gettext/example.php:61
msgctxt "np__ context"
msgid "Hello singular np__"
msgid_plural "Hello plural np__"
msgstr[0] ""
msgstr[1] ""

#: tests/fixtures/gettext/example.php:64
msgctxt "np__ context"
msgid "Hello singular np__ %s"
msgid_plural "Hello plural np__ %s"
msgstr[0] ""
msgstr[1] ""

#: tests/fixtures/gettext/example.php:67
msgctxt "np__ context"
msgid "Hello singular np__ %name1%"
msgid_plural "Hello plural np__ %name2%"
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
        app()['config']->set('translationio.target_locales', ['fr-BE', 'lv', 'ru']);
        app()['config']->set('translationio.key', '1bd053fe4148408cbd869101dcae9419');

        // a directory without Gettext
        app()['config']->set('translationio.gettext_parse_paths', ['config']);

        $this->cassette('integration/init_with_no_gettext.yml');
        $this->artisan('translation:init');

        $this->assertDirectoryNotExists($this->gettextDir());
    }
}
