<?php
// ulat/chat_widget_ulat.php
// Asistente Virtual Minimalista de Admisiones: Guía ULAT
?>
<div id="chat-widget" class="chat-widget">
    <button id="chat-toggle" class="chat-toggle-btn d-flex align-items-center justify-content-center" title="Consultar con Guía ULAT">
        <span class="online-aura"></span>
        <span>Guía ULAT · Orientación</span>
    </button>
    <div id="chat-window" class="chat-window d-none">
        <div class="chat-header">
            <div class="d-flex align-items-center gap-2">
                <div class="chat-avatar">ULAT</div>
                <div>
                    <div class="fw-medium lh-1" style="font-size: 0.86rem;">Guía ULAT</div>
                    <small class="text-white-50" style="font-size: 0.7rem;">Admisiones y Orientación Vocacional</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" id="chat-reset" class="btn btn-sm btn-link text-white-50 p-1 text-decoration-none" title="Reiniciar conversación">
                    <i data-lucide="rotate-ccw" class="lucide-sm"></i>
                </button>
                <button type="button" id="chat-close" class="btn-close btn-close-white" style="font-size: 0.68rem;" aria-label="Cerrar"></button>
            </div>
        </div>
        <div id="chat-messages" class="chat-messages">
            <div class="message bot-message">
                <div class="message-content">
                    Hola, soy <strong>Guía ULAT</strong>, del equipo de admisiones de la Universidad Latinoamericana.
                    <br><br>
                    ¿Sobre qué carrera, malla curricular, beca o requisito te gustaría conversar?
                </div>
            </div>
        </div>
        <!-- Chips de acceso rápido minimalistas -->
        <div class="px-3 py-2 bg-white border-top d-flex gap-1 overflow-auto" style="scrollbar-width: none;">
            <button type="button" class="chat-chip" onclick="window.enviarChipChat('¿Qué ingenierías tienen?')">5 Ingenierías</button>
            <button type="button" class="chat-chip" onclick="window.enviarChipChat('¿En qué consiste el Plan Uno a Uno?')">Plan Uno a Uno</button>
            <button type="button" class="chat-chip" onclick="window.enviarChipChat('¿Cuáles son los requisitos de admisión?')">Requisitos</button>
            <button type="button" class="chat-chip" onclick="window.enviarChipChat('¿Qué carreras de salud ofrecen?')">Salud</button>
        </div>
        <div class="p-2 bg-white border-top">
            <form id="chat-form" class="d-flex gap-2">
                <input type="text" id="chat-input" class="form-control form-control-sm px-3 border-0 bg-light" style="border-radius: 4px; font-size: 0.84rem;" placeholder="Escribe tu consulta..." autocomplete="off" required>
                <button type="submit" class="btn btn-ulat-primary btn-sm px-3 d-flex align-items-center justify-content-center" style="border-radius: 4px;">
                    <i data-lucide="arrow-up" class="lucide-sm"></i>
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

    const STORAGE_KEY = 'ulat_chat_session_v2';
    let historialIA = [];

    function guardarEstado() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                historial: historialIA.slice(-14),
                html: chatMessages.innerHTML,
                open: !chatWindow.classList.contains('d-none')
            }));
        } catch (e) {}
    }

    function restaurarEstado() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed.historial) && parsed.historial.length > 0 && parsed.html) {
                historialIA = parsed.historial;
                chatMessages.innerHTML = parsed.html;
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            if (parsed.open) {
                chatWindow.classList.remove('d-none');
            }
        } catch (e) {}
    }

    restaurarEstado();

    chatToggle.addEventListener('click', () => {
        chatWindow.classList.toggle('d-none');
        if (!chatWindow.classList.contains('d-none')) {
            chatInput.focus();
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        guardarEstado();
    });

    chatClose.addEventListener('click', () => {
        chatWindow.classList.add('d-none');
        guardarEstado();
    });

    chatReset.addEventListener('click', () => {
        historialIA = [];
        localStorage.removeItem(STORAGE_KEY);
        chatMessages.innerHTML = `
            <div class="message bot-message">
                <div class="message-content">
                    Conversación reiniciada. ¿Sobre qué carrera, malla curricular o beneficio de la ULAT te gustaría consultar?
                </div>
            </div>
        `;
    });

    function escapeHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function limpiarTextoIA(texto) {
        return String(texto)
            .replace(/```(?:json)?[\s\S]*?```/gi, '')
            .replace(/\{[^{}]*"(?:tipo|accion|slug|nombre|acciones|respuesta)"[^{}]*\}/gi, '')
            .replace(/\*\*(.*?)\*\*/g, '$1')
            .trim();
    }

    function agregarMensaje(texto, emisor) {
        const limpio = emisor === 'bot' ? limpiarTextoIA(texto) : texto;
        if (!limpio) return;
        const div = document.createElement('div');
        div.className = `message ${emisor}-message`;
        div.innerHTML = `<div class="message-content">${escapeHtml(limpio).replace(/\n/g, '<br>')}</div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        guardarEstado();
    }

    function mostrarTyping() {
        const t = document.createElement('div');
        t.id = 'chat-typing';
        t.className = 'message bot-message text-muted small';
        t.innerHTML = 'Escribiendo...';
        chatMessages.appendChild(t);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function ocultarTyping() {
        const t = document.getElementById('chat-typing');
        if (t) t.remove();
    }

    async function enviarPregunta(pregunta) {
        if (!pregunta) return;
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
                historialIA.push({ role: 'assistant', content: limpiarTextoIA(data.respuesta) });

                if (data.acciones && Array.isArray(data.acciones)) {
                    data.acciones.forEach(acc => {
                        if (acc.tipo === 'mostrar_carrera' && acc.slug) {
                            const cardCarr = document.createElement('div');
                            cardCarr.className = 'message bot-message p-2';
                            cardCarr.innerHTML = `
                                <div class="d-flex justify-content-between align-items-center gap-3 py-1 px-1">
                                    <div class="small fw-medium text-dark">${escapeHtml(acc.nombre || 'Ver Carrera')}</div>
                                    <a href="carrera.php?id=${encodeURIComponent(acc.slug)}" class="btn btn-ulat-primary btn-sm py-1 px-2" style="font-size: 0.73rem;">Malla ↗</a>
                                </div>
                            `;
                            chatMessages.appendChild(cardCarr);
                        } else if (acc.tipo === 'derivar_whatsapp' && acc.url) {
                            const waBtn = document.createElement('div');
                            waBtn.className = 'message bot-message p-2';
                            waBtn.innerHTML = `
                                <a href="${escapeHtml(acc.url)}" target="_blank" class="btn btn-sm btn-ulat-outline w-100 d-flex align-items-center justify-content-center gap-2" style="font-size: 0.78rem;">
                                    <span>Abrir WhatsApp Admisiones ↗</span>
                                </a>
                            `;
                            chatMessages.appendChild(waBtn);
                        } else if (acc.tipo === 'registrar_prospecto') {
                            const regInfo = document.createElement('div');
                            regInfo.className = 'message bot-message p-2 small fw-medium text-success';
                            regInfo.innerHTML = `Datos registrados para Admisiones (${escapeHtml(acc.carrera || 'ULAT')}).`;
                            chatMessages.appendChild(regInfo);
                        }
                    });
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                    guardarEstado();
                }
            } else {
                agregarMensaje('Perdón, se cortó un momento la conexión. ¿Me repites tu consulta o nos escribes al WhatsApp 79795778?', 'bot');
            }
        } catch (err) {
            ocultarTyping();
            agregarMensaje('Tuvimos un detalle de red. Por favor intenta de nuevo o escríbenos al WhatsApp 79795778.', 'bot');
        }
    }

    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        enviarPregunta(chatInput.value.trim());
    });

    window.enviarChipChat = function(texto) {
        if (chatWindow.classList.contains('d-none')) {
            chatWindow.classList.remove('d-none');
        }
        enviarPregunta(texto);
    };

    window.preguntarAlChat = function(consultaTexto) {
        if (chatWindow.classList.contains('d-none')) {
            chatWindow.classList.remove('d-none');
        }
        const msg = consultaTexto.startsWith('¿') || consultaTexto.startsWith('Cuéntame') || consultaTexto.startsWith('Hola')
            ? consultaTexto
            : `Quisiera información sobre ${consultaTexto} y su malla curricular`;
        enviarPregunta(msg);
    };
});
</script>
