<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$usuario = $_SESSION['username'];
$file_id = $_POST['file_id'];
$share_type = $_POST['share_type'];

// Conexión a la base de datos
$conn = new mysqli('localhost', 'admin', 'FranPerez', 'usuarios');
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

if ($share_type === 'usuario') {
    $usuario_destinatario = $_POST['usuario_destinatario'];

    // Verificar que el usuario destinatario existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE nombre = ?");
    $stmt->bind_param("s", $usuario_destinatario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Insertar en la tabla de archivos compartidos
        $stmt = $conn->prepare("INSERT INTO archivos_compartidos (archivo_id, usuario_destinatario, compartido_por)
                                VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $file_id, $usuario_destinatario, $usuario);
        $stmt->execute();
        echo "Archivo compartido con $usuario_destinatario.";
    } else {
        echo "El usuario destinatario no existe.";
    }
} elseif ($share_type === 'departamento') {
    $departamento_destinatario = $_POST['departamento_destinatario'];

    // Verificar que el departamento existe
    $stmt = $conn->prepare("SELECT id FROM departamentos WHERE nombre = ?");
    $stmt->bind_param("s", $departamento_destinatario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Obtener los usuarios del departamento
        $stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE departamento = ?");
        $stmt->bind_param("s", $departamento_destinatario);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Insertar en la tabla de archivos compartidos para cada usuario del departamento
            $usuario_destinatario = $row['nombre'];
            $stmt = $conn->prepare("INSERT INTO archivos_compartidos (archivo_id, usuario_destinatario, compartido_por)
                                    VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $file_id, $usuario_destinatario, $usuario);
            $stmt->execute();
        }

        echo "Archivo compartido con todos los usuarios de $departamento_destinatario.";
    } else {
        echo "El departamento no existe.";
    }
}

$conn->close();
?>
