<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

use Services\Security\SecurityKernel;

$csrfToken = SecurityKernel::csrfToken();

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="fw-bold mb-1 text-dark"><i class="fas fa-brain text-primary"></i> Jarvis ULAT — Inteligencia de Admisiones</h2>
        <p class="text-secondary small mb-0">Diagnóstico de captación estudiantil, exportación de prospectos, métricas de Guía ULAT y consulta RAG.</p>
    </div>
    <div>
        <span class="badge bg-success text-white px-3 py-2 rounded-pill fw-semibold">DeepSeek BI &amp; RAG ULAT</span>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-3 col-md-4">
        <div class="card shadow-sm border h-100" style="border-radius: 14px;">
            <div class="card-header bg-white border-bottom pt-3 pb-2 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark small">Sesiones BI</span>
                <button type="button" id="btn-nuevo-hilo" class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-semibold">+ Nueva</button>
            </div>
            <div class="card-body p-2 bg-light-subtle">
                <div id="lista-hilos" class="list-group list-group-flush overflow-auto pe-1" style="max-height: 520px;">
                    <div class="text-center py-4 text-secondary small">Cargando historial...</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-9 col-md-8">
        <div class="card shadow-sm border d-flex flex-column" style="border-radius: 14px; height: 610px;">
            <div class="p-2 px-3 border-bottom d-flex gap-2 overflow-x-auto align-items-center bg-white" style="border-radius: 14px 14px 0 0;">
                <span class="small fw-bold text-dark text-nowrap">Acciones rápidas:</span>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill chip-query text-nowrap" data-prompt="Realiza un diagnóstico estratégico de admisiones y desempeño del chatbot">Diagnóstico Admisiones</button>
                <button type="button" class="btn btn-sm btn-outline-success rounded-pill chip-query text-nowrap" data-prompt="Genera el reporte de prospectos de admisiones en CSV">Exportar Prospectos CSV</button>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill chip-query text-nowrap" data-prompt="Genera el reporte de conversaciones del chatbot en CSV">Exportar Logs Chat CSV</button>
                <button type="button" class="btn btn-sm btn-outline-dark rounded-pill chip-query text-nowrap" data-prompt="Muéstrame el gráfico de prospectos por carrera">Gráfico por Carrera</button>
            </div>

            <div id="chat-admin-messages" class="flex-grow-1 p-3 overflow-auto" style="background: #f1f5f9;">
                <div class="mb-3">
                    <div class="p-3 bg-white border rounded-3 text-dark small">
                        <strong class="text-primary d-block mb-1">Jarvis ULAT — Asesor Institucional</strong>
                        Hola Administrador. Puedo analizar los prospectos de admisiones, generar reportes descargables en CSV/HTML y consultar los reglamentos o mallas oficiales en RAG.
                    </div>
                </div>
            </div>

            <div class="p-3 border-top bg-white" style="border-radius: 0 0 14px 14px;">
                <form id="form-chat-admin" class="d-flex gap-2">
                    <input type="text" id="input-admin" class="form-control rounded-pill px-3" maxlength="1500" placeholder="Ej. ¿Cuáles son las carreras más solicitadas este mes?" autocomplete="off" required>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Enviar</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$contenido = ob_get_clean();
$titulo = 'Asistente BI — ULAT';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" integrity="sha384-9nhczxUqK87bcKHh20fSQcTGD4qq5GhayNYSYWqwBkINBhOfQLg/P5HG5lF1urn4" crossorigin="anonymous"></script>';
$csrfJson = json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$extraScripts = <<<HTML
<script>
const ADMIN_CSRF = {$csrfJson};
let hiloActualId = null;
const chatMessages = document.getElementById('chat-admin-messages');
const formChat = document.getElementById('form-chat-admin');
const inputChat = document.getElementById('input-admin');
const listaHilos = document.getElementById('lista-hilos');
const btnNuevoHilo = document.getElementById('btn-nuevo-hilo');

