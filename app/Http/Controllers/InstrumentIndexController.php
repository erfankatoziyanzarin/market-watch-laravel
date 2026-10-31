<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InstrumentIndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        $rawSearch = $request->query('q', '');
        $search = is_string($rawSearch) ? mb_substr(trim($rawSearch), 0, 80) : '';

        $overview = DB::table('instruments')
            ->selectRaw('COUNT(*) AS total, MAX(synced_at) AS last_sync')
            ->first();

        $instruments = DB::table('instruments')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $pattern = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function (Builder $matches) use ($pattern): void {
                    $matches->where('symbol', 'like', $pattern)
                        ->orWhere('company_name', 'like', $pattern)
                        ->orWhere('instrument_code', 'like', $pattern);
                });
            })
            ->orderBy('symbol')
            ->orderBy('instrument_code')
            ->paginate(50)
            ->appends(['q' => $search]);

        $latestSyncAt = $overview->last_sync === null
            ? null
            : CarbonImmutable::parse($overview->last_sync, 'UTC');
        $latestSync = $latestSyncAt?->setTimezone('Asia/Tehran')->format('Y/m/d H:i:s');
        $isStale = $latestSyncAt !== null && $latestSyncAt->lessThan(CarbonImmutable::now('UTC')->subMinute());

        return view('instruments.index', [
            'instruments' => $instruments,
            'totalInstruments' => (int) $overview->total,
            'latestSync' => $latestSync,
            'isStale' => $isStale,
            'search' => $search,
        ]);
    }
}
