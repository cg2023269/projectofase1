<?php
session_start();
if (!isset($_SESSION['username'])) {
    // Mostrar opciones de login/registro y salir si no hay sesión
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Autenticación requerida</title>
      <style>
        /* Estilos básicos para la pantalla de autenticación */
        body { font-family: Arial, sans-serif; background: #ecf0f1; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .auth-container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .auth-container a { display: inline-block; margin: 10px; padding: 10px 20px; text-decoration: none; background: #34495e; color: #fff; border-radius: 4px; }
        .auth-container a:hover { background: #2c3e50; }
      </style>
    </head>
    <body>
      <div class="auth-container">
          <h2>Bienvenido</h2>
          <p>Por favor, inicia sesión o regístrate para continuar:</p>
          <a href="login/login.php">Iniciar Sesión</a>
          <a href="login/register.php">Registrarse</a>
      </div>
    </body>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Antiv FA - Página Principal</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
      body {
          font-family: 'Arial', sans-serif;
          margin: 0;
          padding: 0;
          background-color: #f8f9fa;
      }
      header {
          background-color: #2c3e50;
          color: white;
          padding: 15px;
          text-align: center;
      }
      header img {
          height: 50px;
          vertical-align: middle;
      }
      header h1 {
          display: inline;
          margin-left: 10px;
          font-size: 24px;
      }
      nav {
          display: flex;
          justify-content: flex-end;
          background-color: #34495e;
          padding: 10px;
      }
      nav a {
          color: white;
          margin: 0 10px;
          text-decoration: none;
          padding: 8px 12px;
          border-radius: 4px;
          background-color: #2c3e50;
      }
      nav a:hover {
          background-color: #1abc9c;
      }
      .container {
          max-width: 1200px;
          margin: 20px auto;
          padding: 20px;
          background: white;
          border-radius: 10px;
          box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      }
      .section {
          margin-bottom: 20px;
      }
      .section h2 {
          border-bottom: 2px solid #2c3e50;
          padding-bottom: 5px;
          margin-bottom: 15px;
      }
      .results div {
          padding: 10px;
          background-color: #f1f1f1;
          border-radius: 8px;
          box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      }
      .results p {
          margin: 5px 0;
      }
      .loading {
          display: none;
          color: #2c3e50;
          font-weight: bold;
      }
      .progress-container {
          margin-top: 10px;
          display: none;
      }
      .progress-bar {
          width: 100%;
          background-color: #f1f1f1;
          border-radius: 4px;
          height: 20px;
      }
      .progress {
          width: 0%;
          height: 20px;
          background-color: #1abc9c;
          border-radius: 4px;
          text-align: center;
          line-height: 20px;
          color: white;
      }
      .upload-options {
          display: flex;
          gap: 20px;
          margin-bottom: 15px;
      }
      .upload-option {
          flex: 1;
          padding: 15px;
          background-color: #f1f1f1;
          border-radius: 8px;
          box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
          text-align: center;
      }
      .results-table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 15px;
      }
      .results-table th, .results-table td {
          border: 1px solid #ddd;
          padding: 8px;
          text-align: left;
      }
      .results-table th {
          background-color: #2c3e50;
          color: white;
      }
      .results-table tr:nth-child(even) {
          background-color: #f2f2f2;
      }
      .results-summary {
          margin-top: 15px;
          padding: 10px;
          background-color: #e8f4f8;
          border-radius: 4px;
      }
  </style>
</head>
<body>
  <header>
      <img src="logo.png" alt="Antiv FA Logo">
      <h1>Antiv FA</h1>
  </header>
  <nav>
      <a href="registro.php">Administrador de archivos</a>
      <a href="historial.php">Historial</a> <!-- Nuevo botón de historial -->
      <?php
      // Mostrar el enlace al panel admin solo si el usuario tiene rol de administrador.
      if (isset($_SESSION['role']) && $_SESSION['role'] === 'administrador') {
          echo '<a href="admin.php">Panel Admin</a>';
      }
      ?>
      <a href="logout.php">Cerrar Sesión</a>
  </nav>
  <div class="container">
      <div class="section">
          <h2>Subir y Analizar Archivos</h2>

          <div class="upload-options">
              <div class="upload-option">
                  <h3>Analizar Archivo Individual</h3>
                  <form id="uploadForm" method="post" enctype="multipart/form-data">
                      <input type="file" id="file" name="file" required>
                      <button type="submit">Subir y analizar</button>
                  </form>
              </div>

              <div class="upload-option">
                  <h3>Analizar Carpeta</h3>
                  <form id="folderForm" method="post" enctype="multipart/form-data">
                      <input type="file" id="folder" name="folder[]" webkitdirectory directory multiple>
                      <button type="submit">Subir y analizar carpeta</button>
                  </form>
              </div>
          </div>

          <div class="loading" id="loading">Analizando archivos, por favor espere...</div>
          <div class="progress-container" id="progressContainer">
              <div class="progress-bar">
                  <div class="progress" id="progressBar">0%</div>
              </div>
              <p id="progressText">Procesando: 0/0 archivos</p>
          </div>
      </div>

      <div class="section">
          <h2>Resultados</h2>
          <div class="results">
              <div id="resultsContent">
                  <p><strong>Seleccione un archivo o carpeta para analizar.</strong></p>
              </div>
          </div>
      </div>
  </div>

  <script>
      document.addEventListener('DOMContentLoaded', () => {
          const singleForm = document.getElementById('uploadForm');
          const folderForm = document.getElementById('folderForm');
          const resultsDiv = document.getElementById('resultsContent');
          const loadingDiv = document.getElementById('loading');
          const progressContainer = document.getElementById('progressContainer');
          const progressBar = document.getElementById('progressBar');
          const progressText = document.getElementById('progressText');

          // Función para analizar un archivo individual
          singleForm.addEventListener('submit', async (event) => {
              event.preventDefault();
              const fileInput = document.getElementById('file');
              const file = fileInput.files[0];

              if (!file) {
                  alert('Por favor, seleccione un archivo.');
                  return;
              }

              // Mostrar mensaje de carga
              loadingDiv.style.display = 'block';

              const formData = new FormData();
              formData.append('file', file);
              formData.append('mode', 'single');

              try {
                  const response = await fetch('/cgi-bin/check_file.py', {
                      method: 'POST',
                      body: formData
                  });

                  if (!response.ok) {
                      throw new Error(`Error: ${response.statusText}`);
                  }

                  const data = await response.json();

                  if (data.error) {
                      resultsDiv.innerHTML = `<p><strong>Error:</strong> ${data.error}</p>`;
                  } else {
                      resultsDiv.innerHTML =
                          `<p><strong>Nombre del archivo:</strong> ${data.file_name}</p>
                           <p><strong>Hash (SHA256):</strong> ${data.hash}</p>
                           <p><strong>Estado:</strong> ${data.status === 'infected' ? 'Malicioso' : 'Limpio'}</p>
                           <p><strong>Ubicación:</strong> ${data.location}</p>`;

                      // Guardar registro en la base de datos vía PHP
                      fetch('save_record.php', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          body: JSON.stringify({
                              file_name: data.file_name,
                              hash: data.hash,
                              status: data.status,
                              location: data.location
                          })
                      })
                      .then(r => r.json())
                      .then(rdata => {
                          console.log('Registro guardado:', rdata);
                      })
                      .catch(e => console.error('Error al guardar registro:', e));
                  }
              } catch (error) {
                  resultsDiv.innerHTML = `<p><strong>Error:</strong> ${error.message}</p>`;
              } finally {
                  loadingDiv.style.display = 'none';
              }
          });

          // Función para analizar una carpeta
          folderForm.addEventListener('submit', async (event) => {
              event.preventDefault();
              const folderInput = document.getElementById('folder');
              const files = folderInput.files;

              if (!files || files.length === 0) {
                  alert('Por favor, seleccione una carpeta con archivos.');
                  return;
              }

              // Mostrar mensaje de carga y barra de progreso
              loadingDiv.style.display = 'block';
              progressContainer.style.display = 'block';
              progressBar.style.width = '0%';
              progressBar.textContent = '0%';
              progressText.textContent = `Procesando: 0/${files.length} archivos`;

              // Preparar tabla de resultados
              resultsDiv.innerHTML = `
                  <div class="results-summary">
                      <h3>Resumen</h3>
                      <p><strong>Total de archivos:</strong> ${files.length}</p>
                      <p><strong>Analizados:</strong> <span id="analyzed">0</span></p>
                      <p><strong>Limpios:</strong> <span id="clean">0</span></p>
                      <p><strong>Maliciosos:</strong> <span id="infected">0</span></p>
                  </div>
                  <table class="results-table">
                      <thead>
                          <tr>
                              <th>Archivo</th>
                              <th>Hash (SHA256)</th>
                              <th>Estado</th>
                              <th>Ubicación</th>
                          </tr>
                      </thead>
                      <tbody id="resultsTableBody">
                      </tbody>
                  </table>
              `;

              const tableBody = document.getElementById('resultsTableBody');
              const analyzedSpan = document.getElementById('analyzed');
              const cleanSpan = document.getElementById('clean');
              const infectedSpan = document.getElementById('infected');

              let analyzed = 0;
              let clean = 0;
              let infected = 0;

              // Procesar archivos uno por uno
              for (let i = 0; i < files.length; i++) {
                  const file = files[i];
                  const formData = new FormData();
                  formData.append('file', file);
                  formData.append('mode', 'folder');
                  formData.append('relativePath', file.webkitRelativePath || file.name);

                  try {
                      const response = await fetch('/cgi-bin/check_file.py', {
                          method: 'POST',
                          body: formData
                      });

                      if (!response.ok) {
                          throw new Error(`Error: ${response.statusText}`);
                      }

                      const data = await response.json();

                      analyzed++;
                      data.status === 'clean' ? clean++ : infected++;

                      // Actualizar tabla
                      const row = document.createElement('tr');
                      row.innerHTML = `
                          <td>${data.file_name}</td>
                          <td>${data.hash}</td>
                          <td>${data.status === 'infected' ? 'Malicioso' : 'Limpio'}</td>
                          <td>${data.location}</td>
                      `;
                      tableBody.appendChild(row);

                      // Actualizar resumen
                      analyzedSpan.textContent = analyzed;
                      cleanSpan.textContent = clean;
                      infectedSpan.textContent = infected;

                      // Actualizar barra de progreso
                      const progress = Math.floor((analyzed / files.length) * 100);
                      progressBar.style.width = `${progress}%`;
                      progressBar.textContent = `${progress}%`;
                      progressText.textContent = `Procesando: ${analyzed}/${files.length} archivos`;

                      // Guardar registro en la base de datos
                      fetch('save_record.php', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          body: JSON.stringify({
                              file_name: data.file_name,
                              hash: data.hash,
                              status: data.status,
                              location: data.location
                          })
                      })
                      .then(r => r.json())
                      .catch(e => console.error('Error al guardar registro:', e));

                  } catch (error) {
                      analyzed++;
                      // Actualizar tabla con error
                      const row = document.createElement('tr');
                      row.innerHTML = `
                          <td>${file.name}</td>
                          <td>-</td>
                          <td>Error: ${error.message}</td>
                          <td>-</td>
                      `;
                      tableBody.appendChild(row);

                      // Actualizar barra de progreso
                      const progress = Math.floor((analyzed / files.length) * 100);
                      progressBar.style.width = `${progress}%`;
                      progressBar.textContent = `${progress}%`;
                      progressText.textContent = `Procesando: ${analyzed}/${files.length} archivos`;
                  }
              }

              // Finalizar proceso
              loadingDiv.style.display = 'none';
              progressText.textContent = `Completado: ${analyzed}/${files.length} archivos procesados`;
          });
      });
  </script>
</body>
</html>

