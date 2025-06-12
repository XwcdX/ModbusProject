<?php

use App\Http\Controllers\KwhMeterReadingController;
use Illuminate\Support\Facades\Route;

Route::name('modbus.')->group(function () {
    Route::get('/', [KwhMeterReadingController::class, 'viewModbus'])->name('view');
    Route::post('/connect', [KwhMeterReadingController::class, 'connect'])->name('connect');
    Route::get('/disconnect', [KwhMeterReadingController::class, 'disconnect'])->name('disconnect');
    Route::get('/status', [KwhMeterReadingController::class, 'status'])->name('status');
});
