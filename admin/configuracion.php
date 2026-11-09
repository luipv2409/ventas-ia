<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

use Services\Admin\SystemConfigService;
use Services\Security\SecurityKernel;

$pdo    = Database::getConnection();
$error  = null;
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityKernel::requireCsrfPost();
    $accion = (string)($_POST['accion_config'] ?? 'guardar_config');

    // -------------------------------------------------------------------------
    // ACCIÓN A: Guardar parámetros generales, IA, n8n, WhatsApp y SMTP
    // -------------------------------------------------------------------------
    if ($accion === 'guardar_config') {
        $institucion = mb_substr(trim((string)($_POST['nombre_tienda'] ?? '')), 0, 120);
        $asesor      = mb_substr(trim((string)($_POST['nombre_asesor'] ?? '')), 0, 80);
        $telefono    = mb_substr(trim((string)($_POST['contacto_telefono'] ?? '')), 0, 40);
        $email       = mb_substr(trim((string)($_POST['contacto_email'] ?? '')), 0, 120);
        $whatsapp    = preg_replace('/[^0-9]/', '', (string)($_POST['whatsapp_admisiones'] ?? '')) ?? '';

        $notifyEmailTo = trim((string)($_POST['notify_email_to'] ?? ''));
        $webhookUrl    = trim((string)($_POST['lead_webhook_url'] ?? ''));
        $smtpPort      = (int)($_POST['smtp_port'] ?? 587);
        $smtpEnc       = in_array($_POST['smtp_encryption'] ?? 'tls', ['tls', 'ssl', 'none'], true)
            ? (string)$_POST['smtp_encryption']
            : 'tls';

        if ($institucion === '' || $asesor === '') {
            $error = 'El nombre institucional y el nombre del asistente son obligatorios.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'El correo institucional ingresado no tiene formato válido.';
        } elseif ($whatsapp !== '' && (strlen($whatsapp) < 8 || strlen($whatsapp) > 15)) {
            $error = 'El número de WhatsApp debe contener entre 8 y 15 dígitos.';
        } elseif ($notifyEmailTo !== '' && !filter_var($notifyEmailTo, FILTER_VALIDATE_EMAIL)) {
            $error = 'El correo destinatario de alertas de prospectos no es válido.';
        } elseif ($webhookUrl !== '' && !filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            $error = 'La URL del Webhook (n8n / CRM) no tiene un formato HTTP/HTTPS válido.';
        } else {
            $lote = [
                'nombre_tienda'               => $institucion,
                'nombre_asesor'               => $asesor,
                'contacto_telefono'           => $telefono,
                'whatsapp_admisiones'         => $whatsapp,
                'contacto_email'              => $email,
                'deepseek_api_key'            => trim((string)($_POST['deepseek_api_key'] ?? '')),
                'deepseek_model'              => mb_substr(trim((string)($_POST['deepseek_model'] ?? 'deepseek-chat')), 0, 60),
                'chat_external_api_key'       => trim((string)($_POST['chat_external_api_key'] ?? '')),
                'lead_webhook_enabled'        => !empty($_POST['lead_webhook_enabled']) ? '1' : '0',
                'lead_webhook_url'            => $webhookUrl,
                'lead_webhook_secret'         => trim((string)($_POST['lead_webhook_secret'] ?? '')),
                'whatsapp_cloud_enabled'      => !empty($_POST['whatsapp_cloud_enabled']) ? '1' : '0',
                'whatsapp_phone_id'           => preg_replace('/[^0-9]/', '', (string)($_POST['whatsapp_phone_id'] ?? '')) ?? '',
                'whatsapp_business_id'        => preg_replace('/[^0-9]/', '', (string)($_POST['whatsapp_business_id'] ?? '')) ?? '',
                'whatsapp_verify_token'       => trim((string)($_POST['whatsapp_verify_token'] ?? '')),
                'whatsapp_access_token'       => trim((string)($_POST['whatsapp_access_token'] ?? '')),
                'whatsapp_notify_admin_phone' => preg_replace('/[^0-9]/', '', (string)($_POST['whatsapp_notify_admin_phone'] ?? '')) ?? '',
                'notify_email_enabled'        => !empty($_POST['notify_email_enabled']) ? '1' : '0',
                'notify_email_to'             => $notifyEmailTo,
                'smtp_host'                   => mb_substr(trim((string)($_POST['smtp_host'] ?? '')), 0, 120),
                'smtp_port'                   => (string)($smtpPort > 0 && $smtpPort <= 65535 ? $smtpPort : 587),
                'smtp_encryption'             => $smtpEnc,
                'smtp_user'                   => mb_substr(trim((string)($_POST['smtp_user'] ?? '')), 0, 120),
                'smtp_pass'                   => trim((string)($_POST['smtp_pass'] ?? '')),
                'smtp_from_email'             => mb_substr(trim((string)($_POST['smtp_from_email'] ?? 'admisiones@ulat.edu.bo')), 0, 120),
                'smtp_from_name'              => mb_substr(trim((string)($_POST['smtp_from_name'] ?? 'Admisiones ULAT')), 0, 80),
            ];

            SystemConfigService::saveBatch($pdo, $lote);
            $notice = 'Configuración institucional, credenciales cifradas (AES-256-GCM) e integraciones guardadas correctamente.';
        }
    }

    // -------------------------------------------------------------------------
    // ACCIÓN B: Probar disparo de notificaciones en vivo
    // -------------------------------------------------------------------------
    elseif ($accion === 'probar_notificaciones') {
        $resTest = SystemConfigService::notifyNewLead($pdo, [
            'nombre'   => 'Estudiante de Prueba (Demo Admin)',
            'telefono' => '70011223',
            'email'    => 'postulante.demo@ulat.edu.bo',
            'carrera'  => 'Ingeniería de Sistemas',
            'origen'   => 'prueba_dashboard',
            'notas'    => 'Prueba de conectividad disparada desde el Centro de Configuración ULAT.',
        ]);

        $exitos = [];
        if ($resTest['webhook_sent']) {
            $exitos[] = 'Webhook n8n/CRM enviado';
        }
        if ($resTest['email_sent']) {
            $exitos[] = 'Correo de alerta enviado';
        }
        if ($resTest['whatsapp_sent']) {
            $exitos[] = 'Alerta WhatsApp Cloud enviada';
        }

        if (!empty($resTest['errors'])) {
            $error = 'Resultado de prueba con observaciones: ' . implode(' | ', $resTest['errors']);
        } elseif (empty($exitos)) {
            $error = 'No hay canales de alerta activos. Activa al menos uno (Webhook, Correo o WhatsApp Cloud) y guarda antes de probar.';
        } else {
            $notice = 'Prueba exitosa: ' . implode(', ', $exitos) . '.';
        }
    }

    // -------------------------------------------------------------------------
    // ACCIÓN C: Subir nuevo reglamento PDF o documento Markdown (.md)
    // -------------------------------------------------------------------------
    elseif ($accion === 'subir_documento_rag') {
        $up = SystemConfigService::uploadKnowledgeDocument($_FILES['documento_rag'] ?? []);
        if (!$up['ok']) {
            $error = $up['message'];
        } else {
            $reidx = SystemConfigService::reindexUlatKnowledge($pdo);
            if ($reidx['ok']) {
                $notice = $up['message'] . " Base RAG reindexada automáticamente ({$reidx['total_chunks']} fragmentos activos en {$reidx['elapsed_ms']} ms).";
            } else {
                $error = $up['message'] . ' Sin embargo, ocurrió un error al reindexar: ' . ($reidx['error'] ?? '');
            }
        }
    }

    // -------------------------------------------------------------------------
    // ACCIÓN D: Reindexar Base de Conocimiento RAG en un clic
    // -------------------------------------------------------------------------
    elseif ($accion === 'reindexar_rag') {
        $reidx = SystemConfigService::reindexUlatKnowledge($pdo);
        if ($reidx['ok']) {
            $notice = sprintf(
                'Base de conocimiento RAG actualizada con éxito: %d fragmentos activos y %d términos IDF calculados en %d ms.',
                $reidx['total_chunks'],
                $reidx['vocab_size'],
                $reidx['elapsed_ms']
            );
        } else {
            $error = 'Error al reindexar la base de conocimiento: ' . ($reidx['error'] ?? 'Error desconocido');
        }
    }
}

