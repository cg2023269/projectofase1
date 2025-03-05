<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$usuario = $_SESSION['username'];

$conn = new mysqli('localhost', 'admin', 'FranPerez', 'usuarios');
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$stmt = $conn->prepare("SELECT file_name, hash, status, location, upload_time, almacenado FROM archivos WHERE usuario = ? ORDER BY upload_time DESC");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Archivos</title>
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
        .eliminado {
            color: red;
            font-weight: bold;
        }
        .almacenado {
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h2>Historial de Archivos</h2>
    <table>
        <thead>
            <tr>
                <th>Nombre del Archivo</th>
                <th>Hash (SHA256)</th>
                <th>Estado</th>
                <th>Ubicación</th>
                <th>Fecha</th>
                <th>Estado de Almacenamiento</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                <td><?php echo htmlspecialchars($row['hash']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td><?php echo htmlspecialchars($row['location']); ?></td>
                <td><?php echo $row['upload_time']; ?></td>
                <td class="<?php echo $row['almacenado'] === 'Sí' ? 'almacenado' : 'eliminado'; ?>">
                    <?php echo $row['almacenado'] === 'Sí' ? 'Almacenado' : 'Eliminado'; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <p><a href="index.php">Volver a la página principal</a></p>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
