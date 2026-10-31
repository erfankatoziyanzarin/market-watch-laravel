<?php

use App\Market\TsetmcSource;

return [
    'source' => env('MARKET_SOURCE', TsetmcSource::class),
    'tsetmc_url' => env('TSETMC_URL', 'https://cdn.tsetmc.com/api/ClosingPrice/GetMarketWatch?market=0&paperTypes%5B0%5D=1'),
    'alternative_url' => env('ALTERNATIVE_MARKET_URL', 'https://example.test/quotes'),
    'fixture_path' => env('MARKET_FIXTURE_PATH', base_path('tests/Fixtures/tsetmc.json')),
    'lock_path' => env('MARKET_LOCK_PATH', storage_path('app/instrument-sync.lock')),
    'chunk_size' => 500,
    'max_records' => 20000,
    'connect_timeout' => 4,
    'http_timeout' => 8,
    'http_attempts' => 2,
    'retry_delay_ms' => 200,
];