$config = SystemConfigService::loadAll($pdo, true);

// Estadísticas en vivo del almacén RAG
$ragStats = ['total' => 0, 'por_coleccion' => []];
try {
    $rowsCol = $pdo->query('SELECT collection, COUNT(*) AS c FROM rag_documents GROUP BY collection')->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    $ragStats['por_coleccion'] = $rowsCol;
    $ragStats['total'] = array_sum(array_map('intval', $rowsCol));
} catch (Throwable $e) {
}

$docsArchivos = [];
$docsDir = dirname(__DIR__) . '/knowledge/ulat_docs';
if (is_dir($docsDir)) {
    foreach (glob($docsDir . '/*.{pdf,md}', GLOB_BRACE) ?: [] as $f) {
        $docsArchivos[] = [
            'nombre' => basename($f),
            'tipo'   => strtoupper(pathinfo($f, PATHINFO_EXTENSION)),
            'kb'     => round(filesize($f) / 1024, 1),
        ];
    }
}

$baseUrl = rtrim((string)(defined('APP_URL') ? preg_replace('#/ulat/?$#i', '', APP_URL) : 'http://localhost/ventas-ia-main'), '/');
$webhookEndpointUrl = $baseUrl . '/api/chat_externo.php';

ob_start();
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="fw-bold brand-font mb-1">Centro de Configuración e Integraciones — ULAT</h2>
        <p class="text-muted small mb-0">Administra el motor de IA, alertas de prospectos, WhatsApp Business, n8n, correo SMTP y la base de conocimiento RAG sin tocar código.</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
            <i class="fas fa-lock me-1"></i> Secretos Cifrados AES-256-GCM
        </span>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
            <i class="fas fa-database me-1"></i> <?= (int)$ragStats['total'] ?> Fragmentos RAG Activos
        </span>
    </div>
