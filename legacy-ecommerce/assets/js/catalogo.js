// ============================================================
// ARCHIVO: assets/js/catalogo.js
// PROPÓSITO: Manejo interactivo, minimalista y ordenado del catálogo
//            por departamentos, buscador en vivo y sincronización con IA.
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    const formFiltros = document.getElementById('form-filtros');
    const productosContainer = document.getElementById('productos-container');
    const skeletonContainer = document.getElementById('skeleton-container');
    const sinResultados = document.getElementById('sin-resultados');
    const contadorResultados = document.getElementById('contador-resultados');
    const inputTexto = document.getElementById('filtro-texto');
    const selectOrden = document.getElementById('filtro-orden');
    const inputCatHidden = document.getElementById('filtro-categoria-hidden');
    const btnLimpiar = document.getElementById('btn-limpiar-filtros');

    let debounceTimer = null;

    function asignarEventosBotones() {
        document.querySelectorAll('.add-to-cart').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const nombre = btn.dataset.nombre;
                const precio = parseFloat(btn.dataset.precio);
                const stock = parseInt(btn.dataset.stock) || 0;
                if (typeof carrito !== 'undefined') {
                    carrito.agregar({ id, nombre, precio, stock });
                }
            });
        });
    }

    async function cargarProductos(params = '') {
        // Mostrar skeleton
        if (skeletonContainer) skeletonContainer.style.display = 'flex';
        if (productosContainer) productosContainer.style.display = 'none';
        if (sinResultados) sinResultados.style.display = 'none';

        try {
            const resp = await fetch(`api/filtrar_productos.php?${params}`);
            const data = await resp.json();
            
            // Ocultar skeleton
            if (skeletonContainer) skeletonContainer.style.display = 'none';

            if (data.html && data.total > 0) {
                productosContainer.innerHTML = data.html;
                productosContainer.style.display = 'block';
                if (sinResultados) sinResultados.style.display = 'none';
                if (contadorResultados) contadorResultados.textContent = `${data.total} producto(s)`;
                asignarEventosBotones();
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            } else {
                productosContainer.style.display = 'none';
                if (sinResultados) sinResultados.style.display = 'block';
                if (contadorResultados) contadorResultados.textContent = '0 productos';
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            }
            return data.total || 0;
        } catch (err) {
            if (skeletonContainer) skeletonContainer.style.display = 'none';
            if (sinResultados) sinResultados.style.display = 'block';
            if (contadorResultados) contadorResultados.textContent = 'Error';
            console.error('Error al filtrar productos:', err);
            return 0;
        }
    }

    function obtenerParametrosActuales() {
        if (!formFiltros) return '';
        const formData = new FormData(formFiltros);
        return new URLSearchParams(formData).toString();
    }

    // Cambiar de Categoría desde las pestañas minimalistas
    window.cambiarCategoriaTab = function(catId) {
        catId = (catId === 'todas' || catId === 0 || catId === '') ? 'todas' : catId.toString();

        if (inputCatHidden) {
            inputCatHidden.value = catId;
        }

        // Actualizar aspecto visual de pestañas
        document.querySelectorAll('.category-pill-tab').forEach(tab => {
            if (tab.dataset.categoria === catId) {
                tab.classList.add('active');
                // Scroll horizontal suave a la pestaña activa si está oculta
                tab.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            } else {
                tab.classList.remove('active');
            }
        });

        const params = obtenerParametrosActuales();
        cargarProductos(params);
    };

    // Búsqueda en vivo con debounce de 280ms
    if (inputTexto) {
        inputTexto.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const params = obtenerParametrosActuales();
                cargarProductos(params);
            }, 280);
        });
    }

    // Cambio de ordenación
    if (selectOrden) {
        selectOrden.addEventListener('change', () => {
            const params = obtenerParametrosActuales();
            cargarProductos(params);
        });
    }

    // Manejador del formulario (precios, etc.)
    if (formFiltros) {
        formFiltros.addEventListener('submit', (e) => {
            e.preventDefault();
            const params = obtenerParametrosActuales();
            cargarProductos(params);
        });
    }

    // Limpieza completa de filtros
    window.limpiarTodosFiltros = function() {
        if (formFiltros) {
            formFiltros.reset();
        }
        if (inputCatHidden) {
            inputCatHidden.value = 'todas';
        }
        document.querySelectorAll('.category-pill-tab').forEach(tab => {
            if (tab.dataset.categoria === 'todas') {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });
        cargarProductos('categoria_id=todas');
        mostrarFeedbackFiltro('✨ Catálogo completo restablecido');
    };

    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', window.limpiarTodosFiltros);
    }

    // Clic en las pestañas de categorías
    document.querySelectorAll('.category-pill-tab').forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            const cat = tab.dataset.categoria;
            window.cambiarCategoriaTab(cat);
        });
    });

    // Clic en chips del Hero para salto directo
    document.querySelectorAll('.hero-category-chip').forEach(chip => {
        chip.addEventListener('click', (e) => {
            e.preventDefault();
            const cat = chip.dataset.categoria;
            window.cambiarCategoriaTab(cat);
            const catalogoEl = document.getElementById('catalogo-seccion');
            if (catalogoEl) {
                catalogoEl.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Exponer globalmente para que el chatbot (Valeria) filtre dinámicamente
    window.aplicarFiltrosCatalogo = async function(filtros = {}) {
        if (filtros.categoria_id !== undefined && filtros.categoria_id !== null) {
            window.cambiarCategoriaTab(filtros.categoria_id);
        }
        if (filtros.texto !== undefined && filtros.texto !== null && inputTexto) {
            inputTexto.value = filtros.texto;
        }
        if (filtros.precio_min !== undefined && filtros.precio_min !== null) {
            const minEl = formFiltros.querySelector('input[name="precio_min"]');
            if (minEl) minEl.value = filtros.precio_min;
        }
        if (filtros.precio_max !== undefined && filtros.precio_max !== null) {
            const maxEl = formFiltros.querySelector('input[name="precio_max"]');
            if (maxEl) maxEl.value = filtros.precio_max;
        }

        const params = obtenerParametrosActuales();
        await cargarProductos(params);

        mostrarFeedbackFiltro(filtros.mensaje_filtro || '✨ Catálogo filtrado automáticamente');

        const target = document.getElementById('catalogo-seccion');
        if (target) {
            const y = target.getBoundingClientRect().top + window.pageYOffset - 75;
            window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
        }
    };

    function mostrarFeedbackFiltro(texto) {
        let toast = document.getElementById('chat-filter-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'chat-filter-toast';
            toast.style.cssText = `
                position: fixed;
                top: 24px;
                right: 24px;
                background: #0f172a;
                color: #ffffff;
                padding: 12px 22px;
                border-radius: 9999px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                font-weight: 600;
                font-size: 0.9rem;
                z-index: 9999;
                transition: opacity 0.3s ease, transform 0.3s ease;
                display: flex;
                align-items: center;
                gap: 10px;
                border: 1px solid rgba(255,255,255,0.15);
            `;
            document.body.appendChild(toast);
        }
        toast.innerHTML = `<i data-lucide="sliders-horizontal" style="width:16px;height:16px;" class="text-white"></i> <span>${texto}</span>`;
        if (window.lucide) {
            lucide.createIcons();
        }
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
        clearTimeout(toast._timeout);
        toast._timeout = setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
        }, 3200);
    }

    // ============================================================
    // GESTIÓN Y PERSISTENCIA DE FILTROS COLAPSABLES
    // ============================================================
    const colFiltros = document.getElementById('col-filtros');
    const colProductos = document.getElementById('col-productos');
    const btnToggleFiltros = document.getElementById('btn-toggle-filtros');
    const btnCerrarFiltros = document.getElementById('btn-cerrar-filtros');
    const iconoToggleFiltros = document.getElementById('icono-toggle-filtros');
    const textoToggleFiltros = document.getElementById('texto-toggle-filtros');

    function setFiltrosVisibles(visible, guardar = true) {
        if (!colFiltros || !colProductos) return;

        if (visible) {
            colFiltros.classList.remove('d-none');
            if (iconoToggleFiltros) {
                iconoToggleFiltros.setAttribute('data-lucide', 'eye-off');
                if (window.lucide) lucide.createIcons();
            }
            if (textoToggleFiltros) textoToggleFiltros.textContent = 'Ocultar Filtros';
            if (btnToggleFiltros) {
                btnToggleFiltros.classList.remove('btn-primary');
                btnToggleFiltros.classList.add('btn-outline-secondary');
            }
            if (guardar) localStorage.setItem('tienda_filtros_ocultos', '0');
        } else {
            colFiltros.classList.add('d-none');
            if (iconoToggleFiltros) {
                iconoToggleFiltros.setAttribute('data-lucide', 'sliders-horizontal');
                if (window.lucide) lucide.createIcons();
            }
            if (textoToggleFiltros) textoToggleFiltros.textContent = 'Mostrar Filtros';
            if (btnToggleFiltros) {
                btnToggleFiltros.classList.remove('btn-outline-secondary');
                btnToggleFiltros.classList.add('btn-primary');
            }
            if (guardar) localStorage.setItem('tienda_filtros_ocultos', '1');
        }
    }

    if (btnToggleFiltros) {
        btnToggleFiltros.addEventListener('click', () => {
            const estaOculto = colFiltros && colFiltros.classList.contains('d-none');
            setFiltrosVisibles(estaOculto);
        });
    }

    if (btnCerrarFiltros) {
        btnCerrarFiltros.addEventListener('click', () => {
            setFiltrosVisibles(false);
        });
    }

    // Restaurar preferencia guardada
    const filtrosGuardados = localStorage.getItem('tienda_filtros_ocultos');
    if (filtrosGuardados === '1') {
        setFiltrosVisibles(false, false);
    }

    // Carga inicial
    cargarProductos('categoria_id=todas');
});