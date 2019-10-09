<?php

namespace Tio\Laravel\Tests;

use Tio\Laravel\TranslationExtractor;

class TranslationExtractorTest extends TestCase
{
    public function testItReturnsSimpleArray()
    {
        $this->addTranslationFixture('en', [], 'file', [
            'a' => 'A',
            'b' => 'B',
        ]);

        $expected = [
            'file.a' => 'A',
            'file.b' => 'B',
        ];

        $this->assertCorrectExtraction($expected);
    }

    public function testItReturnsNothingIfTranslationFileDoesNotExist()
    {
        $this->assertCorrectExtraction([]);
    }

    public function testItDoesNotReturnEmptyNestedTranslations()
    {
        $this->addTranslationFixture('en', [], 'file', [
            'a' => [
                'b' => []
            ],
            'c' => []
        ]);

        $this->assertCorrectExtraction([]);
    }

    public function testItReturnsNestedTranslations()
    {
        $this->addTranslationFixture('en', [], 'file', [
            'a' => 'A',
            'b' => [
                'c' => [
                    'd' => 'D'
                ]
            ]
        ]);

        $expected = [
            'file.a' => 'A',
            'file.b.c.d' => 'D',
        ];

        $this->assertCorrectExtraction($expected);
    }

    public function testItReturnsTranslationsFromMultipleFiles()
    {
        $this->addTranslationFixture('en', [], 'file', [
            'a' => 'A',
            'b' => 'B',
        ]);

        $this->addTranslationFixture('en', [], 'file2', [
            'a' => 'A',
            'b' => 'B',
            'c' => 'C',
        ]);

        $expected = [
            'file.a' => 'A',
            'file.b' => 'B',
            'file2.a' => 'A',
            'file2.b' => 'B',
            'file2.c' => 'C',
        ];

        $this->assertCorrectExtraction($expected);
    }

    public function testItReturnsTranslationsWithSubfolders()
    {
        $this->addTranslationFixture('en', [], 'file', [
            'a' => 'A',
            'b' => 'B',
        ]);

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

        $expected = [
            'file.a' => 'A',
            'file.b' => 'B',
            'subfolder/auth.password' => 'Password changed',
            'subfolder/auth.email' => 'Email changed',
            'subfolder/auth.fields.first_name' => 'First name changed',
            'subfolder/auth.fields.last_name' => 'Last name changed',
            'subfolder/subsubfolder/test.keytest' => 'This is a test'
        ];

        $this->assertCorrectExtraction($expected);
    }

    public function testItReturnsTranslationsWithIgnoredKeyPrefixes()
    {
        // simple prefix (ignore complete file)
        app()['config']->set('translation.ignored_key_prefixes', ['world']);

        $this->assertCorrectRejectIgnoredKeyPrefixes(
            [
                'hello.aa' => 'notempty',
                'wo.bb' => '',
                'world.hello' => '',
                'world.hello.people' => '',
                'world.hello.people.bb' => ''
            ],
            [
                'hello.aa' => 'notempty',
                'wo.bb' => ''
            ]
        );

        // prefix with ending separator (same effect here!)
        app()['config']->set('translation.ignored_key_prefixes', ['world.']);

        $this->assertCorrectRejectIgnoredKeyPrefixes(
            [
                'hello.aa' => '',
                'wo.bb' => '',
                'world.hello' => '',
                'world.hello.people' => '',
                'world.hello.people.bb' => ''
            ],
            [
                'hello.aa' => '',
                'wo.bb' => ''
            ]
        );

        // multi-prefixes (file + root key)
        app()['config']->set('translation.ignored_key_prefixes', ['hello.world']);

        $this->assertCorrectRejectIgnoredKeyPrefixes(
            [
                'hello.hello' => '',
                'hello.world' => '',
                'hello.wo' => '',
                'hello.world.hello' => '',
                'hello.world.hello.people' => '',
                'hello.world.hello.people.bb' => '',
                'hello.worlda' => '',
                'hello.worldab' => ''
            ],
            [
                'hello.hello' => '',
                'hello.wo' => '',
                'hello.worlda' => '',
                'hello.worldab' => ''
            ]
        );

        // multi-prefixes (file + root key) with ending separator (keep "hello.world" if plain value and not subtree)
        app()['config']->set('translation.ignored_key_prefixes', ['hello.world.']);

        $this->assertCorrectRejectIgnoredKeyPrefixes(
            [
                'hello.hello' => '',
                'hello.world' => '',
                'hello.wo' => '',
                'hello.world.hello' => '',
                'hello.world.hello.people' => '',
                'hello.world.hello.people.bb' => '',
                'hello.worlda' => '',
                'hello.worldab' => ''
            ],
            [
                'hello.hello' => '',
                'hello.world' => '',
                'hello.wo' => '',
                'hello.worlda' => '',
                'hello.worldab' => ''
            ]
        );

        // Folder prefix (ignore whole folder)
        app()['config']->set('translation.ignored_key_prefixes', ['subfolder/']);

        $this->assertCorrectRejectIgnoredKeyPrefixes(
            [
                'subfolder/hello.world' => '',
                'subfolder/bye.world' => '',
                'hello.world' => '',
                'folder/' => '',
                'subfolder.test.a' => '',
                'subfolder.test' => ''
            ],
            [
                'hello.world' => '',
                'folder/' => '',
                'subfolder.test.a' => '',
                'subfolder.test' => ''
            ]
        );

        // Folder prefix with key (ignore file inside folder)
        app()['config']->set('translation.ignored_key_prefixes', ['subfolder/hello']);

        $this->assertCorrectRejectIgnoredKeyPrefixes(
            [
                'subfolder/hello.world' => '',
                'subfolder/bye.world' => '',
                'hello.world' => '',
                'folder/' => '',
                'subfolder.test.a' => '',
                'subfolder.test' => ''
            ],
            [
                'subfolder/bye.world' => '',
                'hello.world' => '',
                'folder/' => '',
                'subfolder.test.a' => '',
                'subfolder.test' => ''
            ]
        );

        // Works with multiple ignored prefixes
        app()['config']->set('translation.ignored_key_prefixes', ['hello', 'bye']);

        $this->assertCorrectRejectIgnoredKeyPrefixes(
            [
                'hello.world' => '',
                'goodmorning.world' => '',
                'bye.world' => ''
            ],
            [
                'goodmorning.world' => ''
            ]
        );
    }

    private function assertCorrectExtraction($expected)
    {
        $this->assertEquals($expected, $this->extractor()->call('en'));
    }

    private function assertCorrectRejectIgnoredKeyPrefixes($translations, $expected)
    {
        $this->assertEquals($expected, $this->extractor()->rejectIgnoredKeyPrefixes($translations));
    }

    private function extractor(): TranslationExtractor
    {
        return app(TranslationExtractor::class);
    }
}
