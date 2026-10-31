<?php

namespace Tests\Fixtures;

use App\Market\MarketSnapshot;
use App\Market\MarketSource;
use App\Market\TsetmcSource;

/** Used only by the explicit scheduler smoke demo; intentionally takes twelve seconds. */
final class SlowSource implements MarketSource
{
    public function __construct(private TsetmcSource $source) {}

    public function fetch(): MarketSnapshot
    {
        sleep(12);

        return $this->source->parse(file_get_contents(base_path('tests/Fixtures/tsetmc.json')));
    }
}
