<html>
<head>
    <meta http-equiv='content-type' content='text/html;charset=iso-8859-1'>

    <?php
    $host = $_GET ['host'];
    $cp = $_GET ['cp'];
    $agencias_clientes = $_GET ['clientes'];
    $opc = $_GET ['opc'];
    $url = $_GET ['uriPS'];
    ?>

    <script type='text/javascript'>
        let uri = '<?php echo $url;?>';
        let opc = '<?php echo $opc;?>';

        if (top.name != null && top.name !== '') {
            try {
                if (window.opener && !window.opener.closed) {
                    try {
                        window.opener.seleccionadoNacexShop('E', top.name, uri, opc);
                    } catch (e) {
                        // Cross-origin: usar postMessage como fallback
                        window.opener.postMessage({
                            type: 'nacexShopSelected',
                            tipo: 'E',
                            txt: top.name,
                            uri: uri,
                            opc: opc
                        }, '*');
                    }
                }
                top.name = '';
            } catch (e) {
                alert('Error' + e.message);
            }
            window.close();
        } else {
            top.name = document.location;
            document.location = 'https://<?php echo $host;?>/selectorNacexShop.do?codigo_postal=<?php echo $cp;?>&clientes=<?php echo $agencias_clientes;?>';
        }
    </script>
</head>
<body>
</body>
</html>
