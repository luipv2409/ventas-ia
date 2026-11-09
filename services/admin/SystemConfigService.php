<?php
declare(strict_types=1);

namespace Services\Admin;

use PDO;
use Throwable;
use Services\Rag\DocumentChunker;
use Services\Rag\EmbeddingService;
use Services\Rag\PdfExtractor;
use Services\Rag\VectorStore;
use Services\Security\SecurityKernel;

/**
 * Servicio centralizado de Configuración Plug & Play, Notificaciones Omnicanal
 * (Webhook/n8n, SMTP Email, WhatsApp Cloud API) y Gestión de Conocimiento RAG desde el Dashboard.
 *
 * Jerarquía de resolución (Cascading Config):
 *   1. Valor configurado en Base de Datos (tabla `configuracion`, descifrado con AES-256-GCM si es secreto)
 *   2. Variable de entorno (.env / constante global)
 *   3. Valor por defecto seguro
 */
final class SystemConfigService
{
    /** Claves que contienen credenciales/secretos y se cifran con AES-256-GCM en la BD. */
    public const SECRET_KEYS = [
        'deepseek_api_key',
        'chat_external_api_key',
        'whatsapp_verify_token',
        'whatsapp_access_token',
        'lead_webhook_secret',
        'smtp_pass',
    ];

    /** Todas las claves gestionables desde el Centro de Configuración del Admin. */
    public const ALLOWED_KEYS = [
        // 1. Identidad institucional
        'nombre_tienda',
        'nombre_asesor',
        'contacto_telefono',
        'whatsapp_admisiones',
        'contacto_email',
        // 2. Motor IA (DeepSeek)
        'deepseek_api_key',
        'deepseek_model',
        // 3. Integración n8n / Webhook de Prospectos y Multicanal
        'chat_external_api_key',
        'lead_webhook_enabled',
        'lead_webhook_url',
        'lead_webhook_secret',
        // 4. WhatsApp Business Cloud API (Meta Directo o vía n8n)
        'whatsapp_cloud_enabled',
        'whatsapp_phone_id',
        'whatsapp_business_id',
        'whatsapp_verify_token',
        'whatsapp_access_token',
        'whatsapp_notify_admin_phone',
        // 5. Notificaciones por Correo Electrónico (SMTP / Alertas a Admisiones)
        'notify_email_enabled',
        'notify_email_to',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_user',
        'smtp_pass',
        'smtp_from_email',
        'smtp_from_name',
    ];

    /** @var array<string, string>|null */
    private static ?array $cache = null;

