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

    private function saver(): TranslationSaver
    {
        return app(TranslationSaver::class);
    }
}
