/**
 * datatables-es.js — Traducción nativa al español para DataTables.
 * Evita llamadas externas a CDN y bloqueos de CORS.
 */
const DATATABLES_ESPANOL = {
    processing: "Procesando...",
    search: "Buscar:",
    lengthMenu: "Mostrar _MENU_ registros",
    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
    infoEmpty: "Mostrando 0 a 0 de 0 registros",
    infoFiltered: "(filtrado de un total de _MAX_ registros)",
    infoPostFix: "",
    loadingRecords: "Cargando...",
    zeroRecords: "No se encontraron resultados coincidentes",
    emptyTable: "No hay información disponible en la tabla",
    paginate: {
        first: "Primero",
        previous: "Anterior",
        next: "Siguiente",
        last: "Último"
    },
    aria: {
        sortAscending: ": Activar para ordenar la columna ascendente",
        sortDescending: ": Activar para ordenar la columna descendente"
    },
    buttons: {
        copy: "Copiar",
        colvis: "Visibilidad",
        collection: "Colección",
        colvisRestore: "Restaurar visibilidad",
        copyTitle: "Copiar al portapapeles",
        copySuccess: {
            1: "Copiada 1 fila al portapapeles",
            _: "Copiadas %d filas al portapapeles"
        },
        csv: "CSV",
        excel: "Excel",
        pageLength: {
            "-1": "Mostrar todas las filas",
            _: "Mostrar %d filas"
        },
        pdf: "PDF",
        print: "Imprimir"
    }
};

if (typeof window !== 'undefined') {
    window.DATATABLES_ESPANOL = DATATABLES_ESPANOL;
    if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.dataTable) {
        jQuery.extend(true, jQuery.fn.dataTable.defaults, {
            language: DATATABLES_ESPANOL
        });
    }
}
