<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$usuario = $_SESSION['username'];

// Conexión a la base de datos
$conn = new mysqli('localhost', 'admin', 'FranPerez', 'usuarios');
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Consultar los archivos subidos por el usuario, excluyendo los marcados como eliminados
$stmt = $conn->prepare("SELECT id, file_name, status, upload_time, almacenado, location
                        FROM archivos
                        WHERE usuario = ? AND almacenado = 'Sí'
                        ORDER BY upload_time DESC");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

// Consultar los archivos compartidos con el usuario
$stmt_share = $conn->prepare("SELECT archivos.id, archivos.file_name, archivos.status, archivos.upload_time, archivos_compartidos.compartido_por
                              FROM archivos_compartidos
                              JOIN archivos ON archivos_compartidos.archivo_id = archivos.id
                              WHERE archivos_compartidos.usuario_destinatario = ?
                              ORDER BY archivos.upload_time DESC");
$stmt_share->bind_param("s", $usuario);
$stmt_share->execute();
$result_share = $stmt_share->get_result();

// Obtener la lista de usuarios y departamentos
$usuarios = [];
$departamentos = [];

$result_usuarios = $conn->query("SELECT nombre FROM usuarios WHERE nombre != '$usuario'");
while ($row = $result_usuarios->fetch_assoc()) {
    $usuarios[] = $row['nombre'];
}

$result_departamentos = $conn->query("SELECT nombre FROM departamentos");
while ($row = $result_departamentos->fetch_assoc()) {
    $departamentos[] = $row['nombre'];
}

// Manejar el formulario de compartir archivo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['compartir'])) {
    $archivo_id = $_POST['archivo_id'];
    $usuario_destinatario = $_POST['usuario_destinatario'];
    $departamento_destinatario = $_POST['departamento_destinatario'];

    // Variables para el mensaje de respuesta
    $mensaje = '';
    $tipo_mensaje = '';

    // Insertar archivo compartido en la base de datos
    if (!empty($usuario_destinatario)) {
        // Verificar que no se esté compartiendo consigo mismo
        if ($usuario_destinatario !== $usuario) {
            // Verificar si ya está compartido
            $check_stmt = $conn->prepare("SELECT id FROM archivos_compartidos WHERE archivo_id = ? AND usuario_destinatario = ?");
            $check_stmt->bind_param("is", $archivo_id, $usuario_destinatario);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                $stmt_insert = $conn->prepare("INSERT INTO archivos_compartidos (archivo_id, usuario_destinatario, compartido_por)
                                              VALUES (?, ?, ?)");
                $stmt_insert->bind_param("iss", $archivo_id, $usuario_destinatario, $usuario);
                if ($stmt_insert->execute()) {
                    $mensaje = "Archivo compartido exitosamente con $usuario_destinatario";
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = "Error al compartir el archivo";
                    $tipo_mensaje = 'error';
                }
            } else {
                $mensaje = "El archivo ya está compartido con este usuario";
                $tipo_mensaje = 'warning';
            }
        } else {
            $mensaje = "No puedes compartir un archivo contigo mismo";
            $tipo_mensaje = 'warning';
        }
    } elseif (!empty($departamento_destinatario)) {
        $stmt_insert = $conn->prepare("INSERT IGNORE INTO archivos_compartidos (archivo_id, usuario_destinatario, compartido_por)
                                      SELECT ?, usuarios.nombre, ? FROM usuarios
                                      WHERE usuarios.departamento = ? AND usuarios.nombre != ?");
        $stmt_insert->bind_param("isss", $archivo_id, $usuario, $departamento_destinatario, $usuario);
        if ($stmt_insert->execute()) {
            $affected_rows = $stmt_insert->affected_rows;
            $mensaje = "Archivo compartido con $affected_rows usuarios del departamento $departamento_destinatario";
            $tipo_mensaje = 'success';
        } else {
            $mensaje = "Error al compartir el archivo con el departamento";
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = "Debe seleccionar un usuario o departamento";
        $tipo_mensaje = 'warning';
    }
}

// Eliminar archivo de 'Archivos subidos por Usuario'
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar'])) {
    $archivo_id = $_POST['archivo_id'];

    // Marcar el archivo como eliminado en la base de datos
    $stmt_delete = $conn->prepare("UPDATE archivos SET almacenado = 'No' WHERE id = ? AND usuario = ?");
    $stmt_delete->bind_param("is", $archivo_id, $usuario);
    if ($stmt_delete->execute()) {
        $mensaje = "Archivo eliminado exitosamente";
        $tipo_mensaje = 'success';
    } else {
        $mensaje = "Error al eliminar el archivo";
        $tipo_mensaje = 'error';
    }
}

// Eliminar archivo de 'Archivos compartidos con usted'
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_compartido'])) {
    $archivo_id = $_POST['archivo_id'];

    // Eliminar solo de la tabla archivos_compartidos (no afecta a la tabla archivos)
    $stmt_delete_shared = $conn->prepare("DELETE FROM archivos_compartidos WHERE archivo_id = ? AND usuario_destinatario = ?");
    $stmt_delete_shared->bind_param("is", $archivo_id, $usuario);
    if ($stmt_delete_shared->execute()) {
        $mensaje = "Archivo eliminado de sus compartidos";
        $tipo_mensaje = 'success';
    } else {
        $mensaje = "Error al eliminar el archivo compartido";
        $tipo_mensaje = 'error';
    }
}

// Recargar los resultados después de las operaciones
if (isset($mensaje)) {
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt_share->execute();
    $result_share = $stmt_share->get_result();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administrador de Archivos - Antiv FA</title>
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
          padding: 20px 0;
          box-shadow: 0 4px 15px rgba(0,0,0,0.1);
          margin-bottom: 20px;
      }
      
      header h1 {
          margin: 0;
          font-size: 2em;
          font-weight: 600;
      }
      
      .user-info {
          margin-top: 8px;
          opacity: 0.9;
          font-size: 1.1em;
      }
      
      .container {
          max-width: 1400px;
          margin: 0 auto;
          padding: 20px;
      }
      
      .section {
          background: white;
          border-radius: 15px;
          box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
          margin-bottom: 30px;
          overflow: hidden;
      }
      
      .section-header {
          background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
          color: white;
          padding: 20px 25px;
          font-size: 1.3em;
          font-weight: 600;
          box-shadow: 0 2px 10px rgba(44, 62, 80, 0.2);
      }
      
      .table-container {
          overflow-x: auto;
          padding: 0;
      }
      
      table {
          border-collapse: collapse;
          width: 100%;
          background: #fff;
          margin: 0;
      }
      
      th, td {
          padding: 15px 20px;
          text-align: left;
          border-bottom: 1px solid #ecf0f1;
      }
      
      th {
          background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
          color: #fff;
          font-weight: 600;
          text-transform: uppercase;
          font-size: 0.85em;
          letter-spacing: 0.5px;
      }
      
      tr:hover {
          background-color: #f8f9fa;
          transition: background-color 0.3s ease;
      }
      
      tr:nth-child(even) {
          background-color: #fafbfc;
      }
      
      .button {
          padding: 8px 16px;
          border-radius: 6px;
          border: none;
          cursor: pointer;
          font-weight: 600;
          font-size: 0.85em;
          margin: 2px;
          transition: all 0.3s ease;
          text-decoration: none;
          display: inline-block;
          text-align: center;
      }
      
      .btn-primary {
          background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
          color: white;
          box-shadow: 0 4px 15px rgba(26, 188, 156, 0.3);
      }
      
      .btn-primary:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(26, 188, 156, 0.4);
      }
      
      .btn-secondary {
          background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
          color: white;
          box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
      }
      
      .btn-secondary:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
      }
      
      .btn-danger {
          background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
          color: white;
          box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
      }
      
      .btn-danger:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
      }
      
      .btn-back {
          background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
          color: white;
          box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
          padding: 12px 24px;
          font-size: 1em;
      }
      
      .btn-back:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(149, 165, 166, 0.4);
      }
      
      .status-badge {
          padding: 4px 12px;
          border-radius: 12px;
          font-size: 0.8em;
          font-weight: bold;
          text-transform: uppercase;
          letter-spacing: 0.5px;
      }
      
      .status-clean {
          background-color: #d4edda;
          color: #155724;
      }
      
      .status-infected {
          background-color: #f8d7da;
          color: #721c24;
      }
      
      .popup {
          display: none;
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.6);
          justify-content: center;
          align-items: center;
          z-index: 1000;
          backdrop-filter: blur(5px);
      }
      
      .popup-content {
          background: white;
          padding: 30px;
          border-radius: 15px;
          width: 90%;
          max-width: 500px;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
          animation: popupSlideIn 0.3s ease-out;
      }
      
      @keyframes popupSlideIn {
          from {
              opacity: 0;
              transform: translateY(-50px) scale(0.9);
          }
          to {
              opacity: 1;
              transform: translateY(0) scale(1);
          }
      }
      
      .popup-content h3 {
          margin-top: 0;
          color: #2c3e50;
          font-size: 1.4em;
          margin-bottom: 20px;
          text-align: center;
      }
      
      .form-group {
          margin-bottom: 20px;
      }
      
      .form-group label {
          display: block;
          margin-bottom: 8px;
          font-weight: 600;
          color: #2c3e50;
      }
      
      .form-group select {
          width: 100%;
          padding: 12px;
          border: 2px solid #ecf0f1;
          border-radius: 8px;
          font-size: 1em;
          background: white;
          transition: border-color 0.3s ease;
      }
      
      .form-group select:focus {
          outline: none;
          border-color: #1abc9c;
          box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.1);
      }
      
      .popup-buttons {
          display: flex;
          gap: 10px;
          justify-content: center;
          margin-top: 25px;
      }
      
      .no-files-message {
          text-align: center;
          padding: 40px 20px;
          color: #7f8c8d;
          font-style: italic;
          font-size: 1.1em;
      }
      
      .actions-cell {
          white-space: nowrap;
      }
      
      .file-info {
          display: flex;
          flex-direction: column;
          gap: 4px;
      }
      
      .file-name {
          font-weight: 600;
          color: #2c3e50;
      }
      
      .file-size {
          font-size: 0.85em;
          color: #7f8c8d;
      }
      
      .back-container {
          display: flex;
          justify-content: flex-start;
          margin-top: 30px;
          padding: 0 20px;
      }
      
      .message {
          padding: 15px 20px;
          border-radius: 8px;
          margin-bottom: 20px;
          font-weight: 600;
          text-align: center;
          animation: messageSlideIn 0.5s ease-out;
      }
      
      @keyframes messageSlideIn {
          from {
              opacity: 0;
              transform: translateY(-20px);
          }
          to {
              opacity: 1;
              transform: translateY(0);
          }
      }
      
      .message.success {
          background-color: #d4edda;
          color: #155724;
          border: 1px solid #c3e6cb;
      }
      
      .message.error {
          background-color: #f8d7da;
          color: #721c24;
          border: 1px solid #f5c6cb;
      }
      
      .message.warning {
          background-color: #fff3cd;
          color: #856404;
          border: 1px solid #ffeaa7;
      }
      
      .stats-container {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 20px;
          margin-bottom: 30px;
      }
      
      .stat-card {
          background: white;
          padding: 20px;
          border-radius: 12px;
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
          text-align: center;
      }
      
      .stat-number {
          font-size: 2em;
          font-weight: bold;
          color: #2c3e50;
          margin-bottom: 5px;
      }
      
      .stat-label {
          color: #7f8c8d;
          font-size: 0.9em;
          text-transform: uppercase;
          letter-spacing: 0.5px;
      }
      
      @media (max-width: 768px) {
          .container {
              padding: 10px;
          }
          
          th, td {
              padding: 10px 8px;
              font-size: 0.9em;
          }
          
          .button {
              padding: 6px 10px;
              font-size: 0.8em;
              margin: 1px;
          }
          
          .popup-content {
              width: 95%;
              padding: 20px;
          }
          
          .actions-cell {
              white-space: normal;
          }
          
          .actions-cell .button {
              display: block;
              margin: 2px 0;
              width: 100%;
          }
      }
  </style>
