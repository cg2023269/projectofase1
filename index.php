<?php
session_start();
if (!isset($_SESSION['username'])) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Autenticación requerida</title>
      <style>
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
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Antiv FA - Página Principal</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
      body { 
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
          margin: 0; 
          padding: 0; 
          background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
          min-height: 100vh;
      }
      
      header { 
          background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
          color: white; 
          text-align: center; 
          padding: 15px 0;
          box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      }
      
      header img { 
          height: 50px; 
          vertical-align: middle;
          filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
      }
      
      nav { 
          display: flex; 
          justify-content: flex-end; 
          background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
          padding: 12px 20px;
          box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      }
      
      nav a { 
          color: white; 
          margin: 0 8px; 
          text-decoration: none; 
          padding: 10px 16px; 
          border-radius: 6px; 
          background: rgba(255,255,255,0.1);
          transition: all 0.3s ease;
          font-weight: 500;
      }
      
      nav a:hover { 
          background: #1abc9c;
          transform: translateY(-2px);
          box-shadow: 0 4px 8px rgba(26,188,156,0.3);
      }
      
      .container { 
          max-width: 1200px; 
          margin: 20px auto; 
          padding: 20px; 
          background: white; 
          border-radius: 15px; 
          box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
          backdrop-filter: blur(10px);
      }
      
      .section { 
          margin-bottom: 30px; 
      }
      
      .section h2 { 
          background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
          color: white;
          padding: 15px 20px;
          margin: -5px -5px 20px -5px;
          border-radius: 10px;
          font-size: 1.3em;
          font-weight: 600;
          box-shadow: 0 4px 15px rgba(44, 62, 80, 0.2);
      }
      
      .upload-options { 
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 20px; 
          margin-bottom: 20px; 
      }
      
      .upload-option { 
          padding: 20px; 
          background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
          border-radius: 12px; 
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
          text-align: center;
          border: 2px solid transparent;
          transition: all 0.3s ease;
      }
      
      .upload-option:hover {
          transform: translateY(-5px);
          box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
          border-color: #1abc9c;
      }
      
      .upload-option h3 {
          color: #2c3e50;
          margin-bottom: 15px;
          font-size: 1.1em;
      }
      
      .upload-option input[type="file"] {
          margin: 10px 0;
          padding: 8px;
          border: 2px dashed #bdc3c7;
          border-radius: 8px;
          width: 100%;
          box-sizing: border-box;
          transition: border-color 0.3s ease;
      }
      
      .upload-option input[type="file"]:hover {
          border-color: #1abc9c;
      }
      
      .upload-option button {
          background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
          color: white;
          border: none;
          padding: 12px 24px;
          border-radius: 8px;
          cursor: pointer;
          font-weight: 600;
          transition: all 0.3s ease;
          box-shadow: 0 4px 15px rgba(26, 188, 156, 0.3);
      }
      
      .upload-option button:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(26, 188, 156, 0.4);
      }
      
      .loading { 
          display: none; 
          color: #2c3e50; 
          font-weight: bold;
          text-align: center;
          padding: 20px;
          background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
          color: white;
          border-radius: 10px;
          box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
      }
      
      .progress-container { 
          display: none; 
          margin: 20px 0;
          padding: 20px;
          background: #f8f9fa;
          border-radius: 10px;
          box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);
      }
      
      .progress-bar { 
          width: 100%; 
          background-color: #ecf0f1; 
          border-radius: 25px; 
          height: 25px;
          overflow: hidden;
          box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);
      }
      
      .progress { 
          width: 0%; 
          height: 25px; 
          background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
          border-radius: 25px; 
          text-align: center; 
          line-height: 25px; 
          color: white;
          font-weight: bold;
          transition: width 0.3s ease;
          box-shadow: 0 2px 10px rgba(26, 188, 156, 0.3);
      }
      
      .results-table { 
          width: 100%; 
          border-collapse: collapse; 
          margin-top: 20px;
          border-radius: 10px;
          overflow: hidden;
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      }
      
      .results-table th, .results-table td { 
          border: none;
          padding: 12px 15px; 
          text-align: left; 
      }
      
      .results-table th { 
          background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
          color: white;
          font-weight: 600;
      }
      
      .results-table tr:nth-child(even) { 
          background-color: #f8f9fa; 
      }
      
      .results-table tr:hover {
          background-color: #e8f4f8;
          transition: background-color 0.3s ease;
      }
      
      .results-summary { 
          margin-top: 20px; 
          padding: 20px; 
          background: linear-gradient(135deg, #e8f4f8 0%, #d5e8f3 100%);
          border-radius: 12px;
          border-left: 5px solid #1abc9c;
          box-shadow: 0 4px 15px rgba(26, 188, 156, 0.1);
      }
      
      .results-summary h3 {
          color: #2c3e50;
          margin-top: 0;
          margin-bottom: 15px;
      }
      
      .chart-container {
          display: flex;
          justify-content: center;
          align-items: center;
          margin: 15px 0;
          padding: 15px;
          background: #f8f9fa;
          border-radius: 12px;
          box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);
      }
      
      canvas#resultsChart {
          max-width: 350px;
          max-height: 350px;
          display: block;
      }
      
      .status-badge {
          padding: 4px 8px;
          border-radius: 12px;
          font-size: 0.85em;
          font-weight: bold;
      }
      
      .status-clean {
          background-color: #d4edda;
          color: #155724;
      }
      
      .status-infected {
          background-color: #f8d7da;
          color: #721c24;
      }
      
      #progressText {
          text-align: center;
          margin-top: 10px;
          font-weight: 600;
          color: #2c3e50;
      }
      
      @media (max-width: 768px) {
          .upload-options {
              grid-template-columns: 1fr;
          }
          
          .container {
              margin: 10px;
              padding: 15px;
          }
          
          nav {
              flex-wrap: wrap;
              justify-content: center;
          }
          
          canvas#resultsChart {
              max-width: 280px;
              max-height: 280px;
          }
      }
  </style>
