// ============================================================
// ARCHIVO: assets/js/carrito.js
// PROPÓSITO: Manejar el carrito de compras en el frontend
//            usando localStorage. Los productos se almacenan
//            como array de objetos { id, nombre, precio, cantidad }
// ============================================================

class Carrito {
    constructor() {
        this.clave = 'carrito_ventas_ia';
    }

    obtener() {
        return JSON.parse(localStorage.getItem(this.clave)) || [];
    }

    guardar(items) {
        localStorage.setItem(this.clave, JSON.stringify(items));
    }

    agregar(producto, boton = null) {
        const items = this.obtener();
        const index = items.findIndex(item => item.id === producto.id);
        const stockDisponible = producto.stock || 999;

        if (index !== -1) {
            if (items[index].cantidad + 1 <= stockDisponible) {
                items[index].cantidad += 1;
            } else {
                alert(`Solo hay ${stockDisponible} unidades disponibles.`);
                return;
            }
        } else {
            if (1 <= stockDisponible) {
                items.push({ ...producto, cantidad: 1 });
            } else {
                alert('Producto agotado.');
                return;
            }
        }
        this.guardar(items);
        this.actualizarContador();

        // Animación del botón
        if (boton) {
            boton.classList.add('added');
            setTimeout(() => boton.classList.remove('added'), 300);
        }

        // Animación del contador (rebote)
        const contador = document.getElementById('carrito-contador');
        if (contador) {
            contador.classList.add('bump');
            setTimeout(() => contador.classList.remove('bump'), 300);
        }

        this.mostrarNotificacion(producto.nombre);
    }

    eliminar(nombre) {
        let items = this.obtener();
        items = items.filter(item => item.nombre !== nombre);
        this.guardar(items);
        this.actualizarContador();
    }

    vaciar() {
        localStorage.removeItem(this.clave);
        this.actualizarContador();
    }

    total() {
        return this.obtener().reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
    }

    cantidadTotal() {
        return this.obtener().reduce((sum, item) => sum + item.cantidad, 0);
    }

    actualizarContador() {
        const contador = document.getElementById('carrito-contador');
        if (contador) {
            const total = this.cantidadTotal();
            contador.textContent = total;
            contador.style.display = total > 0 ? 'inline-block' : 'none';
        }
    }

    mostrarNotificacion(nombre) {
        // Pequeña notificación toast (opcional)
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.textContent = `✅ "${nombre}" añadido al carrito`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    }
}

// Instancia global
const carrito = new Carrito();

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    carrito.actualizarContador();

    // Asignar eventos a los botones "Añadir" dinámicamente
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const nombre = btn.getAttribute('data-nombre');
            const precio = parseFloat(btn.getAttribute('data-precio'));
            const id = btn.getAttribute('data-id');
            carrito.agregar({ id, nombre, precio });
        });
    });
});