    /**
     * Carga todas las configuraciones resolviendo cifrado AES-256-GCM y fallbacks a .env.
     *
     * @return array<string, string>
     */
    public static function loadAll(PDO $pdo, bool $forceReload = false): array
    {
        if (self::$cache !== null && !$forceReload) {
            return self::$cache;
        }

        $dbValues = [];
        try {
            $in = implode(',', array_fill(0, count(self::ALLOWED_KEYS), '?'));
            $stmt = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ({$in})");
            $stmt->execute(self::ALLOWED_KEYS);
            $rawRows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
            foreach ($rawRows as $k => $v) {
                $val = (string)$v;
                if (in_array($k, self::SECRET_KEYS, true) && $val !== '') {
                    $val = SecurityKernel::decryptSecret($val);
                }
                $dbValues[$k] = $val;
            }
        } catch (Throwable $e) {
            error_log('[SystemConfigService] Error leyendo tabla configuracion: ' . $e->getMessage());
        }

        $envFallbacks = [
            'nombre_tienda'               => 'Universidad Latinoamericana (ULAT)',
            'nombre_asesor'               => 'Guía ULAT',
            'contacto_telefono'           => '(4) 4509488 - 4221004',
            'whatsapp_admisiones'         => '59179795778',
            'contacto_email'              => 'latinoamericana@ulat.edu.bo',
            'deepseek_api_key'            => defined('DEEPSEEK_API_KEY') ? (string)DEEPSEEK_API_KEY : '',
            'deepseek_model'              => defined('DEEPSEEK_MODEL') ? (string)DEEPSEEK_MODEL : 'deepseek-chat',
            'chat_external_api_key'       => defined('CHAT_EXTERNAL_API_KEY') ? (string)CHAT_EXTERNAL_API_KEY : '',
            'whatsapp_verify_token'       => defined('WHATSAPP_VERIFY_TOKEN') ? (string)WHATSAPP_VERIFY_TOKEN : '',
            'lead_webhook_enabled'        => '0',
            'lead_webhook_url'            => '',
            'lead_webhook_secret'         => '',
            'whatsapp_cloud_enabled'      => '0',
            'whatsapp_phone_id'           => '',
            'whatsapp_business_id'        => '',
            'whatsapp_access_token'       => '',
            'whatsapp_notify_admin_phone' => '',
            'notify_email_enabled'        => '0',
            'notify_email_to'             => '',
            'smtp_host'                   => '',
            'smtp_port'                   => '587',
            'smtp_encryption'             => 'tls',
            'smtp_user'                   => '',
            'smtp_pass'                   => '',
            'smtp_from_email'             => 'admisiones@ulat.edu.bo',
            'smtp_from_name'              => 'Admisiones ULAT',
        ];

        $resolved = [];
        foreach (self::ALLOWED_KEYS as $key) {
            $dbVal = trim((string)($dbValues[$key] ?? ''));
            $resolved[$key] = $dbVal !== '' ? $dbVal : (string)($envFallbacks[$key] ?? '');
        }

        self::$cache = $resolved;
        return $resolved;
    }

    public static function get(PDO $pdo, string $key, string $default = ''): string
    {
        $all = self::loadAll($pdo);
        $val = $all[$key] ?? '';
        return $val !== '' ? $val : $default;
    }

    /**
     * Guarda un lote de configuraciones en la base de datos cifrando automáticamente las claves secretas.
     * Si un campo secreto llega vacío o con máscara ('••••'), conserva el secreto actual en BD.
     *
     * @param array<string, string> $newValues
     */
    public static function saveBatch(PDO $pdo, array $newValues, array $clearSecrets = []): void
    {
        $stmtGet = $pdo->prepare('SELECT valor FROM configuracion WHERE clave = :c LIMIT 1');
        $stmtUpd = $pdo->prepare('REPLACE INTO configuracion (clave, valor) VALUES (:c, :v)');

        foreach ($newValues as $key => $rawVal) {
            if (!in_array($key, self::ALLOWED_KEYS, true)) {
                continue;
            }
            $val = trim((string)$rawVal);

            if (in_array($key, self::SECRET_KEYS, true)) {
                if (in_array($key, $clearSecrets, true)) {
                    $stmtUpd->execute([':c' => $key, ':v' => '']);
                    continue;
                }
                // Si el usuario no tocó el campo secreto (vacío o contiene máscara), mantener el valor previo en BD
                if ($val === '' || str_contains($val, '••••')) {
                    $stmtGet->execute([':c' => $key]);
                    $existing = $stmtGet->fetchColumn();
                    if (is_string($existing) && $existing !== '') {
                        continue;
                    }
                    $stmtUpd->execute([':c' => $key, ':v' => '']);
                    continue;
                }
                $encrypted = SecurityKernel::encryptSecret($val);
                $stmtUpd->execute([':c' => $key, ':v' => $encrypted]);
            } else {
                $stmtUpd->execute([':c' => $key, ':v' => $val]);
            }
        }

        self::$cache = null;
    }

