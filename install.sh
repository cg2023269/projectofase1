#!/bin/bash

# Script de instalación para Antiv FA en Ubuntu con Apache2

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
sudo cp index.html /var/www/html/viruscheck/
sudo cp login.html /var/www/html/viruscheck/login/
sudo cp login.php /var/www/html/viruscheck/login/
sudo cp register.php /var/www/html/viruscheck/login/
sudo cp signup.html /var/www/html/viruscheck/login/
sudo cp welcome.php /var/www/html/viruscheck/login/
sudo cp check_file.py /var/www/html/viruscheck/cgi-bin/

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
    DirectoryIndex index.html
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
sudo mysql -e "CREATE DATABASE usuarios;"
sudo mysql -e "CREATE USER 'root'@'localhost' IDENTIFIED BY 'FranPerez';"
sudo mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;"
sudo mysql -e "FLUSH PRIVILEGES;"

# Crear la tabla usuarios
echo "Creando la tabla usuarios..."
sudo mysql -e "USE usuarios; CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    correo VARCHAR(255) NOT NULL,
    contraseña VARCHAR(255) NOT NULL
);"

echo "¡Instalación completada!"
echo "Accede a la aplicación en: http://localhost/viruscheck/"
