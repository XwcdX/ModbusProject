<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>ModbusPoll</title>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.18/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        label {
            display: block;
            margin: 10px 0 5px;
        }

        button {
            cursor: pointer;
        }

        legend {
            font-weight: bold;
        }

        input,
        select,
        button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        fieldset {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ccc;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>ModbusPoll Connection</h1>

        <form id="modbus-connection-form" action="{{ route('modbus.connect') }}" method="POST">
            @csrf
            <fieldset>
                <legend>Connection</legend>
                <label for="connection-type">Connection: </label>
                <select id="connection-type" name="connection" disabled>
                    <option value="serial">Serial Port</option>
                </select>
                <input type="hidden" name="connection" value="serial">
            </fieldset>

            <fieldset>
                <legend>Serial Settings</legend>
                <label for="com-port">Serial Port: </label>
                <select id="com-port" name="com_port">
                    @for ($i = 1; $i <= 64; $i++)
                        <option value="COM{{ $i }}" {{ $i === 1 ? 'selected' : '' }}>COM{{ $i }}
                        </option>
                    @endfor
                </select>

                <label for="baud-rate">Baud: </label>
                <select id="baud-rate" name="baud_rate">
                    @foreach ([300, 600, 1200, 2400, 4800, 9600, 14400, 19200, 38400, 57600, 115200, 128000, 256000] as $baud)
                        <option value="{{ $baud }}" {{ $baud === 9600 ? 'selected' : '' }}>{{ $baud }}
                        </option>
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

                <label for="stop-bits">Stop Bit: </label>
                <select id="stop-bits" name="stop_bits">
                    <option value="1">1 Stop Bit</option>
                    <option value="2">2 Stop Bits</option>
                </select>

                <label for="response-timeout">Response Timeout (ms): </label>
                <input type="number" id="response-timeout" name="response_timeout" value="1000" min="1">

                <label for="poll-delay">Delay Between Polls (ms): </label>
                <input type="number" id="poll-delay" name="poll_delay" value="100" min="1">
            </fieldset>

            <fieldset>
                <legend>Setup Parameters</legend>
                <label for="slave-id">Slave ID: </label>
                <input type="number" id="slave-id" name="slave_id" value="1" min="1" max="247">

                <label for="function">Function: </label>
                <select id="function" name="function" disabled>
                    <option value="read_holding_register">Read Holding Register</option>
                </select>
                <input type="hidden" name="function" value="read_holding_register">

                <label for="address">Address: </label>
                <input type="number" id="address" name="address" value="2699" min="0" max="65535">

                <label for="quantity">Quantity: </label>
                <input type="number" id="quantity" name="quantity" value="10" min="1">

                <label for="scan-rate">Scan Rate (s): </label>
                <input type="number" id="scan-rate" name="scan_rate" value="60" min="60">
            </fieldset>
            @if ($status && array_key_exists('connected', $status) && !$status['connected'])
                <button type="submit" id="connect-btn">Connect</button>
                <button type="button" id="disconnect-btn" disabled>Disconnect</button>
            @else
                <button type="submit" id="connect-btn" disabled>Connect</button>
                <button type="button" id="disconnect-btn">Disconnect</button>
            @endif

        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.18/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const connectBtn = document.getElementById('connect-btn');
            const disconnectBtn = document.getElementById('disconnect-btn');

            const updateButtonStates = (connected) => {
                if (connected) {
                    connectBtn.disabled = true;
                    disconnectBtn.disabled = false;
                } else {
                    connectBtn.disabled = false;
                    disconnectBtn.disabled = true;
                }
            };

            const checkConnectionStatus = () => {
                fetch('{{ route('modbus.status') }}')
                    .then(response => response.json())
                    .then(data => {
                        updateButtonStates(data.data.connected);
                    })
                    .catch(error => console.error('Failed to fetch status:', error));
            };

            document.getElementById('modbus-connection-form').onsubmit = function(event) {
                event.preventDefault();
                const formData = new FormData(this);
                const jsonPayload = Object.fromEntries(formData.entries());

                fetch(this.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(jsonPayload),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            console.error('Error:', data.message);
                            Swal.fire({
                                icon: 'error',
                                title: 'Connection failed',
                                text: data.message,
                            });
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Connected successfully',
                                text: data.message || 'Connection established',
                            });
                            updateButtonStates(true);
                        }
                    })
                    .catch(error => {
                        console.error('Request failed:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'An error occurred',
                            text: 'There was an error while connecting.',
                        });
                    });
            };

            disconnectBtn.onclick = function() {
                fetch('{{ route('modbus.disconnect') }}', {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            console.error('Error:', data.message);
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed to disconnect',
                                text: data.message,
                            });
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Disconnected successfully',
                                text: data.message || 'Connection terminated',
                            });
                            updateButtonStates(false);
                        }
                    })
                    .catch(error => {
                        console.error('Request failed:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'An error occurred',
                            text: 'There was an error while disconnecting.',
                        });
                    });
            };

            checkConnectionStatus();
            setInterval(checkConnectionStatus, 10000);
        });
    </script>

</body>

</html>
