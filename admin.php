<?php
session_start();


// Verificar que el usuario sea administrador
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'administrador') {
    header("Location: index.php?error=access_denied");
    exit;
}


// Conexión a la base de datos
$conn = new mysqli('localhost', 'admin', 'FranPerez', 'usuarios');
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");


// Variables para mensajes
$success_message = '';
$error_message = '';


// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificación CSRF básica
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Token de seguridad inválido.";
    } else {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_user':
                    $user_id = intval($_POST['user_id'] ?? 0);
                    $new_department = trim($_POST['department'] ?? '');
                    if ($user_id && $new_department) {
                        $stmt = $conn->prepare("UPDATE usuarios SET departamento = ? WHERE id = ?");
                        $stmt->bind_param("si", $new_department, $user_id);
                        if ($stmt->execute()) {
                            $success_message = "Departamento asignado correctamente.";
                        } else {
                            $error_message = "Error al asignar departamento.";
                        }
                        $stmt->close();
                    }
                    break;


                case 'add_department':
                    $dept_name = trim($_POST['dept_name'] ?? '');
                    if ($dept_name) {
                        // Verificar si el departamento ya existe
                        $check_stmt = $conn->prepare("SELECT id FROM departamentos WHERE nombre = ?");
                        $check_stmt->bind_param("s", $dept_name);
                        $check_stmt->execute();
                        $result = $check_stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $error_message = "El departamento ya existe.";
                        } else {
                            $stmt = $conn->prepare("INSERT INTO departamentos (nombre) VALUES (?)");
                            $stmt->bind_param("s", $dept_name);
                            if ($stmt->execute()) {
                                $success_message = "Departamento agregado correctamente.";
                            } else {
                                $error_message = "Error al agregar departamento.";
                            }
                            $stmt->close();
                        }
                        $check_stmt->close();
                    }
                    break;


                case 'delete_department':
                    $dept_id = intval($_POST['dept_id'] ?? 0);
                    if ($dept_id) {
                        // Verificar si hay usuarios asignados a este departamento
                        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuarios WHERE departamento = (SELECT nombre FROM departamentos WHERE id = ?)");
                        $check_stmt->bind_param("i", $dept_id);
                        $check_stmt->execute();
                        $result = $check_stmt->get_result();
                        $count = $result->fetch_assoc()['count'];
                        
                        if ($count > 0) {
                            $error_message = "No se puede eliminar el departamento. Hay $count usuario(s) asignado(s).";
                        } else {
                            $stmt = $conn->prepare("DELETE FROM departamentos WHERE id = ?");
                            $stmt->bind_param("i", $dept_id);
                            if ($stmt->execute()) {
                                $success_message = "Departamento eliminado correctamente.";
                            } else {
                                $error_message = "Error al eliminar departamento.";
                            }
                            $stmt->close();
                        }
                        $check_stmt->close();
                    }
                    break;
case 'delete_user':
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id === 0) {
        $error_message = "ID de usuario no válido.";
        break;
    }

    // Obtener el nombre del usuario para eliminar archivos relacionados
    $stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($nombre_usuario);
    $stmt->fetch();
    $stmt->close();

    if ($nombre_usuario) {
        // Eliminar archivos compartidos donde sea destinatario
        $stmt = $conn->prepare("DELETE FROM archivos_compartidos WHERE usuario_destinatario = ?");
        $stmt->bind_param("s", $nombre_usuario);
        $stmt->execute();
        $stmt->close();
    }

    // Ahora eliminar el usuario
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $success_message = "Usuario eliminado correctamente.";
    } else {
        $error_message = "Error al eliminar usuario: " . $stmt->error;
    }
    $stmt->close();
    break;




                    
                case 'validate_user':
                    $user_id = intval($_POST['user_id'] ?? 0);
                    if ($user_id) {
                        $stmt = $conn->prepare("UPDATE usuarios SET validado = TRUE WHERE id = ?");
                        $stmt->bind_param("i", $user_id);
                        if ($stmt->execute()) {
                            $success_message = "Usuario validado correctamente.";
                        } else {
                            $error_message = "Error al validar usuario.";
                        }
                        $stmt->close();
                    }
                    break;


                case 'toggle_user_status':
                    $user_id = intval($_POST['user_id'] ?? 0);
                    $new_status = $_POST['new_status'] === '1' ? 1 : 0;
                    if ($user_id) {
                        $stmt = $conn->prepare("UPDATE usuarios SET validado = ? WHERE id = ?");
                        $stmt->bind_param("ii", $new_status, $user_id);
                        if ($stmt->execute()) {
                            $status_text = $new_status ? "activado" : "desactivado";
                            $success_message = "Usuario $status_text correctamente.";
                        } else {
                            $error_message = "Error al cambiar estado del usuario.";
                        }
                        $stmt->close();
                    }
                    break;
            }
        }
    }
}


// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Obtener filtros
$filtro_estado = $_GET['filtro_estado'] ?? '';
$filtro_departamento = $_GET['filtro_departamento'] ?? '';
$buscar = $_GET['buscar'] ?? '';


// Construir consulta de usuarios con filtros
$where_conditions = [];
$params = [];
$types = "";


if (!empty($filtro_estado)) {
    if ($filtro_estado === 'validado') {
        $where_conditions[] = "validado = 1";
    } elseif ($filtro_estado === 'pendiente') {
        $where_conditions[] = "validado = 0";
    }
}


if (!empty($filtro_departamento)) {
    $where_conditions[] = "departamento = ?";
    $params[] = $filtro_departamento;
    $types .= "s";
}


if (!empty($buscar)) {
    $where_conditions[] = "(nombre LIKE ? OR correo LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $types .= "ss";
}


$user_query = "SELECT id, nombre, correo, departamento, validado FROM usuarios";
if (!empty($where_conditions)) {
    $user_query .= " WHERE " . implode(" AND ", $where_conditions);
}
$user_query .= " ORDER BY id ASC";


$user_stmt = $conn->prepare($user_query);
if (!empty($params)) {
    $user_stmt->bind_param($types, ...$params);
}
$user_stmt->execute();
$user_stmt->store_result();
$user_stmt->bind_result($id, $nombre, $correo, $departamento, $validado);

$userResult = [];
while ($user_stmt->fetch()) {
    $userResult[] = [
        'id' => $id,
        'nombre' => $nombre,
        'correo' => $correo,
        'departamento' => $departamento,
        'validado' => $validado,
    ];
}

// Consultar estadísticas
$stats_query = "SELECT 
    COUNT(*) as total_usuarios,
    SUM(CASE WHEN validado = 1 THEN 1 ELSE 0 END) as usuarios_validados,
    SUM(CASE WHEN validado = 0 THEN 1 ELSE 0 END) as usuarios_pendientes,
    COUNT(DISTINCT departamento) as total_departamentos
    FROM usuarios WHERE departamento IS NOT NULL AND departamento != ''";


$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();


// Consultar departamentos
$deptResult = $conn->query("SELECT id, nombre FROM departamentos ORDER BY nombre ASC");
$departments = $deptResult->fetch_all(MYSQLI_ASSOC);
$deptResult->free();


