<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['error' => 'Datos no recibidos correctamente']);
    exit;
}

$file_name = $data['file_name'] ?? '';
$hash      = $data['hash'] ?? '';
$status    = $data['status'] ?? '';
$location  = $data['location'] ?? '';
$usuario   = $_SESSION['username'];

if (!$file_name || !$hash || !$status || !$location) {
    echo json_encode(['error' => 'Faltan datos']);
    exit;
}

$conn = new mysqli('localhost', 'admin', 'FranPerez', 'usuarios');
if ($conn->connect_error) {
    echo json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

$stmt = $conn->prepare("INSERT INTO archivos (usuario, file_name, hash, status, location, almacenado, upload_time) VALUES (?, ?, ?, ?, ?, 'Sí', NOW())");
$stmt->bind_param("sssss", $usuario, $file_name, $hash, $status, $location);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
