<?php
declare(strict_types=1);

/**
 * Autoloader PSR-4 ligero para el namespace Services\*
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'Services\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $segments = explode('\\', $relativeClass);
    $fileName = array_pop($segments);
    // Directorios en minúsculas (services/chat, services/rag, services/admin, services/security)
    $subDir = implode(DIRECTORY_SEPARATOR, array_map('strtolower', $segments));
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'services';

    $file = $baseDir
        . ($subDir !== '' ? DIRECTORY_SEPARATOR . $subDir : '')
        . DIRECTORY_SEPARATOR . $fileName . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
