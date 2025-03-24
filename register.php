<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $contraseña = password_hash($_POST['contraseña'], PASSWORD_DEFAULT);

    $conn = new mysqli('localhost', 'admin', 'FranPerez', 'usuarios');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // Insertar usuario con validado=FALSE por defecto
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, contraseña, validado) VALUES (?, ?, ?, FALSE)");
    $stmt->bind_param("sss", $nombre, $correo, $contraseña);

    if ($stmt->execute()) {
        $mensaje = "¡Registro exitoso! Un administrador revisará tu solicitud y se te validará";
    } else {
        $mensaje = "Error: " . $stmt->error;
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
    <title>Registro</title>
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
        .register-container {
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

        /* Botón de registro */
        .register-btn {
            background-color: #34495e;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .register-btn:hover {
            background-color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Registro</h2>
        <?php if (isset($mensaje)): ?>
            <div style="margin-bottom: 20px; padding: 10px; background: #d4edda; color: #155724; border-radius: 4px;">
                <?= $mensaje ?>
            </div>
            <a href="../index.php" style="display: inline-block; margin-top: 10px; color: #3498db;">Volver al inicio</a>
        <?php else: ?>
            <form method="post" action="">
                <div class="input-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Ingrese su nombre" required>
                </div>
                <div class="input-group">
                    <label for="correo">Correo</label>
                    <input type="email" id="correo" name="correo" placeholder="Ingrese su correo" required>
                </div>
                <div class="input-group">
                    <label for="contraseña">Contraseña</label>
                    <input type="password" id="contraseña" name="contraseña" placeholder="Ingrese su contraseña" required>
                </div>
                <button type="submit" class="register-btn">Registrarse</button>
            </form>
            <p style="margin-top: 15px; font-size: 14px; color: #7f8c8d;">
                Después de registrarte, un administrador revisará tu solicitud y te validará.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
