<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$usuario = $_SESSION['username'];

// Conexión a la base de datos
$conn = new mysqli('localhost', 'admin', 'FranPerez', 'usuarios');
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Verifica que la columna 'usuario' existe en la base de datos
$checkColumn = $conn->query("SHOW COLUMNS FROM archivos LIKE 'usuario'");
if ($checkColumn->num_rows === 0) {
    die("Error: La columna 'usuario' no existe en la tabla 'archivos'.");
}

// Obtener parámetros de filtrado y búsqueda
$filtro_estado = $_GET['filtro_estado'] ?? '';
$filtro_almacenado = $_GET['filtro_almacenado'] ?? '';
$buscar = $_GET['buscar'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Construcción de la consulta SQL según el usuario y filtros
$where_conditions = [];
$params = [];
$types = "";

if ($usuario !== "Administrador") {
    $where_conditions[] = "usuario = ?";
    $params[] = $usuario;
    $types .= "s";
}

if (!empty($filtro_estado)) {
    $where_conditions[] = "status = ?";
    $params[] = $filtro_estado;
    $types .= "s";
}

if (!empty($filtro_almacenado)) {
    $where_conditions[] = "almacenado = ?";
    $params[] = $filtro_almacenado;
    $types .= "s";
}

if (!empty($buscar)) {
    $where_conditions[] = "(file_name LIKE ? OR hash LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $types .= "ss";
}

if (!empty($fecha_desde)) {
    $where_conditions[] = "DATE(upload_time) >= ?";
    $params[] = $fecha_desde;
    $types .= "s";
}

if (!empty($fecha_hasta)) {
    $where_conditions[] = "DATE(upload_time) <= ?";
    $params[] = $fecha_hasta;
    $types .= "s";
}

$query = "SELECT usuario, file_name, hash, status, location, upload_time, almacenado FROM archivos";
if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}
$query .= " ORDER BY upload_time DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Obtener estadísticas
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'clean' THEN 1 ELSE 0 END) as limpios,
    SUM(CASE WHEN status = 'infected' THEN 1 ELSE 0 END) as infectados,
    SUM(CASE WHEN almacenado = 'Sí' THEN 1 ELSE 0 END) as almacenados,
    SUM(CASE WHEN almacenado = 'No' THEN 1 ELSE 0 END) as eliminados
    FROM archivos";

if ($usuario !== "Administrador") {
    $stats_query .= " WHERE usuario = ?";
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->bind_param("s", $usuario);
} else {
    $stats_stmt = $conn->prepare($stats_query);
}

