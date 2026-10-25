<?php
declare(strict_types=1);

namespace Services\Chat;

use PDO;
use Throwable;

/**
 * Exclusión mutua por conversación usando GET_LOCK de MySQL/MariaDB.
 *
 * Problema que resuelve: si un cliente de WhatsApp manda 3 mensajes seguidos, llegan 3 peticiones
 * concurrentes que leen el MISMO historial y responden desordenadas (y triplican el gasto de tokens
 * sobre contexto desactualizado). Con el candado, los mensajes de una misma conversación se procesan
 * en orden: el segundo ya ve la respuesta del primero.
 *
 * El candado es por conexión: se libera solo si el proceso muere o la conexión se cierra.
 */
final class SessionLock
{
    private PDO $pdo;
    private string $name;
    private bool $held = false;

    public function __construct(PDO $pdo, string $canal, string $remitente)
    {
        $this->pdo  = $pdo;
        // MySQL limita el nombre del candado a 64 caracteres: se usa un hash
        $this->name = 'chat:' . substr(sha1($canal . '|' . $remitente), 0, 40);
    }

    public function acquire(int $timeoutSeconds = 30): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT GET_LOCK(:n, :t)');
            $stmt->bindValue(':n', $this->name);
            $stmt->bindValue(':t', max(0, $timeoutSeconds), PDO::PARAM_INT);
            $stmt->execute();
            $this->held = ((int)$stmt->fetchColumn()) === 1;
        } catch (Throwable $e) {
            // Si el candado no se puede usar, se sigue sin él (degradación, no caída)
            error_log('[SessionLock] No se pudo adquirir el candado: ' . $e->getMessage());
            $this->held = false;
            return true;
        }
        return $this->held;
    }

    public function release(): void
    {
        if (!$this->held) {
            return;
        }
        try {
            $stmt = $this->pdo->prepare('SELECT RELEASE_LOCK(:n)');
            $stmt->execute([':n' => $this->name]);
        } catch (Throwable $e) {
            error_log('[SessionLock] Error liberando candado: ' . $e->getMessage());
        }
        $this->held = false;
    }

    public function isHeld(): bool
    {
        return $this->held;
    }
}
