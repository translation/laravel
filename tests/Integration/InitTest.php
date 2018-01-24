<?php

namespace Armandsar\LaravelTranslationio\Tests\Integration;


use Armandsar\LaravelTranslationio\Tests\TestCase;

class InitTest extends TestCase
{
    public function testItWorks()
    {
        app()['config']->set('translationio.target_locales', ['lv', 'ru']);
        app()['config']->set('translationio.key', '13f5dbf135334c20aa4fed952f5a81f9');

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
        $this->artisan('translations:init');

        // if we got this far, all is good
        $this->assertTrue(true);
    }
}
