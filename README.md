# Market Watch

این پروژه با Laravel 13 اطلاعات نمادها را از TSETMC می‌گیرد، در MySQL نگه می‌دارد و در صفحهٔ فارسی نشان می‌دهد. زمان‌بند هر ۱۰ ثانیه برای دریافت تازه تلاش می‌کند. اگر API خطا بدهد، دادهٔ قبلی می‌ماند و پس از یک دقیقه بدون ذخیرهٔ موفق، صفحه هشدار قدیمی‌بودن داده می‌دهد.

## پیش‌نیازها

- Linux، PHP 8.3 یا جدیدتر با افزونهٔ `pdo_mysql` و سایر افزونه‌های Laravel، Composer و MySQL 8.
- برای دیتابیس محلی همراه پروژه: `mysql`، `mysqladmin`، `mysqld`، `ss` و `rg`.
- ابزار `/usr/bin/timeout` برای محدود کردن زمان همگام‌سازی.
- برای این صفحه نیازی به نصب Node یا اجرای Vite نیست.

## اجرای همین نصب در `/var/www/market-watch`

سه سرویس کاربری دیتابیس، وب و زمان‌بند روی این دستگاه تنظیم شده‌اند:

```bash
cd /var/www/market-watch
systemctl --user start market-watch-mysql market-watch-web market-watch-scheduler
systemctl --user status market-watch-mysql market-watch-web market-watch-scheduler
```

صفحهٔ برنامه: [http://127.0.0.1:8013/](http://127.0.0.1:8013/)  
مسیر بررسی سلامت: [http://127.0.0.1:8013/up](http://127.0.0.1:8013/up)

برای دیدن نتیجهٔ دریافت‌ها:

```bash
tail -f /var/www/market-watch/storage/logs/laravel.log
```

در رخداد `market_sync`، مقدار `success` یعنی ذخیرهٔ موفق و `failed` همراه با `reason` علت شکست را نشان می‌دهد. زمان آخرین ذخیرهٔ موفق در بالای صفحه دیده می‌شود.

## نصب تازه در همین مسیر

اگر `.env` و دیتابیس فعلی را دارید، آن‌ها را بازنویسی نکنید. برای یک کپی تازه:

```bash
cd /var/www/market-watch
composer install --no-interaction --prefer-dist
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --no-interaction
fi
bin/local-db start
php artisan migrate --no-interaction
php artisan instruments:sync
```

در `.env` مقدار `APP_URL` را `http://127.0.0.1:8013` قرار دهید. `DB_SOCKET` باید مسیر کامل `/var/www/market-watch/storage/mysql.sock` باشد؛ فایل نمونه همین مقدار را دارد. اگر پروژه را جابه‌جا کردید، این مسیر و مسیرهای مطلق فایل‌های `deploy/` را اصلاح کنید.

`bin/local-db` یک MySQL مستقل داخل `storage/` با اتصال فقط از طریق Unix socket راه می‌اندازد. این تنظیم مخصوص توسعهٔ محلی است. برای سرور عمومی، دیتابیس و کاربر محدود خودتان را در `.env` تنظیم کنید. منبع پیش‌فرض TSETMC کلید API نمی‌خواهد.

### اجرای دستی

بعد از آماده‌کردن دیتابیس، این دو فرمان را در دو ترمینال جدا اجرا کنید:

```bash
cd /var/www/market-watch
php artisan serve --host=127.0.0.1 --port=8013
```

```bash
cd /var/www/market-watch
bin/run-scheduler
```

اجرای دستی فوری همگام‌سازی: `php artisan instruments:sync`. اگر سرویس‌های کاربری فعال‌اند، وب یا زمان‌بند را دوباره به‌صورت دستی اجرا نکنید.

### فعال‌سازی سرویس‌های کاربری در نصب تازه

فایل‌های آمادهٔ `deploy/` برای همین مسیر نوشته شده‌اند:

```bash
systemctl --user link /var/www/market-watch/deploy/market-watch-mysql.service
systemctl --user link /var/www/market-watch/deploy/market-watch-web.service
systemctl --user link /var/www/market-watch/deploy/market-watch-scheduler.service
systemctl --user daemon-reload
systemctl --user enable --now market-watch-mysql.service market-watch-web.service market-watch-scheduler.service
```

برای توقف: `systemctl --user stop market-watch-scheduler market-watch-web market-watch-mysql`. سرویس کاربری پس از ورود کاربر بالا می‌آید؛ اجرای پیش از ورود به تنظیمات `linger` سیستم وابسته است.

## اجرای تست‌ها

تست‌ها از دیتابیس جداگانهٔ `market_watch_testing` استفاده می‌کنند؛ آن‌ها را روی دیتابیس اصلی اجرا نکنید.

```bash
cd /var/www/market-watch
if [ ! -f .env.testing ]; then
    cp .env.testing.example .env.testing
    php artisan key:generate --env=testing --no-interaction
fi
bin/local-db start
php artisan test --compact
```

آخرین اجرای کامل: ۶۱ تست و ۲۲۹ assertion موفق. HTTP بیرونی در تست‌ها شبیه‌سازی می‌شود؛ parser، قفل، تراکنش و MySQL واقعی بررسی می‌شوند.

## عیب‌یابی کوتاه

| نشانه | بررسی |
|---|---|
| صفحه باز نمی‌شود | `systemctl --user status market-watch-web market-watch-mysql` و مسیر `/up`. |
| پورت 8013 اشغال است | احتمالاً وب‌سرور همین پروژه فعال است؛ نمونهٔ دستی دیگری اجرا نکنید. |
| داده به‌روز نمی‌شود | وضعیت `market-watch-scheduler` و رخدادهای `market_sync` در `storage/logs/laravel.log`. |
| API قطع یا کند است | دادهٔ قبلی حفظ می‌شود؛ زمان آخرین ذخیره و هشدار دادهٔ قدیمی را ببینید. |
| اتصال MySQL خطا دارد | `bin/local-db status` و مقدار `DB_SOCKET` در `.env`. |

تنظیم منبع در `config/market.php` است. منبع جایگزین باید قرارداد `MarketSource` را رعایت کند؛ انتخاب منبع با `MARKET_SOURCE` و URL مربوط در `.env` انجام می‌شود. پس از تغییر تنظیمات، `php artisan config:clear --no-interaction` را اجرا کنید. تعویض منبع به‌صورت خودکار انجام نمی‌شود.

برای معماری، سناریوهای خطا و شواهد تست، [مستند فنی فارسی](TASK_PRESENTATION_FA.md) را ببینید.
