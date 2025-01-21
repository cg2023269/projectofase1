#!/usr/bin/python3

import cgi
import os
import hashlib
import requests

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
def check_file_with_virustotal(file_hash):
    url = f'https://www.virustotal.com/api/v3/files/{file_hash}'
    headers = {'x-apikey': API_KEY}

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
    else:
        return f"Error en la consulta: {response.status_code}"

# Crear directorio de subida si no existe
if not os.path.exists(UPLOAD_FOLDER):
    os.makedirs(UPLOAD_FOLDER)

# Manejo del formulario de subida
form = cgi.FieldStorage()

# Comprobar si se ha recibido el archivo
if "file" not in form:
    print("Content-Type: text/html\n")
    print("<h1>No se ha recibido ningún archivo.</h1>")
else:
    uploaded_file = form["file"]

    if uploaded_file.filename:
        # Guardar el archivo en el servidor
        file_path = os.path.join(UPLOAD_FOLDER, uploaded_file.filename)

        try:
            with open(file_path, 'wb') as f:
                f.write(uploaded_file.file.read())

            print("Content-Type: text/html\n")
            print(f"<h1>Archivo recibido: {uploaded_file.filename}</h1>")
            print(f"<p>Archivo guardado en: {file_path}</p>")

            # Obtener el hash del archivo
            file_hash = get_file_hash(file_path)
            print(f"<p>Hash del archivo (SHA256): {file_hash}</p>")

            # Comprobar el archivo con VirusTotal
            print("<p>Realizando la llamada a la API de VirusTotal...</p>")
            status = check_file_with_virustotal(file_hash)

            # Mostrar el resultado del análisis
            print(f"<h2>Resultado del análisis: {status}</h2>")

            # Mover el archivo a la carpeta correspondiente según el estado
            if status == "infected":
                infected_folder = '/var/www/html/viruscheck/infected/'
                if not os.path.exists(infected_folder):
                    os.makedirs(infected_folder)
                os.rename(file_path, os.path.join(infected_folder, uploaded_file.filename))
                print(f"<p>El archivo se ha movido a la carpeta de infectados: {infected_folder}</p>")
            else:
                clean_folder = '/var/www/html/viruscheck/clean/'
                if not os.path.exists(clean_folder):
                    os.makedirs(clean_folder)
                os.rename(file_path, os.path.join(clean_folder, uploaded_file.filename))
                print(f"<p>El archivo se ha movido a la carpeta limpia: {clean_folder}</p>")

        except Exception as e:
            print(f"<h1>Error al guardar el archivo: {str(e)}</h1>")
    else:
        print("<h1>No se ha seleccionado un archivo.</h1>")
