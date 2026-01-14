<?php
// create_admin.php

// Datos de conexión a la base de datos
$host = 'localhost';
$user = 'admin';
$password = 'FranPerez';
$dbname = 'usuarios';

// Crear la conexión
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Verificar si ya existe un usuario con el rol (departamento) administrador
$sql = "SELECT id FROM usuarios WHERE departamento = 'administrador' LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "Ya existe un usuario administrador.";
    exit;
}

// Datos para el usuario administrador
$nombre = 'Administrador';
$correo = 'admin@example.com';
$contraseña_plana = 'admin123'; // Contraseña en texto plano
$contraseña_hash = password_hash($contraseña_plana, PASSWORD_DEFAULT);
$departamento = 'administrador';

// Preparar e insertar el nuevo usuario
$stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, contraseña, departamento) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    die("Error en la preparación de la consulta: " . $conn->error);
}
$stmt->bind_param("ssss", $nombre, $correo, $contraseña_hash, $departamento);

if ($stmt->execute()) {
    echo "Usuario administrador creado exitosamente.<br>";
    echo "Correo: " . htmlspecialchars($correo) . "<br>";
    echo "Contraseña: " . htmlspecialchars($contraseña_plana) . "<br>";
    echo "Por favor, elimina o protege este archivo una vez que se haya creado el usuario.";
} else {
    echo "Error al crear el usuario administrador: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
