<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_id'])) {
    $file_id = $_POST['file_id'];
    $usuario = $_SESSION['username'];

    $conn = new mysqli('localhost', 'admin', 'FranPerez', 'usuarios');
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // Verificar si el archivo está compartido con el usuario actual (Usuario 2)
    $stmt = $conn->prepare("SELECT archivo_id FROM archivos_compartidos WHERE archivo_id = ? AND usuario_destinatario = ?");
    $stmt->bind_param("is", $file_id, $usuario);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // El archivo está compartido con el usuario actual, eliminarlo solo de la tabla de archivos compartidos
        $stmt = $conn->prepare("DELETE FROM archivos_compartidos WHERE archivo_id = ? AND usuario_destinatario = ?");
        $stmt->bind_param("is", $file_id, $usuario);
        $stmt->execute();
    } else {
        // El archivo no está compartido con el usuario actual, no hacer nada o manejarlo como error
        echo "Este archivo no está compartido con usted.";
    }

    $stmt->close();
    $conn->close();
}

// Redirigir de nuevo a la página de registro
header("Location: registro.php");
exit;
?>
