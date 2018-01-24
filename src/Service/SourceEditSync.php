<?php

namespace Armandsar\LaravelTranslationio\Service;

use Armandsar\LaravelTranslationio\SourceSaver;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

class SourceEditSyncException extends \Exception {};

class SourceEditSync
{
    private $config;

    /**
     * @var Application
     */
    private $application;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var SourceSaver
     */
    private $sourceSaver;

    public function __construct(
        Application $application,
        Filesystem $filesystem,
        SourceSaver $sourceSaver
    )
    {
        $this->config = $application['config']['translationio'];
        $this->application = $application;
        $this->filesystem = $filesystem;
        $this->sourceSaver = $sourceSaver;
    }

    public function call()
    {
      $client = new Client(['base_uri' => $this->url() ]);
      $body = $this->createBody();
      $responseData = $this->makeRequest($client, $body);

      foreach ($responseData['source_edits'] as $sourceEdit) {
          $this->sourceSaver->call(
              $sourceEdit,
              $this->sourceLocale()
          );
      }

      $this->updateMetadataTimestamp();
    }

    private function createBody()
    {
        $locale = $this->sourceLocale();

        $formData = [
            'from' => 'laravel-translationio',
            'gem_version' => '2.0',
            'timestamp' => $this->metadataTimestamp(),
            'source_language' => $locale
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
        return 'https://translation.io/api/projects/' . $this->config['key'] . '/source_edits';
    }

    private function metadataFilePath()
    {
        return $this->application['path.lang'] . DIRECTORY_SEPARATOR . '.translation_io';
    }

    private function metadataTimestamp()
    {
        $metadataFilePath = $this->metadataFilePath();

        if ($this->filesystem->exists($metadataFilePath)) {
            $metadataContent = $this->filesystem->get($metadataFilePath);

            return $this->timestampFromMetadataContent($metadataContent);
        }
        else {
            return 0;
        }
    }

    private function timestampFromMetadataContent($metadataContent)
    {
        $this->throwErrorIfConflictInMetadata($metadataContent);

        $json = json_decode($metadataContent, true);

        if (is_null($json)) {
            return 0;
        }
        else {
            return $json['timestamp'];
        }
    }

    private function throwErrorIfConflictInMetadata($metadataContent)
    {
      if (str_contains($metadataContent, ['>>>>', '<<<<'])) {
          $metadataFilePath = $this->metadataFilePath();
          throw new SourceEditSyncException($metadataFilePath . " file is corrupted and seems to have unresolved versioning conflicts. Please resolve them and try again.");
      }
    }

    private function updateMetadataTimestamp()
    {
        $data = json_encode([ "timestamp" => Carbon::now()->timestamp ]);
        $metadataFilePath = $this->metadataFilePath();

        $this->filesystem->put($metadataFilePath, $data);
    }
}