$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Obtener lista de usuarios únicos para filtros (solo para administrador)
$usuarios_list = [];
if ($usuario === "Administrador") {
    $usuarios_result = $conn->query("SELECT DISTINCT usuario FROM archivos ORDER BY usuario");
    while ($row = $usuarios_result->fetch_assoc()) {
        $usuarios_list[] = $row['usuario'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Archivos - Antiv FA</title>
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
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filters-container {
            background: white;
            padding: 20px;
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
            padding: 10px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 0.9em;
            background: white;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1abc9c;
            box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.1);
        }
        
        .button {
            padding: 10px 20px;
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
        }
        
        th {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.3s ease;
        }
        
        tr:nth-child(even) {
            background-color: #fafbfc;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-clean {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-infected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .almacenado {
            background-color: #d4edda;
            color: #155724;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .eliminado {
            background-color: #f8d7da;
            color: #721c24;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .hash-cell {
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.8em;
            max-width: 200px;
            word-break: break-all;
            color: #7f8c8d;
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
        
        .export-container {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-export {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            font-size: 0.85em;
            padding: 8px 16px;
        }
        
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .filters-form {
                grid-template-columns: 1fr;
            }
            
            th, td {
                padding: 10px 8px;
                font-size: 0.9em;
            }
            
            .hash-cell {
                max-width: 120px;
                font-size: 0.7em;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>📊 Historial de Archivos</h1>
        <div class="user-info">👤 Usuario: <?php echo htmlspecialchars($usuario); ?></div>
 <!-- Contenedor del botón de regreso -->
    <div class="back-container">
        <a href="index.php" class="button btn-secondary">🏠 Volver a la Página Principal</a>
    </div>    

</header>
    
    <div class="container">
        <!-- Estadísticas -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">📄 Total Archivos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['limpios']; ?></div>
                <div class="stat-label">🟢 Archivos Limpios</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['infectados']; ?></div>
                <div class="stat-label">🔴 Archivos Infectados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['almacenados']; ?></div>
                <div class="stat-label">💾 Almacenados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['eliminados']; ?></div>
                <div class="stat-label">🗑️ Eliminados</div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters-container">
            <div class="filters-title">
                🔍 Filtros y Búsqueda
            </div>
            <form method="GET" class="filters-form">
                <div class="form-group">
                    <label for="buscar">🔎 Buscar archivo o hash:</label>
                    <input type="text" id="buscar" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>" placeholder="Nombre de archivo o hash...">
                </div>
                
                <div class="form-group">
                    <label for="filtro_estado">🛡️ Estado:</label>
                    <select id="filtro_estado" name="filtro_estado">
                        <option value="">Todos los estados</option>
                        <option value="clean" <?php echo ($filtro_estado === 'clean') ? 'selected' : ''; ?>>Limpio</option>
                        <option value="infected" <?php echo ($filtro_estado === 'infected') ? 'selected' : ''; ?>>Infectado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="filtro_almacenado">💾 Almacenamiento:</label>
                    <select id="filtro_almacenado" name="filtro_almacenado">
                        <option value="">Todos</option>
                        <option value="Sí" <?php echo ($filtro_almacenado === 'Sí') ? 'selected' : ''; ?>>Almacenado</option>
                        <option value="No" <?php echo ($filtro_almacenado === 'No') ? 'selected' : ''; ?>>Eliminado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="fecha_desde">📅 Desde:</label>
                    <input type="date" id="fecha_desde" name="fecha_desde" value="<?php echo htmlspecialchars($fecha_desde); ?>">
                </div>
                
                <div class="form-group">
                    <label for="fecha_hasta">📅 Hasta:</label>
                    <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="button btn-primary">🔍 Filtrar</button>
                </div>
                
                <div class="form-group">
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="button btn-secondary">🗑️ Limpiar</a>
                </div>
            </form>
            
            <div class="export-container">
                <button onclick="exportToCSV()" class="button btn-export">📊 Exportar CSV</button>
            </div>
        </div>
        
        <!-- Tabla de resultados -->
        <div class="section">
            <div class="section-header">
                📋 Registro de Archivos
                <?php if ($result->num_rows > 0): ?>
                    <span style="float: right; font-size: 0.9em; opacity: 0.8;">
                        Mostrando <?php echo $result->num_rows; ?> resultado(s)
                    </span>
                <?php endif; ?>
            </div>
            <div class="table-container">
                <?php if ($result->num_rows > 0): ?>
                    <table id="historialTable">
                        <thead>
                            <tr>
                                <th>👤 Usuario</th>
                                <th>📄 Nombre del Archivo</th>
                                <th>🔐 Hash (SHA256)</th>
                                <th>🛡️ Estado</th>
                                <th>📍 Ubicación</th>
                                <th>📅 Fecha de Subida</th>
                                <th>💾 Estado de Almacenamiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['usuario'] ?? 'Desconocido'); ?></td>
                                <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                                <td class="hash-cell"><?php echo htmlspecialchars($row['hash']); ?></td>
                                <td>
                                    <?php 
                                    $statusClass = $row['status'] === 'infected' ? 'status-infected' : 'status-clean';
                                    $statusText = $row['status'] === 'infected' ? '🔴 Infectado' : '🟢 Limpio';
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['upload_time'])); ?></td>
                                <td>
                                    <span class="<?php echo ($row['almacenado'] === 'Sí') ? 'almacenado' : 'eliminado'; ?>">
                                        <?php echo ($row['almacenado'] === 'Sí') ? '💾 Almacenado' : '🗑️ Eliminado'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data-message">
                        📭 No se encontraron archivos con los criterios especificados.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="back-container">
        <a href="index.php" class="button btn-secondary">🏠 Volver a la página principal</a>
    </div>

    <script>
        function exportToCSV() {
            const table = document.getElementById('historialTable');
            if (!table) {
                alert('No hay datos para exportar');
                return;
            }
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let cellText = cols[j].innerText.replace(/"/g, '""');
                    // Limpiar emojis y caracteres especiales para CSV
                    cellText = cellText.replace(/[📄👤🔐🛡️📍📅💾🟢🔴🗑️]/g, '').trim();
                    row.push('"' + cellText + '"');
                }
                
                csv.push(row.join(','));
            }
            
            const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
            const downloadLink = document.createElement('a');
            downloadLink.download = 'historial_archivos_' + new Date().toISOString().slice(0, 10) + '.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
        
        // Auto-submit form on date change for better UX
        document.getElementById('fecha_desde').addEventListener('change', function() {
            if (this.value && document.getElementById('fecha_hasta').value) {
                this.form.submit();
            }
        });
        
        document.getElementById('fecha_hasta').addEventListener('change', function() {
            if (this.value && document.getElementById('fecha_desde').value) {
                this.form.submit();
            }
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
