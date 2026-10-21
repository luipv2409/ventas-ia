<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

// Filtros recibidos por GET (para pre-cargar, aunque DataTables los enviará por AJAX luego)
$estadoFiltro = $_GET['estado'] ?? '';
$clienteFiltro = $_GET['cliente'] ?? '';
$fechaDesde = $_GET['fecha_desde'] ?? '';
$fechaHasta = $_GET['fecha_hasta'] ?? '';

// Obtener lista de estados para el filtro
$estados = ['pendiente', 'confirmado', 'enviado', 'entregado', 'cancelado'];

// Obtener clientes únicos para el filtro (para un select, opcional)
$clientes = $pdo->query("SELECT DISTINCT c.id, c.nombre FROM clientes c JOIN pedidos p ON c.id = p.cliente_id ORDER BY c.nombre")->fetchAll();

ob_start();
?>

<h2 class="d-flex align-items-center gap-2">
    <i data-lucide="history" class="text-primary"></i>
    <span>Histórico de Ventas</span>
</h2>
<p class="text-muted">Consulta todas las transacciones registradas. Los datos no se pueden modificar ni eliminar desde esta interfaz.</p>

<!-- Filtros manuales (opcional, DataTables también permite búsqueda global) -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form id="form-filtros-historico" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Desde</label>
                <input type="date" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($fechaDesde) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($fechaHasta) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($estados as $est): ?>
                        <option value="<?= $est ?>" <?= $estadoFiltro == $est ? 'selected' : '' ?>><?= ucfirst($est) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Cliente</label>
                <select name="cliente_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($clientes as $cli): ?>
                        <option value="<?= $cli['id'] ?>" <?= $clienteFiltro == $cli['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cli['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" id="btn-limpiar-filtros" class="btn btn-outline-secondary w-100">Limpiar</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla con DataTables -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Listado de Pedidos</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tabla-historico" class="table table-striped table-hover mb-0" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Productos</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Se llena vía AJAX -->
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Total filtrado:</th>
                        <th id="total-filtrado" class="text-primary">Bs 0.00</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- DataTables CSS/JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="../assets/js/datatables-es.js"></script>

<script>
$(document).ready(function() {
    // Función para cargar la tabla con filtros
    function cargarTabla(filtros = {}) {
        if ($.fn.DataTable.isDataTable('#tabla-historico')) {
            $('#tabla-historico').DataTable().destroy();
        }

        const tabla = $('#tabla-historico').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '../api/historico_ventas.php',
                type: 'POST',
                data: function(d) {
                    d.filtros = filtros;
                }
            },
            columns: [
                { data: 'id' },
                { data: 'cliente' },
                { data: 'email' },
                { 
                    data: 'total',
                    render: function(data, type, row) {
                        return 'Bs ' + parseFloat(data).toFixed(2);
                    }
                },
                { 
                    data: 'estado',
                    render: function(data, type, row) {
                        const badgeClass = {
                            'pendiente': 'warning',
                            'confirmado': 'info',
                            'enviado': 'primary',
                            'entregado': 'success',
                            'cancelado': 'danger'
                        }[data] || 'secondary';
                        return `<span class="badge bg-${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                    }
                },
                { 
                    data: 'creado_en',
                    render: function(data, type, row) {
                        const fecha = new Date(data);
                        return fecha.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + fecha.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' });
                    }
                },
                {
                    data: 'id',
                    orderable: false,
                    render: function(data, type, row) {
                        return `<button class="btn btn-sm btn-outline-info ver-productos d-inline-flex align-items-center gap-1" data-pedido-id="${data}" title="Ver productos"><i data-lucide="eye" style="width:14px;height:14px;"></i> <span>Ver</span></button>`;
                    }
                }
            ],
            language: typeof DATATABLES_ESPANOL !== 'undefined' ? DATATABLES_ESPANOL : {},
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copy', text: 'Copiar' },
                { extend: 'excel', text: 'Excel' },
                { extend: 'pdf', text: 'PDF' },
                { extend: 'print', text: 'Imprimir' }
            ],
            drawCallback: function() {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    lucide.createIcons();
                }
            },
            footerCallback: function(row, data, start, end, display) {
                var api = this.api();
                var total = api.column(3, { page: 'current' }).data().reduce(function(a, b) {
                    return parseFloat(a) + parseFloat(b);
                }, 0);
                $('#total-filtrado').html('Bs ' + total.toFixed(2));
            },
            initComplete: function() {
                // Estilo para botones de exportación
                $('.dt-buttons').addClass('mb-2');
                $('.dt-button').addClass('btn btn-sm btn-outline-secondary me-1');
            }
        });

        // Evento para ver productos de un pedido
        $('#tabla-historico').on('click', '.ver-productos', function() {
            const pedidoId = $(this).data('pedido-id');
            // Mostrar modal con los productos (puedes crear una API simple)
            $.getJSON(`../api/detalle_pedido.php?id=${pedidoId}`, function(data) {
                let html = '<ul class="list-group">';
                data.forEach(item => {
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                        ${item.nombre} x${item.cantidad}
                        <span>Bs ${parseFloat(item.subtotal).toFixed(2)}</span>
                    </li>`;
                });
                html += '</ul>';
                $('#modal-productos .modal-body').html(html);
                $('#modal-productos').modal('show');
            });
        });
    }

    // Cargar con filtros iniciales (si existen)
    const filtrosIniciales = {
        fecha_desde: '<?= $fechaDesde ?>',
        fecha_hasta: '<?= $fechaHasta ?>',
        estado: '<?= $estadoFiltro ?>',
        cliente_id: '<?= $clienteFiltro ?>'
    };
    cargarTabla(filtrosIniciales);

    // Aplicar filtros al enviar formulario
    $('#form-filtros-historico').on('submit', function(e) {
        e.preventDefault();
        const filtros = {
            fecha_desde: $('[name="fecha_desde"]').val(),
            fecha_hasta: $('[name="fecha_hasta"]').val(),
            estado: $('[name="estado"]').val(),
            cliente_id: $('[name="cliente_id"]').val()
        };
        cargarTabla(filtros);
    });

    // Limpiar filtros
    $('#btn-limpiar-filtros').on('click', function() {
        $('#form-filtros-historico')[0].reset();
        cargarTabla({});
    });
});
</script>

<!-- Modal para ver productos del pedido -->
<div class="modal fade" id="modal-productos" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Productos del Pedido</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body"></div>
    </div>
  </div>
</div>

<?php
$contenido = ob_get_clean();
$titulo = 'Histórico de Ventas';
$extraHead = ''; // Ya incluimos DataTables CSS
require 'layout.php';