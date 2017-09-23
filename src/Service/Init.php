<?php

namespace Armandsar\LaravelTranslationio\Service;

use Armandsar\LaravelTranslationio\TargetPOGenerator;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;

class Init
{
    private $config;

    /**
     * @var TargetPOGenerator
     */
    private $poGenerator;

    public function __construct(Application $application, TargetPOGenerator $poGenerator)
    {
        $this->poGenerator = $poGenerator;
        $this->config = $application['config']['translationio'];
    }

    public function call()
    {
        $formData = [
            'gem_version' => '2.0',
            'source_language' => $this->sourceLocale(),
        ];

        $poData = $this->poGenerator->call($this->sourceLocale(), $this->targetLocales());
        foreach ($this->targetLocales() as $locale) {
            $formData['yaml_po_data_' . $locale] = $poData[$locale];
        }

        $client = new Client(['base_uri' => $this->url()]);

        $body = http_build_query($formData);

        foreach ($this->targetLocales() as $locale) {
            $body = $body . "&target_languages[]=$locale";
        }

        $client->request('POST', '', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => $body
        ]);
    }


    private function targetLocales()
    {
        return $this->config['target_locales'];
    }

    private function url()
    {
//        return 'https://requestb.in/11l8hjp1';
        return 'https://translation.io/api/projects/' . $this->config['key'] . '/init';
    }

    private function sourceLocale()
    {
        return $this->config['source_locale'];
    }
}