</div>

<?php if ($notice !== null): ?>
    <div class="alert alert-success small py-2 shadow-sm border-0">
        <i class="fas fa-check-circle me-1"></i> <?= SecurityKernel::e($notice) ?>
    </div>
<?php endif; ?>

<?php if ($error !== null): ?>
    <div class="alert alert-danger small py-2 shadow-sm border-0">
        <i class="fas fa-exclamation-triangle me-1"></i> <?= SecurityKernel::e($error) ?>
    </div>
<?php endif; ?>

<form method="POST" autocomplete="off">
    <?= SecurityKernel::csrfInput() ?>
    <input type="hidden" name="accion_config" value="guardar_config">

    <div class="row g-4">
        <!-- 1. IDENTIDAD INSTITUCIONAL Y MOTOR IA DEEPSEEK -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-university text-primary me-2"></i>1. Identidad Institucional y Motor IA</h5>
                    <?php if (!empty($config['deepseek_api_key'])): ?>
                        <span class="badge bg-success">IA Conectada</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Sin API Key</span>
                    <?php endif; ?>
                </div>
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label small fw-semibold">Nombre de la Institución</label>
                        <input type="text" name="nombre_tienda" class="form-control form-control-sm" maxlength="120" required
                               value="<?= SecurityKernel::e($config['nombre_tienda']) ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold">Nombre del Asesor IA</label>
                        <input type="text" name="nombre_asesor" class="form-control form-control-sm" maxlength="80" required
                               value="<?= SecurityKernel::e($config['nombre_asesor']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Teléfonos Fijos</label>
                        <input type="text" name="contacto_telefono" class="form-control form-control-sm" maxlength="40"
                               value="<?= SecurityKernel::e($config['contacto_telefono']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">WhatsApp Oficial</label>
                        <input type="text" name="whatsapp_admisiones" class="form-control form-control-sm" maxlength="15"
                               placeholder="59179795778" value="<?= SecurityKernel::e($config['whatsapp_admisiones']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Correo Institucional</label>
                        <input type="email" name="contacto_email" class="form-control form-control-sm" maxlength="120"
                               value="<?= SecurityKernel::e($config['contacto_email']) ?>">
                    </div>
                    <hr class="my-2">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Clave API de DeepSeek (Cifrada en BD)</label>
                        <input type="password" name="deepseek_api_key" class="form-control form-control-sm"
                               placeholder="<?= !empty($config['deepseek_api_key']) ? SecurityKernel::e(SecurityKernel::maskSecret($config['deepseek_api_key'])) . ' (Dejar vacío para conservar)' : 'sk-...' ?>">
                        <div class="form-text" style="font-size: 0.72rem;">
                            Actual: <code><?= !empty($config['deepseek_api_key']) ? SecurityKernel::e(SecurityKernel::maskSecret($config['deepseek_api_key'])) : 'No configurada' ?></code>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Modelo LLM</label>
                        <input type="text" name="deepseek_model" class="form-control form-control-sm" maxlength="60"
                               value="<?= SecurityKernel::e($config['deepseek_model'] ?: 'deepseek-chat') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. AUTOMATIZACIÓN N8N Y WEBHOOK DE PROSPECTOS -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-project-diagram text-info me-2"></i>2. Automatización n8n y Webhook CRM</h5>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="lead_webhook_enabled" name="lead_webhook_enabled" value="1"
                               <?= ($config['lead_webhook_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small fw-semibold" for="lead_webhook_enabled">Activo</label>
                    </div>
                </div>
                <p class="text-muted small mb-2">
                    Envía automáticamente cada estudiante captado en el chat o formulario hacia n8n, Make, Zapier o el CRM de la universidad.
                </p>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold">URL del Webhook de destino (n8n / CRM)</label>
                        <input type="url" name="lead_webhook_url" class="form-control form-control-sm"
                               placeholder="https://tu-n8n.com/webhook/prospectos-ulat"
                               value="<?= SecurityKernel::e($config['lead_webhook_url']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Firma Secreta Webhook (HMAC)</label>
                        <input type="password" name="lead_webhook_secret" class="form-control form-control-sm"
                               placeholder="<?= !empty($config['lead_webhook_secret']) ? SecurityKernel::e(SecurityKernel::maskSecret($config['lead_webhook_secret'])) : 'Opcional (X-ULAT-Signature)' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Clave API Externa (Para n8n entrante)</label>
                        <input type="password" name="chat_external_api_key" class="form-control form-control-sm"
                               placeholder="<?= !empty($config['chat_external_api_key']) ? SecurityKernel::e(SecurityKernel::maskSecret($config['chat_external_api_key'])) : 'X-API-Key de chat_externo.php' ?>">
                    </div>
                    <div class="col-12">
                        <div class="p-2 bg-light rounded-3 border small">
                            <div class="fw-semibold text-dark mb-1">Endpoint listo para conectar n8n o Meta:</div>
                            <code class="text-primary user-select-all"><?= SecurityKernel::e($webhookEndpointUrl) ?></code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. WHATSAPP BUSINESS CLOUD API (META DIRECTO) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fab fa-whatsapp text-success me-2"></i>3. WhatsApp Business Cloud API</h5>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="whatsapp_cloud_enabled" name="whatsapp_cloud_enabled" value="1"
                               <?= ($config['whatsapp_cloud_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small fw-semibold" for="whatsapp_cloud_enabled">Activo</label>
                    </div>
                </div>
                <p class="text-muted small mb-2">
                    Permite responder mensajes de WhatsApp directo desde este sistema (sin requerir n8n) y avisar al celular del encargado cuando entra un nuevo postulante.
                </p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone Number ID (Meta)</label>
                        <input type="text" name="whatsapp_phone_id" class="form-control form-control-sm"
                               placeholder="Ej. 109283746512345" value="<?= SecurityKernel::e($config['whatsapp_phone_id']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">WhatsApp Business Account ID</label>
                        <input type="text" name="whatsapp_business_id" class="form-control form-control-sm"
                               placeholder="Opcional" value="<?= SecurityKernel::e($config['whatsapp_business_id']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Access Token Permanente (Meta)</label>
                        <input type="password" name="whatsapp_access_token" class="form-control form-control-sm"
                               placeholder="<?= !empty($config['whatsapp_access_token']) ? SecurityKernel::e(SecurityKernel::maskSecret($config['whatsapp_access_token'])) : 'EAAG...' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Verify Token del Webhook</label>
                        <input type="password" name="whatsapp_verify_token" class="form-control form-control-sm"
                               placeholder="<?= !empty($config['whatsapp_verify_token']) ? SecurityKernel::e(SecurityKernel::maskSecret($config['whatsapp_verify_token'])) : 'Token para Meta Developers' ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Celular del Admin para recibir alertas de nuevos prospectos</label>
                        <input type="text" name="whatsapp_notify_admin_phone" class="form-control form-control-sm"
                               placeholder="Ej. 59179795778" value="<?= SecurityKernel::e($config['whatsapp_notify_admin_phone']) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. ALERTAS POR CORREO ELECTRÓNICO (SMTP NATIVO) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-envelope-open-text text-warning me-2"></i>4. Alertas por Correo (SMTP)</h5>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="notify_email_enabled" name="notify_email_enabled" value="1"
                               <?= ($config['notify_email_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small fw-semibold" for="notify_email_enabled">Activo</label>
                    </div>
                </div>
                <p class="text-muted small mb-2">
                    Envía un correo automático a Admisiones cada vez que un estudiante deja su nombre y celular en el chatbot o en el formulario web.
                </p>
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label small fw-semibold">Enviar alertas al correo</label>
                        <input type="email" name="notify_email_to" class="form-control form-control-sm"
                               placeholder="admisiones@ulat.edu.bo" value="<?= SecurityKernel::e($config['notify_email_to']) ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold">Nombre Remitente</label>
                        <input type="text" name="smtp_from_name" class="form-control form-control-sm"
                               value="<?= SecurityKernel::e($config['smtp_from_name'] ?: 'Admisiones ULAT') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Servidor SMTP</label>
                        <input type="text" name="smtp_host" class="form-control form-control-sm"
                               placeholder="smtp.gmail.com" value="<?= SecurityKernel::e($config['smtp_host']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Puerto</label>
                        <input type="number" name="smtp_port" class="form-control form-control-sm"
                               value="<?= SecurityKernel::e($config['smtp_port'] ?: '587') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Cifrado</label>
                        <select name="smtp_encryption" class="form-select form-select-sm">
                            <option value="tls" <?= ($config['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>STARTTLS (587)</option>
                            <option value="ssl" <?= ($config['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL/TLS (465)</option>
                            <option value="none" <?= ($config['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>Sin cifrado</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Usuario SMTP / Correo</label>
                        <input type="text" name="smtp_user" class="form-control form-control-sm"
                               placeholder="notificaciones@ulat.edu.bo" value="<?= SecurityKernel::e($config['smtp_user']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Contraseña / App Password SMTP</label>
                        <input type="password" name="smtp_pass" class="form-control form-control-sm"
                               placeholder="<?= !empty($config['smtp_pass']) ? SecurityKernel::e(SecurityKernel::maskSecret($config['smtp_pass'])) : 'Contraseña de aplicación' ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button type="submit" class="btn btn-primary px-4 fw-semibold">
            <i class="fas fa-save me-1"></i> Guardar Toda la Configuración
        </button>
    </div>
</form>

<!-- BOTÓN DE PRUEBA EN VIVO DE NOTIFICACIONES -->
<div class="mt-2">
    <form method="POST" class="d-inline">
        <?= SecurityKernel::csrfInput() ?>
        <input type="hidden" name="accion_config" value="probar_notificaciones">
        <button type="submit" class="btn btn-outline-secondary btn-sm fw-semibold">
            <i class="fas fa-paper-plane me-1"></i> Probar Disparo de Alertas (Webhook / Correo / WhatsApp)
        </button>
    </form>
</div>

<hr class="my-4">

<!-- 5. GESTIÓN DE BASE DE CONOCIMIENTO RAG Y REGLAMENTOS OFICIALES (SIN TERMINAL) -->
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-book-reader text-primary me-2"></i>5. Base de Conocimiento RAG y Reglamentos</h5>
                <form method="POST" class="m-0">
                    <?= SecurityKernel::csrfInput() ?>
                    <input type="hidden" name="accion_config" value="reindexar_rag">
                    <button type="submit" class="btn btn-sm btn-success fw-semibold">
                        <i class="fas fa-sync-alt me-1"></i> Reindexar Conocimiento Ahora
                    </button>
                </form>
            </div>
            <p class="text-muted small mb-3">
                Sube nuevos reglamentos oficiales en PDF o documentos Markdown (<code>.md</code>). El sistema valida su firma binaria y actualiza automáticamente el cerebro del chatbot sin necesidad de usar la terminal.
            </p>

            <form method="POST" enctype="multipart/form-data" class="row g-2 align-items-center mb-3 p-3 bg-light rounded-3 border">
                <?= SecurityKernel::csrfInput() ?>
                <input type="hidden" name="accion_config" value="subir_documento_rag">
                <div class="col-sm-8">
                    <input type="file" name="documento_rag" class="form-control form-control-sm" accept=".pdf,.md" required>
                </div>
                <div class="col-sm-4 d-grid">
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                        <i class="fas fa-upload me-1"></i> Subir e Indexar
                    </button>
                </div>
            </form>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php foreach ($ragStats['por_coleccion'] as $col => $cant): ?>
                    <span class="badge bg-light text-dark border px-2 py-1">
                        <?= SecurityKernel::e(ucfirst((string)$col)) ?>: <strong><?= (int)$cant ?></strong> chunks
                    </span>
                <?php endforeach; ?>
            </div>

            <div class="table-responsive" style="max-height: 210px;">
                <table class="table table-sm table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Documento Oficial en <code>knowledge/ulat_docs/</code></th>
                            <th>Formato</th>
                            <th class="text-end">Tamaño</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($docsArchivos as $doc): ?>
                            <tr>
                                <td class="fw-medium"><?= SecurityKernel::e($doc['nombre']) ?></td>
                                <td><span class="badge <?= $doc['tipo'] === 'PDF' ? 'bg-danger' : 'bg-secondary' ?>"><?= SecurityKernel::e($doc['tipo']) ?></span></td>
                                <td class="text-end text-muted"><?= number_format($doc['kb'], 1) ?> KB</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 6. SNIPPET DE INTEGRACIÓN WEB Y PLANTILLA N8N -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h5 class="fw-bold mb-2"><i class="fas fa-code text-dark me-2"></i>6. Integración Rápida en Portal Web</h5>
            <p class="text-muted small mb-3">
                Para incrustar a <strong><?= SecurityKernel::e($config['nombre_asesor']) ?></strong> en cualquier sitio web de la universidad, solo incluye el widget antes de cerrar la etiqueta <code>&lt;/body&gt;</code>:
            </p>
            <pre class="bg-dark text-light p-3 rounded-3 small mb-3" style="font-size: 0.76rem; overflow-x: auto;"><code>&lt;!-- Widget Asistente IA Admisiones ULAT --&gt;
&lt;?php include 'ulat/chat_widget_ulat.php'; ?&gt;</code></pre>

            <h6 class="fw-bold small mb-2">Resumen de Estado de Canales:</h6>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span>Widget Web Portal ULAT</span>
                    <span class="badge bg-success rounded-pill">Activo</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span>Guardado Automático en BD (<code>admisiones_prospectos</code>)</span>
                    <span class="badge bg-success rounded-pill">Activo</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span>Webhook Saliente n8n / CRM</span>
                    <span class="badge <?= ($config['lead_webhook_enabled'] ?? '0') === '1' ? 'bg-success' : 'bg-secondary' ?> rounded-pill">
                        <?= ($config['lead_webhook_enabled'] ?? '0') === '1' ? 'Activo' : 'Inactivo' ?>
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span>WhatsApp Business Cloud API Directo</span>
                    <span class="badge <?= ($config['whatsapp_cloud_enabled'] ?? '0') === '1' ? 'bg-success' : 'bg-secondary' ?> rounded-pill">
                        <?= ($config['whatsapp_cloud_enabled'] ?? '0') === '1' ? 'Activo' : 'Inactivo' ?>
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span>Alertas por Correo Electrónico (SMTP)</span>
                    <span class="badge <?= ($config['notify_email_enabled'] ?? '0') === '1' ? 'bg-success' : 'bg-secondary' ?> rounded-pill">
                        <?= ($config['notify_email_enabled'] ?? '0') === '1' ? 'Activo' : 'Inactivo' ?>
                    </span>
                </li>
            </ul>
        </div>
    </div>
</div>
<?php
$contenido = ob_get_clean();
$titulo = 'Centro de Configuración e Integraciones';
require __DIR__ . '/layout.php';
