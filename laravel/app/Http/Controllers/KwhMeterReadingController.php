<?php

namespace App\Http\Controllers;

use App\Models\KwhMeterReading;
use App\Utils\HttpResponseCode;
use Illuminate\Http\Request;

class KwhMeterReadingController extends BaseController
{
    public function __construct(KwhMeterReading $kwh) {
        Parent::__construct($kwh);
    }

    public function saveRead(Request $request){
        $reading = new $this->model();
        $reading->value = $request->input('value');
        if ($reading->save()) {
            return $this->success('Value Saved', $reading, HttpResponseCode::HTTP_CREATED);
        } else {
            return $this->error('Failed to save to database');
        }
    }
}
