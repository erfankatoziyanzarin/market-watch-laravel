<?php

use App\Http\Controllers\InstrumentIndexController;
use Illuminate\Support\Facades\Route;

Route::get('/', InstrumentIndexController::class)->name('market.index');
