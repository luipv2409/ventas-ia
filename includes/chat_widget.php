<?php
// includes/chat_widget.php
// Widget de Chat Flotante reutilizable en todas las páginas públicas
$nombreAsesorWidget = !empty($nombreAsesor) ? $nombreAsesor : (!empty($config['nombre_asesor']) ? $config['nombre_asesor'] : 'Valeria');
?>
<!-- Widget de Chat Flotante (Valeria) -->
<div id="chat-widget" class="chat-widget">
    <button id="chat-toggle" class="chat-toggle-btn d-flex align-items-center justify-content-center" title="Atención y soporte">
        <i data-lucide="message-square" style="width: 22px; height: 22px;"></i>
        <span class="online-aura"></span>
    </button>
    <div id="chat-window" class="chat-window d-none">
        <div class="chat-header">
            <div class="d-flex align-items-center gap-2">
                <div class="chat-avatar position-relative">
                    <?= htmlspecialchars(mb_substr($nombreAsesorWidget, 0, 1)) ?>
                    <span class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-white" style="width: 10px; height: 10px;"></span>
                </div>
                <div>
                    <div class="fw-bold lh-1" style="font-size: 0.95rem;"><?= htmlspecialchars($nombreAsesorWidget) ?> · Ventas</div>
                    <small class="text-light text-opacity-75" style="font-size: 0.72rem;">En línea • Atención al cliente</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" id="chat-reset" class="btn btn-sm btn-link text-white-50 p-1 text-decoration-none d-flex align-items-center justify-content-center" title="Nueva conversación">
                    <i data-lucide="rotate-ccw" style="width: 14px; height: 14px;"></i>
                </button>
                <button type="button" id="chat-close" class="btn-close btn-close-white" aria-label="Cerrar"></button>
            </div>
        </div>
        <div id="chat-messages" class="chat-messages">
            <div class="message bot-message">
                <div class="message-content">¡Hola! ¿En qué puedo ayudarte hoy? Puedes consultarme sobre productos, precios o cualquier duda de compra.</div>
            </div>
        </div>
        <div class="p-2 bg-white border-top">
            <form id="chat-form" class="d-flex gap-2">
                <input type="text" id="chat-input" class="form-control rounded-pill px-3" placeholder="Escribe tu consulta..." autocomplete="off" required>
                <button type="submit" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i data-lucide="send" style="width: 16px; height: 16px;"></i>
                </button>
            </form>
        </div>
    </div>
</div>
