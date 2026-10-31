<?php

namespace App\Market;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class SyncInstruments
{
    /** @var resource|null */
    private $lockHandle = null;

    public function __construct(private MarketSource $source) {}

    /** @return array<string, int|float|string|null> */
    public function run(): array
    {
        $started = hrtime(true);
        $storeStarted = null;
        $context = [
            'source' => $this->source::class, 'status' => 'started', 'received' => null, 'valid' => null,
            'fetch_ms' => null, 'transform_ms' => null, 'store_ms' => 0, 'upsert_statements' => 0,
        ];
        try {
            if (! $this->acquireLock()) {
                $context['status'] = 'skipped_locked';
                $context['total_ms'] = (hrtime(true) - $started) / 1e6;
                Log::info('market_sync', $context);

                return $context;
            }
            $snapshot = $this->source->fetch();
            $context = array_replace($context, ['received' => count($snapshot->quotes), 'valid' => count($snapshot->quotes), 'fetch_ms' => $snapshot->fetchMs, 'transform_ms' => $snapshot->transformMs]);
            $storeStarted = hrtime(true);
            $now = now()->utc()->format('Y-m-d H:i:s.u');
            $rows = array_map(fn (array $quote): array => $quote + ['synced_at' => $now, 'created_at' => $now, 'updated_at' => $now], $snapshot->quotes);
            usort($rows, fn (array $a, array $b): int => strcmp($a['instrument_code'], $b['instrument_code']));
            DB::transaction(function () use ($rows, &$context): void {
                foreach (array_chunk($rows, max(1, min(1000, (int) config('market.chunk_size')))) as $chunk) {
                    DB::table('instruments')->upsert($chunk, ['instrument_code'], [...array_slice(MarketSnapshot::FIELDS, 1), 'synced_at', 'updated_at']);
                    $context['upsert_statements']++;
                }
            });
            $context['store_ms'] = (hrtime(true) - $storeStarted) / 1e6;
            $context['status'] = 'success';
            $context['total_ms'] = (hrtime(true) - $started) / 1e6;
            Log::info('market_sync', $context);

            return $context;
        } catch (Throwable $error) {
            if ($error instanceof FeedFailure) {
                $context = array_replace($context, $error->metrics);
            }
            if ($storeStarted !== null) {
                $context['store_ms'] = (hrtime(true) - $storeStarted) / 1e6;
            }
            $context['status'] = 'failed';
            $context['total_ms'] = (hrtime(true) - $started) / 1e6;
            $context['error_type'] = $error::class;
            $context['reason'] = mb_substr($error->getMessage(), 0, 1500);
            Log::error('market_sync', $context);
            throw $error;
        } finally {
            $this->releaseLock();
        }
    }

    private function acquireLock(): bool
    {
        $handle = fopen(config('market.lock_path'), 'c');
        if ($handle === false) {
            throw new RuntimeException('Cannot open sync lock.');
        }
        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return false;
        }
        $this->lockHandle = $handle;

        return true;
    }

    private function releaseLock(): void
    {
        if (is_resource($this->lockHandle)) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            $this->lockHandle = null;
        }
    }
}
