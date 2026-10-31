<?php

namespace App\Providers;

use App\Market\MarketSource;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MarketSource::class, function ($app): MarketSource {
            $class = config('market.source');
            if (! is_string($class) || ! is_a($class, MarketSource::class, true)) {
                throw new InvalidArgumentException('MARKET_SOURCE must implement MarketSource.');
            }

            return $app->make($class);
        });
    }

    public function boot(): void {}
}