</head>
<body>
  <header>
      <h1>📁 Administrador de Archivos</h1>
      <div class="user-info">👤 Usuario: <?php echo htmlspecialchars($usuario); ?></div>
 <!-- Contenedor del botón de regreso -->
    <div class="back-container">
        <a href="index.php" class="button btn-secondary">🏠 Volver a la Página Principal</a>
    </div>
  </header>

  
  <div class="container">
      <?php if (isset($mensaje)): ?>
          <div class="message <?php echo $tipo_mensaje; ?>">
              <?php echo htmlspecialchars($mensaje); ?>
          </div>
      <?php endif; ?>
      
      <?php
      // Calcular estadísticas
      $total_archivos = $result->num_rows;
      $archivos_compartidos = $result_share->num_rows;
      
      // Contar archivos por estado
      $result->data_seek(0);
      $archivos_limpios = 0;
      $archivos_infectados = 0;
      while ($row = $result->fetch_assoc()) {
          if ($row['status'] == 'clean') $archivos_limpios++;
          else $archivos_infectados++;
      }
      $result->data_seek(0);
      ?>
      
      <div class="stats-container">
          <div class="stat-card">
              <div class="stat-number"><?php echo $total_archivos; ?></div>
              <div class="stat-label">📄 Mis Archivos</div>
          </div>
          <div class="stat-card">
              <div class="stat-number"><?php echo $archivos_compartidos; ?></div>
              <div class="stat-label">🤝 Compartidos Conmigo</div>
          </div>
          <div class="stat-card">
              <div class="stat-number"><?php echo $archivos_limpios; ?></div>
              <div class="stat-label">🟢 Archivos Limpios</div>
          </div>
          <div class="stat-card">
              <div class="stat-number"><?php echo $archivos_infectados; ?></div>
              <div class="stat-label">🔴 Archivos Infectados</div>
          </div>
      </div>
      
      <div class="section">
          <div class="section-header">
              📂 Mis Archivos Subidos
          </div>
          <div class="table-container">
              <?php if ($result->num_rows > 0): ?>
                  <table>
                      <thead>
                          <tr>
                              <th>📋 ID</th>
                              <th>📄 Nombre del Archivo</th>
                              <th>🛡️ Estado</th>
                              <th>📅 Fecha de Subida</th>
                              <th>⚡ Acciones</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php while ($row = $result->fetch_assoc()): ?>
                          <tr>
                              <td><?php echo $row['id']; ?></td>
                              <td>
                                  <div class="file-info">
                                      <div class="file-name"><?php echo htmlspecialchars($row['file_name']); ?></div>
                                      <div class="file-size">📍 <?php echo htmlspecialchars($row['location']); ?></div>
                                  </div>
                              </td>
                              <td>
                                  <?php 
                                  $statusClass = $row['status'] === 'infected' ? 'status-infected' : 'status-clean';
                                  $statusText = $row['status'] === 'infected' ? '🔴 Infectado' : '🟢 Limpio';
                                  ?>
                                  <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                              </td>
                              <td><?php echo date('d/m/Y H:i', strtotime($row['upload_time'])); ?></td>
                              <td class="actions-cell">
                                  <a class="button btn-primary" href="download.php?file=<?php echo urlencode($row['file_name']); ?>">⬇️ Descargar</a>
                                  <button class="button btn-secondary" onclick="openPopup(<?php echo $row['id']; ?>)">🤝 Compartir</button>
                                  <form method="post" action="registro.php" style="display:inline;">
                                      <input type="hidden" name="archivo_id" value="<?php echo $row['id']; ?>">
                                      <input type="submit" name="eliminar" value="🗑️ Eliminar" class="button btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este archivo?');">
                                  </form>
                              </td>
                          </tr>
                          <?php endwhile; ?>
                      </tbody>
                  </table>
              <?php else: ?>
                  <div class="no-files-message">
                      📭 No tienes archivos subidos todavía.
                  </div>
              <?php endif; ?>
          </div>
      </div>

      <div class="section">
          <div class="section-header">
              🤝 Archivos Compartidos Conmigo
          </div>
          <div class="table-container">
              <?php if ($result_share->num_rows > 0): ?>
                  <table>
                      <thead>
                          <tr>
                              <th>📋 ID</th>
                              <th>📄 Nombre del Archivo</th>
                              <th>🛡️ Estado</th>
                              <th>📅 Fecha de Subida</th>
                              <th>👤 Compartido por</th>
                              <th>⚡ Acciones</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php while ($row = $result_share->fetch_assoc()): ?>
                          <tr>
                              <td><?php echo $row['id']; ?></td>
                              <td>
                                  <div class="file-info">
                                      <div class="file-name"><?php echo htmlspecialchars($row['file_name']); ?></div>
                                  </div>
                              </td>
                              <td>
                                  <?php 
                                  $statusClass = $row['status'] === 'infected' ? 'status-infected' : 'status-clean';
                                  $statusText = $row['status'] === 'infected' ? '🔴 Infectado' : '🟢 Limpio';
                                  ?>
                                  <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                              </td>
                              <td><?php echo date('d/m/Y H:i', strtotime($row['upload_time'])); ?></td>
                              <td><?php echo htmlspecialchars($row['compartido_por']); ?></td>
                              <td class="actions-cell">
                                  <a class="button btn-primary" href="download.php?file=<?php echo urlencode($row['file_name']); ?>">⬇️ Descargar</a>
                                  <button class="button btn-secondary" onclick="openPopup(<?php echo $row['id']; ?>)">🤝 Compartir</button>
                                  <form method="post" action="registro.php" style="display:inline;">
                                      <input type="hidden" name="archivo_id" value="<?php echo $row['id']; ?>">
                                      <input type="submit" name="eliminar_compartido" value="🗑️ Quitar" class="button btn-danger" onclick="return confirm('¿Deseas quitar este archivo de tus compartidos?');">
                                  </form>
                              </td>
                          </tr>
                          <?php endwhile; ?>
                      </tbody>
                  </table>
              <?php else: ?>
                  <div class="no-files-message">
                      📭 No tienes archivos compartidos contigo.
                  </div>
              <?php endif; ?>
          </div>
      </div>
  </div>

  <div class="back-container">
      <form method="get" action="index.php">
          <input type="submit" value="🏠 Volver al Inicio" class="button btn-back">
      </form>
  </div>

  <!-- Pop-up para compartir archivo -->
  <div id="sharePopup" class="popup">
      <div class="popup-content">
          <h3>🤝 Compartir Archivo</h3>
          <form method="post" action="registro.php">
              <input type="hidden" id="archivo_id" name="archivo_id">
              
              <div class="form-group">
                  <label for="usuario_destinatario">👤 Usuario destinatario:</label>
                  <select id="usuario_destinatario" name="usuario_destinatario">
                      <option value="">Seleccione un usuario</option>
                      <?php foreach ($usuarios as $usr): ?>
                          <option value="<?php echo htmlspecialchars($usr); ?>"><?php echo htmlspecialchars($usr); ?></option>
                      <?php endforeach; ?>
                  </select>
              </div>
              
              <div class="form-group">
                  <label for="departamento_destinatario">🏢 Departamento destinatario:</label>
                  <select id="departamento_destinatario" name="departamento_destinatario">
                      <option value="">Seleccione un departamento</option>
                      <?php foreach ($departamentos as $dept): ?>
                          <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                      <?php endforeach; ?>
                  </select>
              </div>
              
              <div class="popup-buttons">
                  <input type="submit" name="compartir" value="✅ Compartir" class="button btn-primary">
                  <button type="button" onclick="closePopup()" class="button btn-danger">❌ Cancelar</button>
              </div>
          </form>
      </div>
  </div>

  <script>
      function openPopup(archivoId) {
          document.getElementById("sharePopup").style.display = "flex";
          document.getElementById("archivo_id").value = archivoId;
          // Limpiar selecciones previas
          document.getElementById("usuario_destinatario").value = "";
          document.getElementById("departamento_destinatario").value = "";
      }

      function closePopup() {
          document.getElementById("sharePopup").style.display = "none";
      }

      // Cerrar popup al hacer clic fuera de él
      document.getElementById("sharePopup").addEventListener("click", function(event) {
          if (event.target === this) {
              closePopup();
          }
      });

      // Validar que solo se seleccione usuario O departamento
      document.getElementById("usuario_destinatario").addEventListener("change", function() {
          if (this.value) {
              document.getElementById("departamento_destinatario").value = "";
          }
      });

      document.getElementById("departamento_destinatario").addEventListener("change", function() {
          if (this.value) {
              document.getElementById("usuario_destinatario").value = "";
          }
      });

      // Auto-hide messages after 5 seconds
      const messageElement = document.querySelector('.message');
      if (messageElement) {
          setTimeout(() => {
              messageElement.style.opacity = '0';
              messageElement.style.transform = 'translateY(-20px)';
              setTimeout(() => {
                  messageElement.style.display = 'none';
              }, 300);
          }, 5000);
      }
  </script>
</body>
</html>

<?php
$conn->close();
?>

