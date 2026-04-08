class nacexlogs {
    static get(_method, _url, message = "", _file = "*") {
        let _message = message;
        if (_message !== "") {
            if (!confirm(_message)) {
                return;
            }
        }
        _url += "modules/nacex/COnacexlogs.php";
        var _current_url = window.location.href;
        $.ajax({
            type: 'GET',
            url: _url,
            data: 'method=' + _method + '&file=' + _file + '&current_url=' + _current_url,
            dataType: 'json',
            beforeSend: function() { jQuery.LoadingOverlay("show"); },
        })
            .done(function (_data) {
                if (_data[0].cod_response === '404') {
                    document.getElementById('resultado').innerHTML = _data[0].result;
                    document.getElementById('resultado').style.backgroundColor = 'orange';
                } else {
                    document.getElementById('cabecera').innerHTML = _data[0].header;
                    document.getElementById('resultado').innerHTML = _data[0].result;
                }
                console.log("MODULO NACEX - INFO - FUNCION GET_LOG COMPLETADA");
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                document.getElementById('resultado').innerHTML =
                    '<div class="alert alert-danger">Error: ' + textStatus + ' - ' + (errorThrown || 'No se pudo conectar') + '</div>';
                console.error("MODULO NACEX - ERROR - FUNCION GET_LOG - " + textStatus + ": " + errorThrown);
            })
            .always(function() {
                jQuery.LoadingOverlay("hide");
            });
    }
}