    /**
     * Dispara notificaciones en tiempo real cuando el Chatbot o el Formulario Web capturan un nuevo prospecto.
     * Nunca lanza excepciones hacia el flujo del estudiante (fail-safe).
     *
     * @param array{nombre: string, telefono: string, carrera: string, email?: string, origen?: string, notas?: string} $lead
     * @return array{webhook_sent: bool, email_sent: bool, whatsapp_sent: bool, errors: string[]}
     */
    public static function notifyNewLead(PDO $pdo, array $lead): array
    {
        $cfg = self::loadAll($pdo);
        $result = [
            'webhook_sent'  => false,
            'email_sent'    => false,
            'whatsapp_sent' => false,
            'errors'        => [],
        ];

        $payload = [
            'evento'    => 'nuevo_prospecto_ulat',
            'institucion' => $cfg['nombre_tienda'] ?? 'Universidad Latinoamericana (ULAT)',
            'prospecto' => [
                'nombre'   => (string)($lead['nombre'] ?? 'Postulante'),
                'telefono' => (string)($lead['telefono'] ?? ''),
                'email'    => (string)($lead['email'] ?? ''),
                'carrera'  => (string)($lead['carrera'] ?? 'General'),
                'origen'   => (string)($lead['origen'] ?? 'chatbot'),
                'notas'    => (string)($lead['notas'] ?? ''),
                'whatsapp_url' => 'https://wa.me/591' . ltrim(preg_replace('/\D+/', '', (string)($lead['telefono'] ?? '')) ?? '', '0'),
            ],
            'timestamp' => date('c'),
        ];

        // 1. Webhook saliente hacia n8n / Make / CRM institucional
        if (($cfg['lead_webhook_enabled'] ?? '0') === '1' && !empty($cfg['lead_webhook_url'])) {
            $whErr = null;
            $result['webhook_sent'] = self::sendWebhook($cfg['lead_webhook_url'], $payload, $cfg['lead_webhook_secret'] ?? '', $whErr);
            if ($whErr !== null) {
                $result['errors'][] = 'Webhook: ' . $whErr;
            }
        }

        // 2. Alerta por Correo Electrónico (SMTP nativo o mail())
        if (($cfg['notify_email_enabled'] ?? '0') === '1' && !empty($cfg['notify_email_to'])) {
            $emErr = null;
            $result['email_sent'] = self::sendLeadEmail($cfg, $payload['prospecto'], $emErr);
            if ($emErr !== null) {
                $result['errors'][] = 'Email: ' . $emErr;
            }
        }

        // 3. Alerta por WhatsApp Cloud API al encargado de Admisiones
        if (
            ($cfg['whatsapp_cloud_enabled'] ?? '0') === '1'
            && !empty($cfg['whatsapp_phone_id'])
            && !empty($cfg['whatsapp_access_token'])
            && !empty($cfg['whatsapp_notify_admin_phone'])
        ) {
            $waErr = null;
            $p = $payload['prospecto'];
            $textoWa = "🎓 *Nuevo Prospecto Captado — ULAT*\n"
                . "• *Nombre:* {$p['nombre']}\n"
                . "• *WhatsApp:* {$p['telefono']}\n"
                . "• *Carrera:* {$p['carrera']}\n"
                . "• *Origen:* {$p['origen']}\n"
                . "👉 Contactar: {$p['whatsapp_url']}";
            $result['whatsapp_sent'] = self::sendWhatsAppCloudMessage(
                $cfg['whatsapp_phone_id'],
                $cfg['whatsapp_access_token'],
                $cfg['whatsapp_notify_admin_phone'],
                $textoWa,
                $waErr
            );
            if ($waErr !== null) {
                $result['errors'][] = 'WhatsApp Cloud: ' . $waErr;
            }
        }

        return $result;
    }

