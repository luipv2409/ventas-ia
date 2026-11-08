<?php
// ulat/chat_widget_ulat.php
// Asistente Virtual Minimalista de Admisiones: Guía ULAT (DOM-Safe & Server-Canonical History)
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
        <!-- Chips de acceso rápido minimalistas (sin atributos onclick inline) -->
        <div class="px-3 py-2 bg-white border-top d-flex gap-1 overflow-auto" style="scrollbar-width: none;">
            <button type="button" class="chat-chip" data-chip-prompt="¿Qué ingenierías tienen?">5 Ingenierías</button>
            <button type="button" class="chat-chip" data-chip-prompt="¿En qué consiste el Plan Uno a Uno?">Plan Uno a Uno</button>
            <button type="button" class="chat-chip" data-chip-prompt="¿Cuáles son los requisitos de admisión?">Requisitos</button>
            <button type="button" class="chat-chip" data-chip-prompt="¿Qué carreras de salud ofrecen?">Salud</button>
        </div>
        <div class="p-2 bg-white border-top">
            <form id="chat-form" class="d-flex gap-2">
                <input type="text" id="chat-input" class="form-control form-control-sm px-3 border-0 bg-light" maxlength="1000" style="border-radius: 4px; font-size: 0.84rem;" placeholder="Escribe tu consulta..." autocomplete="off" required>
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

    const STORAGE_KEY = 'ulat_chat_session_v3';
    let historialUI = [];

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function esUrlWhatsAppSegura(url) {
        return typeof url === 'string' && /^https:\/\/(wa\.me|api\.whatsapp\.com)\//i.test(url);
    }

    function limpiarTextoIA(texto) {
        return String(texto)
            .replace(/```(?:json)?[\s\S]*?```/gi, '')
            .replace(/\{[^{}]*"(?:tipo|accion|slug|nombre|acciones|respuesta)"[^{}]*\}/gi, '')
            .replace(/\*\*(.*?)\*\*/g, '$1')
            .trim();
    }

    function guardarEstado() {
        try {
            // Nunca se almacena ni restaura HTML crudo en localStorage (previene DOM XSS persistente)
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                historial: historialUI.slice(-14),
                open: !chatWindow.classList.contains('d-none')
            }));
        } catch (e) {}
    }

    function renderizarBurbujaDOM(texto, emisor) {
        const limpio = emisor === 'bot' ? limpiarTextoIA(texto) : String(texto || '').trim();
        if (!limpio) return;
        const div = document.createElement('div');
        div.className = 'message ' + (emisor === 'user' ? 'user-message' : 'bot-message');
        const content = document.createElement('div');
        content.className = 'message-content';
        content.innerHTML = escapeHtml(limpio).replace(/\n/g, '<br>');
        div.appendChild(content);
        chatMessages.appendChild(div);
    }

    function restaurarEstado() {
        try {
            // Limpiar clave heredada v2 que guardaba innerHTML
            localStorage.removeItem('ulat_chat_session_v2');
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed.historial) && parsed.historial.length > 0) {
                chatMessages.textContent = '';
                historialUI = [];
                parsed.historial.slice(-14).forEach(turn => {
                    if (turn && (turn.role === 'user' || turn.role === 'assistant') && typeof turn.content === 'string') {
                        historialUI.push({ role: turn.role, content: turn.content });
                        renderizarBurbujaDOM(turn.content, turn.role === 'user' ? 'user' : 'bot');
                    }
                });
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

    chatReset.addEventListener('click', async () => {
        historialUI = [];
        localStorage.removeItem(STORAGE_KEY);
        chatMessages.textContent = '';
        renderizarBurbujaDOM('Conversación reiniciada. ¿Sobre qué carrera, malla curricular o beneficio de la ULAT te gustaría consultar?', 'bot');
        try {
            await fetch('../api/chat_frontend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reset: true }),
                credentials: 'same-origin'
            });
        } catch (e) {}
    });

    function agregarMensaje(texto, emisor) {
        renderizarBurbujaDOM(texto, emisor);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        guardarEstado();
    }

    function mostrarTyping() {
        const t = document.createElement('div');
        t.id = 'chat-typing';
        t.className = 'message bot-message text-muted small';
        t.textContent = 'Escribiendo...';
        chatMessages.appendChild(t);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function ocultarTyping() {
        document.getElementById('chat-typing')?.remove();
    }

    async function enviarPregunta(pregunta) {
        if (!pregunta) return;
        agregarMensaje(pregunta, 'user');
        historialUI.push({ role: 'user', content: pregunta });
        guardarEstado();
        chatInput.value = '';
        mostrarTyping();

        try {
            const resp = await fetch('../api/chat_frontend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    pregunta: pregunta,
                    historial: historialUI.slice(-10),
                    use_rag: true
                }),
                credentials: 'same-origin'
            });

            const data = await resp.json();
            ocultarTyping();

            if (data.ok && data.respuesta) {
                agregarMensaje(data.respuesta, 'bot');
                if (!data.degraded) {
                    historialUI.push({ role: 'assistant', content: limpiarTextoIA(data.respuesta) });
                } else if (historialUI.length > 0 && historialUI[historialUI.length - 1].role === 'user') {
                    historialUI.pop();
                }
                guardarEstado();

                if (data.acciones && Array.isArray(data.acciones)) {
                    data.acciones.forEach(acc => {
                        if (acc.tipo === 'mostrar_carrera' && typeof acc.slug === 'string' && /^[a-z0-9\-]+$/i.test(acc.slug)) {
                            const cardCarr = document.createElement('div');
                            cardCarr.className = 'message bot-message p-2';

                            const row = document.createElement('div');
                            row.className = 'd-flex justify-content-between align-items-center gap-3 py-1 px-1';

                            const title = document.createElement('div');
                            title.className = 'small fw-medium text-dark';
                            title.textContent = acc.nombre || 'Ver Carrera';

                            const link = document.createElement('a');
                            link.href = 'carrera.php?id=' + encodeURIComponent(acc.slug);
                            link.className = 'btn btn-ulat-primary btn-sm py-1 px-2';
                            link.style.fontSize = '0.73rem';
                            link.textContent = 'Malla ↗';

                            row.appendChild(title);
                            row.appendChild(link);
                            cardCarr.appendChild(row);
                            chatMessages.appendChild(cardCarr);
                        } else if (acc.tipo === 'derivar_whatsapp' && esUrlWhatsAppSegura(acc.url)) {
                            const waBtn = document.createElement('div');
                            waBtn.className = 'message bot-message p-2';

                            const link = document.createElement('a');
                            link.href = acc.url;
                            link.target = '_blank';
                            link.rel = 'noopener noreferrer';
                            link.className = 'btn btn-sm btn-ulat-outline w-100 d-flex align-items-center justify-content-center gap-2';
                            link.style.fontSize = '0.78rem';
                            link.textContent = 'Abrir WhatsApp Admisiones ↗';

                            waBtn.appendChild(link);
                            chatMessages.appendChild(waBtn);
                        } else if (acc.tipo === 'registrar_prospecto') {
                            const regInfo = document.createElement('div');
                            regInfo.className = 'message bot-message p-2 small fw-medium text-success';
                            regInfo.textContent = 'Datos registrados para Admisiones (' + String(acc.carrera || 'ULAT') + ').';
                            chatMessages.appendChild(regInfo);
                        }
                    });
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            } else {
                agregarMensaje(data.error || 'Perdón, se cortó un momento la conexión. ¿Me repites tu consulta o nos escribes al WhatsApp 79795778?', 'bot');
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
        enviarPregunta(String(texto || '').trim());
    };

    window.preguntarAlChat = function(consultaTexto) {
        if (chatWindow.classList.contains('d-none')) {
            chatWindow.classList.remove('d-none');
        }
        const raw = String(consultaTexto || '').trim();
        const msg = raw.startsWith('¿') || raw.startsWith('Cuéntame') || raw.startsWith('Hola')
            ? raw
            : 'Quisiera información sobre ' + raw + ' y su malla curricular';
        enviarPregunta(msg);
    };

    // Delegación segura de eventos para chips y botones académicos (sin onclick inline)
    document.addEventListener('click', (ev) => {
        const chipBtn = ev.target.closest('[data-chip-prompt]');
        if (chipBtn) {
            ev.preventDefault();
            window.enviarChipChat(chipBtn.getAttribute('data-chip-prompt') || '');
            return;
        }
        const askBtn = ev.target.closest('[data-chat-prompt]');
        if (askBtn) {
            ev.preventDefault();
            window.preguntarAlChat(askBtn.getAttribute('data-chat-prompt') || '');
        }
    });
});
</script>
