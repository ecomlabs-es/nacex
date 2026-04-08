<html>
<head>
    <meta http-equiv='content-type' content='text/html;charset=iso-8859-1'>

    <?php
    $host = $_GET ['host'];
    $cp = $_GET ['cp'];
    $agencias_clientes = $_GET ['clientes'];
    $opc = $_GET ['opc'];
    //$url = $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REWRITEBASE"];
    $url = $_GET ['uriPS'];
    ?>

    <script type='text/javascript'>
        let uri = '<?php echo $url;?>';
        let opc = '<?php echo $opc;?>';

        if (top.name != null && top.name !== '') {
            try {
                window.opener.seleccionadoNacexShop('E', top.name, uri, opc);
                top.name = '';
            } catch (e) {
                alert('Error' + e.message);
            }
            window.close();
        } else {
            //http://sv553.altadis.com:8082/selectorNacexShop.do
            top.name = document.location;
            // En vez de codigp_postal hemos probado también con el CP_POB que es el name y el id del campo pero no se rellena tampoco
            document.location = 'https://<?php echo $host;?>/selectorNacexShop.do?codigo_postal=<?php echo $cp;?>&clientes=<?php echo $agencias_clientes;?>';
        }
    </script>
</head>
<body>
</body>
</html>