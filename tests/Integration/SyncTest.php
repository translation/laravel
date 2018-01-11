<?php

namespace Armandsar\LaravelTranslationio\Tests\Integration;

use Armandsar\LaravelTranslationio\Tests\TestCase;
use Carbon\Carbon;

class SyncTest extends TestCase
{
    public function testItWorks()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp('1506615677'));
        app()['config']->set('translationio.target_locales', ['lv', 'ru']);
        app()['config']->set('translationio.key', '13f5dbf135334c20aa4fed952f5a81f9');

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
    }

    public function testItWorksWithSourceEdits()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp('1506615677'));
        app()['config']->set('translationio.target_locales', ['lv', 'ru']);
        app()['config']->set('translationio.key', '13f5dbf135334c20aa4fed952f5a81f9');

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

    public function testItWorksWithSubfolders()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp('1506615677'));
        app()['config']->set('translationio.target_locales', ['lv', 'ru']);
        app()['config']->set('translationio.key', '13f5dbf135334c20aa4fed952f5a81f9');

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

    public function testItWorksWithSubfoldersAndSourceEdits()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp('1506615677'));
        app()['config']->set('translationio.target_locales', ['lv', 'ru']);
        app()['config']->set('translationio.key', '13f5dbf135334c20aa4fed952f5a81f9');

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
