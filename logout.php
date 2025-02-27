<?php
session_start();
session_unset();
session_destroy();

// Redirige al usuario a la pantalla de autenticación (index.php)
header("Location: index.php");
exit();
?>
