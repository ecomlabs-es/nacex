class unitaria {
    static search(_idpedido, mess = '') {
        if (_idpedido === "" || isNaN(_idpedido)) {
            alert(mess);
            document.getElementById('idpedido').focus();
            return;
        }
        this.get_unitaria('search', Base_uri, _idpedido);
    }

    static get_unitaria(_method, _url, _idpedido = "") {
        _url += "modules/nacex/COunitaria.php";
        var _current_url = window.location.href;
        // De la URL de AdminOrders nos quedamos sólo con la parte del token
        var oToken = localStorage.getItem('ordersToken').split('&token=')[1];
        var controller = _current_url.split('controller=')[1].split('&token=')[0];
        //if(_idpedido === '') _idpedido = ['1'];
        $.ajax({
            type: 'GET',
            url: _url,
            data: 'method=' + _method + '&current_url=' + _current_url + '&oToken=' + oToken + '&pedido=' + _idpedido + '&controller=' + controller,
            dataType: 'json',
            beforeSend: $('#ncx-loading').show(),
        })
            .done(function (_data) {
                if (_data[0].cod_response === '100') {
                    document.getElementById('cabecera').innerHTML = _data[0].header;
//CALL WHEN YOU ARE COMING FROM ORDER DETAIL LIST
                    if (document.getElementById("idpedido").value !== "") {
                        unitaria.get_unitaria('unitaria', Base_uri, $("#idpedido").val());
                    }
                } else {
                    document.getElementById('resultado').innerHTML = _data[0].result;
                    if (_data[0].cod_response === '400') {
                        document.getElementById('resultado').style.backgroundColor = 'red';
//RUN FUNCTION nacexcontenido() IF nacex_contenido exits
                    } else {
                        if (typeof nacex_contenido != "undefined") {
                            nacexcontenido();
                        }
                    }
                    console.log("MODULO NACEX - INFO - FUNCION GET_UNITARIA COMPLETADA");
                }

                // Mantenemos el último valor seleccionado de fecha nacex del formulario
                //var fechaNacex = (typeof localStorage.nacex_fec !== 'undefined') ? localStorage.nacex_fec : moment(new Date()).format('YYYY-MM-DD');
				var fechaNacex = (typeof localStorage.nacex_fec !== 'undefined') ? localStorage.nacex_fec : formattedNow();
                $('#nacex_fec').val(fechaNacex);

                // Prealerta por defecto no cogía el valor
                var tipoPrealerta = $('input[name=nacex_tip_pre1]:checked').val();
                setprealerta(tipoPrealerta);
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
                document.getElementById('resultado').innerHTML = textStatus;
                console.error("MODULO NACEX - ERROR - FUNCION GET_UNITARIA - " + textStatus);
            })
            .complete(function () {
                $('#ncx-loading').hide();
            });
    }

    static post_order(_url, _idpedido, _form, _shop = '', mess = '', devolucion = false) {

        _url += "modules/nacex/COunitaria.php";

        _shop = _shop !== '';

        var _contenido = "";
        var _des_contenido = "";
        var _comision_reembolso = "O";
        //var _reembolso = 0;
        var _reembolso = _form.nacex_imp_ree.value;
        var _retorno = "NO";
        var _importe_declarado = 0;
        var _devolucion = 'method=';

        // Controlamos el reembolso máximo
        var _reembolso_max = $('#nacex_imp_ree').attr('max');

        if (_shop && _reembolso > _reembolso_max) {
            alert(mess + ' ' + _reembolso_max + '€');
            return false;
        }

//CONTROL VALUES FOR INTERNATIONAL
        if (typeof nacex_contenido != "undefined") {
            _contenido = document.getElementById("nacex_contenido").value;
            _des_contenido = document.getElementById("nacex_descripcion_contenido").value;
            _importe_declarado = document.getElementById("nacex_impDeclarado").value;
        } else {
            _comision_reembolso = _form.nacex_tip_ree.value;
            _reembolso = _form.nacex_imp_ree.value;
            _retorno = _form.nacex_ret.value;
        }

        if (devolucion) _devolucion += 'printDevolucion';
        else _devolucion += "crear_expedicion";

        var _data = _devolucion + '&id_pedido=' + _idpedido + '&nacex_agcli=' + _form.nacex_agcli.value +
            '&nacex_tip_ser=' + _form.nacex_tip_ser.value + '&nacex_tip_cob=' + _form.nacex_tip_cob.value +
            '&nacex_tip_ree=' + _comision_reembolso + '&nacex_tip_env=' + _form.nacex_tip_env.value +
            '&nacex_ret=' + _retorno + '&nacex_tip_seg=' + _form.nacex_tip_seg.value
            + '&nacex_imp_seg=' + _form.nacex_imp_seg.value + '&nacex_tip_pre1=' + _form.nacex_tip_pre1.value + '&nacex_pre1=' + _form.nacex_pre1.value
            + '&nacex_mod_pre1=' + _form.nacex_mod_pre1.value;
        if (typeof (_form.nacex_pre1_plus) !== "undefined" && _form.nacex_pre1_plus !== null)
            _data += '&nacex_pre1_plus=' + _form.nacex_pre1_plus.value;

        _data += '&inst_adi=' + _form.inst_adi.value + '&nacex_obs1=' + _form.nacex_obs1.value + '&nacex_obs2=' + _form.nacex_obs2.value
            + '&nacex_bul=' + _form.nacex_bul.value + '&nacex_fec=' + _form.nacex_fec.value + '&nacex_imp_ree=' + _reembolso + '&nacex_contenido=' + _contenido + '&nacex_descripcion_contenido=' + _des_contenido
            + '&nacex_impDeclarado=' + _importe_declarado + '&oToken=' + _form.oToken.value;

        $.ajax({
            type: 'POST',
            url: _url,
            data: _data,
            dataType: 'json',
            beforeSend: $('#ncx-loading').show(),
        })
            .done(function (_data) {
                document.getElementById('resultado').innerHTML = _data[0].result;
                console.log("MODULO NACEX - INFO - FUNCION POST_ORDER COMPLETADA");
                console.log(_data);
            })
            .fail(function (_data) {
                document.getElementById('resultado').innerHTML = _data;
                console.error("MODULO NACEX - ERROR - FUNCION POST_ORDER - " + _data);
            })
            .complete(function () {
                $('#ncx-loading').hide();
                window.scrollTo(0, 0);

                // Mostramos de nuevo la búsqueda de expediciones una vez finalizada la documentación de la expedición
                setTimeout(function () {
                    /*$('#ncx-loading').hide();
                    window.scrollTo(0, 0);*/
                    unitaria.search(_idpedido);
                }, 2000);
            });

    }
}