from pymodbus.client import ModbusSerialClient as ModbusClient
from pymodbus.constants import Endian
from pymodbus.payload import BinaryPayloadDecoder
import requests
import sys
import json

def send_to_laravel(kwh_value):
    url = 'http://localhost:8000/api/save-reading'
    data = {
        'value': kwh_value
    }
    response = requests.post(url, json=data)
    
    # Always return the response as JSON
    return response.json() if response.status_code == 200 else {'error': 'Failed to send data'}

def main():
    # Getting the connection parameters from the arguments passed
    try:
        com_port = sys.argv[1]
        baud_rate = int(sys.argv[2])
        data_bits = int(sys.argv[3])
        parity = sys.argv[4]
        stop_bits = int(sys.argv[5])
        response_timeout = int(sys.argv[6]) / 1000  # convert from ms to seconds
        poll_delay = int(sys.argv[7])
        slave_id = int(sys.argv[8])
        address = int(sys.argv[9])
        quantity = int(sys.argv[10])
        scan_rate = int(sys.argv[11]) / 1000  # convert from ms to seconds
    except IndexError:
        print(json.dumps({"error": "Missing arguments for connection. Please pass the correct parameters."}))
        sys.exit(1)

    # Modbus client configuration
    client = ModbusClient(
        port=com_port,         # Serial Port (e.g., COM7)
        baudrate=baud_rate,    # Baud rate
        bytesize=data_bits,    # Data bits (7 or 8)
        parity=parity if parity.lower() != 'none' else 'N',  # Parity (None, Odd, Even)
        stopbits=stop_bits,    # Stop bits (1 or 2)
        timeout=response_timeout  # Timeout in seconds
    )

    # Connect to the Modbus client (serial device)
    connection = client.connect()

    if connection:
        print(f"Connected to {com_port} successfully.")

        while True:
            # Reading holding registers
            response = client.read_holding_registers(address, quantity, slave=slave_id)

            if not response.isError():
                # Use a decoder to convert the registers into required data type
                decoder = BinaryPayloadDecoder.fromRegisters(response.registers, byteorder=Endian.BIG, wordorder=Endian.LITTLE)
                value = decoder.decode_16bit_int()  # or another type based on the meter
                laravel_response = send_to_laravel(value)
                print(json.dumps(laravel_response))  # Optionally print the response from Laravel
            else:
                # Send an error JSON back if there's an error reading the register
                error_response = {'error': f"Error reading the register: {response}"}
                print(json.dumps(error_response))

            # Polling delay
            client.sleep(poll_delay / 1000)  # Delay between polls
            
    else:
        error_response = {"error": f"Unable to connect to {com_port}."}
        print(json.dumps(error_response))

    # Close the client connection
    client.close()

if __name__ == "__main__":
    main()