    /**
     * Envía un mensaje saliente mediante la API oficial de WhatsApp Business Cloud (Meta Graph API).
     */
    public static function sendWhatsAppCloudMessage(
        string $phoneId,
        string $accessToken,
        string $recipientPhone,
        string $text,
        ?string &$errorOut = null
    ): bool {
        $cleanPhoneId = preg_replace('/[^0-9]/', '', $phoneId) ?? '';
        $cleanRecipient = preg_replace('/[^0-9]/', '', $recipientPhone) ?? '';
        if ($cleanPhoneId === '' || $accessToken === '' || $cleanRecipient === '') {
            $errorOut = 'Faltan parámetros de WhatsApp Cloud (Phone ID, Access Token o destinatario).';
            return false;
        }

        $url = "https://graph.facebook.com/v19.0/{$cleanPhoneId}/messages";
        $body = json_encode([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $cleanRecipient,
            'type'              => 'text',
            'text'              => ['preview_url' => false, 'body' => mb_substr($text, 0, 4000)],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cErr = curl_error($ch);
        curl_close($ch);

        if ($cErr !== '' || $code < 200 || $code >= 300) {
            $errorOut = $cErr !== '' ? $cErr : "HTTP {$code}: " . mb_substr((string)$resp, 0, 180);
            error_log('[SystemConfigService WhatsApp Error] ' . $errorOut);
            return false;
        }
        return true;
    }

    /**
     * Valida y guarda un nuevo documento (.pdf o .md) en knowledge/ulat_docs/ verificando Magic Bytes.
     *
     * @param array<string, mixed> $uploadedFile Elemento de $_FILES['documento_rag']
     * @return array{ok: bool, filename?: string, message: string}
     */
    public static function uploadKnowledgeDocument(array $uploadedFile): array
    {
        $errCode = (int)($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errCode !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'No se recibió un archivo válido (código ' . $errCode . ').'];
        }

        $tmpPath  = (string)($uploadedFile['tmp_name'] ?? '');
        $origName = basename((string)($uploadedFile['name'] ?? ''));
        $size     = (int)($uploadedFile['size'] ?? 0);

        if ($tmpPath === '' || !is_file($tmpPath)) {
            return ['ok' => false, 'message' => 'Archivo temporal no encontrado.'];
        }

        // Límite estricto de 15 MB
        if ($size <= 10 || $size > 15 * 1024 * 1024) {
            return ['ok' => false, 'message' => 'El tamaño del documento debe estar entre 10 bytes y 15 MB.'];
        }

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'md'], true)) {
            return ['ok' => false, 'message' => 'Solo se permiten reglamentos en formato .pdf o documentos .md.'];
        }

        // Nombre de archivo limpio sin doble extensión ni caracteres de ruta
        $baseName = pathinfo($origName, PATHINFO_FILENAME);
        $safeBase = preg_replace('/[^a-zA-Z0-9_.\- áéíóúÁÉÍÓÚñÑ]/u', '', $baseName) ?? '';
        $safeBase = trim(preg_replace('/\.+/', '_', $safeBase) ?? '');
        if ($safeBase === '') {
            $safeBase = 'documento_ulat_' . date('Ymd_His');
        }
        $finalFilename = $safeBase . '.' . $ext;

        // Inspección de Magic Bytes y contenido real (Defensa contra subida de código malicioso)
        $headBytes = (string)file_get_contents($tmpPath, false, null, 0, 1024);
        if ($ext === 'pdf') {
            if (!str_starts_with($headBytes, '%PDF-')) {
                return ['ok' => false, 'message' => 'El archivo rechazado no tiene cabecera binaria PDF válida (%PDF-).'];
            }
        } else {
            $fullContent = (string)file_get_contents($tmpPath);
            if (!mb_check_encoding($fullContent, 'UTF-8')) {
                return ['ok' => false, 'message' => 'El archivo Markdown (.md) debe estar codificado en UTF-8.'];
            }
            if (preg_match('/<\?(?:php|=)|<\s*script\b/i', $fullContent) === 1) {
                return ['ok' => false, 'message' => 'Se detectaron etiquetas de script o código no permitidas en el documento.'];
            }
        }

        $targetDir = dirname(__DIR__, 2) . '/knowledge/ulat_docs';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . '/' . $finalFilename;
        if (!@move_uploaded_file($tmpPath, $targetPath) && !@copy($tmpPath, $targetPath)) {
            return ['ok' => false, 'message' => 'No se pudo almacenar el documento en knowledge/ulat_docs/.'];
        }
        @chmod($targetPath, 0644);

        return [
            'ok'       => true,
            'filename' => $finalFilename,
            'message'  => "Documento '{$finalFilename}' validado y almacenado correctamente.",
        ];
    }

    /**
     * Reindexa toda la base de conocimiento RAG ULAT directamente desde el panel de administración.
     *
     * @return array{ok: bool, total_chunks: int, vocab_size: int, collections: array<string, int>, elapsed_ms: int, error?: string}
     */
    public static function reindexUlatKnowledge(PDO $pdo): array
    {
        $start = microtime(true);
        @set_time_limit(90);

        require_once dirname(__DIR__) . '/rag/EmbeddingService.php';
        require_once dirname(__DIR__) . '/rag/VectorStore.php';
        require_once dirname(__DIR__) . '/rag/DocumentChunker.php';
        require_once dirname(__DIR__) . '/rag/PdfExtractor.php';

        try {
            $embeddingService = new EmbeddingService();
            $vectorStore      = new VectorStore($pdo);
            $vectorStore->ensureSchema();

            $docs        = [];
            $rootDir     = dirname(__DIR__, 2);
            $ulatDocsDir = $rootDir . '/knowledge/ulat_docs';
            $catalogFile = $rootDir . '/data/ulat_catalog.json';

            // 1. Catálogo estructurado de carreras
            if (is_file($catalogFile)) {
                $catalog = json_decode((string)file_get_contents($catalogFile), true) ?: [];
                foreach ($catalog as $slug => $c) {
                    $materiasResumen = '';
                    if (!empty($c['semestres']) && is_array($c['semestres'])) {
                        foreach ($c['semestres'] as $semNom => $mats) {
                            $materiasResumen .= "{$semNom}: " . implode(', ', $mats) . '. ';
                        }
                    }

                    $content  = "CARRERA: {$c['nombre']}\n";
                    $content .= "FACULTAD: {$c['facultad']}\n";
                    $content .= "GRADO: {$c['grado']}\n";
                    $content .= "DURACIÓN: {$c['duracion']}\n";
                    $content .= "MODALIDAD: {$c['modalidad']}\n";
                    $content .= 'SEDE: ' . ($c['sede'] ?? 'Campus Central') . "\n\n";
                    $content .= "MALLA CURRICULAR: {$materiasResumen}\n\n";
                    $content .= "DESCRIPCIÓN Y PERFIL:\n" . substr((string)$c['descripcion'], 0, 1200) . "\n";

                    $embedText = "{$c['nombre']} {$c['facultad']} {$c['grado']} {$c['duracion']} {$c['modalidad']} malla curricular materias semestres {$materiasResumen} " . substr((string)$c['descripcion'], 0, 600);

                    $docs[] = [
                        'collection' => 'carreras',
                        'doc_id'     => 'carrera_' . $slug,
                        'title'      => (string)$c['nombre'],
                        'content'    => $content,
                        'embed_text' => $embedText,
                        'metadata'   => [
                            'tipo'     => 'carrera_universitaria',
                            'slug'     => $slug,
                            'facultad' => $c['facultad'],
                            'grado'    => $c['grado'],
                            'duracion' => $c['duracion'],
                        ],
                    ];
                }
            }

            // 2. Documentos Markdown institucionales (.md)
            foreach (glob($ulatDocsDir . '/*.md') ?: [] as $filePath) {
                $filename = basename($filePath);
                $raw = (string)file_get_contents($filePath);
                if (trim($raw) === '') {
                    continue;
                }
                $col = str_starts_with($filename, 'institucional_beca') || str_starts_with($filename, 'institucional_plan')
                    ? 'becas'
                    : (str_starts_with($filename, 'institucional_admi') ? 'admisiones' : 'institucional');

                foreach (DocumentChunker::chunkMarkdown($raw, $filename) as $chunk) {
                    $docs[] = [
                        'collection' => $col,
                        'doc_id'     => $chunk['doc_id'],
                        'title'      => $chunk['title'],
                        'content'    => $chunk['content'],
                        'embed_text' => $chunk['embed_text'],
                        'metadata'   => [
                            'tipo'    => 'documento_ulat',
                            'archivo' => $filename,
                            'seccion' => $chunk['section'],
                        ],
                    ];
                }
            }

            // 3. Reglamentos oficiales en PDF (.pdf)
            foreach (glob($ulatDocsDir . '/*.pdf') ?: [] as $filePath) {
                $filename = basename($filePath);
                $extractedText = PdfExtractor::extractText($filePath);
                if (mb_strlen(trim($extractedText)) < 30) {
                    continue;
                }
                foreach (DocumentChunker::chunkPdf($extractedText, $filename) as $chunk) {
                    $docs[] = [
                        'collection' => 'reglamentos',
                        'doc_id'     => $chunk['doc_id'],
                        'title'      => $chunk['title'],
                        'content'    => $chunk['content'],
                        'embed_text' => $chunk['embed_text'],
                        'metadata'   => $chunk['metadata'],
                    ];
                }
            }

            // 4. Cálculo de IDF + almacenamiento vectorial + limpieza de obsoletos y caché
            $idfMap = [];
            if ($embeddingService->isLocalMode()) {
                $featureSets = array_map(
                    static fn(array $d): array => $embeddingService->extractFeatures($d['embed_text']),
                    $docs
                );
                $idfMap = EmbeddingService::computeIdf($featureSets);
                $embeddingService->setIdf($idfMap);
                $vectorStore->saveMeta('idf', [
                    'idf'       => $idfMap,
                    'docs'      => count($docs),
                    'generated' => date('c'),
                ]);
            }

            $collections = ['carreras' => 0, 'institucional' => 0, 'admisiones' => 0, 'becas' => 0, 'reglamentos' => 0];
            $vigentes    = [];

            foreach ($docs as $d) {
                $vec = $embeddingService->getEmbedding($d['embed_text']);
                if ($vec === null || empty($vec)) {
                    continue;
                }
                $ok = $vectorStore->saveDocument(
                    collection: $d['collection'],
                    docId: $d['doc_id'],
                    title: $d['title'],
                    content: $d['content'],
                    metadata: $d['metadata'],
                    embedding: $vec
                );
                if ($ok) {
                    $col = $d['collection'];
                    $collections[$col] = ($collections[$col] ?? 0) + 1;
                    $vigentes[$col][]  = $d['doc_id'];
                }
            }

            foreach ($vigentes as $col => $ids) {
                $vectorStore->deleteStale($col, $ids);
            }
            $pdo->exec("DELETE FROM rag_documents WHERE collection NOT IN ('admisiones', 'becas', 'carreras', 'institucional', 'reglamentos')");

            try {
                $pdo->exec('TRUNCATE TABLE rag_query_cache');
            } catch (Throwable $e) {
            }

            return [
                'ok'           => true,
                'total_chunks' => count($docs),
                'vocab_size'   => count($idfMap),
                'collections'  => $collections,
                'elapsed_ms'   => (int)round((microtime(true) - $start) * 1000),
            ];
        } catch (Throwable $e) {
            error_log('[SystemConfigService::reindexUlatKnowledge] Error: ' . $e->getMessage());
            return [
                'ok'           => false,
                'total_chunks' => 0,
                'vocab_size'   => 0,
                'collections'  => [],
                'elapsed_ms'   => (int)round((microtime(true) - $start) * 1000),
                'error'        => $e->getMessage(),
            ];
        }
    }

    /**
     * Envía un POST JSON firmado por HMAC-SHA256 hacia un Webhook externo (n8n, Make, Zapier o CRM).
     */
    private static function sendWebhook(string $url, array $payload, string $secret, ?string &$errorOut = null): bool
    {
        $url = trim($url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errorOut = 'URL de webhook inválida.';
            return false;
        }
        $parts = parse_url($url);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host   = strtolower((string)($parts['host'] ?? ''));
        if (!in_array($scheme, ['https', 'http'], true) || $host === '') {
            $errorOut = 'El webhook debe usar protocolo HTTP/HTTPS válido.';
            return false;
        }
        // Prevenir SSRF contra metadatos cloud
        if ($host === '169.254.169.254' || str_starts_with($host, '169.254.')) {
            $errorOut = 'Destino de webhook bloqueado por política anti-SSRF.';
            return false;
        }

        $json = (string)json_encode($payload, JSON_UNESCAPED_UNICODE);
        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'User-Agent: ULAT-Admissions-Webhook/1.0',
        ];
        if ($secret !== '') {
            $sig = hash_hmac('sha256', $json, $secret);
            $headers[] = 'X-ULAT-Signature: sha256=' . $sig;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 4,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cErr = curl_error($ch);
        curl_close($ch);

        if ($cErr !== '' || $code < 200 || $code >= 300) {
            $errorOut = $cErr !== '' ? $cErr : "HTTP {$code}: " . mb_substr((string)$resp, 0, 150);
            error_log('[SystemConfigService Webhook Error] ' . $errorOut);
            return false;
        }
        return true;
    }

    /**
     * Envía alerta por correo electrónico al equipo de Admisiones vía SMTP directo (STARTTLS/SSL) o mail().
     *
     * @param array<string, string> $cfg
     * @param array<string, string> $prospecto
     */
    private static function sendLeadEmail(array $cfg, array $prospecto, ?string &$errorOut = null): bool
    {
        $to = trim((string)($cfg['notify_email_to'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $errorOut = 'Correo destinatario de alertas inválido.';
            return false;
        }

        $fromEmail = filter_var($cfg['smtp_from_email'] ?? '', FILTER_VALIDATE_EMAIL)
            ? $cfg['smtp_from_email']
            : 'admisiones@ulat.edu.bo';
        $fromName  = preg_replace('/[\r\n]+/', ' ', (string)($cfg['smtp_from_name'] ?? 'Admisiones ULAT')) ?? 'Admisiones ULAT';

        $subject = 'Nuevo Prospecto ULAT: ' . ($prospecto['nombre'] ?? 'Postulante') . ' (' . ($prospecto['carrera'] ?? 'General') . ')';
        $bodyTxt = "NUEVO PROSPECTO CAPTADO — ADMISIONES ULAT\r\n"
            . "==========================================\r\n"
            . "Nombre: " . ($prospecto['nombre'] ?? '') . "\r\n"
            . "WhatsApp / Teléfono: " . ($prospecto['telefono'] ?? '') . "\r\n"
            . "Correo: " . (($prospecto['email'] ?? '') !== '' ? $prospecto['email'] : 'No especificado') . "\r\n"
            . "Carrera de interés: " . ($prospecto['carrera'] ?? '') . "\r\n"
            . "Canal de origen: " . ($prospecto['origen'] ?? 'chatbot') . "\r\n"
            . "Notas: " . ($prospecto['notas'] ?? '') . "\r\n"
            . "Contacto directo WhatsApp: " . ($prospecto['whatsapp_url'] ?? '') . "\r\n"
            . "Fecha: " . date('Y-m-d H:i:s') . "\r\n";

        $smtpHost = trim((string)($cfg['smtp_host'] ?? ''));
        if ($smtpHost === '') {
            $headers = "From: {$fromName} <{$fromEmail}>\r\n"
                . "Reply-To: {$fromEmail}\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n";
            $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $bodyTxt, $headers);
            if (!$ok) {
                $errorOut = 'Función mail() local no disponible; configure un servidor SMTP en el panel.';
            }
            return $ok;
        }

        return self::sendNativeSmtp(
            $smtpHost,
            (int)($cfg['smtp_port'] ?? 587),
            strtolower((string)($cfg['smtp_encryption'] ?? 'tls')),
            (string)($cfg['smtp_user'] ?? ''),
            (string)($cfg['smtp_pass'] ?? ''),
            $fromEmail,
            $fromName,
            $to,
            $subject,
            $bodyTxt,
            $errorOut
        );
    }

    /**
     * Cliente SMTP ligero con soporte STARTTLS/SSL nativo (sin requerir librerías externas).
     */
    private static function sendNativeSmtp(
        string $host,
        int $port,
        string $encryption,
        string $user,
        string $pass,
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $subject,
        string $body,
        ?string &$errorOut = null
    ): bool {
        $cleanHost = preg_replace('/[^a-zA-Z0-9.\-]/', '', $host) ?? '';
        if ($cleanHost === '' || $port <= 0 || $port > 65535) {
            $errorOut = 'Host o puerto SMTP inválido.';
            return false;
        }

        $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $cleanHost . ':' . $port;
        $errno  = 0;
        $errstr = '';
        $fp = @stream_socket_client($remote, $errno, $errstr, 4);
        if (!is_resource($fp)) {
            $errorOut = "No se pudo conectar al servidor SMTP ({$cleanHost}:{$port}): {$errstr}";
            return false;
        }

        stream_set_timeout($fp, 4);

        $readReply = static function () use ($fp): string {
            $data = '';
            while (($line = fgets($fp, 515)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };

        $sendCmd = static function (string $cmd, int $expectCode) use ($fp, $readReply, &$errorOut): bool {
            fwrite($fp, $cmd . "\r\n");
            $reply = $readReply();
            $code  = (int)substr($reply, 0, 3);
            if ($code !== $expectCode) {
                $errorOut = "SMTP esperaba {$expectCode}, recibió: " . trim($reply);
                return false;
            }
            return true;
        };

        try {
            $greeting = $readReply();
            if ((int)substr($greeting, 0, 3) !== 220) {
                $errorOut = 'Saludo SMTP inválido: ' . trim($greeting);
                return false;
            }

            if (!$sendCmd('EHLO ulat.edu.bo', 250)) {
                return false;
            }

            if ($encryption === 'tls') {
                if (!$sendCmd('STARTTLS', 220)) {
                    return false;
                }
                if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    $errorOut = 'No se pudo negociar cifrado STARTTLS con el servidor SMTP.';
                    return false;
                }
                if (!$sendCmd('EHLO ulat.edu.bo', 250)) {
                    return false;
                }
            }

            if ($user !== '' && $pass !== '') {
                if (!$sendCmd('AUTH LOGIN', 334)) {
                    return false;
                }
                if (!$sendCmd(base64_encode($user), 334)) {
                    return false;
                }
                if (!$sendCmd(base64_encode($pass), 235)) {
                    return false;
                }
            }

            if (!$sendCmd("MAIL FROM:<{$fromEmail}>", 250)) {
                return false;
            }
            if (!$sendCmd("RCPT TO:<{$toEmail}>", 250)) {
                return false;
            }
            if (!$sendCmd('DATA', 354)) {
                return false;
            }

            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $headers = [
                'Date: ' . date('r'),
                "From: {$fromName} <{$fromEmail}>",
                "To: <{$toEmail}>",
                "Subject: {$encodedSubject}",
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];
            $safeBody = str_replace("\r\n.", "\r\n..", $body);
            $message  = implode("\r\n", $headers) . "\r\n\r\n" . $safeBody . "\r\n.";

            if (!$sendCmd($message, 250)) {
                return false;
            }
            fwrite($fp, "QUIT\r\n");
            return true;
        } finally {
            fclose($fp);
        }
    }
}
