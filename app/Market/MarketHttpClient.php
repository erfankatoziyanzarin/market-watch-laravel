<?php

namespace App\Market;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Throwable;

final class MarketHttpClient
{
    /** @return array{body: string, fetch_ms: float} */
    public function get(string $url): array
    {
        $started = hrtime(true);
        try {
            for ($attempt = 1; $attempt <= config('market.http_attempts'); $attempt++) {
                try {
                    $response = Http::acceptJson()
                        ->connectTimeout(config('market.connect_timeout'))
                        ->timeout(config('market.http_timeout'))
                        ->withOptions(['allow_redirects' => false])
                        ->get($url);
                    if ($response->successful()) {
                        return ['body' => $response->body(), 'fetch_ms' => (hrtime(true) - $started) / 1e6];
                    }
                    if ($attempt === config('market.http_attempts') || ($response->status() !== 429 && $response->status() < 500)) {
                        $response->throw();
                        throw new \RuntimeException('Unexpected HTTP status: '.$response->status());
                    }
                } catch (ConnectionException $error) {
                    if ($attempt === config('market.http_attempts')) {
                        throw $error;
                    }
                }
                Sleep::for(config('market.retry_delay_ms'))->milliseconds();
            }
            throw new \RuntimeException('No HTTP attempts configured.');
        } catch (Throwable $error) {
            throw new FeedFailure($error->getMessage(), ['received' => null, 'valid' => null, 'fetch_ms' => (hrtime(true) - $started) / 1e6, 'transform_ms' => 0], $error);
        }
    }
}
