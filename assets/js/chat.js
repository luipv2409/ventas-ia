document.addEventListener('DOMContentLoaded', () => {
    const chatToggle   = document.getElementById('chat-toggle');
    const chatWindow   = document.getElementById('chat-window');
    const chatClose    = document.getElementById('chat-close');
    const chatReset    = document.getElementById('chat-reset');
    const chatForm     = document.getElementById('chat-form');
    const chatInput    = document.getElementById('chat-input');
    const chatMessages = document.getElementById('chat-messages');

    if (!chatToggle || !chatWindow || !chatForm || !chatInput || !chatMessages) {
        return; // No hay widget de chat en esta vista
    }

    // Claves de persistencia en localStorage
    const STORAGE_HISTORIAL = 'valeria_chat_historial_v2';
    const STORAGE_ABIERTO   = 'valeria_chat_abierto_v2';
    const STORAGE_TIEMPO    = 'valeria_chat_ultimo_tiempo_v2';
    const MAX_HISTORIAL_HORAS = 4; // Persistencia de 4 horas

    // Historial para enviar a la IA (array de {role, content})
    let historialIA = [];

    // -------------------------------------------------------------
    // 1. CARGAR Y RESTAURAR ESTADO GUARDADO
    // -------------------------------------------------------------
    function cargarHistorialGuardado() {
        const ultimoTiempo = parseInt(localStorage.getItem(STORAGE_TIEMPO) || '0', 10);
        const ahora = Date.now();

        // Si pasaron más de 4 horas sin actividad, limpiar chat
        if (ultimoTiempo > 0 && (ahora - ultimoTiempo) > (MAX_HISTORIAL_HORAS * 60 * 60 * 1000)) {
            limpiarHistorialStorage();
            return;
        }

        const guardados = localStorage.getItem(STORAGE_HISTORIAL);
        if (!guardados) return;

        try {
            const mensajes = JSON.parse(guardados);
            if (Array.isArray(mensajes) && mensajes.length > 0) {
                // Limpiar mensaje de bienvenida estático para restaurar la conversación real
                chatMessages.innerHTML = '';
                
                mensajes.forEach(msg => {
                    if (msg.tipo === 'producto' && msg.producto) {
                        renderizarTarjetaProducto(msg.producto, false);
                    } else {
                        renderizarMensajeEnDOM(msg.texto, msg.emisor, false);
                    }
                    if (msg.emisor === 'user') {
                        historialIA.push({ role: 'user', content: msg.texto });
                    } else if (msg.emisor === 'bot' && msg.texto) {
                        historialIA.push({ role: 'assistant', content: msg.texto });
                    }
                });

                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        } catch (e) {
            console.error('Error al restaurar historial de chat:', e);
        }
    }

    function guardarMensajeEnStorage(item) {
        try {
            const guardados = JSON.parse(localStorage.getItem(STORAGE_HISTORIAL) || '[]');
            guardados.push(item);
            // Mantener últimos 30 mensajes
            const recortados = guardados.slice(-30);
            localStorage.setItem(STORAGE_HISTORIAL, JSON.stringify(recortados));
            localStorage.setItem(STORAGE_TIEMPO, Date.now().toString());
        } catch (e) {
            console.error('Error al guardar mensaje en storage:', e);
        }
    }

    function limpiarHistorialStorage() {
        localStorage.removeItem(STORAGE_HISTORIAL);
        localStorage.removeItem(STORAGE_TIEMPO);
        historialIA = [];
    }

    // Restaurar si el chat estaba abierto al navegar entre páginas
    const estabaAbierto = localStorage.getItem(STORAGE_ABIERTO);
    if (estabaAbierto === '1') {
        chatWindow.classList.remove('d-none');
    }

    // Ejecutar restauración
    cargarHistorialGuardado();

    // -------------------------------------------------------------
    // 2. CONTROLADORES DE VENTANA (ABRIR, CERRAR, REINICIAR)
    // -------------------------------------------------------------
    chatToggle.addEventListener('click', () => {
        chatWindow.classList.toggle('d-none');
        const abierto = !chatWindow.classList.contains('d-none');
        localStorage.setItem(STORAGE_ABIERTO, abierto ? '1' : '0');
        if (abierto) {
            chatInput.focus();
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });

    if (chatClose) {
        chatClose.addEventListener('click', () => {
            chatWindow.classList.add('d-none');
            localStorage.setItem(STORAGE_ABIERTO, '0');
        });
    }

    if (chatReset) {
        chatReset.addEventListener('click', () => {
            if (confirm('¿Deseas reiniciar la conversación con Valeria?')) {
                limpiarHistorialStorage();
                chatMessages.innerHTML = `
                    <div class="message bot-message">
                        <div class="message-content">¡Hola! ¿Cómo estás? Te atiende Valeria de ventas. Si buscas algo en específico, quieres comparar productos, agregar al carrito o consultar envíos, dime con toda confianza. 😊</div>
                    </div>
                `;
            }
        });
    }

    // -------------------------------------------------------------
    // 3. ENVÍO DE MENSAJES Y LLAMADA A LA IA
    // -------------------------------------------------------------
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const pregunta = chatInput.value.trim();
        if (pregunta === '') return;

        // Interceptar comandos directos de carrito
        if (/ver carrito|mi carrito|qu[eé] hay en mi carrito|qu[eé] tengo en el carrito|productos en mi carrito/i.test(pregunta)) {
            agregarMensaje(pregunta, 'user');
            const items = (typeof carrito !== 'undefined') ? carrito.obtener() : [];
            if (items.length === 0) {
                agregarMensaje('Por ahora no tienes nada en tu carrito. Si te interesa algún producto, solo avísame y te lo anoto de inmediato 😊', 'bot');
            } else {
                let lista = 'Esto es lo que tienes en el carrito hasta ahora:\n';
                items.forEach(item => {
                    lista += `• ${item.nombre} x${item.cantidad} = Bs ${(item.precio * item.cantidad).toFixed(2)}\n`;
                });
                const total = typeof carrito.total === 'function' ? carrito.total() : items.reduce((s, i) => s + (i.precio * i.cantidad), 0);
                lista += `\nTotal: Bs ${total.toFixed(2)}`;
                agregarMensaje(lista, 'bot');
            }
            chatInput.value = '';
            return;
        }

        // Agregar mensaje del usuario al chat y a la memoria
        agregarMensaje(pregunta, 'user');
        historialIA.push({ role: 'user', content: pregunta });
        chatInput.value = '';
        mostrarTyping();

        try {
            const response = await fetch('api/chat_frontend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    pregunta: pregunta,
                    historial: historialIA.slice(-12),
                    use_rag: true
                })
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            ocultarTyping();

            if (data.ok) {
                let respuestaFinal = data.respuesta;
                // Extraer JSON si la IA lo envolvió en markdown
                const jsonMatch = respuestaFinal.match(/\{.*"respuesta".*\}/s);
                if (jsonMatch) {
                    try {
                        const obj = JSON.parse(jsonMatch[0]);
                        if (obj.respuesta) {
                            respuestaFinal = obj.respuesta;
                            if (obj.acciones && Array.isArray(obj.acciones)) {
                                data.acciones = [...(data.acciones || []), ...obj.acciones];
                            }
                        }
                    } catch(e) {}
                    respuestaFinal = respuestaFinal.replace(/\{.*\}/s, '').trim();
                }

                // Mostrar respuesta de Valeria
                agregarMensaje(respuestaFinal, 'bot');
                historialIA.push({ role: 'assistant', content: respuestaFinal });

                // ⚡ EJECUTAR ACCIONES EN TIEMPO REAL
                if (data.acciones && Array.isArray(data.acciones)) {
                    data.acciones.forEach(accion => {
                        if (accion.tipo === 'filtrar_catalogo') {
                            if (typeof window.aplicarFiltrosCatalogo === 'function') {
                                window.aplicarFiltrosCatalogo(accion);
                            }
                        } else if (accion.tipo === 'mostrar_producto') {
                            mostrarProductoEnChat(accion);
                        } else if (accion.tipo === 'agregar_carrito' && accion.nombre) {
                            agregarAlCarritoDesdeChat(accion);
                        }
                    });
                }
            } else {
                agregarMensaje('Disculpa, tuve un problema para procesar tu consulta. ¿Podrías repetirla por favor?', 'bot');
            }
        } catch (error) {
            ocultarTyping();
            console.error('Error chat:', error);
            agregarMensaje('Perdón, se me cortó un momento la conexión 😅 ¿Me escribes de nuevo?', 'bot');
        }
    });

    // -------------------------------------------------------------
    // 4. FUNCIONES DE RENDERIZADO
    // -------------------------------------------------------------
    function agregarMensaje(texto, tipo) {
        renderizarMensajeEnDOM(texto, tipo, true);
    }

    function renderizarMensajeEnDOM(texto, tipo, guardar = false) {
        const div = document.createElement('div');
        div.className = `message ${tipo}-message`;
        div.innerHTML = `<div class="message-content">${escapeHTML(texto)}</div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        if (guardar) {
            guardarMensajeEnStorage({ tipo: 'texto', emisor: tipo, texto: texto });
        }
    }

    function escapeHTML(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function mostrarTyping() {
        const typing = document.createElement('div');
        typing.className = 'message bot-message typing-indicator';
        typing.innerHTML = '<span></span><span></span><span></span>';
        typing.id = 'typing-indicator';
        chatMessages.appendChild(typing);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function ocultarTyping() {
        document.getElementById('typing-indicator')?.remove();
    }

    function mostrarProductoEnChat(producto) {
        renderizarTarjetaProducto(producto, true);
    }

    function renderizarTarjetaProducto(producto, guardar = false) {
        const div = document.createElement('div');
        div.className = 'message bot-message product-card-chat';
        const moneda = producto.moneda || 'Bs';
        const precioNum = parseFloat(producto.precio) || 0;
        const precioFormateado = precioNum.toFixed(2);
        const imagenUrl = producto.imagen || producto.imagen_url || 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&auto=format&fit=crop&q=60';

        div.innerHTML = `
            <div class="card mb-2 shadow-sm border" style="max-width: 230px; border-radius: 12px; overflow: hidden; background: #ffffff;">
                <img src="${imagenUrl}" class="card-img-top" alt="${escapeHTML(producto.nombre)}" style="height: 120px; object-fit: cover;">
                <div class="card-body p-2">
                    <h6 class="card-title mb-1 fw-bold text-dark text-truncate" style="font-size: 0.88rem;" title="${escapeHTML(producto.nombre)}">${escapeHTML(producto.nombre)}</h6>
                    <p class="card-text small fw-bold text-primary mb-2" style="font-size: 0.95rem;">${moneda} ${precioFormateado}</p>
                    <button class="btn btn-sm btn-primary w-100 rounded-pill add-from-chat py-1 fw-semibold d-flex align-items-center justify-content-center gap-1" 
                            data-id="${producto.id || ''}" 
                            data-nombre="${escapeHTML(producto.nombre)}" 
                            data-precio="${precioNum}"
                            data-stock="${producto.stock || 50}">
                        <i data-lucide="shopping-bag" style="width:14px;height:14px;"></i> <span>Agregar al Carrito</span>
                    </button>
                </div>
            </div>`;
        
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        if (window.lucide) {
            lucide.createIcons();
        }

        div.querySelector('.add-from-chat').addEventListener('click', (e) => {
            const btn = e.currentTarget;
            const accion = {
                id: btn.dataset.id,
                nombre: btn.dataset.nombre,
                precio: parseFloat(btn.dataset.precio),
                stock: parseInt(btn.dataset.stock) || 50
            };
            agregarAlCarritoDesdeChat(accion);
        });

        if (guardar) {
            guardarMensajeEnStorage({ tipo: 'producto', emisor: 'bot', producto: producto });
        }
    }

    // -------------------------------------------------------------
    // 5. AGREGAR AL CARRITO DE COMPRAS DESDE EL CHAT
    // -------------------------------------------------------------
    function agregarAlCarritoDesdeChat(accion) {
        const nombre = accion.nombre || 'Producto';
        const precio = parseFloat(accion.precio) || 0;
        const id = accion.id || ('prod-' + Math.floor(Math.random() * 10000));
        const stock = parseInt(accion.stock) || 50;
        const imagen = accion.imagen || '';

        if (typeof carrito !== 'undefined') {
            carrito.agregar({ id, nombre, precio, stock, imagen });
            
            // Si el cliente está en la página carrito.php, actualizar la vista de la bolsa al instante
            if (typeof renderizarVistaCarrito === 'function') {
                renderizarVistaCarrito();
            }

            // Confirmación amable en el chat
            agregarMensaje(`🛒 ¡Listo! Ya te agregué "${nombre}" a tu carrito de compras (Bs ${precio.toFixed(2)}). ¿Deseas algo más o vamos a finalizar tu pedido?`, 'bot');
            
            // Feedback flotante
            mostrarToastCarrito(`🛒 "${nombre}" añadido al carrito`);
        } else {
            console.error('El objeto carrito no está definido.');
        }
    }

    function mostrarToastCarrito(texto) {
        let toast = document.getElementById('chat-cart-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'chat-cart-toast';
            toast.style.cssText = `
                position: fixed;
                top: 25px;
                right: 25px;
                background: linear-gradient(135deg, #10b981, #059669);
                color: #ffffff;
                padding: 12px 22px;
                border-radius: 50px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.25);
                font-weight: 600;
                font-size: 0.92rem;
                z-index: 100000;
                display: flex;
                align-items: center;
                gap: 8px;
                transition: opacity 0.3s ease, transform 0.3s ease;
            `;
            document.body.appendChild(toast);
        }
        toast.innerHTML = `<i data-lucide="check-circle-2" style="width:16px;height:16px;"></i> <span>${escapeHTML(texto)}</span>`;
        if (window.lucide) {
            lucide.createIcons();
        }
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
        clearTimeout(toast._timeout);
        toast._timeout = setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
        }, 3000);
    }
});