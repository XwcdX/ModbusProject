<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ModbusPoll</title>
    <!-- Add any CSS styles as necessary (like Bootstrap for layout) -->
</head>

<body>
    <div class="container">
        <h1>ModbusPoll Connection</h1>

        <!-- Connection Form -->
        <form id="modbus-connection-form" action="{{ route('modbus.connect') }}" method="POST">
            @csrf
            <fieldset disabled>
                <legend>Connection</legend>
                <label for="connection-type">Connection: </label>
                <select id="connection-type" name="connection" disabled>
                    <option value="serial">Serial Port</option>
                </select>
            </fieldset>

            <!-- Serial Settings -->
            <fieldset>
                <legend>Serial Settings</legend>
                <label for="com-port">Serial Port: </label>
                <select id="com-port" name="com_port">
                    @for ($i = 1; $i <= 64; $i++)
                        <option value="COM{{ $i }}">COM{{ $i }}</option>
                    @endfor
                </select>

                <label for="baud-rate">Baud: </label>
                <select id="baud-rate" name="baud_rate">
                    @foreach ([300, 600, 1200, 2400, 4800, 9600, 14400, 19200, 38400, 57600, 115200, 128000, 256000] as $baud)
                        @if ($baud == 9600)
                            <option value="{{ $baud }}" selected>{{ $baud }}</option>
                        @else
                            <option value="{{ $baud }}">{{ $baud }}</option>
                        @endif
                    @endforeach
                </select>

                <label for="data-bits">Data Bits: </label>
                <select id="data-bits" name="data_bits">
                    <option value="7">7</option>
                    <option value="8" selected>8</option>
                </select>

                <label for="parity">Parity: </label>
                <select id="parity" name="parity">
                    <option value="none" selected>None</option>
                    <option value="odd">Odd</option>
                    <option value="even">Even</option>
                </select>

                <label for="stop-bit">Stop Bit: </label>
                <select id="stop-bit" name="stop_bit">
                    <option value="1">1 Stop Bit</option>
                    <option value="2">2 Stop Bits</option>
                </select>

                <label for="response-timeout">Response Timeout (ms): </label>
                <input type="number" id="response-timeout" name="response_timeout" value="1000">

                <label for="poll-delay">Delay Between Polls (ms): </label>
                <input type="number" id="poll-delay" name="poll_delay" value="100">
            </fieldset>

            <!-- Setup Button -->
            <h2>Setup</h2>
            <fieldset>
                <legend>Setup Parameters</legend>
                <label for="slave-id">Slave ID: </label>
                <input type="number" id="slave-id" name="slave_id" value="1">

                <label for="function">Function: </label>
                <select id="function" name="function" disabled>
                    <option value="read_holding_register">Read Holding Register</option>
                </select>

                <label for="address">Address: </label>
                <input type="number" id="address" name="address" value="2699">

                <label for="quantity">Quantity: </label>
                <input type="number" id="quantity" name="quantity" value="10">

                <label for="scan-rate">Scan Rate (ms): </label>
                <input type="number" id="scan-rate" name="scan_rate" value="1000">
            </fieldset>

            <!-- Submit Button -->
            <button type="submit">Connect</button>
        </form>
    </div>

    <!-- Add any necessary JavaScript -->
</body>

</html>
