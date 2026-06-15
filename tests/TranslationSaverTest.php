<?php

namespace Tio\Laravel\Tests;

use Tio\Laravel\TranslationSaver;

class TranslationSaverTest extends TestCase
{
    public function testItSavesCorrectly()
    {
        $locale = 'lv';

        $translations = [
            'file.a' => 'A',
            'file.nested' => [
                'deeper' => [
                    'b' => 'B'
                ]
            ],
            'file.empty' => '',
            'file.space' => ' ',
            'file2.a' => 'A'
        ];

        $this->saver()->call($locale, $translations);

        $file = $this->filesystem->getRequire($this->localePath($locale) . DIRECTORY_SEPARATOR . 'file.php');
        $file2 = $this->filesystem->getRequire($this->localePath($locale) . DIRECTORY_SEPARATOR . 'file2.php');

        $this->assertEquals(
            [
                'a' => 'A',
                'nested' => [
                    'deeper' => [
                        'b' => 'B'
                    ]
                ],
                'space' => ' '
            ]
            , $file);
        $this->assertEquals(['a' => 'A'], $file2);
    }

    public function testItPreservesIgnoredKeyPrefixFiles()
    {
        $locale = 'fr';

        // Pre-populate the locale directory with an ignored file.
        $this->addTranslationFixture($locale, [], 'admin', [
            'title' => 'Admin panel',
            'users' => 'Users',
        ]);

        // Also add a non-ignored file so we can confirm it is still rebuilt.
        $this->addTranslationFixture($locale, [], 'messages', [
            'hello' => 'Old hello',
        ]);

        // Configure "admin" as an ignored prefix — its file should survive the rebuild.
        app()['config']->set('translation.ignored_key_prefixes', ['admin']);

        // The API response only contains non-ignored keys.
        $translationsDotted = [
            'messages.hello' => 'Bonjour',
        ];

        $this->saver()->call($locale, $translationsDotted);

        // The ignored file must still exist with its original content.
        $adminFile = $this->filesystem->getRequire($this->localePath($locale) . DIRECTORY_SEPARATOR . 'admin.php');
        $this->assertEquals(['title' => 'Admin panel', 'users' => 'Users'], $adminFile);

        // The non-ignored file must have been updated from the API response.
        $messagesFile = $this->filesystem->getRequire($this->localePath($locale) . DIRECTORY_SEPARATOR . 'messages.php');
        $this->assertEquals(['hello' => 'Bonjour'], $messagesFile);
    }

    public function testItPreservesIgnoredKeyPrefixesWithSubfolders()
    {
        $locale = 'fr';

        // Pre-populate a subfolder file that is fully ignored.
        $this->addTranslationFixture($locale, ['admin'], 'settings', [
            'foo' => 'Foo value',
            'bar' => 'Bar value',
        ]);

        // A normal (non-ignored) subfolder file.
        $this->addTranslationFixture($locale, ['public'], 'home', [
            'welcome' => 'Old welcome',
        ]);

        app()['config']->set('translation.ignored_key_prefixes', ['admin/settings']);

        $translationsDotted = [
            'public/home.welcome' => 'Bienvenue',
        ];

        $this->saver()->call($locale, $translationsDotted);

        // The ignored subfolder file must be preserved.
        $adminSettingsFile = $this->filesystem->getRequire(
            $this->localePath($locale) . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'settings.php'
        );
        $this->assertEquals(['foo' => 'Foo value', 'bar' => 'Bar value'], $adminSettingsFile);

        // The non-ignored file must be updated.
        $homeFile = $this->filesystem->getRequire(
            $this->localePath($locale) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'home.php'
        );
        $this->assertEquals(['welcome' => 'Bienvenue'], $homeFile);
    }

    public function testItPreservesPartiallyIgnoredKeys()
    {
        $locale = 'fr';

        // A file where only a subtree is ignored.
        $this->addTranslationFixture($locale, [], 'validation', [
            'required' => 'This field is required.',
            'custom'   => [
                'email' => 'Custom email rule.',
            ],
        ]);

        // Only the "validation.custom" subtree is ignored.
        app()['config']->set('translation.ignored_key_prefixes', ['validation.custom']);

        // The API returns the non-ignored part of validation.php.
        $translationsDotted = [
            'validation.required' => 'Ce champ est obligatoire.',
        ];

        $this->saver()->call($locale, $translationsDotted);

        $file = $this->filesystem->getRequire($this->localePath($locale) . DIRECTORY_SEPARATOR . 'validation.php');

        // Non-ignored key comes from the API response.
        $this->assertEquals('Ce champ est obligatoire.', $file['required']);

        // Ignored subtree must survive.
        $this->assertEquals(['email' => 'Custom email rule.'], $file['custom']);
    }

    private function saver(): TranslationSaver
    {
        return app(TranslationSaver::class);
    }
}
