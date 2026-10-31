<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InstrumentDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_homepage_displays_saved_market_data(): void
    {
        $this->insertInstrument('TEST-A', 'آزمایش', '<script>شرکت آزمایشی</script>', '9007199254740993.1234');

        $this->get('/')
            ->assertOk()
            ->assertSee('دیده‌بان نمادها')
            ->assertSee('آزمایش')
            ->assertSee('9٬007٬199٬254٬740٬993.1234')
            ->assertSee('&lt;script&gt;شرکت آزمایشی&lt;/script&gt;', false)
            ->assertDontSee('<script>شرکت آزمایشی</script>', false);
    }

    public function test_search_filters_by_symbol_company_and_code(): void
    {
        $this->insertInstrument('TEST-A', 'فولاد', 'شرکت فولاد');
        $this->insertInstrument('TEST-B', 'خودرو', 'ایران خودرو');

        $this->get('/?q='.urlencode('فولاد'))
            ->assertOk()
            ->assertSee('TEST-A')
            ->assertDontSee('TEST-B');

        $this->get('/?q='.urlencode('ایران خودرو'))
            ->assertOk()
            ->assertSee('TEST-B')
            ->assertDontSee('TEST-A');

        $this->get('/?q=TEST-B')
            ->assertOk()
            ->assertSee('TEST-B')
            ->assertDontSee('TEST-A');

        $this->get('/?q=not-found')
            ->assertSee('نمادی با این جست‌وجو پیدا نشد.');
    }

    public function test_homepage_paginates_large_result_sets(): void
    {
        $rows = [];
        for ($index = 1; $index <= 51; $index++) {
            $rows[] = $this->instrumentRow(
                sprintf('TEST-%02d', $index),
                sprintf('نماد%02d', $index),
                'شرکت آزمایشی',
            );
        }
        DB::table('instruments')->insert($rows);

        $this->get('/')
            ->assertOk()
            ->assertSee('TEST-01')
            ->assertDontSee('TEST-51')
            ->assertSee('نمایش 1 تا 50 از 51 نتیجه');

        $this->get('/?page=2')
            ->assertOk()
            ->assertSee('TEST-51')
            ->assertDontSee('TEST-01');
    }

    public function test_homepage_warns_when_saved_data_is_stale(): void
    {
        $this->insertInstrument('TEST-A', 'آزمایش', 'شرکت آزمایشی');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('داده‌ها بیش از یک دقیقه به‌روزرسانی نشده‌اند');

        DB::table('instruments')->where('instrument_code', 'TEST-A')
            ->update(['synced_at' => now()->utc()->subMinutes(2)]);

        $this->get('/')
            ->assertOk()
            ->assertSee('داده‌ها بیش از یک دقیقه به‌روزرسانی نشده‌اند');
    }

    private function insertInstrument(string $code, string $symbol, string $company, string $price = '1250.5000'): void
    {
        DB::table('instruments')->insert($this->instrumentRow($code, $symbol, $company, $price));
    }

    private function instrumentRow(string $code, string $symbol, string $company, string $price = '1250.5000'): array
    {
        return [
            'instrument_code' => $code,
            'symbol' => $symbol,
            'company_name' => $company,
            'last_price' => $price,
            'closing_price' => '1200.0000',
            'previous_closing_price' => '1190.0000',
            'opening_price' => '1180.0000',
            'trade_volume' => '1000',
            'trade_value' => '1250500.0000',
            'trade_count' => '12',
            'synced_at' => now()->utc(),
            'created_at' => now()->utc(),
            'updated_at' => now()->utc(),
        ];
    }
}
