<?php
// ============================================================
// ARCHIVO: config/database.php
// PROPÓSITO: Clase Database — conexión PDO con patrón Singleton.
//
// PATRÓN SINGLETON: Garantiza que solo exista UNA conexión
// a la base de datos durante toda la ejecución del script,
// evitando conexiones duplicadas y mejorando el rendimiento.
// ============================================================

require_once __DIR__ . '/config.php';

class Database
{
    // --------------------------------------------------------
    // Propiedad estática que almacena la única instancia PDO.
    // --------------------------------------------------------
    private static ?PDO $instance = null;

    // --------------------------------------------------------
    // Constructor privado: impide instanciar la clase con "new".
    // --------------------------------------------------------
    private function __construct() {}

    // --------------------------------------------------------
    // Impide la clonación del objeto Singleton.
    // --------------------------------------------------------
    private function __clone() {}

    // --------------------------------------------------------
    // MÉTODO PRINCIPAL: Devuelve la conexión PDO.
    // Si no existe, la crea. Si ya existe, la reutiliza.
    // --------------------------------------------------------
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }

    // --------------------------------------------------------
    // Crea y configura la conexión PDO.
    // Lanza una excepción si la conexión falla.
    // --------------------------------------------------------
    private static function createConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            defined('DB_PORT') ? DB_PORT : '3306',
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            // Lanza excepciones en caso de error SQL
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Devuelve resultados como arrays asociativos por defecto
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Desactiva emulación de prepared statements (más seguro)
            PDO::ATTR_EMULATE_PREPARES   => false,
            // Tiempo de conexión máximo en segundos
            PDO::ATTR_TIMEOUT            => 5,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e) {
            // En producción: loguear el error, no mostrarlo
            self::handleError($e);
        }
    }

    // --------------------------------------------------------
    // Manejo centralizado de errores de conexión.
    // --------------------------------------------------------
    private static function handleError(PDOException $e): never
    {
        if (APP_DEBUG) {
            // Modo desarrollo: muestra detalles del error
            throw new RuntimeException(
                '❌ Error de conexión a la BD: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        } else {
            // Modo producción: mensaje genérico (no expone datos internos)
            error_log('[DB ERROR] ' . $e->getMessage()); // Guarda en error_log de PHP
            throw new RuntimeException('Error interno del servidor. Intente más tarde.');
        }
    }
}
