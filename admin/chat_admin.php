<?php
require_once __DIR__ . '/../config/database.php';

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="fw-bold mb-1 text-dark"><i class="fas fa-brain text-primary"></i> Jarvis — Inteligencia de Negocios (BI)</h2>
        <p class="text-secondary small mb-0">Asesor analítico de operaciones, toma de decisiones, reportes descargables y base de conocimiento RAG.</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-success text-white d-flex align-items-center gap-2 px-3 py-2 shadow-sm rounded-pill fw-semibold">
            <span class="spinner-grow spinner-grow-sm text-white" role="status" style="width: 8px; height: 8px;"></span>
            DeepSeek BI & RAG En Línea
        </span>
    </div>
</div>

<div class="row g-3">
    <!-- Columna Izquierda: Historial de Conversaciones Persistente -->
    <div class="col-lg-3 col-md-4">
        <div class="card shadow-sm border h-100" style="border-radius: 14px; background: #ffffff; border-color: #cbd5e1 !important;">
            <div class="card-header bg-white border-bottom pt-3 pb-2 d-flex justify-content-between align-items-center" style="border-color: #e2e8f0 !important;">
                <span class="fw-bold text-dark small"><i class="fas fa-clock-rotate-left text-primary me-1"></i> Sesiones y Consultas</span>
                <button type="button" id="btn-nuevo-hilo" class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-semibold d-flex align-items-center gap-1" title="Nueva Consulta">
                    <i class="fas fa-plus"></i> <span>Nueva</span>
                </button>
            </div>
            <div class="card-body p-2 bg-light-subtle">
                <div id="lista-hilos" class="list-group list-group-flush overflow-auto pe-1" style="max-height: 540px;">
                    <div class="text-center py-4 text-secondary small fw-medium">Cargando historial...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Consola de Chat & Analítica -->
    <div class="col-lg-9 col-md-8">
        <div class="card shadow-sm border d-flex flex-column" style="border-radius: 14px; height: 620px; background: #ffffff; border-color: #cbd5e1 !important;">
            <!-- Sugerencias rápidas -->
            <div class="p-2 px-3 border-bottom d-flex gap-2 overflow-x-auto align-items-center" style="background: #ffffff; border-radius: 14px 14px 0 0; border-color: #e2e8f0 !important;">
                <span class="small fw-bold text-dark text-nowrap me-1"><i class="fas fa-bolt text-warning me-1"></i> Acciones rápidas:</span>
                <button class="btn btn-sm chip-query text-nowrap" data-prompt="Realiza un análisis estratégico y diagnóstico de stock del negocio">
                    <i class="fas fa-chart-line text-primary me-1"></i> Diagnóstico BI
                </button>
                <button class="btn btn-sm chip-query text-nowrap" data-prompt="Genera y prepara para descarga el reporte ejecutivo de ventas en CSV">
                    <i class="fas fa-file-csv text-success me-1"></i> Reporte Ventas CSV
                </button>
                <button class="btn btn-sm chip-query text-nowrap" data-prompt="Genera el reporte de inventario valorizado en CSV">
                    <i class="fas fa-boxes text-warning me-1"></i> Inventario Valorizado
                </button>
                <button class="btn btn-sm chip-query text-nowrap" data-prompt="Muéstrame un gráfico de ventas diarias de este mes">
                    <i class="fas fa-chart-bar text-info me-1"></i> Gráfico del Mes
                </button>
            </div>

            <!-- Contenedor de Mensajes -->
            <div id="chat-admin-messages" class="flex-grow-1 p-3 overflow-auto" style="background: #f1f5f9;">
                <div class="message-admin bot-admin mb-3">
                    <div class="msg-bubble bot-bubble">
                        <div class="d-flex align-items-center gap-2 mb-2 pb-1 border-bottom border-light-subtle">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white" style="width: 24px; height: 24px; font-size: 0.75rem;">
                                <i class="fas fa-robot"></i>
                            </span>
                            <span class="fw-bold text-primary">Jarvis BI — Asesor Ejecutivo</span>
                        </div>
                        <div class="bubble-text text-dark">
                            Hola Administrador. Tengo acceso en tiempo real a las ventas, stock valorizado, márgenes de productos y base de conocimiento RAG. ¿Qué decisión o reporte necesitas revisar hoy?
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input Bar -->
            <div class="p-3 border-top bg-white" style="border-radius: 0 0 14px 14px; border-color: #e2e8f0 !important;">
                <form id="form-chat-admin" class="d-flex gap-2">
                    <input type="text" id="input-admin" class="form-control rounded-pill px-3 py-2 border-2 text-dark fw-medium" placeholder="Pregunta sobre finanzas, stock, rentabilidad o pide un reporte descargable..." autocomplete="off" required style="border-color: #cbd5e1;">
                    <button type="submit" id="btn-enviar-admin" class="btn btn-primary rounded-pill px-4 fw-bold d-flex align-items-center gap-2 shadow-sm">
                        <span>Enviar</span> <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
