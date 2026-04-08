function initJsTables() {
    $('#tableCarriers').DataTable({
        "deferRender": true,
        'processing': true,
        'language': {
            'url': "https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json",
            'processing': '<img src="../modules/nacex/images/loading.gif" style="width:30px">'
        }
    });
    $('.dataTables_length').addClass('bs-select');
}
