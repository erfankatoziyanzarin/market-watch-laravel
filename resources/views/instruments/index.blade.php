<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#103b43">
    <title>دیده‌بان نمادها | MarketWatch</title>
    <style>
        :root { color-scheme: light; --deep:#103b43; --deep-2:#174b53; --teal:#116e70; --ink:#18363d; --muted:#637b80; --line:#dbe5e5; --page:#edf3f2; --paper:#fff; --lime:#bde0b4; }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin:0; background:var(--page); color:var(--ink); font-family:"Noto Sans Arabic UI","Noto Sans Arabic",Tahoma,sans-serif; font-size:14px; line-height:1.75; }
        a { color:inherit; }
        button, input { font:inherit; }
        :focus-visible { outline:3px solid #e5a742; outline-offset:3px; }
        .shell { max-width:1500px; margin:0 auto; padding:0 36px; }
        .masthead { background:var(--deep); color:#f3faf8; border-bottom:5px solid #288d87; }
        .masthead .shell { padding-top:26px; padding-bottom:34px; }
        .topline { display:flex; align-items:center; justify-content:space-between; gap:20px; padding-bottom:22px; border-bottom:1px solid rgba(255,255,255,.15); }
        .brand { display:flex; align-items:center; gap:12px; font-size:16px; font-weight:800; letter-spacing:-.035em; }
        .brand-mark { width:36px; height:36px; display:flex; align-items:flex-end; gap:3px; padding:8px; border:1px solid rgba(255,255,255,.3); border-radius:10px; }
        .brand-mark i { width:5px; display:block; background:var(--lime); border-radius:2px 2px 0 0; }
        .brand-mark i:nth-child(1) { height:8px; }.brand-mark i:nth-child(2) { height:14px; }.brand-mark i:nth-child(3) { height:19px; }
        .topnote { color:#bcd2d2; font-size:12px; }
        .hero { display:flex; align-items:end; justify-content:space-between; gap:34px; padding-top:30px; }
        .hero-copy { max-width:700px; }
        .hero h1 { margin:0 0 5px; font-size:clamp(29px,3.4vw,49px); font-weight:800; line-height:1.4; letter-spacing:-.055em; }
        .hero p { margin:0; color:#c4dad8; font-size:14px; }
        .hero-metrics { display:flex; align-items:end; gap:32px; flex:none; }
        .metric { min-width:155px; padding-inline-start:28px; border-inline-start:1px solid rgba(255,255,255,.18); }
        .metric:first-child { border:0; padding-inline-start:0; }
        .metric-label { display:block; color:#bad2d1; font-size:12px; margin-bottom:4px; }
        .metric strong { display:block; line-height:1.2; font-size:27px; font-weight:800; font-variant-numeric:tabular-nums; }
        .metric time { display:block; font-size:15px; font-weight:700; direction:ltr; text-align:right; white-space:nowrap; }
        .content { padding-top:28px; padding-bottom:70px; }
        .stale-banner { margin-bottom:18px; padding:11px 16px; border:1px solid #e6c47d; border-radius:10px; background:#fff4dc; color:#684c13; font-weight:700; }
        .toolbar { display:flex; justify-content:space-between; align-items:end; gap:16px; margin-bottom:18px; }
        .section-title { margin:0 0 2px; font-size:19px; font-weight:800; letter-spacing:-.04em; }
        .section-copy { margin:0; color:var(--muted); font-size:12px; }
        .tools { display:flex; align-items:center; gap:9px; }
        .search { display:flex; width:min(440px,100%); background:#fff; border:1px solid #cbdadb; border-radius:10px; overflow:hidden; box-shadow:0 1px 0 rgba(6,49,53,.03); }
        .search:focus-within { border-color:var(--teal); box-shadow:0 0 0 3px rgba(17,110,112,.15); }
        .search input { min-width:0; flex:1; border:0; background:transparent; outline:0; padding:11px 14px; color:var(--ink); }
        .search input::placeholder { color:#8ca0a1; }
        .search button { border:0; padding:0 17px; color:#fff; background:var(--teal); font-weight:700; cursor:pointer; }
        .search button:hover { background:#0a5558; }
        .refresh { text-decoration:none; background:#fff; border:1px solid #cbdadb; border-radius:10px; padding:10px 15px; white-space:nowrap; font-weight:700; }
        .refresh:hover { border-color:var(--teal); color:var(--teal); }
        .table-wrap { overflow-x:auto; background:var(--paper); border:1px solid var(--line); border-radius:13px; box-shadow:0 5px 25px rgba(16,59,67,.035); }
        table { width:100%; min-width:1180px; border-collapse:collapse; }
        caption { text-align:right; padding:0; height:0; overflow:hidden; }
        thead { background:#e7f0ef; }
        th { padding:13px 15px; color:#536d72; font-size:12px; font-weight:700; text-align:right; white-space:nowrap; border-bottom:1px solid var(--line); }
        td { padding:13px 15px; border-bottom:1px solid #e8eeee; vertical-align:middle; white-space:nowrap; }
        tbody tr:last-child td { border-bottom:0; }
        tbody tr:hover { background:#f7fbfa; }
        .symbol { font-size:15px; font-weight:800; color:#0a5c61; }
        .code { display:block; color:#809397; font-size:11px; letter-spacing:.015em; direction:ltr; text-align:right; font-variant-numeric:tabular-nums; }
        .company { max-width:260px; overflow:hidden; text-overflow:ellipsis; }
        .numeric { direction:ltr; text-align:left; font-variant-numeric:tabular-nums; font-feature-settings:"tnum"; }
        .last-price { font-weight:800; color:#153d44; }
        .empty { margin:0; padding:50px 20px; text-align:center; color:var(--muted); }
        .empty strong { display:block; color:var(--ink); font-size:17px; margin-bottom:4px; }
        .footbar { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-top:16px; color:var(--muted); font-size:12px; }
        .pagination { display:flex; align-items:center; gap:10px; }
        .page-link { text-decoration:none; background:#fff; border:1px solid var(--line); border-radius:8px; padding:6px 12px; color:var(--ink); }
        .page-link:hover { border-color:var(--teal); }
        .page-link[aria-disabled="true"] { color:#aab7b8; pointer-events:none; }
        .page-current { white-space:nowrap; }
        @media (max-width:1000px) { .hero { align-items:start; flex-direction:column; }.hero-metrics { width:100%; }.toolbar { align-items:stretch; flex-direction:column; }.tools { width:100%; }.search { width:min(580px,100%); } }
        @media (max-width:650px) { .shell { padding-inline:16px; }.masthead .shell { padding-top:18px; padding-bottom:23px; }.topline { padding-bottom:15px; }.topnote { display:none; }.hero { padding-top:19px; gap:20px; }.hero h1 { font-size:29px; }.hero p { font-size:12px; }.hero-metrics { gap:20px; }.metric { min-width:0; flex:1; padding-inline-start:16px; }.metric strong { font-size:22px; }.metric time { font-size:12px; }.content { padding-top:20px; }.tools { flex-wrap:wrap; }.search { flex:1; min-width:230px; }.refresh { padding:10px 12px; }.footbar { align-items:start; flex-direction:column; } }
        @media (prefers-reduced-motion:reduce) { *,*::before,*::after { scroll-behavior:auto !important; } }
    </style>
</head>
<body>
@php
    $formatNumber = static function ($value): string {
        $parts = explode('.', (string) $value, 2);
        $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', '٬', $parts[0]);
        $fraction = isset($parts[1]) ? rtrim($parts[1], '0') : '';
        return $fraction === '' ? $whole : $whole.'.'.$fraction;
    };
@endphp
<header class="masthead">
    <div class="shell">
        <div class="topline">
            <div class="brand"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>MarketWatch</span></div>
            <span class="topnote">نمای زنده از داده‌های ذخیره‌شدهٔ بازار</span>
        </div>
        <div class="hero">
            <div class="hero-copy">
                <h1>دیده‌بان نمادها</h1>
                <p>قیمت‌ها و معاملات نمادهای دریافت‌شده از بازار، در یک نگاه.</p>
            </div>
            <div class="hero-metrics">
                <div class="metric"><span class="metric-label">نمادهای ثبت‌شده</span><strong>{{ $formatNumber($totalInstruments) }}</strong></div>
                <div class="metric"><span class="metric-label">آخرین ذخیرهٔ موفق · تهران</span><time>{{ $latestSync ?? 'هنوز داده‌ای ثبت نشده' }}</time></div>
            </div>
        </div>
    </div>
</header>
<main class="shell content">
    @if($isStale)
        <div class="stale-banner" role="status">داده‌ها بیش از یک دقیقه به‌روزرسانی نشده‌اند. زمان آخرین ذخیره را بررسی کنید.</div>
    @endif
    <div class="toolbar">
        <div>
            <h2 class="section-title">فهرست بازار</h2>
            <p class="section-copy">برنامه هر ۱۰ ثانیه برای دریافت دادهٔ تازه تلاش می‌کند. این صفحه هنگام نمایش فعال هر ۱۰ ثانیه تازه می‌شود.</p>
        </div>
        <div class="tools">
            <form class="search" method="get" action="{{ route('market.index') }}" role="search">
                <input name="q" value="{{ $search }}" type="search" maxlength="80" placeholder="جست‌وجوی نماد، نام یا شناسه" aria-label="جست‌وجوی نماد، نام یا شناسه">
                <button type="submit">جست‌وجو</button>
            </form>
            <a class="refresh" href="{{ route('market.index', $search !== '' ? ['q' => $search] : []) }}">تازه‌سازی</a>
        </div>
    </div>
    @if($instruments->isEmpty())
        <div class="table-wrap">
            <p class="empty"><strong>{{ $search !== '' ? 'نمادی با این جست‌وجو پیدا نشد.' : 'هنوز نمادی ثبت نشده است.' }}</strong>{{ $search !== '' ? 'عبارت دیگری را امتحان کنید.' : 'پس از نخستین دریافت موفق، نمادها اینجا نمایش داده می‌شوند.' }}</p>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <caption>اطلاعات قیمت و معاملات نمادها</caption>
                <thead><tr>
                    <th scope="col">نماد / شناسه</th>
                    <th scope="col">نام شرکت یا ابزار</th>
                    <th scope="col" class="numeric">آخرین قیمت<br>ریال</th>
                    <th scope="col" class="numeric">قیمت پایانی<br>ریال</th>
                    <th scope="col" class="numeric">پایانی دیروز<br>ریال</th>
                    <th scope="col" class="numeric">قیمت آغازین<br>ریال</th>
                    <th scope="col" class="numeric">حجم معاملات</th>
                    <th scope="col" class="numeric">ارزش معاملات<br>ریال</th>
                    <th scope="col" class="numeric">تعداد معاملات</th>
                </tr></thead>
                <tbody>
                @foreach($instruments as $instrument)
                    <tr>
                        <td><span class="symbol">{{ $instrument->symbol }}</span><span class="code">{{ $instrument->instrument_code }}</span></td>
                        <td class="company" title="{{ $instrument->company_name }}">{{ $instrument->company_name }}</td>
                        <td class="numeric last-price">{{ $formatNumber($instrument->last_price) }}</td>
                        <td class="numeric">{{ $formatNumber($instrument->closing_price) }}</td>
                        <td class="numeric">{{ $formatNumber($instrument->previous_closing_price) }}</td>
                        <td class="numeric">{{ $formatNumber($instrument->opening_price) }}</td>
                        <td class="numeric">{{ $formatNumber($instrument->trade_volume) }}</td>
                        <td class="numeric">{{ $formatNumber($instrument->trade_value) }}</td>
                        <td class="numeric">{{ $formatNumber($instrument->trade_count) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
    <div class="footbar">
        <span>@if($instruments->total()) نمایش {{ $formatNumber($instruments->firstItem()) }} تا {{ $formatNumber($instruments->lastItem()) }} از {{ $formatNumber($instruments->total()) }} نتیجه @else نتیجه‌ای برای نمایش وجود ندارد @endif</span>
        @if($instruments->hasPages())
            <nav class="pagination" aria-label="صفحه‌بندی نمادها">
                <a class="page-link" href="{{ $instruments->previousPageUrl() ?? '#' }}" @if($instruments->onFirstPage()) aria-disabled="true" @endif>قبلی</a>
                <span class="page-current">صفحه {{ $formatNumber($instruments->currentPage()) }} از {{ $formatNumber($instruments->lastPage()) }}</span>
                <a class="page-link" href="{{ $instruments->nextPageUrl() ?? '#' }}" @if(! $instruments->hasMorePages()) aria-disabled="true" @endif>بعدی</a>
            </nav>
        @endif
    </div>
</main>
<script>
    window.setInterval(() => {
        if (!document.hidden && document.activeElement?.tagName !== 'INPUT') {
            window.location.reload();
        }
    }, 10000);
</script>
</body>
</html>
