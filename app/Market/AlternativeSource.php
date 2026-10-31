<?php

namespace App\Market;

/** Example second HTTP provider; its identifier must map to the same canonical market identity. */
final class AlternativeSource implements MarketSource
{
    public function __construct(private MarketHttpClient $http) {}

    public function fetch(): MarketSnapshot
    {
        $result = $this->http->get(config('market.alternative_url'));

        return MarketSnapshot::fromJson($result['body'], 'items', fn (array $row): array => [
            'instrument_code' => $row['code'] ?? null, 'symbol' => $row['ticker'] ?? null,
            'company_name' => $row['issuer'] ?? null, 'last_price' => $row['prices']['last'] ?? null,
            'closing_price' => $row['prices']['close'] ?? null, 'previous_closing_price' => $row['prices']['previous'] ?? null,
            'opening_price' => $row['prices']['open'] ?? null, 'trade_volume' => $row['activity']['volume'] ?? null,
            'trade_value' => $row['activity']['value'] ?? null, 'trade_count' => $row['activity']['count'] ?? null,
        ], $result['fetch_ms']);
    }
}
