<?php
// Inicia sesión para obtener el nombre de usuario
session_start();

// Verifica si hay un usuario logueado
if (!isset($_SESSION['username'])) {
    // Si no hay un usuario logueado, redirige al login
    header("Location: login.html");
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
        /* Estilos para la página */
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