/* Burbujas de Chat de Alto Contraste */
.msg-bubble {
    max-width: 85%;
    padding: 14px 18px;
    border-radius: 16px;
    font-size: 0.95rem;
    line-height: 1.55;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
}

.user-admin {
    display: flex;
    justify-content: flex-end;
}

.user-bubble {
    background: linear-gradient(135deg, #4f46e5, #3730a3);
    color: #ffffff !important;
    border-bottom-right-radius: 4px;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    font-weight: 500;
}

.bot-admin {
    display: flex;
    justify-content: flex-start;
}

.bot-bubble {
    background: #ffffff;
    color: #0f172a !important; /* Texto negro-slate nítido y de máximo contraste */
    border: 1.5px solid #cbd5e1;
    border-bottom-left-radius: 4px;
}

.bot-bubble .bubble-text {
    color: #0f172a !important;
    font-size: 0.94rem;
}

.bot-bubble strong {
    color: #0f172a !important;
    font-weight: 700;
}

/* Chips de Acciones Rápidas */
.chip-query {
    background: #ffffff;
    color: #0f172a;
    border: 1.5px solid #cbd5e1;
    border-radius: 9999px;
    font-size: 0.82rem;
    padding: 5px 13px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.chip-query:hover {
    background: #eef2ff;
    color: #4f46e5;
    border-color: #818cf8;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(99, 102, 241, 0.15);
}

/* Lista de Hilos (Sesiones) */
.hilo-item {
    cursor: pointer;
    border-radius: 10px !important;
    margin-bottom: 5px;
    transition: all 0.2s ease;
    border: 1.5px solid #e2e8f0 !important;
    background: #ffffff;
    padding: 9px 12px;
}

.hilo-item:hover {
    background: #f8fafc;
    border-color: #cbd5e1 !important;
    transform: translateX(2px);
}

.hilo-item.active {
    background: #eef2ff !important;
    border-color: #818cf8 !important;
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.12);
}

.hilo-title {
    color: #0f172a;
    font-weight: 600;
    font-size: 0.86rem;
}

.hilo-item.active .hilo-title {
    color: #3730a3;
    font-weight: 700;
}

.hilo-date {
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 500;
}

/* Tarjeta de Descarga de Reportes (Máxima Visibilidad) */
.reporte-card {
    background: #f0fdf4;
    border: 2px solid #86efac;
    border-radius: 14px;
    padding: 14px 18px;
    margin-top: 12px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.12);
}

.reporte-title {
    color: #14532d;
    font-weight: 800;
    font-size: 0.98rem;
}

.reporte-meta {
    color: #166534;
    font-size: 0.82rem;
    font-weight: 600;
}

