<?php

namespace App\Http\Controllers;

use App\Models\KwhMeterReading;
use App\Utils\HttpResponseCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class KwhMeterReadingController extends BaseController
{
    protected $pythonApi;

    public function __construct(KwhMeterReading $kwh)
    {
        parent::__construct($kwh);
        $this->pythonApi = env('PYTHON_API');
    }

    public function viewModbus()
    {
        $response = Http::get($this->pythonApi . '/status');
        $res = $response->json();
        Log::info($res);
        return view('modbuspoll', [
            'status' => $res
        ]);
    }

    public function connect(Request $request)
    {
        $data = $request->all();

        $data['baud_rate'] = (int) $data['baud_rate'];
        $data['data_bits'] = (int) $data['data_bits'];
        $data['stop_bits'] = (int) $data['stop_bits'];
        $data['response_timeout'] = (int) $data['response_timeout'];
        $data['poll_delay'] = (int) $data['poll_delay'];
        $data['slave_id'] = (int) $data['slave_id'];
        $data['address'] = (int) $data['address'];
        $data['quantity'] = (int) $data['quantity'];
        $data['scan_rate'] = (int) $data['scan_rate'];

        $validator = Validator::make($data, [
            'com_port' => 'required|string',
            'baud_rate' => 'required|integer',
            'data_bits' => 'required|integer',
            'parity' => 'required|string',
            'stop_bits' => 'required|integer',
            'response_timeout' => 'required|integer',
            'poll_delay' => 'required|integer',
            'slave_id' => 'required|integer',
            'address' => 'required|integer',
            'quantity' => 'required|integer',
            'scan_rate' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }
        try {
            Log::info('Sending data to Python API:', $data);
            $response = Http::post($this->pythonApi . '/connect', $data);

            if (!$response->ok()) {
                Log::error('Python API error response:', $response->json());
                return $this->error($response->json()['error'] ?? 'Failed to establish connection', $response->status());
            }

            return $this->success('Connection established', $response->json());
        } catch (\Exception $e) {
            Log::error('Connection error: ' . $e->getMessage());
            return $this->error('Internal server error');
        }
    }


    public function disconnect()
    {
        try {
            $response = Http::get($this->pythonApi . '/disconnect');
            if ($response->ok()) {
                return $this->success('Disconnected successfully', $response->json());
            }
            return $this->error('Failed to disconnect', $response->status());
        } catch (\Exception $e) {
            Log::error('Disconnection error: ' . $e->getMessage());
            return $this->error('Internal server error');
        }
    }

    public function status()
    {
        try {
            $response = Http::get($this->pythonApi . '/status');

            if ($response->ok()) {
                return $this->success('Status fetched successfully', $response->json());
            }

            return $this->error('Failed to fetch status', $response->status());
        } catch (\Exception $e) {
            Log::error('Status fetch error: ' . $e->getMessage());
            return $this->error('Internal server error');
        }
    }

    public function saveRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), HttpResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        $reading = new $this->model();
        $reading->value = $request->input('value');
        if ($reading->save()) {
            return $this->success('Value Saved', $reading, HttpResponseCode::HTTP_CREATED);
        } else {
            return $this->error('Failed to save to database');
        }
    }
}
