<?php

namespace Tests\Feature;

use App\Market\AlternativeSource;
use App\Market\SyncInstruments;
use App\Market\TsetmcSource;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class MarketSyncTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['market.source' => TsetmcSource::class]);
        Http::preventStrayRequests();
    }

    private mixed $nextResponse = null;

    private bool $httpFaked = false;

    private function fakeResponse(mixed $response): void
    {
        $this->nextResponse = $response;
        if (! $this->httpFaked) {
            Http::fake([config('market.tsetmc_url') => fn () => $this->nextResponse]);
            $this->httpFaked = true;
        }
    }

    private function row(string $code = '1001', array $overrides = []): array
    {
        $rows = json_decode(file_get_contents(base_path('tests/Fixtures/tsetmc.json')), true)['marketwatch'];

        return array_replace($rows[0], ['insCode' => $code], $overrides);
    }

    private function fakeRows(array $rows): void
    {
        $this->fakeResponse(Http::response(['marketwatch' => $rows]));
    }

    private function records(): array
    {
        return DB::table('instruments')->orderBy('instrument_code')->get()->map(fn ($row): array => (array) $row)->all();
    }

    public function test_inserts_selected_fields_from_a_real_response_shape(): void
    {
        $this->fakeRows([$this->row(), $this->row('1002')]);

        $result = app(SyncInstruments::class)->run();

        $this->assertSame('success', $result['status']);
        $this->assertSame(2, $result['valid']);
        $this->assertSame(1, $result['upsert_statements']);
        $this->assertDatabaseCount('instruments', 2);
        $this->assertDatabaseHas('instruments', ['instrument_code' => '1001', 'symbol' => 'خوساز', 'last_price' => '18260.0000', 'closing_price' => '18000.0000', 'trade_volume' => '806518', 'trade_value' => '14516223770.0000']);
        Http::assertSentCount(1);
    }

    public function test_updates_existing_symbol_preserving_id_and_creation_time(): void
    {
        $this->freezeTime();
        $this->fakeRows([$this->row()]);
        app(SyncInstruments::class)->run();
        $before = DB::table('instruments')->first();
        $this->travel(20)->seconds();
        $this->fakeRows([$this->row(overrides: ['pdv' => 123, 'lva' => 'جدید'])]);

        app(SyncInstruments::class)->run();

        $after = DB::table('instruments')->first();
        $this->assertSame($before->id, $after->id);
        $this->assertSame($before->created_at, $after->created_at);
        $this->assertNotSame($before->synced_at, $after->synced_at);
        $this->assertDatabaseCount('instruments', 1);
        $this->assertDatabaseHas('instruments', ['last_price' => '123.0000', 'symbol' => 'جدید']);
        Http::assertSentCount(2);
    }

    public function test_repeated_identical_snapshots_preserve_uniqueness(): void
    {
        $this->fakeRows([$this->row()]);

        for ($i = 0; $i < 3; $i++) {
            app(SyncInstruments::class)->run();
        }

        $this->assertDatabaseCount('instruments', 1);
        Http::assertSentCount(3);
    }

    public function test_absent_symbols_are_not_deleted(): void
    {
        $this->fakeRows([$this->row(), $this->row('1002')]);
        app(SyncInstruments::class)->run();
        $this->fakeRows([$this->row()]);

        app(SyncInstruments::class)->run();

        $this->assertDatabaseCount('instruments', 2);
        $this->assertDatabaseHas('instruments', ['instrument_code' => '1002']);
        Http::assertSentCount(2);
    }

    public static function badResponses(): array
    {
        return [
            'empty list' => ['{"marketwatch":[]}'],
            'malformed JSON' => ['{"marketwatch":'],
            'missing envelope' => ['{"items":[]}'],
            'object instead of list' => ['{"marketwatch":{"bad":{}}}'],
            'null envelope' => ['{"marketwatch":null}'],
            'HTML' => ['<html>blocked</html>'],
            'scalar row' => ['{"marketwatch":[12]}'],
            'empty body' => [''],
        ];
    }

    #[DataProvider('badResponses')]
    public function test_preserves_previous_snapshot_for_invalid_responses(string $body): void
    {
        $this->fakeRows([$this->row()]);
        app(SyncInstruments::class)->run();
        $before = $this->records();
        $this->fakeResponse(Http::response($body));

        $this->artisan('instruments:sync')->assertFailed();

        $this->assertSame($before, $this->records());
        Http::assertSentCount(2);
    }

    public function test_rejects_unquoted_numeric_json_keys_before_numeric_token_conversion(): void
    {
        $this->fakeRows([$this->row()]);
        app(SyncInstruments::class)->run();
        $before = $this->records();
        $malformed = '{"marketwatch":['.json_encode($this->row(overrides: ['pdv' => 999])).'],1:2}';
        $this->fakeResponse(Http::response($malformed));

        $this->artisan('instruments:sync')->assertFailed();

        $this->assertSame($before, $this->records());
        Http::assertSentCount(2);
    }

    public static function invalidRecords(): array
    {
        return [
            'null numeric' => [['pdv' => null]],
            'negative price' => [['pdv' => '-1']],
            'invalid numeric' => [['qtj' => 'NaN']],
            'fractional volume' => [['qtj' => '1.1']],
            'price overflow' => [['pdv' => '100000000000000000000000000']],
            'volume overflow' => [['qtj' => '10000000000000000000000000000']],
            'too much precision' => [['pcl' => '1.00001']],
            'blank symbol' => [['lva' => ' ']],
            'too long name' => [['lvc' => str_repeat('a', 256)]],
            'zero identifier' => [['insCode' => '0']],
            'too long identifier' => [['insCode' => str_repeat('1', 21)]],
            'invalid identifier' => [['insCode' => 'abc']],
            'leading zero identifier' => [['insCode' => '001']],
            'null identifier' => [['insCode' => null]],
            'control character' => [['lva' => "bad\0name"]],
            'leading null character' => [['lvc' => "\0name"]],
            'boolean numeric' => [['qtj' => true]],
        ];
    }

    #[DataProvider('invalidRecords')]
    public function test_rejects_whole_mixed_snapshot_when_one_record_is_invalid(array $invalid): void
    {
        $this->fakeRows([$this->row()]);
        app(SyncInstruments::class)->run();
        $before = $this->records();
        $this->fakeRows([$this->row(overrides: ['pdv' => 999]), $this->row('1002', $invalid)]);

        $this->artisan('instruments:sync')->assertFailed();

        $this->assertSame($before, $this->records());
        Http::assertSentCount(2);
    }

    public function test_rejects_missing_fields_before_any_write(): void
    {
        $row = $this->row();
        unset($row['pf']);
        $this->fakeRows([$this->row('1002'), $row]);

        $this->artisan('instruments:sync')->assertFailed();

        $this->assertDatabaseEmpty('instruments');
        Http::assertSentCount(1);
    }

    public function test_zero_is_a_valid_value_for_all_numeric_fields(): void
    {
        $this->fakeRows([$this->row(overrides: array_fill_keys(['pdv', 'pcl', 'py', 'pf', 'qtj', 'qtc', 'ztt'], 0))]);

        $this->artisan('instruments:sync')->assertSuccessful();

        $this->assertDatabaseHas('instruments', ['last_price' => '0.0000', 'opening_price' => '0.0000', 'trade_volume' => '0', 'trade_count' => '0', 'trade_value' => '0.0000']);
        Http::assertSentCount(1);
    }

    public function test_normalizes_trailing_whitespace_observed_in_live_company_names(): void
    {
        $this->fakeRows([$this->row(overrides: ['lvc' => "شرکت نمونه\t", 'lva' => ' نماد '])]);

        $this->artisan('instruments:sync')->assertSuccessful();

        $this->assertDatabaseHas('instruments', ['company_name' => 'شرکت نمونه', 'symbol' => 'نماد']);
        Http::assertSentCount(1);
    }

    public function test_duplicate_identifiers_reject_even_identical_records(): void
    {
        $this->fakeRows([$this->row(), $this->row()]);

        $this->artisan('instruments:sync')->assertFailed();

        $this->assertDatabaseEmpty('instruments');
        Http::assertSentCount(1);
    }

    public function test_preserves_raw_numeric_precision_beyond_binary_float_range(): void
    {
        $row = $this->row();
        $body = json_encode(['marketwatch' => [$row]], JSON_UNESCAPED_UNICODE);
        $body = str_replace('"qtc":14516223770', '"qtc":9007199254740993.1234', $body);
        $body = str_replace('"qtj":806518', '"qtj":1.2345e8', $body);
        $this->fakeResponse(Http::response($body));

        $this->artisan('instruments:sync')->assertSuccessful();

        $this->assertDatabaseHas('instruments', ['trade_value' => '9007199254740993.1234', 'trade_volume' => '123450000']);
        Http::assertSentCount(1);
    }

    public static function httpFailures(): array
    {
        return ['forbidden without retry' => [403, 1], 'not found without retry' => [404, 1], 'redirect refused' => [302, 1], 'rate limit with retry' => [429, 2], 'server failure with retry' => [503, 2]];
    }

    #[DataProvider('httpFailures')]
    public function test_http_errors_preserve_previous_data_with_bounded_retry(int $status, int $attempts): void
    {
        Sleep::fake();
        $this->fakeRows([$this->row()]);
        app(SyncInstruments::class)->run();
        $before = $this->records();
        $this->fakeResponse(Http::response('upstream error', $status));

        $this->artisan('instruments:sync')->assertFailed();

        $this->assertSame($before, $this->records());
        Http::assertSentCount(1 + $attempts);
        Sleep::assertSleptTimes($attempts - 1);
    }

    public static function connectionFailures(): array
    {
        return ['network' => ['Connection refused'], 'timeout' => ['cURL error 28: Operation timed out']];
    }

    #[DataProvider('connectionFailures')]
    public function test_connection_failure_preserves_data_and_releases_lock(string $reason): void
    {
        Sleep::fake();
        $this->fakeRows([$this->row()]);
        app(SyncInstruments::class)->run();
        $before = $this->records();
        $this->fakeResponse(Http::failedConnection($reason));

        $this->artisan('instruments:sync')->assertFailed();

        $this->assertSame($before, $this->records());
        Sleep::assertSleptTimes(1);
        $this->fakeRows([$this->row(overrides: ['pdv' => 77])]);
        $this->artisan('instruments:sync')->assertSuccessful();
        $this->assertDatabaseHas('instruments', ['last_price' => '77.0000']);
        Http::assertSentCount(4);
    }

    public function test_transient_server_failure_is_retried_and_then_succeeds(): void
    {
        Sleep::fake();
        Http::fake([config('market.tsetmc_url') => Http::sequence()->push('temporary', 503)->push(['marketwatch' => [$this->row()]])]);

        $this->artisan('instruments:sync')->assertSuccessful();

        $this->assertDatabaseCount('instruments', 1);
        Http::assertSentCount(2);
        Sleep::assertSleptTimes(1);
    }

    public function test_alternative_http_source_uses_new_url_and_nested_keys_without_sync_changes(): void
    {
        $this->fakeRows([$this->row()]);
        app(SyncInstruments::class)->run();
        $originalId = DB::table('instruments')->value('id');
        Http::assertSentCount(1);
        config(['market.source' => AlternativeSource::class, 'market.alternative_url' => 'https://other.example.test/v2/feed']);
        Http::fake(['https://other.example.test/v2/feed' => Http::response(['items' => [[
            'code' => '1001', 'ticker' => 'بدیل', 'issuer' => 'شرکت آزمایشی',
            'prices' => ['last' => '12.25', 'close' => 12, 'previous' => 11, 'open' => 11],
            'activity' => ['volume' => 100, 'value' => 1225, 'count' => 2],
        ]]])]);

        $this->artisan('instruments:sync')->assertSuccessful();

        $this->assertDatabaseHas('instruments', ['instrument_code' => '1001', 'symbol' => 'بدیل', 'last_price' => '12.2500']);
        $this->assertDatabaseCount('instruments', 1);
        $this->assertSame($originalId, DB::table('instruments')->value('id'));
        Http::assertSent(fn ($request): bool => $request->url() === 'https://other.example.test/v2/feed');
    }

    public function test_another_process_holds_lock_until_death_without_network_or_database_work(): void
    {
        Http::fake([config('market.tsetmc_url') => Http::response(['marketwatch' => [$this->row()]])]);
        $process = new Process([PHP_BINARY, '-r', '$f=fopen($argv[1],"c");flock($f,LOCK_EX);echo "READY";fflush(STDOUT);sleep(60);', config('market.lock_path')]);
        $process->start();
        try {
            $this->assertTrue($process->waitUntil(fn ($type, $output): bool => str_contains($output, 'READY')));
            $result = app(SyncInstruments::class)->run();
            $this->assertSame('skipped_locked', $result['status']);
            $this->assertDatabaseEmpty('instruments');
            Http::assertNothingSent();
        } finally {
            $process->stop(0, SIGKILL);
        }

        $this->artisan('instruments:sync')->assertSuccessful();
        $this->assertDatabaseCount('instruments', 1);
        Http::assertSentCount(1);
    }

    public function test_unique_constraint_prevents_duplicate_rows_outside_synchronizer(): void
    {
        $this->fakeRows([$this->row()]);
        app(SyncInstruments::class)->run();
        $row = (array) DB::table('instruments')->first();
        unset($row['id']);

        try {
            DB::table('instruments')->insert($row);
            $this->fail('Unique constraint did not reject duplicate.');
        } catch (QueryException $error) {
            $this->assertSame('23000', $error->errorInfo[0]);
        }

        $this->assertDatabaseCount('instruments', 1);
        Http::assertSentCount(1);
    }

    public function test_logs_success_and_failure_with_counts_and_phase_timings(): void
    {
        Log::spy();
        $this->fakeRows([$this->row()]);
        app(SyncInstruments::class)->run();
        $this->fakeResponse(Http::response('{invalid'));

        $this->artisan('instruments:sync')->assertFailed();

        Log::shouldHaveReceived('info')->with('market_sync', \Mockery::on(fn (array $c): bool => $c['status'] === 'success' && $c['valid'] === 1 && $c['fetch_ms'] >= 0 && $c['transform_ms'] >= 0 && $c['store_ms'] >= 0 && $c['total_ms'] >= 0))->once();
        Log::shouldHaveReceived('error')->with('market_sync', \Mockery::on(fn (array $c): bool => $c['status'] === 'failed' && $c['reason'] !== '' && $c['upsert_statements'] === 0))->once();
        $this->assertDatabaseCount('instruments', 1);
        Http::assertSentCount(2);
    }

    public function test_batches_database_writes_instead_of_querying_per_symbol(): void
    {
        config(['market.chunk_size' => 500]);
        $this->fakeRows(array_map(fn (int $n): array => $this->row((string) $n), range(1, 1001)));
        DB::enableQueryLog();
        DB::flushQueryLog();

        $result = app(SyncInstruments::class)->run();

        $statements = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(3, $statements);
        $this->assertSame(3, $result['upsert_statements']);
        $this->assertDatabaseCount('instruments', 1001);
        Http::assertSentCount(1);
    }
}
