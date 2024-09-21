<?php

namespace App\Http\Controllers;

use App\Models\KwhMeterReading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ModbusController extends BaseController
{
    public function __construct(KwhMeterReading $kwh)
    {
        parent::__construct($kwh);
    }

    public function connect(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'com_port' => 'required|string',
            'baud_rate' => 'required|integer',
            'data_bits' => 'required|integer',
            'parity' => 'required|string',
            'stop_bit' => 'required|integer',
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

        $pythonScript = 'C:\\xampp\\htdocs\\SmartSystemProject\\MODBUS\\Python\\modbus1.py';
        $command = $this->buildCommand($pythonScript, $request);

        $output = [];
        $returnCode = $this->executeCommand($command, $output);

        if ($returnCode !== 0) {
            return response()->json([
                'error' => 'Failed to execute Python script',
                'command' => $command,
                'output' => implode("\n", $output),
                'return_code' => $returnCode
            ]);
        }

        return $this->success('Connection established', implode("\n", $output));
    }


    protected function buildCommand($pythonScript, Request $request)
    {
        $args = [
            $request->input('com_port'),
            $request->input('baud_rate'),
            $request->input('data_bits'),
            $request->input('parity'),
            $request->input('stop_bit'),
            $request->input('response_timeout'),
            $request->input('poll_delay'),
            $request->input('slave_id'),
            $request->input('address'),
            $request->input('quantity'),
            $request->input('scan_rate'),
        ];

        return "python \"$pythonScript\" " . implode(' ', array_map('escapeshellarg', $args));
    }

    protected function executeCommand($command, &$output)
    {
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        // Log detailed output
        Log::info('Command executed: ' . $command);
        Log::info('Output: ' . implode("\n", $output));

        return $returnCode;
    }
}
