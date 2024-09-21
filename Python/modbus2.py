from pymodbus.client import ModbusSerialClient as ModbusClient
from pymodbus.exceptions import ModbusIOException
import struct

# Configure the connection settings
client = ModbusClient(
    port='COM7',            # Your COM port
    baudrate=9600,          # Baud rate
    bytesize=8,             # Data bits
    parity='N',             # Parity (None)
    stopbits=1,             # Stop bits
    timeout=3               # Timeout for response
)

# Connect to the Modbus client (your KWH meter)
connection = client.connect()
if not connection:
    print("Failed to connect to the Modbus device.")
else:
    print("Connection successful.")

try:
    # Read holding register at address 2699
    SLAVE_ID = 1  # Your Modbus slave ID
    ADDRESS = 2699  # The holding register address you want to read
    COUNT = 2  # Number of registers to read (depends on the size of data, here we'll assume 2 registers for 32-bit float)

    # Reading holding registers (read_holding_registers function)
    result = client.read_holding_registers(ADDRESS, COUNT, slave=SLAVE_ID)

    # Check if the read was successful
    if isinstance(result, ModbusIOException):
        print("Failed to read from the Modbus device.")
    else:
        # Extract the value of the register
        if result.isError():
            print(f"Error reading register: {result}")
        else:
            combined_value = (result.registers[1] << 16) + result.registers[0]
            try:
                byte_array = struct.pack('>I', combined_value)  # Ensure that 'combined_value' is packed as unsigned int
                float_val = struct.unpack('>f', byte_array)[0]  # Interpret the packed bytes as a 32-bit float
                print(f"Register value at address {ADDRESS}: {float_val}")
                print(f"Register value at address {ADDRESS}: {result.registers[0]}")
            except struct.error as struct_err:
                print(f"Struct packing/unpacking error: {struct_err}")

except Exception as e:
    print(f"An error occurred: {e}")

finally:
    # Close the Modbus connection
    client.close()
