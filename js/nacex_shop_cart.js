/*function GetShop(carrier_sel,_url,codigo_postal_entrega,agencias_clientes) {
    $('#order').fadeTo('fast', 0.4);
    //modalWin(_url+'modules/nacex/COnxShop.php?cp=' + codigo_postal_entrega + '&clientes=' + agencias_clientes,200,200);
    //_url=_url+'modules/nacex/COnxShop.php?cp=' + codigo_postal_entrega + '&clientes=' + agencias_clientes;
    //tb_show('', 'http://magento2.nacex.local/ps1760/modules/nacex/COnxShop.php/?KeepThis=true&TB_iframe=true&height=409&width=674 &modal=false', 'null');
    $('#order').fadeTo('fast', 1);
    return false;
}*/
function unsetDatosSession(_url) {
    $.ajax({
        type: 'POST',
        url: _url + 'modules/nacex/CPuntoNacexShop.php',
        data: 'metodo_nacex=unsetSession',
        async: false,
        success: function (msg) {
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
        success: function (msg) {
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

    var shopc = $('#nxshop_codigo').val();
    var shopa = $('#nxshop_alias').val();
    var shopn = $('#nxshop_nombre').val();
    var shopd = $('#nxshop_direccion').val();
    var shopcp = $('#nxshop_cp').val();
    var shopp = $('#nxshop_poblacion').val();
    var shoppr = $('#nxshop_provincia').val();

    let opc_shop_datos = shopc.trim() + '|' + shopa.trim() + '|' + shopn.trim() + '|' + shopd.trim() + '|' + shopcp.trim() + '|' + shopp.trim() + '|' + shoppr.trim();
    document.getElementById('shop_datos').value = opc_shop_datos;
    document.cookie = 'opc_id_cart=' + id_cart;
    document.cookie = 'opc_shop_datos=' + encodeURIComponent(opc_shop_datos);

    // Guardar en localStorage para persistir entre pasos del checkout
    try {
        localStorage.setItem('nacex_shop_datos', txt);
        localStorage.setItem('nacex_shop_cart', id_cart);
    } catch (e) {}

    if (opc !== false) $('#' + opc).prop('disabled', false);
    else document.getElementById("btnfinalizar").focus();
}

function ClearShop(_url) {
    $('#nxshop').val('');
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

function rellenarNacexShop(txt, idCart = 0) {

    //1085|0831-03|LIBRERÃA OPERA|Major 7|08870|SITGES|BARCELONA|938942143
    if (txt == null || typeof txt !== 'string' || txt.length <= 0 || txt.indexOf('|') === -1) {
        return false
    }

    // Actualizamos también id del carrito para el caso de que tengamos seleccionado un punto
    if (idCart !== 0) document.cookie = 'opc_id_cart=' + idCart;

    $('#shop_datos').val(txt);
    //$('#shop_datos').value = txt;

    //var datos = txt.split('|');
    var datos = txt.replace(/~/g, " ").split('|');

    //if(datos.length >= 8 && datos[0] !== '') {
    $('#nxshop_codigo').val(datos[0]);
    $('#nxshop_alias').val(datos[1]);
    $('#nxshop_nombre').val(datos[2]);
    $('#nxshop_direccion').val(datos[3]);
    $('#nxshop_cp').val(datos[4]);
    $('#nxshop_poblacion').val(datos[5]);
    $('#nxshop_provincia').val(datos[6]);
    //$('#nxshop_telefono').val(datos[7]);
    //}
}
function hide_show (_object){
    /* if (document.getElementById(_object.name).style.visibility=='hidden'){
             document.getElementById(_object.name).style.visibility = 'visible';
         }else{
             document.getElementById(_object.name).style.visibility = 'hidden';
         }*/
    if (document.getElementById(_object.name).style.display == 'none') {
        document.getElementById(_object.name).style.display = 'block';
    } else {
        document.getElementById(_object.name).style.display = 'none';
    }
}

function seleccionar_punto_shop(_url, opc, mess) {
    if ($('input[name=shop_item]:checked').attr('shopalias') == undefined) {
        alert(mess);
        return;
    }
    seleccionadoNacexShop('E', txt = $('input[name=shop_item]:checked').attr('shopcodigo') + '|' + $('input[name=shop_item]:checked').attr('shopalias') + '|' + $('input[name=shop_item]:checked').attr('shopnombre') + '|' + $('input[name=shop_item]:checked').attr('shopdireccion') + '|' + $('input[name=shop_item]:checked').attr('puebcp') + '|' + $('input[name=shop_item]:checked').attr('puebnombre') + '|' + $('input[name=shop_item]:checked').attr('provnombre') + '|' + $('input[name=shop_item]:checked').attr('tlf'), _url, opc);
}

/*function seleccionar_punto_map(_url, _punto, opc = false) {
    seleccionadoNacexShop('E', _punto, _url, opc);
}*/

// Para abrir la nueva ventana y recuperar los valores del punto seleccionado
function modalWin(url) {
    let windowWidth = 820;
    let windowHeight = 650;
    let LeftPosition = (screen.width) ? (screen.width - windowWidth) / 2 : 0;
    let TopPosition = (screen.height) ? (screen.height - windowHeight) / 2 : 0;
    //let ventana = window.open(url, '', 'height=550,width=820,top=' + (TopPosition - 10) + ',left=' + LeftPosition + ',toolbar=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,location=no,modal=yes');
    window.open(url, '', 'height=' + windowHeight + ',width=' + windowWidth + ',top=' + (TopPosition - 10) + ',left=' + LeftPosition + ',toolbar=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,modal=yes');
    //window.open(url, '', 'height=650,width=820,toolbar=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,modal=yes');
}