// Obtener lista de departamentos únicos para filtros
$dept_filter_result = $conn->query("SELECT DISTINCT departamento FROM usuarios WHERE departamento IS NOT NULL AND departamento != '' ORDER BY departamento");
$dept_filters = [];
while ($row = $dept_filter_result->fetch_assoc()) {
    $dept_filters[] = $row['departamento'];
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador - Antiv FA</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            text-align: center;
            padding: 20px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        header h1 {
            margin: 0;
            font-size: 2em;
            font-weight: 600;
        }
        
        .user-info {
            margin-top: 8px;
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .section-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 20px 25px;
            font-size: 1.3em;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(44, 62, 80, 0.2);
        }
        
        .filters-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        
        .filters-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 0.9em;
        }
        
        .form-group input,
        .form-group select {
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 0.9em;
            background: white;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1abc9c;
            box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.1);
        }
        
        .button {
            padding: 12px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9em;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(26, 188, 156, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 188, 156, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(149, 165, 166, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(243, 156, 18, 0.4);
        }
        
        .table-container {
            overflow-x: auto;
            padding: 0;
        }
        
        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
            margin: 0;
        }
        
        th, td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: middle;
        }
        
        th {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.3s ease;
        }
        
        tr:nth-child(even) {
            background-color: #fafbfc;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-validado {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .action-buttons .button {
            padding: 6px 12px;
            font-size: 0.8em;
            min-width: auto;
        }
        
        .quick-add-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .quick-add-form .form-row {
            display: flex;
            gap: 15px;
            align-items: end;
        }
        
        .no-data-message {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
            font-style: italic;
            font-size: 1.1em;
        }
        
        .back-container {
            display: flex;
            justify-content: flex-start;
            margin-top: 30px;
            padding: 0 20px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .filters-form {
                grid-template-columns: 1fr;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            th, td {
                padding: 10px 8px;
                font-size: 0.9em;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .quick-add-form .form-row {
                flex-direction: column;
                align-items: stretch;
            }
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
    </style>
</head>
<body>
    

<header>
    <h1>⚙️ Panel de Administrador</h1>
    <div class="user-info">👤 Administrador: <?php echo htmlspecialchars($_SESSION['username']); ?></div>
    
    <!-- Contenedor del botón de regreso -->
    <div class="back-container">
        <a href="index.php" class="button btn-secondary">🏠 Volver a la Página Principal</a>
    </div>
</header>




    
    <div class="container">
        <!-- Mensajes de estado -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Estadísticas -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_usuarios']; ?></div>
                <div class="stat-label">👥 Total Usuarios</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['usuarios_validados']; ?></div>
                <div class="stat-label">✅ Usuarios Validados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['usuarios_pendientes']; ?></div>
                <div class="stat-label">⏳ Usuarios Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($departments); ?></div>
                <div class="stat-label">🏢 Departamentos</div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters-container">
            <div class="filters-title">
                🔍 Filtros de Búsqueda
            </div>
            <form method="GET" class="filters-form">
                <div class="form-group">
                    <label for="buscar">🔎 Buscar usuario:</label>
                    <input type="text" id="buscar" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>" placeholder="Nombre o correo...">
                </div>
                
                <div class="form-group">
                    <label for="filtro_estado">📊 Estado:</label>
                    <select id="filtro_estado" name="filtro_estado">
                        <option value="">Todos los estados</option>
                        <option value="validado" <?php echo ($filtro_estado === 'validado') ? 'selected' : ''; ?>>Validados</option>
                        <option value="pendiente" <?php echo ($filtro_estado === 'pendiente') ? 'selected' : ''; ?>>Pendientes</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="filtro_departamento">🏢 Departamento:</label>
                    <select id="filtro_departamento" name="filtro_departamento">
                        <option value="">Todos los departamentos</option>
                        <?php foreach ($dept_filters as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($filtro_departamento === $dept) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="button btn-primary">🔍 Filtrar</button>
                </div>
                
                <div class="form-group">
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="button btn-secondary">🗑️ Limpiar</a>
                </div>
            </form>
        </div>
        
        <!-- Gestión de Usuarios -->
        <div class="section">
            <div class="section-header">
                👥 Gestión de Usuarios
                <?php if (count($userResult) > 0): ?>
                    <span style="float: right; font-size: 0.9em; opacity: 0.8;">
                        Mostrando <?php echo count($userResult); ?> usuario(s)
                    </span>
                <?php endif; ?>
            </div>
            <div class="table-container">
                <?php if (count($userResult) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>🆔 ID</th>
                                <th>👤 Nombre</th>
                                <th>📧 Correo</th>
                                <th>📊 Estado</th>
                                <th>🏢 Departamento</th>
                                <th>⚙️ Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userResult as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($user['correo']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $user['validado'] ? 'status-validado' : 'status-pendiente'; ?>">
                                        <?php echo $user['validado'] ? '✅ Validado' : '⏳ Pendiente'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="" style="display: inline-block;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="action" value="update_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="department" onchange="this.form.submit()" style="padding: 5px; border-radius: 4px;">
                                            <option value="">-- Seleccione --</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo htmlspecialchars($dept['nombre']); ?>" 
                                                        <?php echo ($user['departamento'] === $dept['nombre']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($dept['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if (!$user['validado']): ?>
                                            <form method="post" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action" value="validate_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="button btn-success">✅ Validar</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action" value="toggle_user_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="new_status" value="0">
                                                <button type="submit" class="button btn-warning" onclick="return confirm('¿Desactivar este usuario?')">⏸️ Desactivar</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="post" action="" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este usuario? Esta acción no se puede deshacer.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
<button type="submit" class="button btn-danger">🗑️ Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data-message">
                        📂 No se encontraron usuarios que coincidan con los filtros aplicados.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Gestión de Departamentos -->
        <div class="section">
            <div class="section-header">
                🏢 Gestión de Departamentos
            </div>
            
            <!-- Formulario para agregar departamento -->
            <div class="quick-add-form">
                <form method="post" action="" class="form-row">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="add_department">
                    
                    <div class="form-group" style="flex: 1;">
                        <label for="dept_name">🏢 Nuevo Departamento:</label>
                        <input type="text" name="dept_name" id="dept_name" required placeholder="Nombre del departamento">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="button btn-success">➕ Agregar Departamento</button>
                    </div>
                </form>
            </div>
            
            <!-- Lista de departamentos -->
            <div class="table-container">
                <?php if (!empty($departments)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>🆔 ID</th>
                                <th>🏢 Nombre</th>
                                <th>👥 Usuarios Asignados</th>
                                <th>⚙️ Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $dept): ?>
                                <?php
                                // Contar usuarios en este departamento
                                $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuarios WHERE departamento = ?");
                                $count_stmt->bind_param("s", $dept['nombre']);
                                $count_stmt->execute();
                                $count_result = $count_stmt->get_result();
                                $user_count = $count_result->fetch_assoc()['count'];
                                $count_stmt->close();
                                ?>
                                <tr>
                                    <td><?php echo $dept['id']; ?></td>
                                    <td><?php echo htmlspecialchars($dept['nombre']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $user_count > 0 ? 'status-validado' : 'status-pendiente'; ?>">
                                            <?php echo $user_count; ?> usuario(s)
                                        </span>
                                    </td>
                                    <td>
                                        <form method="post" action="" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este departamento? <?php echo $user_count > 0 ? 'Hay ' . $user_count . ' usuario(s) asignado(s).' : ''; ?>');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="action" value="delete_department">
                                            <input type="hidden" name="dept_id" value="<?php echo $dept['id']; ?>">
                                            <button type="submit" class="button btn-danger" <?php echo $user_count > 0 ? 'disabled title="No se puede eliminar: hay usuarios asignados"' : ''; ?>>
                                                🗑️ Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data-message">
                        🏢 No hay departamentos registrados. Agregue el primer departamento usando el formulario superior.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Acciones Rápidas -->
        <div class="section">
            <div class="section-header">
                ⚡ Acciones Rápidas
            </div>
            <div style="padding: 25px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    
                    <!-- Validar todos los usuarios pendientes -->
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #1abc9c;">
                        <h4 style="margin: 0 0 10px 0; color: #2c3e50;">✅ Validación Masiva</h4>
                        <p style="margin: 0 0 15px 0; color: #7f8c8d; font-size: 0.9em;">
                            Validar todos los usuarios pendientes de una vez.
                        </p>
                        <form method="post" action="" onsubmit="return confirm('¿Validar todos los usuarios pendientes?');">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="validate_all_users">
                            <button type="submit" class="button btn-success" <?php echo $stats['usuarios_pendientes'] == 0 ? 'disabled' : ''; ?>>
                                ✅ Validar Todos (<?php echo $stats['usuarios_pendientes']; ?>)
                            </button>
                        </form>
                    </div>
                    
                    <!-- Exportar datos -->
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #3498db;">
                        <h4 style="margin: 0 0 10px 0; color: #2c3e50;">📊 Exportar Datos</h4>
                        <p style="margin: 0 0 15px 0; color: #7f8c8d; font-size: 0.9em;">
                            Descargar lista de usuarios en formato CSV.
                        </p>
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="export_users">
                            <button type="submit" class="button btn-primary">
                                📥 Exportar CSV
                            </button>
                        </form>
                    </div>
                    
                    <!-- Limpiar usuarios no validados antiguos -->
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #e67e22;">
                        <h4 style="margin: 0 0 10px 0; color: #2c3e50;">🧹 Limpieza</h4>
                        <p style="margin: 0 0 15px 0; color: #7f8c8d; font-size: 0.9em;">
                            Eliminar usuarios no validados de más de 30 días.
                        </p>
                        <form method="post" action="" onsubmit="return confirm('¿Eliminar usuarios no validados de más de 30 días?');">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="cleanup_old_users">
                            <button type="submit" class="button btn-warning">
                                🧹 Limpiar Antiguos
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contenedor del botón de regreso -->
    <div class="back-container">
        <a href="index.php" class="button btn-secondary">🏠 Volver a la Página Principal</a>
    </div>
    
    <!-- Modal para confirmaciones -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3 id="modalTitle">Confirmar Acción</h3>
            <p id="modalMessage">¿Está seguro de realizar esta acción?</p>
            <div style="text-align: right; margin-top: 20px;">
                <button id="modalCancel" class="button btn-secondary" style="margin-right: 10px;">Cancelar</button>
                <button id="modalConfirm" class="button btn-danger">Confirmar</button>
            </div>
        </div>
    </div>
    
    <script>
        // Funcionalidad del modal
        const modal = document.getElementById('confirmModal');
        const closeBtn = document.getElementsByClassName('close')[0];
        const cancelBtn = document.getElementById('modalCancel');
        const confirmBtn = document.getElementById('modalConfirm');
        
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }
        
        cancelBtn.onclick = function() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease-out';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
        
        // Confirmar acciones críticas
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form[onsubmit*="confirm"]');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const confirmMessage = form.getAttribute('onsubmit').match(/confirm\('([^']+)'\)/);
                    if (confirmMessage) {
                        e.preventDefault();
                        document.getElementById('modalMessage').textContent = confirmMessage[1];
                        document.getElementById('modalTitle').textContent = 'Confirmar Acción';
                        modal.style.display = 'block';
                        
                        confirmBtn.onclick = function() {
                            modal.style.display = 'none';
                            form.removeAttribute('onsubmit');
                            form.submit();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>


<?php
// Limpiar resultados y cerrar conexión
//$userResult->free();
$conn->close();
?>


