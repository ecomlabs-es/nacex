// Listener para recibir selección NacexShop via postMessage (cross-origin fallback)
window.addEventListener('message', function (event) {
    if (event.data && event.data.type === 'nacexShopSelected') {
        seleccionadoNacexShop(event.data.tipo, event.data.txt, event.data.uri, event.data.opc);
    }
});

function unsetDatosSession(_url) {
    $.ajax({
        type: 'POST',
        url: _url + 'modules/nacex/CPuntoNacexShop.php',
        data: 'metodo_nacex=unsetSession',
        success: function () {
            console.log('NACEX LOG - UNSETDATOSSESSION - SUCCESS');
        }
    });
}

function setDatosSession(txt, _url, id_cart) {
    $.ajax({
        type: 'POST',
        url: _url + 'modules/nacex/CPuntoNacexShop.php',
        data: 'txt=' + txt + '&cart=' + id_cart + '&metodo_nacex=setSession',
        async: false,
        success: function () {
            console.log('NACEX LOG - SETDATOSSESSION - SUCCESS');
        }
    });
}

function getDatosSession(_url) {
    $.ajax({
        type: 'POST',
        url: _url + 'modules/nacex/CPuntoNacexShop.php',
        data: 'metodo_nacex=getSession',
        async: false,
        success: function (msg) {
            console.log('NACEX LOG - GETDATOSSESSION - SUCCESS');
            rellenarNacexShop(msg);
        }
    });
}

function seleccionadoNacexShop(tipo, txt, _url, opc) {
    rellenarNacexShop(txt);
    setDatosSession(txt, _url, id_cart);

    var datos = txt.replace(/~/g, ' ').split('|');
    var opc_shop_datos = datos.map(function (d) { return d.trim(); }).join('|');

    document.getElementById('shop_datos').value = opc_shop_datos;
    document.cookie = 'opc_id_cart=' + id_cart;
    document.cookie = 'opc_shop_datos=' + (datos[0] || '').trim();

    try {
        localStorage.setItem('nacex_shop_datos', txt);
        localStorage.setItem('nacex_shop_cart', id_cart);
    } catch (e) {}

    if (opc !== false) { $('#' + opc).prop('disabled', false); }
    else { document.getElementById('btnfinalizar').focus(); }
}

function ClearShop(_url) {
    $('#nxshop').val('');
    $('#nacexshopChosen, #nacexshopChosenTitle').hide();
    unsetDatosSession(_url);
    try {
        localStorage.removeItem('nacex_shop_datos');
        localStorage.removeItem('nacex_shop_cart');
    } catch (e) {}
}

function restaurarNacexShop() {
    try {
        var datos = localStorage.getItem('nacex_shop_datos');
        var cart = localStorage.getItem('nacex_shop_cart');
        if (datos && cart && typeof id_cart !== 'undefined' && cart == id_cart) {
            rellenarNacexShop(datos);
        }
    } catch (e) {}
}

function rellenarNacexShop(txt, idCart) {
    if (txt == null || typeof txt !== 'string' || txt.length <= 0 || txt.indexOf('|') === -1) {
        return false;
    }

    if (idCart) { document.cookie = 'opc_id_cart=' + idCart; }

    $('#shop_datos').val(txt);

    var datos = txt.replace(/~/g, ' ').split('|');
    $('#nxshop_codigo').val(datos[0]);
    $('#nxshop_alias').val(datos[1]);
    $('#nxshop_nombre').val(datos[2]);
    $('#nxshop_direccion').val(datos[3]);
    $('#nxshop_cp').val(datos[4]);
    $('#nxshop_poblacion').val(datos[5]);
    $('#nxshop_provincia').val(datos[6]);

    $('#nacexshopChosen, #nacexshopChosenTitle').show();
}

function hide_show(_object) {
    var el = document.getElementById(_object.name);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function seleccionar_punto_shop(_url, opc, mess) {
    var selected = $('input[name=shop_item]:checked');
    if (selected.attr('shopalias') == undefined) {
        alert(mess);
        return;
    }
    var txt = selected.attr('shopcodigo') + '|' + selected.attr('shopalias') + '|' + selected.attr('shopnombre') + '|' + selected.attr('shopdireccion') + '|' + selected.attr('puebcp') + '|' + selected.attr('puebnombre') + '|' + selected.attr('provnombre') + '|' + selected.attr('tlf');
    seleccionadoNacexShop('E', txt, _url, opc);
}

function modalWin(url) {
    var w = 820;
    var h = 650;
    var left = (screen.width - w) / 2;
    var top = (screen.height - h) / 2;
    window.open(url, '', 'height=' + h + ',width=' + w + ',top=' + (top - 10) + ',left=' + left + ',toolbar=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,modal=yes');
}