</head>
<body>
  <header>
      <img src="logo2.png" alt="Antiv FA Logo" style="width: 120px; height: auto;">
  </header>
  <nav>
      <a href="registro.php">📁 Administrador de archivos</a>
      <a href="historial.php">📊 Historial</a>
      <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'administrador') echo '<a href="admin.php">⚙️ Panel Admin</a>'; ?>
      <a href="logout.php">🚪 Cerrar Sesión</a>
  </nav>
  <div class="container">
      <div class="section">
          <h2>🔍 Subir y Analizar Archivos</h2>
          <div class="upload-options">
              <div class="upload-option">
                  <h3>📄 Analizar Archivo Individual</h3>
                  <form id="uploadForm" method="post" enctype="multipart/form-data">
                      <input type="file" id="file" name="file" required>
                      <button type="submit">Subir y analizar</button>
                  </form>
              </div>
              <div class="upload-option">
                  <h3>📁 Analizar Carpeta</h3>
                  <form id="folderForm" method="post" enctype="multipart/form-data">
                      <input type="file" id="folder" name="folder[]" webkitdirectory directory multiple>
                      <button type="submit">Subir y analizar carpeta</button>
                  </form>
              </div>
          </div>
          <div class="loading" id="loading">🔄 Analizando archivos, por favor espere...</div>
          <div class="progress-container" id="progressContainer">
              <div class="progress-bar">
                  <div class="progress" id="progressBar">0%</div>
              </div>
              <p id="progressText">Procesando: 0/0 archivos</p>
          </div>
      </div>

      <div class="section">
          <h2>📈 Resultados</h2>
          <div class="results">
              <div id="resultsContent">
                  <p style="text-align: center; color: #7f8c8d; font-style: italic; padding: 40px;">
                      <strong>🎯 Seleccione un archivo o carpeta para analizar.</strong>
                  </p>
              </div>
              <div class="chart-container">
                  <canvas id="resultsChart" width="400" height="300"></canvas>
              </div>
          </div>
      </div>
  </div>

  <script>
      let chart;
      let totalClean = 0;
      let totalInfected = 0;

      function updateChart(cleanCount, infectedCount) {
          totalClean += cleanCount;
          totalInfected += infectedCount;
          const ctx = document.getElementById('resultsChart').getContext('2d');
          if (chart) chart.destroy();
          
          chart = new Chart(ctx, {
              type: 'doughnut',
              data: {
                  labels: ['🟢 Archivos Limpios', '🔴 Archivos Maliciosos'],
                  datasets: [{
                      data: [totalClean, totalInfected],
                      backgroundColor: [
                          'rgba(26, 188, 156, 0.8)',
                          'rgba(231, 76, 60, 0.8)'
                      ],
                      borderColor: [
                          'rgba(26, 188, 156, 1)',
                          'rgba(231, 76, 60, 1)'
                      ],
                      borderWidth: 2,
                      hoverBackgroundColor: [
                          'rgba(26, 188, 156, 1)',
                          'rgba(231, 76, 60, 1)'
                      ]
                  }]
              },
              options: {
                  responsive: true,
                  maintainAspectRatio: true,
                  layout: {
                      padding: 10
                  },
                  plugins: {
                      legend: { 
                          position: 'bottom',
                          labels: {
                              padding: 15,
                              font: {
                                  size: 12,
                                  weight: 'bold'
                              }
                          }
                      },
                      title: { 
                          display: true, 
                          text: '📊 Distribución de Archivos Analizados',
                          font: {
                              size: 16,
                              weight: 'bold'
                          },
                          color: '#2c3e50'
                      }
                  },
                  cutout: '60%'
              }
          });
      }

      document.addEventListener('DOMContentLoaded', () => {
          const resultsDiv = document.getElementById('resultsContent');
          const loadingDiv = document.getElementById('loading');
          const progressContainer = document.getElementById('progressContainer');
          const progressBar = document.getElementById('progressBar');
          const progressText = document.getElementById('progressText');

          document.getElementById('uploadForm').addEventListener('submit', async (event) => {
              event.preventDefault();
              const fileInput = document.getElementById('file');
              const file = fileInput.files[0];
              if (!file) return alert('Por favor, seleccione un archivo.');

              loadingDiv.style.display = 'block';
              const formData = new FormData();
              formData.append('file', file);
              formData.append('mode', 'single');

              try {
                  const response = await fetch('/cgi-bin/check_file.py', { method: 'POST', body: formData });
                  const data = await response.json();
                  const statusClass = data.status === 'infected' ? 'status-infected' : 'status-clean';
                  const statusText = data.status === 'infected' ? '🔴 Malicioso' : '🟢 Limpio';
                  
                  resultsDiv.innerHTML = `
                      <div style="padding: 20px; background: #f8f9fa; border-radius: 10px; margin-bottom: 15px;">
                          <p><strong>📄 Nombre del archivo:</strong> ${data.file_name}</p>
                          <p><strong>🔗 Hash (SHA256):</strong> <span style="font-family: monospace; font-size: 0.9em;">${data.hash}</span></p>
                          <p><strong>🛡️ Estado:</strong> <span class="status-badge ${statusClass}">${statusText}</span></p>
                          <p><strong>📍 Ubicación:</strong> ${data.location}</p>
                      </div>`;
                  updateChart(data.status === 'clean' ? 1 : 0, data.status === 'infected' ? 1 : 0);
              } catch (error) {
                  resultsDiv.innerHTML = `<div style="padding: 20px; background: #f8d7da; color: #721c24; border-radius: 10px;"><p><strong>❌ Error:</strong> ${error.message}</p></div>`;
              } finally {
                  loadingDiv.style.display = 'none';
              }
          });

          document.getElementById('folderForm').addEventListener('submit', async (event) => {
              event.preventDefault();
              const folderInput = document.getElementById('folder');
              const files = folderInput.files;
              if (!files || files.length === 0) return alert('Por favor, seleccione una carpeta con archivos.');

              loadingDiv.style.display = 'block';
              progressContainer.style.display = 'block';
              progressBar.style.width = '0%';
              progressBar.textContent = '0%';
              progressText.textContent = `Procesando: 0/${files.length} archivos`;

              let analyzed = 0, clean = 0, infected = 0;
              resultsDiv.innerHTML = `
                  <div class="results-summary">
                      <h3>📊 Resumen del Análisis</h3>
                      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                          <p><strong>📁 Total de archivos:</strong> ${files.length}</p>
                          <p><strong>🔍 Analizados:</strong> <span id="analyzed">0</span></p>
                          <p><strong>🟢 Limpios:</strong> <span id="clean">0</span></p>
                          <p><strong>🔴 Maliciosos:</strong> <span id="infected">0</span></p>
                      </div>
                  </div>
                  <table class="results-table">
                      <thead><tr><th>📄 Archivo</th><th>🔗 Hash (SHA256)</th><th>🛡️ Estado</th><th>📍 Ubicación</th></tr></thead>
                      <tbody id="resultsTableBody"></tbody>
                  </table>`;
              const tableBody = document.getElementById('resultsTableBody');

              for (let i = 0; i < files.length; i++) {
                  const file = files[i];
                  const formData = new FormData();
                  formData.append('file', file);
                  formData.append('mode', 'folder');
                  formData.append('relativePath', file.webkitRelativePath || file.name);

                  try {
                      const response = await fetch('/cgi-bin/check_file.py', { method: 'POST', body: formData });
                      const data = await response.json();
                      analyzed++;
                      const isClean = data.status === 'clean';
                      isClean ? clean++ : infected++;
                      const statusClass = isClean ? 'status-clean' : 'status-infected';
                      const statusText = isClean ? '🟢 Limpio' : '🔴 Malicioso';
                      
                      tableBody.innerHTML += `<tr><td>${data.file_name}</td><td style="font-family: monospace; font-size: 0.85em;">${data.hash}</td><td><span class="status-badge ${statusClass}">${statusText}</span></td><td>${data.location}</td></tr>`;
                  } catch (error) {
                      analyzed++;
                      tableBody.innerHTML += `<tr><td>${file.name}</td><td>-</td><td><span class="status-badge" style="background-color: #fff3cd; color: #856404;">⚠️ Error: ${error.message}</span></td><td>-</td></tr>`;
                  }

                  document.getElementById('analyzed').textContent = analyzed;
                  document.getElementById('clean').textContent = clean;
                  document.getElementById('infected').textContent = infected;
                  const progress = Math.floor((analyzed / files.length) * 100);
                  progressBar.style.width = `${progress}%`;
                  progressBar.textContent = `${progress}%`;
                  progressText.textContent = `Procesando: ${analyzed}/${files.length} archivos`;
              }

              loadingDiv.style.display = 'none';
              progressContainer.style.display = 'none';
              updateChart(clean, infected);
          });
      });
  </script>
</body>
</html>

