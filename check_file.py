#!/usr/bin/python3
import cgi
import os
import hashlib
import requests
import json
import time

# Ruta donde se guardarán los archivos subidos
UPLOAD_FOLDER = '/var/www/html/viruscheck/uploads/'

# API Key de VirusTotal
API_KEY = 'f14cc7952d80c65551f70f4c9356e33d4019337f7eb76a633b99022c81be7781'

# Función para obtener el hash SHA256 del archivo
def get_file_hash(file_path):
    sha256_hash = hashlib.sha256()
    with open(file_path, "rb") as f:
        for byte_block in iter(lambda: f.read(4096), b""):
            sha256_hash.update(byte_block)
    return sha256_hash.hexdigest()

# Función para comprobar el archivo con VirusTotal usando el hash
def check_file_with_virustotal(file_hash, file_path):
    url = f'https://www.virustotal.com/api/v3/files/{file_hash}'
    headers = {'x-apikey': API_KEY}

    # Intentar obtener el análisis varias veces
    max_retries = 5  # Número máximo de reintentos
    retry_delay = 10  # Segundos entre reintentos

    for attempt in range(max_retries):
        response = requests.get(url, headers=headers)

        if response.status_code == 200:
            result = response.json()
            # Extraer resultados del análisis
            scan_results = result['data']['attributes']['last_analysis_results']

            # Revisar si algún motor marcó el archivo como malicioso
            for engine, analysis in scan_results.items():
                if analysis['category'] == 'malicious':
                    return "infected"

            return "clean"
        elif response.status_code == 404:
            # El archivo no ha sido analizado previamente, subirlo para análisis
            upload_url = 'https://www.virustotal.com/api/v3/files'
            files = {'file': open(file_path, 'rb')}
            upload_response = requests.post(upload_url, headers=headers, files=files)

            if upload_response.status_code == 200:
                # Esperar antes de reintentar
                time.sleep(retry_delay)
                continue
            else:
                return f"Error al subir el archivo: {upload_response.status_code}"
        else:
            return f"Error en la consulta: {response.status_code}"

    return "El análisis no ha finalizado después de varios intentos."

# Crear directorio de subida si no existe
if not os.path.exists(UPLOAD_FOLDER):
    os.makedirs(UPLOAD_FOLDER)

# Manejo del formulario de subida
form = cgi.FieldStorage()

# Comprobar si se ha recibido el archivo
if "file" not in form:
    print("Content-Type: application/json\n")
    print(json.dumps({"error": "No se ha recibido ningún archivo."}))
else:
    uploaded_file = form["file"]

    if uploaded_file.filename:
        # Guardar el archivo en el servidor
        file_path = os.path.join(UPLOAD_FOLDER, uploaded_file.filename)

        try:
            with open(file_path, 'wb') as f:
                f.write(uploaded_file.file.read())

            # Obtener el hash del archivo
            file_hash = get_file_hash(file_path)

            # Comprobar el archivo con VirusTotal
            status = check_file_with_virustotal(file_hash, file_path)

            # Mover el archivo a la carpeta correspondiente según el estado
            if status == "infected":
                infected_folder = '/var/www/html/viruscheck/infected/'
                if not os.path.exists(infected_folder):
                    os.makedirs(infected_folder)
                os.rename(file_path, os.path.join(infected_folder, uploaded_file.filename))
                location = infected_folder
            elif status == "clean":
                clean_folder = '/var/www/html/viruscheck/clean/'
                if not os.path.exists(clean_folder):
                    os.makedirs(clean_folder)
                os.rename(file_path, os.path.join(clean_folder, uploaded_file.filename))
                location = clean_folder
            else:
                location = UPLOAD_FOLDER

            # Devolver la respuesta en formato JSON
            print("Content-Type: application/json\n")
            print(json.dumps({
                "file_name": uploaded_file.filename,
                "hash": file_hash,
                "status": status,
                "location": location
            }))

        except Exception as e:
            print("Content-Type: application/json\n")
            print(json.dumps({"error": f"Error al guardar el archivo: {str(e)}"}))
    else:
        print("Content-Type: application/json\n")
        print(json.dumps({"error": "No se ha seleccionado un archivo."}))