.btn-descargar-reporte {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #ffffff;
    border: none;
    border-radius: 9999px;
    padding: 8px 18px;
    font-weight: 700;
    font-size: 0.9rem;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-descargar-reporte:hover {
    background: linear-gradient(135deg, #059669, #047857);
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);
}
</style>

<script>
let hiloActualId = null;
const chatMessages = document.getElementById('chat-admin-messages');
const formChat = document.getElementById('form-chat-admin');
const inputChat = document.getElementById('input-admin');
const listaHilos = document.getElementById('lista-hilos');
const btnNuevoHilo = document.getElementById('btn-nuevo-hilo');

// Cargar lista de hilos
async function cargarHilos() {
    try {
        const resp = await fetch('../api/chat_admin.php?action=listar_hilos');
        const data = await resp.json();
        if (data.ok && Array.isArray(data.hilos)) {
            listaHilos.innerHTML = '';
            if (data.hilos.length === 0) {
                listaHilos.innerHTML = '<div class="text-center py-4 text-secondary small fw-medium">No hay sesiones previas.</div>';
                return;
            }
            data.hilos.forEach(h => {
                const a = document.createElement('div');
                a.className = `list-group-item hilo-item d-flex justify-content-between align-items-center ${h.id == hiloActualId ? 'active' : ''}`;
                a.innerHTML = `
                    <div class="text-truncate me-2" style="max-width: 170px;">
                        <span class="d-block text-truncate hilo-title">${escapeHTML(h.titulo)}</span>
                        <span class="hilo-date">${h.fecha}</span>
                    </div>
                    <button class="btn btn-sm btn-link text-danger p-0 delete-hilo" data-id="${h.id}" title="Eliminar">&times;</button>
                `;
                a.addEventListener('click', (e) => {
                    if (e.target.classList.contains('delete-hilo')) return;
                    seleccionarHilo(h.id);
                });
                a.querySelector('.delete-hilo').addEventListener('click', (e) => {
                    e.stopPropagation();
                    eliminarHilo(h.id);
                });
                listaHilos.appendChild(a);
            });
        }
    } catch (e) {
        console.error('Error cargando hilos:', e);
    }
}

async function seleccionarHilo(hiloId) {
    hiloActualId = hiloId;
    document.querySelectorAll('.hilo-item').forEach(el => el.classList.remove('active'));
    cargarHilos();

    try {
        const resp = await fetch(`../api/chat_admin.php?action=cargar_hilo&hilo_id=${hiloId}`);
        const data = await resp.json();
        chatMessages.innerHTML = '';
        if (data.ok && Array.isArray(data.mensajes)) {
            data.mensajes.forEach(m => {
                if (m.role === 'user') {
                    agregarMensajeUser(m.content);
                } else {
                    renderizarMensajeBot(m.content, m.tipo, m.datos);
                }
            });
        }
        scrollAbajo();
    } catch (e) {
        console.error('Error al cargar mensajes del hilo:', e);
    }
}

async function eliminarHilo(id) {
    if (!confirm('¿Deseas eliminar este hilo de conversación?')) return;
    const fd = new FormData();
    fd.append('hilo_id', id);
    await fetch('../api/chat_admin.php?action=eliminar_hilo', { method: 'POST', body: fd });
    if (hiloActualId == id) {
        hiloActualId = null;
        chatMessages.innerHTML = '<div class="message-admin bot-admin mb-3"><div class="msg-bubble bot-bubble text-dark">Sesión cerrada. Inicia una nueva consulta.</div></div>';
    }
    cargarHilos();
}

btnNuevoHilo.addEventListener('click', () => {
    hiloActualId = null;
    document.querySelectorAll('.hilo-item').forEach(el => el.classList.remove('active'));
    chatMessages.innerHTML = `
        <div class="message-admin bot-admin mb-3">
            <div class="msg-bubble bot-bubble">
                <div class="d-flex align-items-center gap-2 mb-2 pb-1 border-bottom border-light-subtle">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white" style="width: 24px; height: 24px; font-size: 0.75rem;">
                        <i class="fas fa-robot"></i>
                    </span>
                    <span class="fw-bold text-primary">Jarvis BI — Asesor Ejecutivo</span>
                </div>
                <div class="bubble-text text-dark">
                    Nueva sesión iniciada. Escribe cualquier consulta sobre ventas, finanzas, inventario o pide la generación de reportes en CSV o HTML.
                </div>
            </div>
        </div>
    `;
    inputChat.focus();
});

// Chips de consultas rápidas
document.querySelectorAll('.chip-query').forEach(btn => {
    btn.addEventListener('click', () => {
        inputChat.value = btn.dataset.prompt;
        formChat.dispatchEvent(new Event('submit'));
    });
});

formChat.addEventListener('submit', async (e) => {
    e.preventDefault();
    const pregunta = inputChat.value.trim();
    if (!pregunta) return;

    agregarMensajeUser(pregunta);
    inputChat.value = '';
    mostrarTyping();

    try {
        const resp = await fetch('../api/chat_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pregunta, hilo_id: hiloActualId }),
            credentials: 'same-origin'
        });
        const data = await resp.json();
        ocultarTyping();

        if (data.ok) {
            if (data.hilo_id && !hiloActualId) {
                hiloActualId = data.hilo_id;
                cargarHilos();
            }
            renderizarMensajeBot(data.mensaje, data.tipo, data.datos);
        } else {
            renderizarMensajeBot('❌ Error: ' + (data.error || 'No se pudo procesar la consulta'), 'texto');
        }
    } catch (err) {
        ocultarTyping();
        renderizarMensajeBot('❌ Error de conexión con el servidor analítico.', 'texto');
    }
});

