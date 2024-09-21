<?php

use App\Http\Controllers\ModbusController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('modbuspoll');
});

Route::post('/modbus/connect', [ModbusController::class, 'connect'])->name('modbus.connect');
