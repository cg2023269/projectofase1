from flask import Flask, request, jsonify
import os
from FASE1FINAL import procesar_archivos, conectar_db, guardar_en_db

app = Flask(__name__)
UPLOAD_FOLDER = 'archivos_a_examinar'
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

@app.route('/subir', methods=['POST'])
def subir_archivo():
    if 'file' not in request.files:
        return jsonify({'error': 'No se encontró ningún archivo'}), 400

    file = request.files['file']
    if file.filename == '':
        return jsonify({'error': 'No se seleccionó ningún archivo'}), 400

    filepath = os.path.join(UPLOAD_FOLDER, file.filename)
    file.save(filepath)

    try:
        procesar_archivos(UPLOAD_FOLDER)
        return jsonify({'message': 'Archivo analizado correctamente'})
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/registros', methods=['GET'])
def obtener_registros():
    conexion = conectar_db()
    if not conexion:
        return jsonify({'error': 'No se pudo conectar a la base de datos'}), 500

    try:
        cursor = conexion.cursor(dictionary=True)
        cursor.execute("SELECT archivo, resultado, localizacion FROM resultados")
        registros = cursor.fetchall()
        return jsonify(registros)
    except Exception as e:
        return jsonify({'error': str(e)}), 500
    finally:
        cursor.close()
        conexion.close()

if __name__ == '__main__':
    app.run(debug=True)