function escapeHTML(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function esUrlReporteSegura(url) {
    return typeof url === 'string' && /^\\.\\.\/api\/descargar_reporte\\.php\\?archivo=reporte_[a-z0-9_]+\\.(csv|html)$/i.test(url);
}

async function cargarHilos() {
    try {
        const resp = await fetch('../api/chat_admin.php?action=listar_hilos', { credentials: 'same-origin' });
        const data = await resp.json();
        listaHilos.textContent = '';
        if (!data.ok || !Array.isArray(data.hilos) || data.hilos.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'text-center py-4 text-secondary small';
            empty.textContent = 'No hay sesiones previas.';
            listaHilos.appendChild(empty);
            return;
        }
        data.hilos.forEach(h => {
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex justify-content-between align-items-center mb-1 rounded border ' + (Number(h.id) === Number(hiloActualId) ? 'border-primary bg-light' : '');
            item.style.cursor = 'pointer';

            const info = document.createElement('div');
            info.className = 'text-truncate me-2';
            info.style.maxWidth = '165px';

            const title = document.createElement('div');
            title.className = 'fw-semibold small text-truncate text-dark';
            title.textContent = h.titulo || 'Consulta';

            const date = document.createElement('div');
            date.className = 'text-muted';
            date.style.fontSize = '0.72rem';
            date.textContent = h.fecha || '';

            info.appendChild(title);
            info.appendChild(date);

            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'btn btn-sm btn-link text-danger p-0';
            del.textContent = '×';
            del.addEventListener('click', (ev) => {
                ev.stopPropagation();
                eliminarHilo(Number(h.id));
            });

            item.addEventListener('click', () => seleccionarHilo(Number(h.id)));
            item.appendChild(info);
            item.appendChild(del);
            listaHilos.appendChild(item);
        });
    } catch (e) {}
}

async function seleccionarHilo(id) {
    hiloActualId = id;
    cargarHilos();
    try {
        const resp = await fetch('../api/chat_admin.php?action=cargar_hilo&hilo_id=' + encodeURIComponent(id), { credentials: 'same-origin' });
        const data = await resp.json();
        chatMessages.textContent = '';
        if (data.ok && Array.isArray(data.mensajes)) {
            data.mensajes.forEach(m => {
                if (m.role === 'user') agregarMensajeUser(m.content);
                else renderizarMensajeBot(m.content, m.tipo, m.datos);
            });
        }
    } catch (e) {}
}

async function eliminarHilo(id) {
    if (!confirm('¿Eliminar esta sesión de consulta?')) return;
    const fd = new FormData();
    fd.append('hilo_id', String(id));
    fd.append('csrf_token', ADMIN_CSRF);
    await fetch('../api/chat_admin.php?action=eliminar_hilo', {
        method: 'POST',
        headers: { 'X-CSRF-Token': ADMIN_CSRF },
        body: fd,
        credentials: 'same-origin'
    });
    if (Number(hiloActualId) === Number(id)) {
        hiloActualId = null;
        chatMessages.textContent = '';
    }
    cargarHilos();
}

btnNuevoHilo.addEventListener('click', () => {
    hiloActualId = null;
    chatMessages.textContent = '';
    renderizarMensajeBot('Nueva sesión iniciada. ¿Qué métrica o reporte de admisiones ULAT necesitas?', 'texto', null);
    cargarHilos();
});

document.querySelectorAll('.chip-query').forEach(btn => {
    btn.addEventListener('click', () => {
        inputChat.value = btn.dataset.prompt || '';
        formChat.dispatchEvent(new Event('submit'));
    });
});

function agregarMensajeUser(texto) {
    const wrap = document.createElement('div');
    wrap.className = 'd-flex justify-content-end mb-3';
    const bubble = document.createElement('div');
    bubble.className = 'p-3 rounded-3 bg-primary text-white small';
    bubble.style.maxWidth = '80%';
    bubble.textContent = texto;
    wrap.appendChild(bubble);
    chatMessages.appendChild(wrap);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function parseMarkdownSeguro(text) {
    let escaped = escapeHTML(text);
    escaped = escaped.replace(/\\*\\*(.*?)\\*\\*/g, '<strong>$1</strong>');
    return escaped.replace(/\\n/g, '<br>');
}

function renderizarMensajeBot(texto, tipo = 'texto', datos = null) {
    const wrap = document.createElement('div');
    wrap.className = 'mb-3';

    const card = document.createElement('div');
    card.className = 'p-3 bg-white border rounded-3 text-dark small';
    card.style.maxWidth = '88%';

    const body = document.createElement('div');
    body.innerHTML = parseMarkdownSeguro(texto || '');
    card.appendChild(body);

    if (tipo === 'reporte_descarga' && datos && esUrlReporteSegura(datos.url)) {
        const box = document.createElement('div');
        box.className = 'mt-3 p-3 rounded border border-success bg-success-subtle d-flex justify-content-between align-items-center flex-wrap gap-2';

        const meta = document.createElement('div');
        meta.className = 'fw-semibold text-success-emphasis';
        meta.textContent = (datos.nombre_archivo || 'Reporte') + ' (' + (datos.formato || 'CSV') + ')';

        const link = document.createElement('a');
        link.href = datos.url;
        link.className = 'btn btn-sm btn-success rounded-pill px-3 fw-bold';
        link.setAttribute('download', '');
        link.textContent = 'Descargar Reporte';

        box.appendChild(meta);
        box.appendChild(link);
        card.appendChild(box);
    } else if (tipo === 'grafico' && datos) {
        const chartWrap = document.createElement('div');
        chartWrap.className = 'mt-3 p-2 border rounded bg-white';
        const canvas = document.createElement('canvas');
        canvas.height = 180;
        chartWrap.appendChild(canvas);
        card.appendChild(chartWrap);
        setTimeout(() => {
            new Chart(canvas.getContext('2d'), {
                type: datos.tipo === 'pie' ? 'pie' : 'bar',
                data: {
                    labels: Array.isArray(datos.labels) ? datos.labels : [],
                    datasets: [{
                        label: String(datos.label || 'Prospectos'),
                        data: Array.isArray(datos.datos) ? datos.datos : [],
                        backgroundColor: ['#0D1E36', '#C59B27', '#1E3A8A', '#0F766E', '#64748B'],
                        borderRadius: 6
                    }]
                },
                options: { responsive: true, scales: { y: { beginAtZero: true } } }
            });
        }, 40);
    }

    wrap.appendChild(card);
    chatMessages.appendChild(wrap);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

formChat.addEventListener('submit', async (e) => {
    e.preventDefault();
    const pregunta = inputChat.value.trim();
    if (!pregunta) return;
    agregarMensajeUser(pregunta);
    inputChat.value = '';

    try {
        const resp = await fetch('../api/chat_admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': ADMIN_CSRF
            },
            body: JSON.stringify({ pregunta, hilo_id: hiloActualId }),
            credentials: 'same-origin'
        });
        const data = await resp.json();
        if (data.ok) {
            if (data.hilo_id && !hiloActualId) {
                hiloActualId = data.hilo_id;
                cargarHilos();
            }
            renderizarMensajeBot(data.mensaje, data.tipo, data.datos);
        } else {
            renderizarMensajeBot(data.error || 'No se pudo procesar la consulta.', 'texto', null);
        }
    } catch (err) {
        renderizarMensajeBot('Error de red al contactar al servidor.', 'texto', null);
    }
});

cargarHilos();
</script>
HTML;
require __DIR__ . '/layout.php';
