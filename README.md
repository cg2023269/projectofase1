Aquí tienes una versión mejorada y actualizada de tu archivo `README.md`, incluyendo la información adicional sobre la instalación con el archivo ZIP y los detalles completos de lo que hace el script `install.sh`. He reorganizado y mejorado la redacción para que sea más clara y profesional.

---

# 🛡️ Proyecto Antiv FA 🛡️

Este proyecto es una aplicación web para analizar archivos en busca de virus utilizando la API de VirusTotal. A continuación, se detallan los pasos para instalar y ejecutar la aplicación en un servidor Ubuntu con Apache2.

---

## 📋 Requisitos
- **Sistema operativo**: Ubuntu 20.04 o superior.
- **Servidor web**: Apache2.
- **Base de datos**: MariaDB.
- **Acceso a Internet**: Para usar la API de VirusTotal.

---

## 🚀 Pasos de instalación

### 1. Clonar el repositorio o usar el archivo ZIP
Puedes obtener el código del proyecto de dos maneras:

#### Opción 1: Clonar el repositorio
Abre una terminal y ejecuta los siguientes comandos:
```bash
git clone https://github.com/cg2023269/projectofase1.git
cd projectofase1/
```

#### Opción 2: Usar el archivo ZIP
Si has recibido el proyecto en un archivo ZIP, descomprímelo en tu servidor:
```bash
unzip projectofase1.zip -d projectofase1
cd projectofase1/
```

### 2. Cambiar a la rama `fase0_final`
El proyecto está en la rama `fase0_final`. Cambia a esta rama ejecutando el siguiente comando:
```bash
git checkout fase0_final
```

### 3. Ejecutar el script de instalación
El proyecto incluye un script de instalación (`install.sh`) que automatiza la configuración del servidor. Para ejecutarlo, sigue estos pasos:

1. Dale permisos de ejecución al script:
   ```bash
   chmod +x install.sh
   ```

2. Ejecuta el script:
   ```bash
   sudo ./install.sh
   ```

#### ¿Qué hace el script de instalación?
El script realiza las siguientes tareas:
1. **Actualiza el sistema**:
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

2. **Instala dependencias**:
   - Apache2, PHP y MariaDB.
   - Módulos necesarios para Apache2 (CGI, PHP MySQL).
   ```bash
   sudo apt install apache2 php libapache2-mod-php mariadb-server php-mysql -y
   ```

3. **Habilita el módulo CGI de Apache2**:
   ```bash
   sudo a2enmod cgi
   sudo systemctl restart apache2
   ```

4. **Crea la estructura de directorios**:
   - Crea los directorios necesarios en `/var/www/html/viruscheck`.
   - Asigna permisos adecuados a los directorios.
   ```bash
   sudo mkdir -p /var/www/html/viruscheck/{uploads,clean,infected,cgi-bin,login}
   sudo chown -R www-data:www-data /var/www/html/viruscheck
   sudo chmod -R 755 /var/www/html/viruscheck
   ```

5. **Copia los archivos del proyecto**:
   - Copia los archivos HTML, PHP y el script Python a sus ubicaciones correspondientes.
   ```bash
   sudo cp index.html /var/www/html/viruscheck/
   sudo cp login.html /var/www/html/viruscheck/login/
   sudo cp login.php /var/www/html/viruscheck/login/
   sudo cp register.php /var/www/html/viruscheck/login/
   sudo cp signup.html /var/www/html/viruscheck/login/
   sudo cp welcome.php /var/www/html/viruscheck/login/
   sudo cp check_file.py /var/www/html/viruscheck/cgi-bin/
   ```

6. **Instala `pipx` y `requests`**:
   - Instala `pipx` para gestionar dependencias de Python.
   - Instala la librería `requests` para interactuar con la API de VirusTotal.
   ```bash
   sudo apt install pipx -y
   pipx ensurepath
   pipx install requests
   ```

7. **Configura Apache2**:
   - Crea un archivo de configuración para la aplicación (`viruscheck.conf`).
   - Habilita el nuevo sitio y deshabilita el sitio por defecto de Apache.
   ```bash
   sudo a2enconf viruscheck
   sudo a2ensite viruscheck.conf
   sudo a2dissite 000-default.conf
   sudo systemctl restart apache2
   ```

8. **Configura MariaDB**:
   - Crea una base de datos llamada `usuarios`.
   - Crea una tabla para almacenar la información de los usuarios.
   - Configura un usuario con permisos para acceder a la base de datos.
   ```bash
   sudo mysql -u root <<EOF
   CREATE DATABASE usuarios;
   USE usuarios;
   CREATE TABLE usuarios (
       id INT AUTO_INCREMENT PRIMARY KEY,
       nombre VARCHAR(255) NOT NULL,
       correo VARCHAR(255) NOT NULL UNIQUE,
       contraseña VARCHAR(255) NOT NULL
   );
   CREATE USER 'admin'@'localhost' IDENTIFIED BY 'FranPerez';
   GRANT ALL PRIVILEGES ON usuarios.* TO 'admin'@'localhost';
   FLUSH PRIVILEGES;
   EOF
   ```

9. **Asegura la instalación de MariaDB**:
   - Ejecuta el asistente de seguridad de MariaDB.
   ```bash
   sudo mysql_secure_installation
   ```

---

## 📂 Estructura del proyecto
El proyecto está organizado de la siguiente manera:
```
/var/www/html/viruscheck/
├── cgi-bin/
│   └── check_file.py
├── clean/
├── index.html
├── infected/
├── login/
│   ├── login.html
│   ├── login.php
│   ├── register.php
│   ├── signup.html
│   └── welcome.php
├── uploads/
```

---

## 🖥️ Uso de la aplicación

### Subir un archivo
1. En la página principal, selecciona un archivo y haz clic en "Subir y analizar".
2. El archivo se analizará utilizando la API de VirusTotal.

### Ver resultados
- Después del análisis, se mostrará el resultado (limpio o infectado).
- El archivo se moverá a la carpeta correspondiente (`clean/` o `infected/`).

---

## 🛠️ Solución de problemas

### 1. Si aparece la página por defecto de Apache
Asegúrate de que el sitio por defecto esté deshabilitado:
```bash
sudo a2dissite 000-default.conf
sudo systemctl restart apache2
```

### 2. Si ves un error 403 (Prohibido)
Verifica los permisos de los archivos y directorios:
```bash
sudo chown -R www-data:www-data /var/www/html/viruscheck
sudo chmod -R 755 /var/www/html/viruscheck
```

### 3. Si ves un error 404 (No encontrado)
Asegúrate de que el archivo `index.html` esté en la carpeta correcta (`/var/www/html/viruscheck/`).

---

## 🎉 ¡Listo!
Siguiendo esta guía, deberías poder acceder y usar todas las funciones disponibles de la aplicación. 🚀

---

Espero que esta versión sea de tu agrado. Si necesitas más ajustes o detalles adicionales, ¡no dudes en decírmelo! 😊
