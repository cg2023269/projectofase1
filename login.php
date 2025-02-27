<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $contraseña = $_POST['contraseña'];

    $conn = new mysqli('localhost', 'admin', 'FranPerez', 'usuarios');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // Actualizamos la consulta para recuperar también el departamento
    $stmt = $conn->prepare("SELECT id, nombre, contraseña, departamento FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();
    // Se agrego una variable para el departamento
    $stmt->bind_result($id, $nombre, $hashed_password, $departamento);

    if ($stmt->fetch() && password_verify($contraseña, $hashed_password)) {
        // Guarda el nombre y el departamento del usuario en la sesión
        $_SESSION['username'] = $nombre;
        $_SESSION['role'] = $departamento; // Ej: "administrador" o "cliente"

        // Redirige a welcome.php (o a index.php si prefieres)
        header("Location: welcome.php");
        exit();
    } else {
        echo "Correo o contraseña incorrectos.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <style>
        /* Reset de algunos estilos predeterminados */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Fondo general */
        body {
            background-color: #ecf0f1;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Contenedor principal del formulario */
        .login-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        /* Estilo del título */
        h2 {
            color: #34495e;
            margin-bottom: 20px;
            font-size: 24px;
        }

        /* Estilos de los grupos de entrada */
        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            font-size: 14px;
            color: #34495e;
            display: block;
            margin-bottom: 5px;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            outline: none;
        }

        .input-group input:focus {
            border-color: #34495e;
        }

        /* Botón de inicio de sesión */
        .login-btn {
            background-color: #34495e;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .login-btn:hover {
            background-color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <form method="post" action="">
            <div class="input-group">
                <label for="correo">Correo</label>
                <input type="email" id="correo" name="correo" placeholder="Ingrese su correo" required>
            </div>
            <div class="input-group">
                <label for="contraseña">Contraseña</label>
                <input type="password" id="contraseña" name="contraseña" placeholder="Ingrese su contraseña" required>
            </div>
            <button type="submit" class="login-btn">Iniciar Sesión</button>
        </form>
    </div>
</body>
</html>
