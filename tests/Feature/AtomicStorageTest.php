<?php

namespace Tests\Feature;

use App\Market\SyncInstruments;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AtomicStorageTest extends TestCase
{
    use DatabaseMigrations;

    private mixed $nextResponse = null;

    public function test_mysql_trigger_failure_in_second_chunk_rolls_back_first_chunk_and_releases_lock(): void
    {
        Http::preventStrayRequests();
        $row = json_decode(file_get_contents(base_path('tests/Fixtures/tsetmc.json')), true)['marketwatch'][0];
        $row['insCode'] = '1001';
        $this->nextResponse = Http::response(['marketwatch' => [$row]]);
        Http::fake([config('market.tsetmc_url') => fn () => $this->nextResponse]);
        app(SyncInstruments::class)->run();
        $before = DB::table('instruments')->first();
        config(['market.chunk_size' => 1]);
        DB::unprepared("CREATE TRIGGER fail_second BEFORE INSERT ON instruments FOR EACH ROW BEGIN IF NEW.instrument_code = '1002' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'intentional second chunk failure'; END IF; END");
        $changed = array_replace($row, ['pdv' => 777]);
        $second = array_replace($row, ['insCode' => '1002']);
        $this->nextResponse = Http::response(['marketwatch' => [$changed, $second]]);

        try {
            $this->artisan('instruments:sync')->assertFailed();
            $this->assertDatabaseCount('instruments', 1);
            $this->assertSame((array) $before, (array) DB::table('instruments')->first());
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS fail_second');
        }

        $this->artisan('instruments:sync')->assertSuccessful();
        $this->assertDatabaseCount('instruments', 2);
        $this->assertDatabaseHas('instruments', ['instrument_code' => '1001', 'last_price' => '777.0000']);
        Http::assertSentCount(3);
    }
}
