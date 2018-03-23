<?php

namespace Tio\Laravel\Tests\Integration;

use Tio\Laravel\Facade;
use Tio\Laravel\Tests\TestCase;
use Carbon\Carbon;

class SyncTest extends TestCase
{
    public function testItWorks()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp('1520275983'));
        app()['config']->set('translation.target_locales', ['fr-BE', 'lv', 'ru']);
        app()['config']->set('translation.key', 'b641be726cfc42a3a0e2daa7f6fdda5c');
        app()['config']->set('translation.gettext_parse_paths', ['tests/fixtures/gettext']);

        $this->addTranslationFixture('en', [], 'auth', [
            'password' => 'Password changed',
            'email' => 'Email changed',
            'fields' => [
                'first_name' => 'First name changed',
                'last_name' => 'Last name changed',
            ]
        ]);

        $this->cassette('integration/sync.yml');
        $this->artisan('translation:sync');

        $authFr = $this->filesystem->getRequire($this->localePath('fr-BE') . DIRECTORY_SEPARATOR . 'auth.php');
        $authLv = $this->filesystem->getRequire($this->localePath('lv')    . DIRECTORY_SEPARATOR . 'auth.php');
        $authRu = $this->filesystem->getRequire($this->localePath('ru')    . DIRECTORY_SEPARATOR . 'auth.php');

        # last_name and email were not translated in French (so absent from the response)
        $this->assertEquals(
            [
                'password' => 'Mot de passe',
                'fields' => [
                    'first_name' => 'Prénom',
                ]
            ], $authFr);

        $this->assertEquals(
            [
                'password' => 'Parole',
                'email' => 'Epasts',
                'fields' => [
                    'first_name' => 'Vārds',
                    'last_name' => 'Uzvārds'
                ]
            ], $authLv);

        $this->assertEquals(
            [
                'password' => 'Пароль',
                'email' => 'Почта',
                'fields' => [
                    'first_name' => 'Имя',
                    'last_name' => 'Фамилия'
                ]
            ], $authRu);

        $formsFr = $this->filesystem->getRequire($this->localePath('fr-BE') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'forms.php');

        $this->assertEquals(
            [
                'title' => 'Titre',
            ], $formsFr);

        // empty files are not written on disk
        $this->assertFileNotExists($this->localePath('lv') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'forms.php');
        $this->assertFileNotExists($this->localePath('ru') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'forms.php');

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

        $expectedLvOutput = <<<EOT
Sveiki noop 1
Sveiki noop 2
Sveiki noop 3
Sveiki t
Sveiki t interpolation
Sveiki t complex interpolation
Sveiki plural 2 n
Sveiki plural 2 n interpolation
Sveiki plural 2 n complex interpolation plural
Sveiki singular n
Sveiki singular n interpolation
Sveiki singular n complex interpolation singular
Sveiki plural 1 n
Sveiki plural 1 n interpolation
Sveiki plural 1 n complex interpolation plural
Sveiki p
Sveiki p interpolation
Sveiki p complex interpolation
Sveiki plural 2 np
Sveiki plural 2 np interpolation
Sveiki plural 2 np complex interpolation plural
Sveiki singular np
Sveiki singular np interpolation
Sveiki singular np complex interpolation singular
Sveiki plural 1 np
Sveiki plural 1 np interpolation
Sveiki plural 1 np complex interpolation plural

EOT;

        $expectedFrenchOutput = <<<EOT
