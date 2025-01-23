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
    $password = htmlspecialchars(trim($_POST['password']));

    // Preparar y ejecutar la consulta para obtener los datos del usuario
    $stmt = $conn->prepare("SELECT contrasena FROM usuarios WHERE nombre = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($stored_password);

    // Verificar si se encontró el usuario
    if ($stmt->num_rows > 0) {
        $stmt->fetch();

        // Verificar si la contraseña ingresada es correcta
        if (password_verify($password, $stored_password)) {
            // La contraseña es correcta, iniciar sesión
            session_start();
            $_SESSION['username'] = $username;  // Guardar el nombre de usuario en la sesión
            header("Location: welcome.php");    // Redirigir a la página de bienvenida
            exit();
        } else {
            echo "Contraseña incorrecta. <a href='login.html'>Volver</a>";
        }
    } else {
        echo "Usuario no encontrado. <a href='login.html'>Volver</a>";
    }

    $stmt->close();
} else {
    echo "Acceso no válido.";
}

$conn->close();
?>



