<?php

namespace Tio\Laravel\Service;

use Tio\Laravel\SourceSaver;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

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
        $this->config = $application['config']['translation'];
        $this->application = $application;
        $this->filesystem = $filesystem;
        $this->sourceSaver = $sourceSaver;
    }

    public function call($command)
    {
      $client = new Client(['base_uri' => $this->url() ]);
      $body = $this->createBody($command);
      $responseData = $this->makeRequest($client, $body, $command);

      foreach ($responseData['source_edits'] as $sourceEdit) {
          $this->sourceSaver->call(
              $sourceEdit,
              $this->sourceLocale()
          );
      }

      $this->updateMetadataTimestamp();
    }

    private function createBody($command)
    {
        $locale = $this->sourceLocale();

        $formData = [
            'client' => 'laravel',
            'version' => '1.15',
            'timestamp' => $this->metadataTimestamp($command),
            'source_language' => $locale
        ];

        $body = http_build_query($formData);

        foreach ($this->targetLocales() as $locale) {
            $body = $body . "&target_languages[]=$locale";
        }

        return $body;
    }

    private function makeRequest($client, $body, $command)
    {
        try {
            $response = $client->request('POST', '', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'body' => $body
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $responseData = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->displayErrorAndExit($responseData['error'], $command);
            }
            else {
                $this->displayErrorAndExit($e->getMessage(), $command);
            }
        }
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

    private function metadataTimestamp($command)
    {
        $metadataFilePath = $this->metadataFilePath();

        if ($this->filesystem->exists($metadataFilePath)) {
            $metadataContent = $this->filesystem->get($metadataFilePath);

            return $this->timestampFromMetadataContent($metadataContent, $command);
        }
        else {
            return 0;
        }
    }

    private function timestampFromMetadataContent($metadataContent, $command)
    {
        $this->displayMetadataErrorAndExit($metadataContent, $command);

        $json = json_decode($metadataContent, true);

        if (is_null($json)) {
            return 0;
        }
        else {
            return $json['timestamp'];
        }
    }

    private function displayMetadataErrorAndExit($metadataContent, $command)
    {
      if (Str::contains($metadataContent, ['>>>>', '<<<<'])) {
          $metadataFilePath = $this->metadataFilePath();

          $command->line("----------");
          $command->error("Error: " . $metadataFilePath . " file seems to have unresolved versioning conflicts. Please fix them and try again.");
          $command->line("----------");

          exit(1);
      }
    }

    private function updateMetadataTimestamp()
    {
        $data = json_encode([ "timestamp" => Carbon::now()->timestamp ]);
        $metadataFilePath = $this->metadataFilePath();

        $this->filesystem->put($metadataFilePath, $data);
    }

    private function displayErrorAndExit($error, $command)
    {
        $command->line("----------");
        $command->error("Error: {$error}");
        $command->line("----------");

        throw new \Exception($error);
    }
}
