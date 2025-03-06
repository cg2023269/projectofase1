#!/bin/bash

# Script de instalación para Antiv FA en Ubuntu con Apache2 y MariaDB

# Actualizar el sistema
echo "Actualizando el sistema..."
sudo apt update && sudo apt upgrade -y

# Instalar Apache2, PHP y MariaDB
echo "Instalando Apache2, PHP y MariaDB..."
sudo apt install apache2 php libapache2-mod-php mariadb-server php-mysql -y

# Habilitar el módulo CGI de Apache2
echo "Habilitando el módulo CGI..."
sudo a2enmod cgi
sudo systemctl restart apache2

# Crear directorio principal del proyecto
echo "Creando directorio principal del proyecto..."
sudo mkdir -p /var/www/html/viruscheck
sudo chown -R www-data:www-data /var/www/html/viruscheck
sudo chmod -R 755 /var/www/html/viruscheck

# Crear subdirectorios necesarios
echo "Creando subdirectorios..."
sudo mkdir -p /var/www/html/viruscheck/uploads
sudo mkdir -p /var/www/html/viruscheck/clean
sudo mkdir -p /var/www/html/viruscheck/infected
sudo mkdir -p /var/www/html/viruscheck/cgi-bin
sudo mkdir -p /var/www/html/viruscheck/login

# Asignar permisos a los subdirectorios
echo "Asignando permisos..."
sudo chown -R www-data:www-data /var/www/html/viruscheck/uploads
sudo chown -R www-data:www-data /var/www/html/viruscheck/clean
sudo chown -R www-data:www-data /var/www/html/viruscheck/infected
sudo chown -R www-data:www-data /var/www/html/viruscheck/cgi-bin
sudo chown -R www-data:www-data /var/www/html/viruscheck/login
sudo chmod -R 755 /var/www/html/viruscheck/uploads
sudo chmod -R 755 /var/www/html/viruscheck/clean
sudo chmod -R 755 /var/www/html/viruscheck/infected
sudo chmod -R 755 /var/www/html/viruscheck/cgi-bin
sudo chmod -R 755 /var/www/html/viruscheck/login

# Copiar archivos del proyecto
echo "Copiando archivos del proyecto..."
sudo cp index.php /var/www/html/viruscheck/  # Cambiado de index.html a index.php
sudo cp login.html /var/www/html/viruscheck/login/
sudo cp login.php /var/www/html/viruscheck/login/
sudo cp register.php /var/www/html/viruscheck/login/
sudo cp signup.html /var/www/html/viruscheck/login/
sudo cp welcome.php /var/www/html/viruscheck/login/
sudo cp check_file.py /var/www/html/viruscheck/cgi-bin/

# Copiar los nuevos archivos PHP
echo "Copiando nuevos archivos PHP..."
sudo cp admin.php /var/www/html/viruscheck/
sudo cp archivos_compartidos.php /var/www/html/viruscheck/
sudo cp delete.php /var/www/html/viruscheck/
sudo cp download.php /var/www/html/viruscheck/
sudo cp historial.php /var/www/html/viruscheck/
sudo cp logout.php /var/www/html/viruscheck/
sudo cp registro.php /var/www/html/viruscheck/
sudo cp save_record.php /var/www/html/viruscheck/
sudo cp share.php /var/www/html/viruscheck/

# Asignar permisos al script CGI
sudo chmod +x /var/www/html/viruscheck/cgi-bin/check_file.py

# Instalar pipx
echo "Instalando pipx..."
sudo apt install pipx -y
pipx ensurepath

# Instalar requests con pipx
echo "Instalando requests con pipx..."
pipx install requests

# Configurar Apache2 para permitir la ejecución de scripts CGI
echo "Configurando Apache2 para CGI..."
sudo bash -c 'cat > /etc/apache2/conf-available/viruscheck.conf <<EOF
ScriptAlias /cgi-bin/ /var/www/html/viruscheck/cgi-bin/
<Directory "/var/www/html/viruscheck/cgi-bin">
    AllowOverride None
    Options +ExecCGI -MultiViews +SymLinksIfOwnerMatch
    Require all granted
</Directory>
EOF'

# Crear un VirtualHost para la aplicación
echo "Configurando VirtualHost para la aplicación..."
sudo bash -c 'cat > /etc/apache2/sites-available/viruscheck.conf <<EOF
<VirtualHost *:80>
    # Directorio raíz de la aplicación
    DocumentRoot /var/www/html/viruscheck

    # Nombre del dominio o IP (puedes usar la IP del servidor)
    ServerName localhost

    # Configuración del directorio principal
    <Directory /var/www/html/viruscheck>
        Options Indexes FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>

    # Configuración para CGI
    ScriptAlias /cgi-bin/ /var/www/html/viruscheck/cgi-bin/
    <Directory "/var/www/html/viruscheck/cgi-bin">
        AllowOverride None
        Options +ExecCGI -MultiViews +SymLinksIfOwnerMatch
        Require all granted
    </Directory>

    # Archivo de índice por defecto
    DirectoryIndex index.php  # Cambiado de index.html a index.php