function agregarMensajeUser(texto) {
    const div = document.createElement('div');
    div.className = 'message-admin user-admin mb-3';
    div.innerHTML = `<div class="msg-bubble user-bubble">${escapeHTML(texto)}</div>`;
    chatMessages.appendChild(div);
    scrollAbajo();
}

// Formateador visual de Markdown para negritas y listas
function parseMarkdown(text) {
    if (!text) return '';
    let escaped = escapeHTML(text);
    // Negrita **texto**
    escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong class="fw-bold text-dark">$1</strong>');
    // Listas con guión o viñeta
    escaped = escaped.replace(/^[•\-]\s+(.*)$/gm, '<div class="d-flex align-items-start gap-2 my-1"><span class="text-primary fw-bold">•</span><span class="text-dark">$1</span></div>');
    return escaped;
}

function renderizarMensajeBot(texto, tipo = 'texto', datos = null) {
    const div = document.createElement('div');
    div.className = 'message-admin bot-admin mb-3';
    let extraHTML = '';

    if (tipo === 'reporte_descarga' && datos && datos.url) {
        extraHTML = `
            <div class="reporte-card d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <div class="reporte-title"><i class="fas fa-file-excel text-success me-2"></i> ${escapeHTML(datos.nombre_archivo)}</div>
                    <div class="reporte-meta mt-1">Formato: <strong>${escapeHTML(datos.formato)}</strong> • Tipo: <strong>${escapeHTML(datos.tipo)}</strong> • Generado: ${escapeHTML(datos.fecha || '')}</div>
                </div>
                <a href="${datos.url}" class="btn-descargar-reporte" download>
                    <i class="fas fa-arrow-down-to-bracket"></i> Descargar Archivo
                </a>
            </div>
        `;
    } else if (tipo === 'grafico' && datos) {
        const canvasId = 'chart-' + Date.now();
        extraHTML = `
            <div class="mt-3 p-3 bg-white rounded-3 border" style="border-color: #cbd5e1 !important;">
                <h6 class="fw-bold text-dark mb-2"><i class="fas fa-chart-bar text-primary me-1"></i> ${escapeHTML(datos.label || 'Ventas')}</h6>
                <canvas id="${canvasId}" width="400" height="220"></canvas>
            </div>
        `;
        setTimeout(() => {
            const canvasEl = document.getElementById(canvasId);
            if (canvasEl) {
                new Chart(canvasEl.getContext('2d'), {
                    type: datos.tipo || 'bar',
                    data: {
                        labels: datos.labels || [],
                        datasets: [{
                            label: datos.label || 'Ventas',
                            data: datos.datos || [],
                            backgroundColor: ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                            borderRadius: 6
                        }]
                    },
                    options: { 
                        responsive: true, 
                        plugins: { legend: { display: datos.tipo === 'pie' } },
                        scales: datos.tipo === 'bar' ? { y: { beginAtZero: true } } : {}
                    }
                });
            }
        }, 60);
    }

    div.innerHTML = `
        <div class="msg-bubble bot-bubble">
            <div class="d-flex align-items-center gap-2 mb-2 pb-1 border-bottom border-light-subtle">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white" style="width: 24px; height: 24px; font-size: 0.75rem;">
                    <i class="fas fa-robot"></i>
                </span>
                <span class="fw-bold text-primary">Jarvis BI — Asesor Ejecutivo</span>
            </div>
            <div class="bubble-text text-dark" style="white-space: pre-line;">${parseMarkdown(texto)}</div>
            ${extraHTML}
        </div>
    `;
    chatMessages.appendChild(div);
    scrollAbajo();
}

function mostrarTyping() {
    const div = document.createElement('div');
    div.id = 'typing-admin';
    div.className = 'message-admin bot-admin mb-3';
    div.innerHTML = `
        <div class="msg-bubble bot-bubble text-dark d-flex align-items-center gap-2">
            <i class="fas fa-circle-notch fa-spin text-primary"></i>
            <span class="small fw-semibold text-secondary">Procesando consulta y analítica de negocios...</span>
        </div>
    `;
    chatMessages.appendChild(div);
    scrollAbajo();
}

function ocultarTyping() {
    document.getElementById('typing-admin')?.remove();
}

function scrollAbajo() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function escapeHTML(str) {
    if (!str) return '';
    return str.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

cargarHilos();
</script>
<?php
$contenido = ob_get_clean();
$titulo = 'Chat Administrativo (BI & Decisión)';
require 'layout.php';