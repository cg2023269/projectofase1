<?php
session_start();

// Verificar que el usuario sea administrador
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'administrador') {
    echo "Acceso denegado. Solo administradores pueden acceder a este panel.";
    exit;
}

// Conexión a la base de datos
$conn = new mysqli('localhost', 'admin', 'FranPerez', 'usuarios');
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Procesar acciones (actualizar usuario, agregar departamento, borrar departamento, eliminar usuario)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_user':
                $user_id = $_POST['user_id'] ?? '';
                $new_department = $_POST['department'] ?? '';
                if ($user_id && $new_department) {
                    $stmt = $conn->prepare("UPDATE usuarios SET departamento = ? WHERE id = ?");
                    $stmt->bind_param("si", $new_department, $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                break;

            case 'add_department':
                $dept_name = trim($_POST['dept_name'] ?? '');
                if ($dept_name) {
                    $stmt = $conn->prepare("INSERT INTO departamentos (nombre) VALUES (?)");
                    $stmt->bind_param("s", $dept_name);
                    $stmt->execute();
                    $stmt->close();
                }
                break;

            case 'delete_department':
                $dept_id = $_POST['dept_id'] ?? '';
                if ($dept_id) {
                    $stmt = $conn->prepare("DELETE FROM departamentos WHERE id = ?");
                    $stmt->bind_param("i", $dept_id);
                    $stmt->execute();
                    $stmt->close();
                }
                break;

            case 'delete_user':
                $user_id = $_POST['user_id'] ?? '';
                if ($user_id) {
                    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                break;
        }
    }
}

// Consultar la lista de usuarios
$userResult = $conn->query("SELECT id, nombre, correo, departamento FROM usuarios ORDER BY id ASC");
// Consultar la lista de departamentos
$deptResult = $conn->query("SELECT id, nombre FROM departamentos ORDER BY nombre ASC");
$departments = $deptResult->fetch_all(MYSQLI_ASSOC);
$deptResult->free();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de Administrador</title>
  <style>
      body { font-family: Arial, sans-serif; background: #ecf0f1; margin: 20px; }
      h2, h3 { color: #2c3e50; }
      table { border-collapse: collapse; width: 100%; background: #fff; margin-bottom: 20px; }
      th, td { padding: 10px; border: 1px solid #bdc3c7; text-align: left; }
      th { background-color: #34495e; color: #fff; }
      tr:nth-child(even) { background-color: #f2f2f2; }
      input[type="text"], select { padding: 5px; width: 200px; }
      input[type="submit"], button { padding: 5px 10px; background-color: #34495e; color: #fff; border: none; cursor: pointer; }
      input[type="submit"]:hover, button:hover { background-color: #2c3e50; }
      a { text-decoration: none; color: #3498db; }
  </style>
</head>
<body>
  <h2>Panel de Administrador</h2>
  <h3>Validar Usuarios y Asignar Departamento</h3>
  <table>
      <thead>
          <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Correo</th>
              <th>Departamento Actual</th>
              <th>Asignar Departamento</th>
              <th>Acciones</th>
          </tr>
      </thead>
      <tbody>
          <?php while ($user = $userResult->fetch_assoc()): ?>
          <tr>
              <td><?= $user['id'] ?></td>
              <td><?= htmlspecialchars($user['nombre']) ?></td>
              <td><?= htmlspecialchars($user['correo']) ?></td>
              <td><?= htmlspecialchars($user['departamento'] ?? '') ?></td>
              <td>
                  <form method="post" action="">
                      <input type="hidden" name="action" value="update_user">
                      <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                      <select name="department" required>
                          <option value="">-- Seleccione --</option>
                          <?php foreach ($departments as $dept): ?>
                              <option value="<?= $dept['nombre'] ?>"><?= $dept['nombre'] ?></option>
                          <?php endforeach; ?>
                      </select>
                      <input type="submit" value="Asignar">
                  </form>
              </td>
              <td>
                  <form method="post" action="" onsubmit="return confirm('¿Está seguro de eliminar este usuario?');">
                      <input type="hidden" name="action" value="delete_user">
                      <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                      <input type="submit" value="Eliminar Usuario">
                  </form>
              </td>
          </tr>
          <?php endwhile; ?>
      </tbody>
  </table>

  <h3>Administrar Departamentos</h3>
  <form method="post" action="">
      <input type="hidden" name="action" value="add_department">
      <label for="dept_name">Nuevo Departamento:</label>
      <input type="text" name="dept_name" id="dept_name" required>
      <input type="submit" value="Agregar">
  </form>
  <br>
  <table>
      <thead>
          <tr><th>ID</th><th>Nombre</th><th>Acción</th></tr>
      </thead>
      <tbody>
          <?php foreach ($departments as $dept): ?>
          <tr>
              <td><?= $dept['id'] ?></td>
              <td><?= htmlspecialchars($dept['nombre']) ?></td>
              <td>
                  <form method="post" action="" onsubmit="return confirm('¿Está seguro de borrar este departamento?');">
                      <input type="hidden" name="action" value="delete_department">
                      <input type="hidden" name="dept_id" value="<?= $dept['id'] ?>">
                      <input type="submit" value="Borrar">
                  </form>
              </td>
          </tr>
          <?php endforeach; ?>
      </tbody>
  </table>
<form action="index.php" method="get">
    <button type="submit" style="padding: 10px 15px; background-color: #34495e; color: white; border: none; border-radius: 4px; cursor: pointer;">
        Volver a la página principal
    </button>
</form>

</body>
</html>
<?php $userResult->free(); $conn->close(); ?>
