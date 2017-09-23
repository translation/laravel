<?php

require __DIR__ . '/../vendor/autoload.php';

\VCR\VCR::configure()
    ->setMode('once')
    ->enableRequestMatchers(['method', 'url', 'host', 'query_string', 'post_fields', 'body']);

\VCR\VCR::turnOn();