<?php

namespace Armandsar\LaravelTranslationio\Service;

use Armandsar\LaravelTranslationio\POExtractor;
use Armandsar\LaravelTranslationio\SourcePOGenerator;
use Armandsar\LaravelTranslationio\TranslationSaver;
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
     * @var POExtractor
     */
    private $poExtractor;
    /**
     * @var TranslationSaver
     */
    private $translationSaver;

    public function __construct(
        Application $application,
        SourcePOGenerator $poGenerator,
        POExtractor $poExtractor,
        TranslationSaver $translationSaver
    )
    {
        $this->config = $application['config']['translationio'];
        $this->poGenerator = $poGenerator;
        $this->poExtractor = $poExtractor;
        $this->translationSaver = $translationSaver;
    }

    public function call()
    {
      $client = new Client(['base_uri' => $this->url()]);
      $body = $this->createBody();

      $responseData = $this->makeRequest($client, $body);

      foreach ($this->targetLocales() as $locale) {
          $this->translationSaver->call(
              $locale,
              $this->poExtractor->call($responseData['yaml_po_data_' . $locale])
          );
      }
    }

    private function createBody()
    {
        $locale = $this->sourceLocale();

        $formData = [
            'from' => 'laravel-translationio',
            'gem_version' => '2.0',
            'source_language' => $locale,
            'yaml_pot_data' => $this->poGenerator->call($locale)
        ];

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
