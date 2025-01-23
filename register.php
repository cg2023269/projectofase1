<?php
// Configuración de conexión a la base de datos
$servidor = "10.30.241.174";
$usuario = "admin";
$contrasena = "FranPerez";
$base_datos = "registro";

// Crear conexión
$conn = new mysqli($servidor, $usuario, $contrasena, $base_datos);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Verificar si se enviaron los datos por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpiar los datos recibidos
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = htmlspecialchars(trim($_POST['password']));
    $confirm_password = htmlspecialchars(trim($_POST['confirm-password']));

    // Validar que las contraseñas coincidan
    if ($password !== $confirm_password) {
        die("Las contraseñas no coinciden. <a href='signup.html'>Volver</a>");
    }

    // Cifrar la contraseña
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Preparar y ejecutar la consulta para insertar el usuario
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, contrasena) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password_hash);

    if ($stmt->execute()) {
        // Redirigir al index.html después del registro exitoso
        header("Location: index.html");
        exit();  // Asegúrate de que no se ejecute el resto del código después de la redirección
    } else {
        // Detectar error por correo duplicado (clave UNIQUE)
        if ($conn->errno == 1062) {
            echo "El correo ya está registrado. <a href='signup.html'>Volver</a>";
        } else {
            echo "Error al registrar el usuario: " . $stmt->error;
        }
    }

    $stmt->close();
} else {
    echo "Acceso no válido.";
}

$conn->close();
?>
