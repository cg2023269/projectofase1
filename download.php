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
