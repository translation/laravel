<?php

namespace Armandsar\LaravelTranslationio\Service;

use Armandsar\LaravelTranslationio\POExtractor;
use Armandsar\LaravelTranslationio\GettextPOGenerator;
use Armandsar\LaravelTranslationio\SourcePOGenerator;
use Armandsar\LaravelTranslationio\TranslationSaver;
use Armandsar\LaravelTranslationio\GettextTranslationSaver;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;


class Sync
{
    private $config;

    /**
     * @var SourcePOGenerator
     */
    private $poGenerator;

    /**
     * @var GettextPOGenerator
     */
    private $gettextPoGenerator;

    /**
     * @var POExtractor
     */
    private $poExtractor;

    /**
     * @var TranslationSaver
     */
    private $translationSaver;

    /**
     * @var GettextTranslationSaver
     */
    private $gettextTranslationSaver;

    public function __construct(
        Application $application,
        SourcePOGenerator $poGenerator,
        GettextPOGenerator $gettextPoGenerator,
        POExtractor $poExtractor,
        TranslationSaver $translationSaver,
        GettextTranslationSaver $gettextTranslationSaver
    )
    {
        $this->config = $application['config']['translationio'];
        $this->poGenerator = $poGenerator;
        $this->gettextPoGenerator = $gettextPoGenerator;
        $this->poExtractor = $poExtractor;
        $this->translationSaver = $translationSaver;
        $this->gettextTranslationSaver = $gettextTranslationSaver;
    }

    public function call()
    {
        $client = new Client(['base_uri' => $this->url()]);
        $body = $this->createBody();

        $responseData = $this->makeRequest($client, $body);

        # Save new key/values sent from backend
        foreach ($this->targetLocales() as $locale) {
            $this->translationSaver->call(
                $locale,
                $this->poExtractor->call($responseData['yaml_po_data_' . $locale])
            );
        }

        # Save new po files created and sent from backend
        foreach ($this->targetLocales() as $locale) {
            $this->gettextTranslationSaver->call(
                $locale,
                $responseData['po_data_' . $locale]
            );
        }
    }

    private function createBody()
    {
        $locale = $this->sourceLocale();

        $formData = [
            'from' => 'laravel-translationio',
            'gem_version' => '2.0',
            'source_language' => $locale
        ];

        // keys from PHP translation files
        $formData['yaml_pot_data'] = $this->poGenerator->call($locale);

        // sources from Gettext
        $gettextPoData = $this->gettextPoGenerator->call($locale, []);
        $formData['pot_data'] = $gettextPoData['pot_data'];

        $body = http_build_query($formData);

        foreach ($this->targetLocales() as $locale) {
            $body = $body . "&target_languages[]=$locale";
        }

        return $body;
    }

    private function makeRequest($client, $body)
    {
        $response = $client->request('POST', '', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => $body
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function sourceLocale()
    {
        return $this->config['source_locale'];
    }

    private function targetLocales()
    {
        return $this->config['target_locales'];
    }

    private function url()
    {
        return 'https://translation.io/api/projects/' . $this->config['key'] . '/sync';
    }
}
