#!/usr/bin/python3
import cgi
import os
import hashlib
import requests
import json
import time
import sys
import io
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
from cryptography.hazmat.backends import default_backend

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

UPLOAD_FOLDER = '/var/www/html/viruscheck/uploads/'
CLEAN_FOLDER = '/var/www/html/viruscheck/clean/'
INFECTED_FOLDER = '/var/www/html/viruscheck/infected/'
KEY_FILE = '/var/www/html/viruscheck/.secret.key'

API_KEY = 'f14cc7952d80c65551f70f4c9356e33d4019337f7eb76a633b99022c81be7781'

# Fixed encryption key and IV to match PHP's configuration
# Aseguramos que las claves sean del tamaño exacto requerido para AES-256-CBC
ENCRYPTION_KEY = 'my32supersecretkey1234567890123456'.encode('utf-8')
if len(ENCRYPTION_KEY) != 32:
    ENCRYPTION_KEY = ENCRYPTION_KEY.ljust(32)[:32]  # Aseguramos que sea exactamente 32 bytes

IV = 'my16byteiv123456'.encode('utf-8')
if len(IV) != 16:
    IV = IV.ljust(16)[:16]  # Aseguramos que sea exactamente 16 bytes

def get_file_hash(file_path):
    sha256_hash = hashlib.sha256()
    with open(file_path, "rb") as f:
        for byte_block in iter(lambda: f.read(4096), b""):
            sha256_hash.update(byte_block)
    return sha256_hash.hexdigest()

def check_file_with_virustotal(file_hash, file_path):
    url = f'https://www.virustotal.com/api/v3/files/{file_hash}'
    headers = {'x-apikey': API_KEY}
    max_retries = 5
    retry_delay = 10

    for attempt in range(max_retries):
        response = requests.get(url, headers=headers)
        if response.status_code == 200:
            result = response.json()
            scan_results = result['data']['attributes']['last_analysis_results']
            for engine, analysis in scan_results.items():
                if analysis['category'] == 'malicious':
                    return "infected"
            return "clean"
        elif response.status_code == 404:
            upload_url = 'https://www.virustotal.com/api/v3/files'
            files = {'file': open(file_path, 'rb')}
            upload_response = requests.post(upload_url, headers=headers, files=files)
            if upload_response.status_code == 200:
                time.sleep(retry_delay)
                continue
            else:
                return f"Error al subir el archivo: {upload_response.status_code}"
        else:
            return f"Error en la consulta: {response.status_code}"

    return "El análisis no ha finalizado después de varios intentos."

def pad_data(data):
    """Aplica padding PKCS7."""
    block_size = algorithms.AES.block_size // 8
    padding_length = block_size - (len(data) % block_size)
    padding = bytes([padding_length]) * padding_length
    return data + padding

def encrypt_file(input_path, output_path):
    try:
        # Leer el archivo
        with open(input_path, 'rb') as f:
            data = f.read()

        # Aplicar padding PKCS7
        padded_data = pad_data(data)

        # Crear cifrador AES-256-CBC
        backend = default_backend()
        cipher = Cipher(algorithms.AES(ENCRYPTION_KEY), modes.CBC(IV), backend=backend)
        encryptor = cipher.encryptor()

        # Cifrar los datos
        encrypted = encryptor.update(padded_data) + encryptor.finalize()

        # Guardar el archivo cifrado
        with open(output_path, 'wb') as f:
            f.write(encrypted)

        return True
    except Exception as e:
        print(f"Error en encrypt_file: {e}", file=sys.stderr)
        return False

def process_file(file_item, file_name=None, is_folder=False):
    try:
        if not os.path.exists(UPLOAD_FOLDER):
            os.makedirs(UPLOAD_FOLDER)

        if file_name is None:
            file_name = file_item.filename

        file_path = os.path.join(UPLOAD_FOLDER, file_name)
        os.makedirs(os.path.dirname(file_path), exist_ok=True)

        with open(file_path, 'wb') as f:
            f.write(file_item.file.read())

        file_hash = get_file_hash(file_path)
        status = check_file_with_virustotal(file_hash, file_path)

        if status == "infected":
            target_base = INFECTED_FOLDER
        elif status == "clean":
            target_base = CLEAN_FOLDER
        else:
            return {"error": "Resultado desconocido."}

        os.makedirs(target_base, exist_ok=True)

        # Asegurarse de que todas las rutas de directorio existan
        if is_folder:
            # Para archivos en carpetas, mantenemos la estructura pero añadimos .enc
            target_path = os.path.join(target_base, file_name + ".enc")
            os.makedirs(os.path.dirname(target_path), exist_ok=True)

            # Almacenar la ruta relativa para posterior recuperación
            relative_path = file_name
        else:
            # Para archivos individuales, solo añadimos .enc al nombre base
            base_name = os.path.basename(file_name)
            if base_name.endswith('.enc'):
                base_name = base_name[:-4]
            target_path = os.path.join(target_base, base_name + ".enc")
            relative_path = base_name

        # Imprimir información de depuración
        print(f"Cifrando archivo {file_path} a {target_path}", file=sys.stderr)

        if not encrypt_file(file_path, target_path):
            return {"error": f"Error al cifrar el archivo {file_name}"}

        # Verificar que el archivo cifrado existe
        if not os.path.exists(target_path):
            return {"error": f"El archivo cifrado no se creó correctamente en {target_path}"}

        os.remove(file_path)

        # El nombre devuelto incluye la ruta completa dentro del directorio target_base
        # Esto es importante para los archivos en carpetas
        relative_target_path = os.path.relpath(target_path, target_base)

        return {
            "file_name": relative_target_path,  # Ruta relativa con .enc
            "hash": file_hash,
            "status": status,
            "location": target_base
        }

    except Exception as e:
        return {"error": f"Error al procesar el archivo {file_name}: {str(e)}"}

def main():
    print("Content-Type: application/json\n")
    form = cgi.FieldStorage()

    if "file" not in form:
        print(json.dumps({"error": "No se ha recibido ningún archivo."}))
        return

    mode = form.getvalue("mode", "single")

    if mode == "single":
        file_item = form["file"]
        if not file_item.filename:
            print(json.dumps({"error": "No se ha seleccionado un archivo."}))
            return
        result = process_file(file_item)
        print(json.dumps(result))

    elif mode == "folder":
        file_item = form["file"]
        if not file_item.filename:
            print(json.dumps({"error": "No se ha seleccionado un archivo."}))
            return
        relative_path = form.getvalue("relativePath", file_item.filename)
        result = process_file(file_item, relative_path, is_folder=True)
        print(json.dumps(result))

if __name__ == "__main__":
    main()
