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

// Consultar los archivos compartidos con el usuario
$stmt = $conn->prepare("SELECT archivos.file_name, archivos.upload_time, archivos_compartidos.compartido_por
                        FROM archivos_compartidos
                        JOIN archivos ON archivos_compartidos.archivo_id = archivos.id
                        WHERE archivos_compartidos.usuario_destinatario = ?
                        ORDER BY archivos.upload_time DESC");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Archivos Compartidos</title>
  <style>
      /* Estilos similares a los de `registro.php` */
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
  </style>
</head>
<body>
  <h2>Archivos compartidos con <?php echo htmlspecialchars($usuario); ?></h2>
  <table>
      <thead>
          <tr>
              <th>Nombre del Archivo</th>
              <th>Fecha de Subida</th>
              <th>Compartido por</th>
          </tr>
      </thead>
      <tbody>
          <?php if ($result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                  <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                  <td><?php echo $row['upload_time']; ?></td>
                  <td><?php echo htmlspecialchars($row['compartido_por']); ?></td>
              </tr>
              <?php endwhile; ?>
          <?php else: ?>
              <tr><td colspan="3">No tienes archivos compartidos.</td></tr>
          <?php endif; ?>
      </tbody>
  </table>
</body>
</html>

<?php
$conn->close();
?>
