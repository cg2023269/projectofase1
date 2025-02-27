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

// Manejar el formulario de compartir archivo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['compartir'])) {
    $archivo_id = $_POST['archivo_id'];
    $usuario_destinatario = $_POST['usuario_destinatario'];
    $departamento_destinatario = $_POST['departamento_destinatario'];

    // Insertar archivo compartido en la base de datos
    if (!empty($usuario_destinatario)) {
        $stmt_insert = $conn->prepare("INSERT INTO archivos_compartidos (archivo_id, usuario_destinatario, compartido_por)
                                      VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iss", $archivo_id, $usuario_destinatario, $usuario);
        $stmt_insert->execute();
    } elseif (!empty($departamento_destinatario)) {
        $stmt_insert = $conn->prepare("INSERT INTO archivos_compartidos (archivo_id, usuario_destinatario, compartido_por)
                                      SELECT ?, usuarios.nombre, ? FROM usuarios
                                      WHERE usuarios.departamento = ?");
        $stmt_insert->bind_param("iss", $archivo_id, $usuario, $departamento_destinatario);
        $stmt_insert->execute();
    }

    // Redirigir de nuevo a la página de registro para mantenerla
    header("Location: registro.php");
    exit;
}

// Eliminar archivo de 'Archivos subidos por Usuario'
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar'])) {
    $archivo_id = $_POST['archivo_id'];

    // Marcar el archivo como eliminado en la base de datos
    $stmt_delete = $conn->prepare("UPDATE archivos SET almacenado = 'No' WHERE id = ?");
    $stmt_delete->bind_param("i", $archivo_id);
    $stmt_delete->execute();

    // Redirigir de nuevo a la página de registro para mantenerla
    header("Location: registro.php");
    exit;
}

// Eliminar archivo de 'Archivos compartidos con usted'
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_compartido'])) {
    $archivo_id = $_POST['archivo_id'];

    // Eliminar solo de la tabla archivos_compartidos (no afecta a la tabla archivos)
    $stmt_delete_shared = $conn->prepare("DELETE FROM archivos_compartidos WHERE archivo_id = ? AND usuario_destinatario = ?");
    $stmt_delete_shared->bind_param("is", $archivo_id, $usuario);
    $stmt_delete_shared->execute();

    // Redirigir de nuevo a la página de registro
    header("Location: registro.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Archivos</title>
  <style>
      body {
          font-family: Arial, sans-serif;
          background: #ecf0f1;
          margin: 20px;
      }
      table {
          border-collapse: collapse;
          width: 100%;
          background: #fff;
      }
      th, td {
          padding: 10px;
          border: 1px solid #bdc3c7;
          text-align: left;
      }
      th {
          background-color: #34495e;
          color: #fff;
      }
      tr:nth-child(even) {
          background-color: #f2f2f2;
      }
      .button {
          padding: 6px 10px;
          background: #2c3e50;
          color: #fff;
          border-radius: 4px;
          border: none;
          cursor: pointer;
      }
      .button:hover {
          background: #1abc9c;
      }
      .popup {
          display: none;
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.5);
          justify-content: center;
          align-items: center;
      }
      .popup-content {
          background-color: white;
          padding: 20px;
          border-radius: 4px;
          width: 400px;
      }
  </style>
</head>
<body>
  <h2>Archivos subidos por <?php echo htmlspecialchars($usuario); ?></h2>
  <table>
      <thead>
          <tr>
              <th>ID</th>
              <th>Nombre del Archivo</th>
              <th>Estado</th>
              <th>Fecha de Subida</th>
              <th>Acciones</th>
          </tr>
      </thead>
      <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
              <td><?php echo $row['id']; ?></td>
              <td><?php echo htmlspecialchars($row['file_name']); ?></td>
              <td><?php echo htmlspecialchars($row['status']); ?></td>
              <td><?php echo $row['upload_time']; ?></td>
              <td>
                  <!-- Botón de descarga -->
                  <a class="button" href="download.php?file=<?php echo urlencode($row['file_name']); ?>">Descargar</a>

                  <!-- Botón para compartir (abre el pop-up) -->
                  <button class="button" onclick="openPopup(<?php echo $row['id']; ?>)">Compartir</button>

                  <!-- Botón para eliminar -->
                  <form method="post" action="registro.php" style="display:inline;">
                      <input type="hidden" name="archivo_id" value="<?php echo $row['id']; ?>">
                      <input type="submit" name="eliminar" value="Eliminar" class="button" style="background-color: #e74c3c;">
                  </form>
              </td>
          </tr>
          <?php endwhile; ?>
      </tbody>
  </table>

  <h3>Archivos compartidos con usted</h3>
  <table>
      <thead>
          <tr>
              <th>ID</th>
              <th>Nombre del Archivo</th>
              <th>Estado</th>
              <th>Fecha de Subida</th>
              <th>Compartido por</th>
              <th>Acciones</th>
          </tr>
      </thead>
      <tbody>
          <?php if ($result_share->num_rows > 0): ?>
              <?php while ($row = $result_share->fetch_assoc()): ?>
              <tr>
                  <td><?php echo $row['id']; ?></td>
                  <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                  <td><?php echo htmlspecialchars($row['status']); ?></td>
                  <td><?php echo $row['upload_time']; ?></td>
                  <td><?php echo htmlspecialchars($row['compartido_por']); ?></td>
                  <td>
                      <!-- Botón de descarga -->
                      <a class="button" href="download.php?file=<?php echo urlencode($row['file_name']); ?>">Descargar</a>

                      <!-- Botón para compartir (abre el pop-up) -->
                      <button class="button" onclick="openPopup(<?php echo $row['id']; ?>)">Compartir</button>

                      <!-- Botón para eliminar solo de archivos compartidos -->
                      <form method="post" action="registro.php" style="display:inline;">
                          <input type="hidden" name="archivo_id" value="<?php echo $row['id']; ?>">
                          <input type="submit" name="eliminar_compartido" value="Eliminar" class="button" style="background-color: #e74c3c;">
                      </form>
                  </td>
              </tr>
              <?php endwhile; ?>
          <?php else: ?>
              <tr><td colspan="6">No tienes archivos compartidos.</td></tr>
          <?php endif; ?>
      </tbody>
  </table>

  <!-- Pop-up para compartir archivo -->
  <div id="sharePopup" class="popup">
    <div class="popup-content">
        <h3>Compartir archivo</h3>
        <form method="post" action="registro.php">
            <input type="hidden" id="archivo_id" name="archivo_id">
            <label for="usuario_destinatario">Usuario destinatario:</label>
            <input type="text" id="usuario_destinatario" name="usuario_destinatario" placeholder="Usuario destinatario">            <br><br>
            <label for="departamento_destinatario">Departamento destinatario:</label>
            <input type="text" id="departamento_destinatario" name="departamento_destinatario" placeholder="Departamento destinatario">
            <br><br>
            <input type="submit" name="compartir" value="Compartir" class="button">
            <button type="button" onclick="closePopup()">Cerrar</button>
        </form>
    </div>
  </div>

  <script>
      function openPopup(archivoId) {
          document.getElementById("sharePopup").style.display = "flex";
          document.getElementById("archivo_id").value = archivoId;
      }

      function closePopup() {
          document.getElementById("sharePopup").style.display = "none";
      }
  </script>
<div style="display: flex; justify-content: flex-start; margin-top: 20px;">
    <form method="get" action="index.php">
        <input type="submit" value="Volver a Index" class="button">
    </form>
</div>


</body>
</html>

<?php
$conn->close();
?>