</VirtualHost>
EOF'

# Habilitar la configuración de VirusCheck
sudo a2enconf viruscheck
sudo a2ensite viruscheck.conf

# Deshabilitar el sitio por defecto de Apache
echo "Deshabilitando el sitio por defecto de Apache..."
sudo a2dissite 000-default.conf

# Reiniciar Apache
echo "Reiniciando Apache..."
sudo systemctl restart apache2

# Configurar MariaDB
echo "Configurando MariaDB..."

# Crear la base de datos y las tablas
sudo mysql -u root <<EOF
CREATE DATABASE usuarios;
USE usuarios;

-- Tabla de usuarios

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `departamento` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci



-- Tabla de archivos
CREATE TABLE `archivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `hash` varchar(64) NOT NULL,
  `status` varchar(20) NOT NULL,
  `location` varchar(255) NOT NULL,
  `upload_time` timestamp NULL DEFAULT current_timestamp(),
  `almacenado` enum('Sí','No') DEFAULT 'Sí',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci


-- Tabla de archivos compartidos
CREATE TABLE `archivos_compartidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `archivo_id` int(11) NOT NULL,
  `usuario_destinatario` varchar(255) DEFAULT NULL,
  `departamento_destinatario` varchar(50) DEFAULT NULL,
  `compartido_por` varchar(255) NOT NULL,
  `fecha_compartido` timestamp NULL DEFAULT current_timestamp(),
  `estado` enum('Pendiente','Completado') NOT NULL DEFAULT 'Pendiente',
  PRIMARY KEY (`id`),
  KEY `archivo_id` (`archivo_id`),
  KEY `usuario_destinatario` (`usuario_destinatario`),
  KEY `departamento_destinatario` (`departamento_destinatario`),
  CONSTRAINT `archivos_compartidos_ibfk_1` FOREIGN KEY (`archivo_id`) REFERENCES `archivos` (`id`),
  CONSTRAINT `archivos_compartidos_ibfk_2` FOREIGN KEY (`usuario_destinatario`) REFERENCES `usuarios` (`nombre`),
  CONSTRAINT `archivos_compartidos_ibfk_3` FOREIGN KEY (`departamento_destinatario`) REFERENCES `departamentos` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de departamentos
CREATE TABLE `departamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

-- Crear usuario de base de datos y asignar permisos
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'FranPerez';
GRANT ALL PRIVILEGES ON usuarios.* TO 'admin'@'localhost';
FLUSH PRIVILEGES;
EOF

# Asegurar la instalación de MariaDB
echo "Asegurando la instalación de MariaDB..."
sudo mysql_secure_installation

# Crear el usuario administrador usando PHP
echo "Creando el usuario administrador..."
php <<EOF
<?php
// Datos de conexión a la base de datos
\$host = 'localhost';
\$user = 'admin';
\$password = 'FranPerez';
\$dbname = 'usuarios';

// Crear la conexión
\$conn = new mysqli(\$host, \$user, \$password, \$dbname);
if (\$conn->connect_error) {
    die("Error de conexión: " . \$conn->connect_error);
}
\$conn->set_charset("utf8mb4");

// Verificar si ya existe un usuario con el rol (departamento) administrador
\$sql = "SELECT id FROM usuarios WHERE departamento = 'administrador' LIMIT 1";
\$result = \$conn->query(\$sql);

if (\$result && \$result->num_rows > 0) {
    echo "Ya existe un usuario administrador.\n";
    exit;
}

// Datos para el usuario administrador
\$nombre = 'Administrador';
\$correo = 'admin@example.com';
\$contraseña_plana = 'admin123'; // Contraseña en texto plano
\$contraseña_hash = password_hash(\$contraseña_plana, PASSWORD_DEFAULT);
\$departamento = 'administrador';

// Preparar e insertar el nuevo usuario
\$stmt = \$conn->prepare("INSERT INTO usuarios (nombre, correo, contraseña, departamento) VALUES (?, ?, ?, ?)");
if (!\$stmt) {
    die("Error en la preparación de la consulta: " . \$conn->error);
}
\$stmt->bind_param("ssss", \$nombre, \$correo, \$contraseña_hash, \$departamento);

if (\$stmt->execute()) {
    echo "Usuario administrador creado exitosamente.\n";
    echo "Correo: " . \$correo . "\n";
    echo "Contraseña: " . \$contraseña_plana . "\n";
} else {
    echo "Error al crear el usuario administrador: " . \$stmt->error;
}

\$stmt->close();
\$conn->close();
?>
EOF

echo "¡Instalación completada!"
echo "Accede a la aplicación en: http://localhost/viruscheck/"
