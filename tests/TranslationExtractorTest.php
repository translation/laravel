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

    private function assertCorrectExtraction($expected)
    {
        $this->assertEquals($expected, $this->extractor()->call('en'));
    }

    private function extractor(): TranslationExtractor
    {
        return app(TranslationExtractor::class);
    }
}
