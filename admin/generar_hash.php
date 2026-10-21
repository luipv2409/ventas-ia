<?php
// admin/generar_hash.php
// Ejecutar UNA SOLA VEZ y luego eliminar el archivo

echo "Hash para 'admin123': " . password_hash('admin123', PASSWORD_DEFAULT);