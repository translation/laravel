<?php

namespace Tio\Laravel\Service;

use Tio\Laravel\POExtractor;
use Tio\Laravel\GettextPOGenerator;
use Tio\Laravel\SourcePOGenerator;
use Tio\Laravel\TranslationSaver;
use Tio\Laravel\GettextTranslationSaver;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;

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
        $this->config = $application['config']['translation'];
        $this->poGenerator = $poGenerator;
        $this->gettextPoGenerator = $gettextPoGenerator;
        $this->poExtractor = $poExtractor;
        $this->translationSaver = $translationSaver;
        $this->gettextTranslationSaver = $gettextTranslationSaver;
    }

    public function call($command, $options = [ 'purge' => false, 'show_purgeable' => false ])
    {
        $client = new Client(['base_uri' => $this->url()]);
        $body = $this->createBody($options['purge']);

        $responseData = $this->makeRequest($client, $body, $command);

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

        if ($options['show_purgeable']) {
          $this->displayUnusedSegments($responseData, $command, $options['show_purgeable'], $options['purge']);
        }

        $this->displayInfoProjectUrl($responseData, $command);
    }

    private function createBody($purge = false)
    {
        $locale = $this->sourceLocale();

        $formData = [
            'client' => 'laravel',
            'version' => '1.15',
            'source_language' => $locale
        ];

        if ($purge) {
            $formData['purge'] = 'true';
        }

        // keys from PHP translation files
        $formData['yaml_pot_data'] = $this->poGenerator->call($locale);

        // sources from Gettext
        $gettextPoData = $this->gettextPoGenerator->call([]);
        $formData['pot_data'] = $gettextPoData['pot_data'];

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

    private function displayUnusedSegments($responseData, $command, $showPurgeable, $purge)
    {
        $unusedSegments = collect($responseData['unused_segments']);

        $yamlUnusedSegments = $unusedSegments->filter(function ($unusedSegment) {
            return $unusedSegment['kind'] == 'yaml';
        });

        $gettextUnusedSegments = $unusedSegments->filter(function ($unusedSegment) {
            return $unusedSegment['kind'] == 'gettext';
        });

        $yamlSize    = $yamlUnusedSegments->count();
        $gettextSize = $gettextUnusedSegments->count();
        $totalSize   = $yamlSize + $gettextSize;

        // Quick unused segments summary for simple "sync"
        if ( !$showPurgeable && !$purge) {
            if ($totalSize > 0) {
                $sum = $yamlSize + $gettextSize;

                $command->line("");
                $command->line("----------");
                $command->line("{$sum} keys/strings are in Translation.io but not in your current branch.");
                $command->line('Execute "php artisan translation:sync_and_show_purgeable" to list these keys/strings.');
            }
        }
        // Complete summary for sync_and_show_purgeable or sync_and_purge
        else {
            $text = "";

            if ($purge) {
                $text = "were removed from Translation.io to match your current branch:";
            }
            else if ($showPurgeable) {
                $text = "are in Translation.io but not in your current branch:";
            }

            if ($yamlSize > 0) {
                $keysText = $yamlSize == 1 ? 'key' : 'keys';

                $command->line("");
                $command->line("----------");
                $command->line("{$yamlSize} YAML {$keysText} {$text}");
                $command->line("");

                $yamlUnusedSegments->each(function ($yamlUnusedSegment) use ($command) {
                    $command->line("[{$yamlUnusedSegment['languages']}] [{$yamlUnusedSegment['msgctxt']}] \"{$yamlUnusedSegment['msgid']}\"");
                });
            }

            if ($gettextSize > 0) {
                $stringsText = $gettextSize == 1 ? 'string' : 'strings';

                $command->line("");
                $command->line("----------");
                $command->line("{$gettextSize} GetText {$stringsText} {$text}");
                $command->line("");

                $gettextUnusedSegments->each(function ($gettextUnusedSegment) use ($command) {
                    $command->line("[{$gettextUnusedSegment['languages']}] \"{$gettextUnusedSegment['msgid']}\"");
                });
            }

            // Special message for when nothing need to be purged
            if ($totalSize == 0) {
                $command->line("");
                $command->line("----------");
                $command->line("Nothing to purge: all the keys/strings in Translation.io are also in your current branch.");
            }

            // Special message when sync_and_show_purgeable and unused segments
            if ($showPurgeable && $totalSize > 0) {
                $command->line("");
                $command->line("----------");
                $command->line("If you know what you are doing, you can remove them using \"php artisan translation:sync_and_purge\".");
            }
        }
    }

    private function displayInfoProjectUrl($responseData, $command)
    {
        $command->info("Sync ended with success");
        $command->line("----------");
        $command->info("Use this URL to translate: {$responseData['project_url']}");
        $command->line("----------");
    }


    private function displayErrorAndExit($error, $command)
    {
        $command->line("----------");
        $command->error("Error: {$error}");
        $command->line("----------");

        throw new \Exception($error);
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
