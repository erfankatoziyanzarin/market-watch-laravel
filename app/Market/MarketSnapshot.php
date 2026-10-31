<?php

namespace App\Market;

use Brick\Math\BigDecimal;
use Closure;
use InvalidArgumentException;
use JsonException;
use Throwable;

final readonly class MarketSnapshot
{
    public const FIELDS = ['instrument_code', 'symbol', 'company_name', 'last_price', 'closing_price', 'previous_closing_price', 'opening_price', 'trade_volume', 'trade_value', 'trade_count'];

    /** @param list<array<string, string>> $quotes */
    private function __construct(public array $quotes, public float $fetchMs, public float $transformMs) {}

    /** @param Closure(array<string, mixed>): array<string, mixed> $map */
    public static function fromJson(string $body, string $root, Closure $map, float $fetchMs): self
    {
        $started = hrtime(true);
        $received = null;
        $valid = 0;
        try {
            $payload = self::decodeJson($body);
            $rows = is_array($payload) ? ($payload[$root] ?? null) : null;
            if (is_array($rows) && array_is_list($rows)) {
                $received = count($rows);
            }
            if (! is_array($rows) || ! array_is_list($rows) || $rows === []) {
                throw new InvalidArgumentException('Expected a non-empty list at '.$root);
            }
            $received = count($rows);
            if ($received > config('market.max_records')) {
                throw new InvalidArgumentException('Snapshot exceeds record limit.');
            }
            $quotes = [];
            $seen = [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    throw new InvalidArgumentException('Each record must be an object.');
                }
                $quote = self::validateQuote($map($row));
                $code = $quote['instrument_code'];
                if (isset($seen[$code])) {
                    throw new InvalidArgumentException('Duplicate instrument_code: '.$code);
                }
                $seen[$code] = true;
                $quotes[] = $quote;
                $valid++;
            }

            return new self($quotes, $fetchMs, (hrtime(true) - $started) / 1e6);
        } catch (Throwable $error) {
            throw new FeedFailure($error->getMessage(), [
                'received' => $received, 'valid' => $valid, 'fetch_ms' => $fetchMs,
                'transform_ms' => (hrtime(true) - $started) / 1e6,
            ], $error);
        }
    }

    /** Decode numeric tokens as text so prices never pass through float. */
    private static function decodeJson(string $json): mixed
    {
        if (! json_validate($json)) {
            throw new JsonException(json_last_error_msg());
        }
        $pattern = '~"(?:[^"\\\\]|\\\\.)*"(*SKIP)(*F)|-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?~s';
        $quoted = preg_replace_callback($pattern, fn (array $match): string => '"'.$match[0].'"', $json);
        if ($quoted === null) {
            throw new JsonException('JSON numeric tokenization failed.');
        }

        return json_decode($quoted, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    private static function validateQuote(array $input): array
    {
        $data = [];
        foreach (self::FIELDS as $field) {
            if (! array_key_exists($field, $input) || $input[$field] === null) {
                throw new InvalidArgumentException("Missing or null field: {$field}");
            }
        }
        $code = $input['instrument_code'];
        if (! is_string($code) || ! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]{0,63}\z/D', $code)) {
            throw new InvalidArgumentException('Invalid canonical instrument_code.');
        }
        $data['instrument_code'] = $code;
        foreach (['symbol' => 80, 'company_name' => 255] as $field => $limit) {
            $value = is_string($input[$field]) ? trim(preg_replace('/[\t\r\n]+/u', ' ', $input[$field]), ' ') : $input[$field];
            if (! is_string($value) || trim($value) === '' || mb_strlen($value) > $limit || preg_match('/[\x00-\x1F\x7F]/u', $value)) {
                throw new InvalidArgumentException("Invalid text field: {$field}");
            }
            $data[$field] = trim($value);
        }
        foreach (array_slice(self::FIELDS, 3) as $field) {
            $raw = $input[$field];
            if ((! is_string($raw) && ! is_int($raw)) || strlen((string) $raw) > 80 || ! preg_match('/\A[0-9]+(?:\.[0-9]+)?(?:[eE][+-]?[0-9]{1,3})?\z/D', (string) $raw)) {
                throw new InvalidArgumentException("Invalid exact number: {$field}");
            }
            $scale = in_array($field, ['trade_volume', 'trade_count'], true) ? 0 : 4;
            $precision = $scale === 0 ? 28 : 30;
            $number = BigDecimal::of((string) $raw)->toScale($scale);
            if ($number->isGreaterThanOrEqualTo('1'.str_repeat('0', $precision - $scale))) {
                throw new InvalidArgumentException("Number out of range: {$field}");
            }
            $data[$field] = (string) $number;
        }

        return $data;
    }
}
