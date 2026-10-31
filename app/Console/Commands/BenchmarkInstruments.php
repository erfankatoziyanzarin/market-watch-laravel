<?php

namespace App\Console\Commands;

use App\Market\FixtureSource;
use App\Market\SyncInstruments;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BenchmarkInstruments extends Command
{
    protected $signature = 'instruments:benchmark {--records=5000} {--runs=5}';

    protected $description = 'Compare single-row and grouped upserts in the isolated market_watch_benchmark database';

    public function handle(): int
    {
        $records = (int) $this->option('records');
        $runs = (int) $this->option('runs');
        if ($records < 1 || $records > 10000 || $runs < 1 || $runs > 10) {
            $this->error('records must be 1..10000; runs must be 1..10.');

            return self::FAILURE;
        }
        config(['database.connections.mysql.database' => 'market_watch_benchmark', 'database.default' => 'mysql']);
        DB::purge('mysql');
        $this->callSilent('migrate', ['--force' => true]);
        $template = json_decode(file_get_contents(base_path('tests/Fixtures/tsetmc.json')), true)['marketwatch'][0];
        $rows = [];
        for ($number = 1; $number <= $records; $number++) {
            $rows[] = array_replace($template, ['insCode' => (string) (70000000000000000 + $number)]);
        }
        $path = storage_path('app/benchmark-fixture.json');
        file_put_contents($path, json_encode(['marketwatch' => $rows], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        config(['market.source' => FixtureSource::class, 'market.fixture_path' => $path, 'market.lock_path' => storage_path('app/benchmark.lock')]);
        $report = ['database' => 'market_watch_benchmark', 'engine' => DB::selectOne('SELECT VERSION() AS version')->version, 'php' => PHP_VERSION, 'laravel' => app()->version(), 'records' => $records, 'runs_per_case' => $runs, 'network' => 'offline synthetic fixture; fetch_ms=0', 'cases' => []];
        try {
            foreach ([1, 500] as $chunkSize) {
                config(['market.chunk_size' => $chunkSize]);
                DB::table('instruments')->truncate();
                $samples = [];
                for ($run = 0; $run < $runs; $run++) {
                    DB::enableQueryLog();
                    DB::flushQueryLog();
                    $sample = app(SyncInstruments::class)->run();
                    $sample['database_statements'] = count(DB::getQueryLog());
                    DB::disableQueryLog();
                    DB::flushQueryLog();
                    $samples[] = $sample;
                }
                $report['cases'][] = ['chunk_size' => $chunkSize, 'samples' => $samples, 'rows_after' => DB::table('instruments')->count()];
            }
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        } finally {
            @unlink($path);
        }
    }
}
