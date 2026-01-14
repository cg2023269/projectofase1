<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// Activar reporte de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['file'])) {
    die("No se especificó ningún archivo.");
}

// Definimos las mismas claves de cifrado que en el script de Python
define('ENCRYPTION_KEY', 'my32supersecretkey1234567890123456');
define('IV', 'my16byteiv123456');

function decryptFile($encryptedPath) {
    $method = 'AES-256-CBC';
    $key = ENCRYPTION_KEY;
    $iv = IV;

    // Verificar que el archivo existe
    if (!file_exists($encryptedPath)) {
        error_log("El archivo $encryptedPath no existe");
        return false;
    }

    // Leer el contenido cifrado
    $data = file_get_contents($encryptedPath);
    if ($data === false) {
        error_log("No se pudo leer el archivo $encryptedPath");
        return false;
    }

    // Registrar tamaño de archivo
    error_log("Tamaño del archivo cifrado: " . filesize($encryptedPath) . " bytes");

    // Descifrar el contenido
    $decrypted = openssl_decrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);

    if ($decrypted === false) {
        // Si falla el descifrado, mostrar información de error para depuración
        error_log("Error de descifrado: " . openssl_error_string());
        return false;
    }

    // Eliminar el padding PKCS7
    $padLength = ord(substr($decrypted, -1));
    if ($padLength > 0 && $padLength <= 16) {
        // Verificar que el padding es válido
        $padding = str_repeat(chr($padLength), $padLength);
        if (substr($decrypted, -$padLength) === $padding) {
            $decrypted = substr($decrypted, 0, -$padLength);
        }
    }

    error_log("Descifrado exitoso, tamaño: " . strlen($decrypted) . " bytes");
    return $decrypted;
}

// Obtener la ruta del archivo (puede incluir subdirectorios)
$file_path = $_GET['file'];

// Asegurarse de que tiene la extensión .enc
if (substr($file_path, -4) !== '.enc') {
    $file_path .= '.enc';
}

// Registrar la ruta solicitada para depuración
error_log("Ruta de archivo solicitada: $file_path");

// Rutas a las carpetas donde pueden estar los archivos
$upload_dir = '/var/www/html/viruscheck/clean/';
$infected_dir = '/var/www/html/viruscheck/infected/';

// Buscar el archivo en ambas ubicaciones
$clean_full_path = $upload_dir . $file_path;
$infected_full_path = $infected_dir . $file_path;

// Registrar las rutas para depuración
error_log("Buscando archivo en: $clean_full_path");
error_log("Buscando archivo en: $infected_full_path");

$full_file_path = '';
if (file_exists($clean_full_path)) {
    $full_file_path = $clean_full_path;
    error_log("Archivo encontrado en: $clean_full_path");
} elseif (file_exists($infected_full_path)) {
    $full_file_path = $infected_full_path;
    error_log("Archivo encontrado en: $infected_full_path");
}

// Verificar si el archivo existe
if (empty($full_file_path)) {
    die("El archivo no existe. No se encontró en ninguna ubicación.");
}

// Descifrar el archivo
$decrypted_content = decryptFile($full_file_path);
if ($decrypted_content === false) {
    die("Error al descifrar el archivo. Asegúrese de que el archivo esté correctamente cifrado.");
}

// Obtener el nombre original sin la extensión .enc
$original_filename = basename($file_path);
if (substr($original_filename, -4) === '.enc') {
    $original_filename = substr($original_filename, 0, -4);
}

// Enviar el archivo descifrado al usuario
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $original_filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($decrypted_content));
echo $decrypted_content;
exit;
?>