Hello noop 1
Hello noop 2
Hello noop 3
Bonjour t
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
        Facade::setLocale('lv');

        $this->assertEquals(
            $expectedLvOutput,
            $this->outputOfPhpFile('./tests/fixtures/gettext/example.php')
        );

        // ru is not translated yet!
        Facade::setLocale('ru');

        $this->assertEquals(
            $expectedEnOutput,
            $this->outputOfPhpFile('./tests/fixtures/gettext/example.php')
        );

        // fr-BE has only one translated sentence
        Facade::setLocale('fr-BE');

        $this->assertEquals(
            $expectedFrenchOutput,
            $this->outputOfPhpFile('./tests/fixtures/gettext/example.php')
        );
    }

    // Ignore the Gettext part of the response
    public function testItWorksWithSourceEdits()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp('1520275983'));
        app()['config']->set('translation.target_locales', ['fr-BE', 'lv', 'ru']);
        app()['config']->set('translation.key', 'b641be726cfc42a3a0e2daa7f6fdda5c');
        app()['config']->set('translation.gettext_parse_paths', ['tests/fixtures/gettext']);

        $this->addTranslationFixture('en', [], 'auth', [
            'password' => 'Password changed',
            'email' => 'Email changed',
            'fields' => [
                'first_name' => 'First name changed',
                'last_name' => 'Last name changed',
            ]
        ]);

        $this->cassette('integration/sync_with_source_edits.yml');
        $this->artisan('translation:sync');

        $authFr = $this->filesystem->getRequire($this->localePath('fr-BE') . DIRECTORY_SEPARATOR . 'auth.php');
        $authLv = $this->filesystem->getRequire($this->localePath('lv') . DIRECTORY_SEPARATOR . 'auth.php');
        $authRu = $this->filesystem->getRequire($this->localePath('ru') . DIRECTORY_SEPARATOR . 'auth.php');

        # last_name and email were not translated in French (so absent from the response)
        $this->assertEquals(
            [
                'password' => 'Mot de passe',
                'fields' => [
                    'first_name' => 'Prénom',
                ]
            ], $authFr);

        $this->assertEquals(
            [
                'password' => 'Parole',
                'email' => 'Epasts',
                'fields' => [
                    'first_name' => 'Vārds',
                    'last_name' => 'Uzvārds'
                ]
            ], $authLv);

        $this->assertEquals(
            [
                'password' => 'Пароль',
                'email' => 'Почта',
                'fields' => [
                    'first_name' => 'Имя',
                    'last_name' => 'Фамилия'
                ]
            ], $authRu);

        $authEn = $this->filesystem->getRequire($this->localePath('en') . DIRECTORY_SEPARATOR . 'auth.php');

        $this->assertEquals(
            [
                'password' => 'Password modified',        // was modified by source_edits
                'email' => 'Email changed',               // was not modified by source_edits because old_text didn't match
                'fields' => [
                    'first_name' => 'First name changed',
                    'last_name' => 'Surname changed',     // was modified by source_edits
                ]
            ], $authEn);
    }

    // Ignore the Gettext part of the response
    public function testItWorksWithSubfolders()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp('1520275983'));
        app()['config']->set('translation.target_locales', ['fr-BE', 'lv', 'ru']);
        app()['config']->set('translation.key', 'b641be726cfc42a3a0e2daa7f6fdda5c');
        app()['config']->set('translation.gettext_parse_paths', ['tests/fixtures/gettext']);

        $this->addTranslationFixture('en', ['subfolder'], 'auth', [
            'password' => 'Password changed',
            'email' => 'Email changed',
            'fields' => [
                'first_name' => 'First name changed',
                'last_name' => 'Last name changed',
            ]
        ]);

        $this->addTranslationFixture('en', ['subfolder', 'subsubfolder'], 'test', [
            'keytest' => 'This is a test',
        ]);

        $this->cassette('integration/sync_with_subfolders.yml');
        $this->artisan('translation:sync');

        $authFr = $this->filesystem->getRequire($this->localePath('fr-BE') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'auth.php');
        $authLv = $this->filesystem->getRequire($this->localePath('lv')    . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'auth.php');
        $authRu = $this->filesystem->getRequire($this->localePath('ru')    . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'auth.php');

        # last_name and email were not translated in French (so absent from the response)
        $this->assertEquals(
            [
                'password' => 'Mot de passe',
                'fields' => [
                    'first_name' => 'Prénom',
                ]
            ], $authFr);

        $this->assertEquals(
            [
                'password' => 'Parole',
                'email' => 'Epasts',
                'fields' => [
                    'first_name' => 'Vārds',
                    'last_name' => 'Uzvārds'
                ]
            ], $authLv);

        $this->assertEquals(
            [
                'password' => 'Пароль',
                'email' => 'Почта',
                'fields' => [
                    'first_name' => 'Имя',
                    'last_name' => 'Фамилия'
                ]
            ], $authRu);

        $testFr = $this->filesystem->getRequire($this->localePath('fr-BE') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'subsubfolder' . DIRECTORY_SEPARATOR . 'test.php');
        $testLv = $this->filesystem->getRequire($this->localePath('lv')    . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'subsubfolder' . DIRECTORY_SEPARATOR . 'test.php');
        $testRu = $this->filesystem->getRequire($this->localePath('ru')    . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'subsubfolder' . DIRECTORY_SEPARATOR . 'test.php');

        $this->assertEquals(
            [
                'keytest' => 'Ceci est un test'
            ], $testFr);

        $this->assertEquals(
            [
                'keytest' => 'Šis ir tests'
            ], $testLv);

        $this->assertEquals(
            [
                'keytest' => 'Это тест'
            ], $testRu);
    }

    // Ignore the Gettext part of the response
    public function testItWorksWithSubfoldersAndSourceEdits()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp('1520275983'));
        app()['config']->set('translation.target_locales', ['fr-BE', 'lv', 'ru']);
        app()['config']->set('translation.key', 'b641be726cfc42a3a0e2daa7f6fdda5c');
        app()['config']->set('translation.gettext_parse_paths', ['tests/fixtures/gettext']);

        $this->addTranslationFixture('en', ['subfolder'], 'auth', [
            'password' => 'Password changed',
            'email' => 'Email changed',
            'fields' => [
                'first_name' => 'First name changed',
                'last_name' => 'Last name changed',
            ]
        ]);

        $this->addTranslationFixture('en', ['subfolder', 'subsubfolder'], 'test', [
            'keytest' => 'This is a test',
        ]);

        $this->cassette('integration/sync_with_subfolders_and_source_edits.yml');
        $this->artisan('translation:sync');

        $authFr = $this->filesystem->getRequire($this->localePath('fr-BE') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'auth.php');
        $authLv = $this->filesystem->getRequire($this->localePath('lv')    . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'auth.php');
        $authRu = $this->filesystem->getRequire($this->localePath('ru')    . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'auth.php');

        # last_name and email were not translated in French (so absent from the response)
        $this->assertEquals(
            [
                'password' => 'Mot de passe',
                'fields' => [
                    'first_name' => 'Prénom',
                ]
            ], $authFr);

        $this->assertEquals(
            [
                'password' => 'Parole',
                'email' => 'Epasts',
                'fields' => [
                    'first_name' => 'Vārds',
                    'last_name' => 'Uzvārds'
                ]
            ], $authLv);

        $this->assertEquals(
            [
                'password' => 'Пароль',
                'email' => 'Почта',
                'fields' => [
                    'first_name' => 'Имя',
                    'last_name' => 'Фамилия'
                ]
            ], $authRu);

        $testFr = $this->filesystem->getRequire($this->localePath('fr-BE') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'subsubfolder' . DIRECTORY_SEPARATOR . 'test.php');
        $testLv = $this->filesystem->getRequire($this->localePath('lv')    . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'subsubfolder' . DIRECTORY_SEPARATOR . 'test.php');
        $testRu = $this->filesystem->getRequire($this->localePath('ru')    . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'subsubfolder' . DIRECTORY_SEPARATOR . 'test.php');

        $this->assertEquals(
            [
                'keytest' => 'Ceci est un test'
            ], $testFr);

        $this->assertEquals(
            [
                'keytest' => 'Šis ir tests'
            ], $testLv);

        $this->assertEquals(
            [
                'keytest' => 'Это тест'
            ], $testRu);

        $authEn = $this->filesystem->getRequire($this->localePath('en') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'auth.php');
        $testEn = $this->filesystem->getRequire($this->localePath('en') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'subsubfolder' . DIRECTORY_SEPARATOR . 'test.php');

        $this->assertEquals(
            [
                'password' => 'Password modified',        // was modified by source_edits
                'email' => 'Email changed',               // was not modified by source_edits because old_text didn't match
                'fields' => [
                    'first_name' => 'First name changed',
                    'last_name' => 'Surname changed',     // was modified by source_edits
                ]
            ], $authEn);

        $this->assertEquals(
            [
                'keytest' => 'This is a good test'        // was modified by source_edits
            ], $testEn);
    }

}
