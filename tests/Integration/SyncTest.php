<?php

namespace Armandsar\LaravelTranslationio\Tests\Integration;

use Armandsar\LaravelTranslationio\Tests\TestCase;
use Armandsar\LaravelTranslationio\TranslationIO;
use Carbon\Carbon;

class SyncTest extends TestCase
{
    public function testItWorks()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp('1519315695'));
        app()['config']->set('translationio.target_locales', ['lv', 'ru']);
        app()['config']->set('translationio.key', 'a355468ad9ea4b1a9c793f352dd0c654');

        $this->addTranslationFixture('en', [], 'auth', [
            'password' => 'Password changed',
            'email' => 'Email changed',
            'fields' => [
                'first_name' => 'First name changed',
                'last_name' => 'Last name changed',
            ]
        ]);

        $this->cassette('integration/sync.yml');
        $this->artisan('translations:sync');

        $authLv = $this->filesystem->getRequire($this->localePath('lv') . DIRECTORY_SEPARATOR . 'auth.php');
        $authRu = $this->filesystem->getRequire($this->localePath('ru') . DIRECTORY_SEPARATOR . 'auth.php');

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

        // Check that it's been translated to lv (response includes all translated sentences)
        $t = new TranslationIO(config('translationio'));
        $t->setLocale('lv');

        $expectedLvOutput = <<<EOT
Sveiki noop
Sveiki noop_
Sveiki noop__
Sveiki gettext
Sveiki _
Sveiki i_ interpolation
Sveiki i__ interpolation
Sveiki i__ complex interpolation
Sveiki plural 1 ngettext
Sveiki singular n_
Sveiki plural 1 n__ interpolation
Sveiki plural 1 n__ complex interpolation plural
Sveiki pgettext
Sveiki p_
Sveiki p__
Sveiki p__ complex interpolation
Sveiki npgettext singular
Sveiki singular np_
Sveiki plural 1 np__
Sveiki plural 1 np__ complex interpolation plural
EOT;

        $this->assertEquals(
          $this->outputOfPhpFile('./tests/fixtures/gettext/example.php'),
          $expectedLvOutput
        );

        // ru is not translated yet!
        $t->setLocale('ru');

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
    }

    // Ignore the Gettext part of the response
    public function testItWorksWithSourceEdits()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp('1519315695'));
        app()['config']->set('translationio.target_locales', ['lv', 'ru']);
        app()['config']->set('translationio.key', 'a355468ad9ea4b1a9c793f352dd0c654');

        $this->addTranslationFixture('en', [], 'auth', [
            'password' => 'Password changed',
            'email' => 'Email changed',
            'fields' => [
                'first_name' => 'First name changed',
                'last_name' => 'Last name changed',
            ]
        ]);

        $this->cassette('integration/sync_with_source_edits.yml');
        $this->artisan('translations:sync');

        $authLv = $this->filesystem->getRequire($this->localePath('lv') . DIRECTORY_SEPARATOR . 'auth.php');
        $authRu = $this->filesystem->getRequire($this->localePath('ru') . DIRECTORY_SEPARATOR . 'auth.php');

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
        Carbon::setTestNow(Carbon::createFromTimestamp('1519315695'));
        app()['config']->set('translationio.target_locales', ['lv', 'ru']);
        app()['config']->set('translationio.key', 'a355468ad9ea4b1a9c793f352dd0c654');

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
        $this->artisan('translations:sync');

        $authLv = $this->filesystem->getRequire($this->localePath('lv') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'auth.php');
        $authRu = $this->filesystem->getRequire($this->localePath('ru') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'auth.php');
        $testLv = $this->filesystem->getRequire($this->localePath('lv') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'subsubfolder' . DIRECTORY_SEPARATOR . 'test.php');
        $testRu = $this->filesystem->getRequire($this->localePath('ru') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'subsubfolder' . DIRECTORY_SEPARATOR . 'test.php');

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
        Carbon::setTestNow(Carbon::createFromTimestamp('1519315695'));
        app()['config']->set('translationio.target_locales', ['lv', 'ru']);
        app()['config']->set('translationio.key', 'a355468ad9ea4b1a9c793f352dd0c654');

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
        $this->artisan('translations:sync');

        $authLv = $this->filesystem->getRequire($this->localePath('lv') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'auth.php');
        $authRu = $this->filesystem->getRequire($this->localePath('ru') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'auth.php');
        $testLv = $this->filesystem->getRequire($this->localePath('lv') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'subsubfolder' . DIRECTORY_SEPARATOR . 'test.php');
        $testRu = $this->filesystem->getRequire($this->localePath('ru') . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'subsubfolder' . DIRECTORY_SEPARATOR . 'test.php');

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
