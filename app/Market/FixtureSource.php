<?php

namespace App\Market;

use RuntimeException;

/** Explicitly synthetic/offline demo source. Never selected by default. */
final class FixtureSource implements MarketSource
{
    public function __construct(private TsetmcSource $source) {}

    public function fetch(): MarketSnapshot
    {
        $path = config('market.fixture_path');
        $body = is_file($path) ? file_get_contents($path) : false;
        if ($body === false) {
            throw new RuntimeException('Cannot read demo fixture: '.$path);
        }

        return $this->source->parse($body);
    }
}
