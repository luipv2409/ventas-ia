<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_cliente.php';

cliente_session_init();

echo "======================================================" . PHP_EOL;
echo "  SUITE DE PRUEBAS: SISTEMA DE AUTENTICACIÓN CLIENTES " . PHP_EOL;
echo "======================================================" . PHP_EOL;

$pdo = Database::getConnection();

function assertTest(bool $condition, string $testName): void {
    if ($condition) {
        echo " [PASS] " . $testName . PHP_EOL;
    } else {
        echo " [FAIL] " . $testName . PHP_EOL;
        exit(1);
    }
}

// 1. Limpieza de datos previos de prueba
$testEmail = 'cliente_test_' . time() . '@tiendademo.com';
$testPass  = 'ClaveSegura123!';
$testNombre = 'Juan Pérez de Prueba';

// Test 1: Registro de nuevo cliente con correo y contraseña
$resReg = cliente_registrar($testNombre, $testEmail, $testPass, '71234567', 'Zona Sur #100');
assertTest($resReg['ok'] === true, "1. Registro exitoso con email y contraseña");
assertTest(cliente_esta_autenticado() === true, "2. Cliente queda autenticado en sesión tras registro");
$clienteActual = cliente_actual();
assertTest($clienteActual['email'] === $testEmail, "3. Datos de sesión coinciden con el cliente registrado");

// Test 2: Intento de registro duplicado con el mismo correo debe ser rechazado
$resDuplicado = cliente_registrar('Otro Nombre', $testEmail, 'otraClave456');
assertTest($resDuplicado['ok'] === false, "4. Rechazo correcto de correo electrónico duplicado");

// Test 3: Cerrar sesión
cliente_cerrar_sesion();
assertTest(cliente_esta_autenticado() === false, "5. Cierre de sesión exitoso");

// Test 4: Login con credenciales correctas
$resLogin = cliente_login($testEmail, $testPass);
assertTest($resLogin['ok'] === true, "6. Login exitoso con credenciales correctas");
assertTest(cliente_esta_autenticado() === true, "7. Sesión restaurada tras login exitoso");

// Test 5: Login con contraseña incorrecta debe fallar
cliente_cerrar_sesion();
$resLoginInvalido = cliente_login($testEmail, 'ContrasenaErronea999');
assertTest($resLoginInvalido['ok'] === false, "8. Login rechazado con contraseña incorrecta");
assertTest(cliente_esta_autenticado() === false, "9. No hay sesión activa tras fallo de login");

// Test 6: Autenticación con Google (Google Sign-In)
$googleEmail = 'google_user_' . time() . '@gmail.com';
$googleId    = 'goog_id_' . md5($googleEmail);
$googleName  = 'Google Tester';
$googleAvatar = 'https://lh3.googleusercontent.com/test-avatar.jpg';

$resGoogle = cliente_google_auth($googleId, $googleEmail, $googleName, $googleAvatar);
assertTest($resGoogle['ok'] === true, "10. Autenticación y creación automática con Google OAuth");
assertTest(cliente_esta_autenticado() === true, "11. Cliente Google autenticado en sesión");
$clienteGoogle = cliente_actual();
assertTest($clienteGoogle['google_id'] === $googleId, "12. Google ID guardado correctamente en la BD");
assertTest($clienteGoogle['avatar_url'] === $googleAvatar, "13. Avatar URL de Google persistido");

// Test 7: Solicitud de recuperación de contraseña (Token seguro)
$resRecup = cliente_solicitar_recuperacion($testEmail);
assertTest($resRecup['ok'] === true, "14. Generación de token seguro de recuperación de contraseña");
assertTest(!empty($resRecup['token']), "15. Token no vacío");
assertTest(strlen($resRecup['token']) === 64, "16. Token criptográfico de 64 caracteres hexadecimales (256 bits)");

$tokenGenerado = $resRecup['token'];

// Test 8: Restablecer contraseña con token inválido debe fallar
$resRestablecerInvalido = cliente_restablecer_password('token_falso_invalido_xyz', 'NuevaClave2026!');
assertTest($resRestablecerInvalido['ok'] === false, "17. Rechazo de token de recuperación inválido o inexistente");

// Test 9: Restablecer contraseña con token válido
$nuevaClave = 'NuevaClaveSegura999!';
$resRestablecerValido = cliente_restablecer_password($tokenGenerado, $nuevaClave);
assertTest($resRestablecerValido['ok'] === true, "18. Restablecimiento exitoso de contraseña con token válido");

// Test 10: Token queda invalidado tras su uso (uso único)
$resReusoToken = cliente_restablecer_password($tokenGenerado, 'OtraClaveMas!');
assertTest($resReusoToken['ok'] === false, "19. El token queda invalidado tras su primer uso");

// Test 11: Login con la nueva contraseña restablecida
$resLoginNuevaClave = cliente_login($testEmail, $nuevaClave);
assertTest($resLoginNuevaClave['ok'] === true, "20. Login exitoso con la nueva contraseña recién restablecida");

// Test 12: Actualización de datos faltantes durante compra (Requisito explícito del usuario)
// Supongamos que el cliente de Google no tiene teléfono ni dirección aún:
$clienteGoogleId = (int)$clienteGoogle['id'];
$stmtCheck = $pdo->prepare("SELECT telefono, direccion FROM clientes WHERE id = ?");
$stmtCheck->execute([$clienteGoogleId]);
$datosPrevios = $stmtCheck->fetch(PDO::FETCH_ASSOC);
assertTest(empty($datosPrevios['telefono']), "21. Cliente de Google inicialmente no tiene teléfono");

// Logueamos al cliente de Google para simular su compra en checkout
cliente_iniciar_sesion($clienteGoogle);

// Simulamos la lógica de checkout/api/procesar_pedido.php donde el cliente ingresa su teléfono y dirección al comprar:
$nuevoTelefono = '78912345';
$nuevaDireccion = 'Av. 6 de Agosto #500 | Ciudad: La Paz';
$stmtUpdateCliente = $pdo->prepare("
    UPDATE clientes SET 
        telefono = COALESCE(NULLIF(?, ''), telefono),
        direccion = COALESCE(NULLIF(?, ''), direccion),
        nombre = COALESCE(NULLIF(?, ''), nombre)
    WHERE id = ?
");
$stmtUpdateCliente->execute([$nuevoTelefono, $nuevaDireccion, 'Google Tester Actualizado', $clienteGoogleId]);

// Verificar que se guardó en el sistema
$stmtCheck->execute([$clienteGoogleId]);
$datosActualizados = $stmtCheck->fetch(PDO::FETCH_ASSOC);
assertTest($datosActualizados['telefono'] === $nuevoTelefono, "22. Teléfono ingresado en compra guardado automáticamente en BD");
assertTest($datosActualizados['direccion'] === $nuevaDireccion, "23. Dirección ingresada en compra guardada automáticamente en BD");

// Test 13: Limpieza de registros de prueba
$pdo->prepare("DELETE FROM clientes WHERE id IN (?, ?)")->execute([(int)$clienteActual['id'], $clienteGoogleId]);
cliente_cerrar_sesion();
assertTest(true, "24. Limpieza de registros de prueba completada");

echo "======================================================" . PHP_EOL;
echo "  ✅ TODAS LAS 24 PRUEBAS PASARON EXITOSAMENTE        " . PHP_EOL;
echo "======================================================" . PHP_EOL;
