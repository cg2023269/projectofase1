# Proyecto Antiv FA

Este proyecto es una aplicación web para analizar archivos en busca de virus utilizando la API de VirusTotal. A continuación, se detallan los pasos para instalar y ejecutar la aplicación en un servidor Ubuntu con Apache2.

## Requisitos

- **Sistema operativo**: Ubuntu 20.04 o superior.
- **Servidor web**: Apache2.
- **Acceso a Internet**: Para usar la API de VirusTotal.

## Pasos de instalación

### 1. Clonar el repositorio

Primero, clona el repositorio en tu servidor:

´´´bash
git clone https://github.com/cg2023259/projectofasel.git
cd projectofasel
2. Cambiar a la rama fase0_final
El proyecto está en la rama fase0_final. Cambia a esta rama con el siguiente comando:

bash
Copy
git checkout fase0_final
3. Ejecutar el script de instalación
El proyecto incluye un script de instalación (install.sh) que automatiza la configuración del servidor. Ejecuta el siguiente comando para iniciar la instalación:

bash
Copy
chmod +x install.sh  # Dar permisos de ejecución al script
sudo ./install.sh    # Ejecutar el script de instalación
¿Qué hace el script de instalación?
Actualiza el sistema.

Instala Apache2 y PHP.

Habilita el módulo CGI de Apache2.

Crea la estructura de directorios en /var/www/html/viruscheck.

Copia los archivos del proyecto a la ubicación correcta.

Configura Apache para servir la aplicación desde /var/www/html/viruscheck.

Instala las dependencias de Python (como requests) usando pipx.

Deshabilita el sitio por defecto de Apache y habilita el nuevo sitio (viruscheck.conf).

4. Acceder a la aplicación
Una vez que la instalación haya finalizado, puedes acceder a la aplicación desde un navegador web:

Localmente: Abre tu navegador y visita:

Copy
http://localhost/viruscheck/
Desde otro dispositivo: Usa la IP del servidor:

Copy
http://<tu-ip>/viruscheck/
Estructura del proyecto
El proyecto está organizado de la siguiente manera:

Copy
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
Uso de la aplicación
Subir un archivo:

En la página principal, selecciona un archivo y haz clic en "Subir y analizar".

El archivo se analizará utilizando la API de VirusTotal.

Ver resultados:

Después del análisis, se mostrará el resultado (limpio o infectado) y el archivo se moverá a la carpeta correspondiente (clean/ o infected/).

Solución de problemas
1. Si aparece la página por defecto de Apache
Asegúrate de que el sitio por defecto esté deshabilitado:

bash
Copy
sudo a2dissite 000-default.conf
sudo systemctl restart apache2
2. Si ves un error 403 (Prohibido)
Verifica los permisos de los archivos y directorios:

bash
Copy
sudo chown -R www-data:www-data /var/www/html/viruscheck
sudo chmod -R 755 /var/www/html/viruscheck
3. Si ves un error 404 (No encontrado)
Asegúrate de que el archivo index.html esté en la carpeta correcta (/var/www/html/viruscheck/).
