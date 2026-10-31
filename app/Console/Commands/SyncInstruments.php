<?php

namespace App\Console\Commands;

use App\Market\SyncInstruments as Synchronizer;
use Illuminate\Console\Command;
use Throwable;

class SyncInstruments extends Command
{
    protected $signature = 'instruments:sync';

    protected $description = 'Fetch, validate and atomically upsert a market snapshot';

    public function handle(Synchronizer $sync): int
    {
        try {
            $result = $sync->run();
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }
    }
}
