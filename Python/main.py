from flask import Flask, request, jsonify
from pymodbus.client import ModbusSerialClient as ModbusClient
from pymodbus.constants import Endian
from pymodbus.payload import BinaryPayloadDecoder
import threading
import requests
import time

app = Flask(__name__)

client = None
connection_params = {}
accumulated_value = 0
read_count = 0
start_time = time.time()
first_read = True
polling_thread = None
stop_polling = threading.Event()

def connect_modbus(params):
    global client
    client = ModbusClient(
        port=params['com_port'],
        baudrate=params['baud_rate'],
        bytesize=params['data_bits'],
        parity=params['parity'] if params['parity'].lower() != 'none' else 'N',
        stopbits=params['stop_bits'],
        timeout=params['response_timeout']
    )
    return client.connect()

def read_and_send_data(slave_id, address, quantity, poll_delay, scan_rate):
    global accumulated_value, read_count, start_time, first_read

    while not stop_polling.is_set():
        try:
            response = client.read_holding_registers(address, quantity, slave=slave_id)

            if not response.isError():
                decoder = BinaryPayloadDecoder.fromRegisters(response.registers, byteorder=Endian.BIG, wordorder=Endian.LITTLE)
                value = decoder.decode_16bit_int()
                if first_read:
                    send_to_laravel(value)
                    first_read = False

                accumulated_value += value
                read_count += 1

                if time.time() - start_time >= scan_rate:
                    average_value = accumulated_value / read_count
                    send_to_laravel(average_value)

                    accumulated_value = 0
                    read_count = 0
                    start_time = time.time()

            else:
                print(f"Error reading the register: {response}")

            time.sleep(poll_delay / 1000)
        except Exception as e:
            print(f"Error: {e}")

def send_to_laravel(kwh_value):
    url = 'http://localhost:8000/api/save-reading'
    data = {'value': kwh_value}
    try:
        response = requests.post(url, json=data)
        response.raise_for_status()
        print("Data sent successfully:", response.json())
    except requests.RequestException as e:
        print("Failed to send data:", str(e))

@app.route('/connect', methods=['POST'])
def connect():
    global connection_params, polling_thread, stop_polling

    data = request.json
    app.logger.debug(f"Received connection request: {data}")

    required_params = [
        'com_port', 'baud_rate', 'data_bits', 'parity',
        'stop_bits', 'response_timeout', 'poll_delay', 'slave_id', 'address', 'quantity', 'scan_rate'
    ]

    missing_params = [param for param in required_params if param not in data]
    if missing_params:
        return jsonify({"error": f"Missing required parameters: {', '.join(missing_params)}"}), 400

    connection_params = data
    connection_params['response_timeout'] = connection_params['response_timeout'] / 1000

    app.logger.debug(f"Attempting to connect with params: {connection_params}")
    if connect_modbus(connection_params):
        stop_polling.clear()
        polling_thread = threading.Thread(
            target=read_and_send_data,
            args=(data['slave_id'], data['address'], data['quantity'], data['poll_delay'], data['scan_rate'])
        )
        polling_thread.start()
        app.logger.debug("Polling started successfully.")
        return jsonify({"message": "Connection successful, polling started."})
    else:
        app.logger.error("Failed to connect to Modbus device.")
        return jsonify({"error": "Failed to connect to Modbus device."}), 500


@app.route('/disconnect', methods=['GET'])
def disconnect():
    global client, stop_polling

    stop_polling.set()
    if client:
        client.close()
        return jsonify({"message": "Disconnected from Modbus device."})

    return jsonify({"error": "No active connection to close."}), 400

@app.route('/status', methods=['GET'])
def status():
    global client
    return jsonify({"connected": client.is_socket_open() if client else False})

if __name__ == '__main__':
    app.run(debug=True)