<?php

use App\Http\Controllers\KwhMeterReadingController;
use Illuminate\Support\Facades\Route;

Route::post('/save-reading', [KwhMeterReadingController::class, 'saveRead'])->name('save');