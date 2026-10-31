<?php

namespace App\Market;

use InvalidArgumentException;

final class TsetmcSource implements MarketSource
{
    public function __construct(private MarketHttpClient $http) {}

    public function fetch(): MarketSnapshot
    {
        $result = $this->http->get(config('market.tsetmc_url'));

        return $this->parse($result['body'], $result['fetch_ms']);
    }

    public function parse(string $body, float $fetchMs = 0): MarketSnapshot
    {
        return MarketSnapshot::fromJson($body, 'marketwatch', function (array $row): array {
            $code = $row['insCode'] ?? null;
            if (! is_string($code) || ! preg_match('/\A[1-9][0-9]{0,19}\z/D', $code)) {
                throw new InvalidArgumentException('Invalid TSETMC insCode.');
            }

            return [
                'instrument_code' => $code, 'symbol' => $row['lva'] ?? null,
                'company_name' => $row['lvc'] ?? null, 'last_price' => $row['pdv'] ?? null,
                'closing_price' => $row['pcl'] ?? null, 'previous_closing_price' => $row['py'] ?? null,
                'opening_price' => $row['pf'] ?? null, 'trade_volume' => $row['qtj'] ?? null,
                'trade_value' => $row['qtc'] ?? null, 'trade_count' => $row['ztt'] ?? null,
            ];
        }, $fetchMs);
    }
}
