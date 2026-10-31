<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instruments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('instrument_code', 64)->collation('utf8mb4_bin')->unique();
            $table->string('symbol', 80);
            $table->string('company_name');
            $table->decimal('last_price', 30, 4)->unsigned();
            $table->decimal('closing_price', 30, 4)->unsigned();
            $table->decimal('previous_closing_price', 30, 4)->unsigned();
            $table->decimal('opening_price', 30, 4)->unsigned();
            $table->decimal('trade_volume', 28, 0)->unsigned();
            $table->decimal('trade_value', 30, 4)->unsigned();
            $table->decimal('trade_count', 28, 0)->unsigned();
            $table->timestamp('synced_at', 6);
            $table->timestamps(6);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instruments');
    }
};
