<?php
// ulat/chat_widget_ulat.php
// Asistente Virtual Inteligente de Admisiones: Guía ULAT
?>
<!-- Widget Flotante de Admisiones ULAT -->
<div id="chat-widget" class="chat-widget">
    <button id="chat-toggle" class="chat-toggle-btn d-flex align-items-center justify-content-center" title="Asistente de Admisiones ULAT">
        <i class="fa-solid fa-comments fs-4"></i>
        <span class="online-aura"></span>
    </button>
    <div id="chat-window" class="chat-window d-none">
        <div class="chat-header">
            <div class="d-flex align-items-center gap-2">
                <div class="chat-avatar position-relative">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-white" style="width: 10px; height: 10px;"></span>
                </div>
                <div>
                    <div class="fw-bold lh-1" style="font-size: 0.95rem;">Guía ULAT · Admisiones</div>
                    <small class="text-light text-opacity-75" style="font-size: 0.72rem;">En línea • Orientación y Carreras</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" id="chat-reset" class="btn btn-sm btn-link text-white-50 p-1 text-decoration-none" title="Reiniciar conversación">
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
                <button type="button" id="chat-close" class="btn-close btn-close-white" aria-label="Cerrar"></button>
            </div>
        </div>
        <div id="chat-messages" class="chat-messages">
            <div class="message bot-message">
                <div class="message-content">
                    ¡Hola! Soy <strong>Guía ULAT</strong>, tu asesor virtual de la Universidad Latinoamericana 🎓
                    <br><br>
                    ¿En qué carrera estás interesado o sobre qué requisitos, becas o mallas curriculares deseas información hoy?
                </div>
            </div>
        </div>
        <div class="p-2 bg-white border-top">
            <form id="chat-form" class="d-flex gap-2">
                <input type="text" id="chat-input" class="form-control rounded-pill px-3" placeholder="Pregunta sobre carreras, becas..." autocomplete="off" required>
                <button type="submit" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: var(--ulat-blue-primary); border: none;">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const chatToggle = document.getElementById('chat-toggle');
    const chatWindow = document.getElementById('chat-window');
    const chatClose = document.getElementById('chat-close');
    const chatReset = document.getElementById('chat-reset');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const chatMessages = document.getElementById('chat-messages');

    if (!chatToggle || !chatWindow || !chatForm || !chatInput || !chatMessages) return;

    let historialIA = [];

    // Abrir/cerrar
    chatToggle.addEventListener('click', () => {
        chatWindow.classList.toggle('d-none');
        if (!chatWindow.classList.contains('d-none')) {
            chatInput.focus();
        }
    });

    chatClose.addEventListener('click', () => {
        chatWindow.classList.add('d-none');
    });

    chatReset.addEventListener('click', () => {
        historialIA = [];
        chatMessages.innerHTML = `
            <div class="message bot-message">
                <div class="message-content">
                    ¡Conversación reiniciada! ¿Sobre qué carrera, requisito o beneficio de la ULAT te gustaría consultar?
                </div>
            </div>
        `;
    });

    function agregarMensaje(texto, emisor) {
        const div = document.createElement('div');
        div.className = `message ${emisor}-message`;
        div.innerHTML = `<div class="message-content">${escapeHtml(texto).replace(/\n/g, '<br>')}</div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function mostrarTyping() {
        const t = document.createElement('div');
        t.id = 'chat-typing';
        t.className = 'message bot-message text-muted fst-italic';
        t.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-2"></i> Guía ULAT está respondiendo...';
        chatMessages.appendChild(t);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function ocultarTyping() {
        const t = document.getElementById('chat-typing');
        if (t) t.remove();
    }

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const pregunta = chatInput.value.trim();
        if (pregunta === '') return;

        agregarMensaje(pregunta, 'user');
        historialIA.push({ role: 'user', content: pregunta });
        chatInput.value = '';
        mostrarTyping();

        try {
            const resp = await fetch('../api/chat_frontend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    pregunta: pregunta,
                    historial: historialIA.slice(-10),
                    use_rag: true
                })
            });

            const data = await resp.json();
            ocultarTyping();

            if (data.ok && data.respuesta) {
                agregarMensaje(data.respuesta, 'bot');
                historialIA.push({ role: 'assistant', content: data.respuesta });

                // Acciones especiales emitidas por la IA
                if (data.acciones && Array.isArray(data.acciones)) {
                    data.acciones.forEach(acc => {
                        if (acc.tipo === 'mostrar_carrera' && acc.slug) {
                            const cardCarr = document.createElement('div');
                            cardCarr.className = 'message bot-message p-2 border-primary';
                            cardCarr.innerHTML = `
                                <div class="card card-sm border-0 bg-light p-2 text-center">
                                    <div class="fw-bold text-primary mb-1">🎓 ${escapeHtml(acc.nombre || 'Carrera')}</div>
                                    <a href="carrera.php?id=${encodeURIComponent(acc.slug)}" class="btn btn-sm btn-outline-primary rounded-pill mt-1">Ver Malla y Perfil</a>
                                </div>
                            `;
                            chatMessages.appendChild(cardCarr);
                        } else if (acc.tipo === 'derivar_whatsapp' && acc.url) {
                            const waBtn = document.createElement('div');
                            waBtn.className = 'message bot-message p-2';
                            waBtn.innerHTML = `
                                <a href="${escapeHtml(acc.url)}" target="_blank" class="btn btn-sm btn-success w-100 rounded-pill d-flex align-items-center justify-content-center gap-2">
                                    <i class="fa-brands fa-whatsapp fs-5"></i> Contactar por WhatsApp
                                </a>
                            `;
                            chatMessages.appendChild(waBtn);
                        } else if (acc.tipo === 'registrar_prospecto') {
                            const regInfo = document.createElement('div');
                            regInfo.className = 'message bot-message p-2 text-success fw-bold';
                            regInfo.innerHTML = `✔ ¡Tus datos fueron registrados para el equipo de admisiones de ${escapeHtml(acc.carrera)}!`;
                            chatMessages.appendChild(regInfo);
                        }
                    });
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            } else {
                agregarMensaje('Disculpa, ocurrió un detalle al conectar con el servidor. Puedes escribirnos directamente al WhatsApp oficial: (4) 4509488.', 'bot');
            }
        } catch (err) {
            ocultarTyping();
            agregarMensaje('Error de conexión. Por favor reintenta o comunícate al (4) 4509488.', 'bot');
        }
    });

    // Función global para consultar desde botones en las páginas de carreras
    window.preguntarAlChat = function(carreraNombre) {
        if (chatWindow.classList.contains('d-none')) {
            chatWindow.classList.remove('d-none');
        }
        chatInput.value = `Hola, quisiera más información sobre la carrera de ${carreraNombre}, duración y campo laboral.`;
        chatInput.focus();
    };
});
</script>

