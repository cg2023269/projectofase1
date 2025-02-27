<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['file'])) {
    die("No se especificó ningún archivo.");
}

$file_name = basename($_GET['file']); // Sanitizamos el nombre

// Definir la carpeta donde se almacenan los archivos subidos.
// Puedes ajustar esta ruta según donde se guarden tus archivos.
$upload_dir = '/var/www/html/viruscheck/clean/'; // Ejemplo: archivos limpios
$infected_dir = '/var/www/html/viruscheck/infected/'; // Ejemplo: archivos infectados

// Opcional: se puede buscar en ambas carpetas según se necesite.
// Aquí se intenta en ambas rutas:
$file_path = '';
if (file_exists($upload_dir . $file_name)) {
    $file_path = $upload_dir . $file_name;
} elseif (file_exists($infected_dir . $file_name)) {
    $file_path = $infected_dir . $file_name;
} else {
    die("El archivo no existe.");
}

// Forzar la descarga del archivo
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));
readfile($file_path);
exit;
?>

alex@alex:/var/www/html/viruscheck$ ls
admin.php                 clean             download.php   infected    registro.php     uploads
archivos_compartidos.php  create_admin.php  historial.php  login       save_record.php
cgi-bin                   delete.php        index.php      logout.php  share.php
alex@alex:/var/www/html/viruscheck$ cat historial.php
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
