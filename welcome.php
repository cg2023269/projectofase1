<?php
// Inicia sesión para obtener el nombre de usuario
session_start();

// Verifica si hay un usuario logueado
if (!isset($_SESSION['username'])) {
    // Si no hay un usuario logueado, redirige al login
    header("Location: login/login.php");
    exit();
}

$username = $_SESSION['username']; // Obtén el nombre de usuario de la sesión
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            flex-direction: column;
            background-color: #f5f5f5;
        }

        .welcome-container {
            text-align: center;
        }

        .progress-bar-container {
            width: 100%;
            height: 20px;
            background-color: #ddd;
            border-radius: 10px;
            margin-top: 20px;
        }

        .progress-bar {
            height: 100%;
            width: 0;
            background-color: #4caf50;
            border-radius: 10px;
        }

        h1 {
            color: #333;
        }

        p {
            color: #666;
        }
    </style>
    <script>
        // Función para redirigir a index.html después de 5 segundos
        setTimeout(function() {
            window.location.href = "../index.html";
        }, 3000); // 3000 ms = 3 segundos

        // Función para simular la barra de carga
        let width = 0;
        function move() {
            if (width >= 100) {
                clearInterval(id);
            } else {
                width++;
                document.getElementById("progress-bar").style.width = width + "%";
            }
        }
        let id = setInterval(move, 30); // 30ms para llenar la barra lentamente
    </script>
</head>
<body>
    <div class="welcome-container">
        <h1>¡Bienvenido, <?php echo htmlspecialchars($username); ?>!</h1>
        <p>Serás redirigido al inicio en breve...</p>

        <!-- Barra de carga -->
        <div class="progress-bar-container">
            <div id="progress-bar" class="progress-bar"></div>
        </div>
    </div>
</body>
</html>



