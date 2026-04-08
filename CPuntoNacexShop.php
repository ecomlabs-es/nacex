<?php

//SET ENVIRONMENT
include dirname(__FILE__) . '/../../config/config.inc.php';
include dirname(__FILE__) . '/../../init.php';
session_start();

class CPuntoNacexShop
{
    private $codigo;
    private $alias;
    private $nombre;
    private $direccion;
    private $cp;
    private $poblacion;
    private $provincia;
    private $telefono;

    public function getCodigo()
    {
        return $this->codigo;
    }
    public function setCodigo($codigo)
    {
        $this->codigo = $codigo;
    }

    public function getAlias()
    {
        return $this->alias;
    }
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    public function getNombre()
    {
        return $this->nombre;
    }
    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }
    public function getDireccion()
    {
        return $this->direccion;
    }
    public function setDireccion($direccion)
    {
        $this->direccion = $direccion;
    }
    public function getCp()
    {
        return $this->cp;
    }
    public function setCp($cp)
    {
        $this->cp = $cp;
    }
    public function getPoblacion()
    {
        return $this->poblacion;
    }
    public function setPoblacion($poblacion)
    {
        $this->poblacion = $poblacion;
    }
    public function getProvincia()
    {
        return $this->provincia;
    }

    public function setProvincia($provincia)
    {
        $this->provincia = $provincia;
    }

    public function getTelefono()
    {
        return $this->telefono;
    }

    public function setTelefono($telefono)
    {
        $this->telefono = $telefono;
    }

    public function setDatosNXSH_Session($txt, $id_cart)
    {
        $datos = explode('|', $txt);

        if (count($datos) < 8) {
            return;
        }

        $cpnx = new CPuntoNacexShop();
        $cpnx->setCodigo($datos[0]);
        $cpnx->setAlias($datos[1]);
        $cpnx->setNombre($datos[2]);
        $cpnx->setDireccion($datos[3]);
        $cpnx->setCp($datos[4]);
        $cpnx->setPoblacion($datos[5]);
        $cpnx->setProvincia($datos[6]);
        $cpnx->setTelefono($datos[7]);

        if (isset($_COOKIE['opc_id_cart'])) {
            $idCart = (int) $_COOKIE['opc_id_cart'];
        } else {
            $idCart = (int) $id_cart;
        }

        if ($idCart <= 0) {
            return;
        }

        $query = Db::getInstance()->executeS(
            'SELECT ncx FROM ' . _DB_PREFIX_ . 'cart WHERE id_cart = ' . $idCart
        );

        // Guardamos los datos completos del punto en cart.ncx
        if (!empty($query)) {
            $shopData = $datos[0] . '|' . $datos[1] . '|' . $datos[2] . '|' . $datos[3] . '|' . $datos[4] . '|' . $datos[5] . '|' . $datos[6];
            Db::getInstance()->execute(
                'UPDATE ' . _DB_PREFIX_ . 'cart SET ncx = \'' . pSQL($shopData) . '\' WHERE id_cart = ' . $idCart
            );
        }
    }

    public function getDatosNXSH_Session()
    {
        $valor = '';

        $valor .= $this->getCodigo() . '|';
        $valor .= $this->getAlias() . '|';
        $valor .= $this->getNombre() . '|';
        $valor .= $this->getDireccion() . '|';
        $valor .= $this->getCp() . '|';
        $valor .= $this->getPoblacion() . '|';
        $valor .= $this->getProvincia() . '|';
        $valor .= $this->getTelefono();

        echo $valor;
    }

    public function unsetDatosNXSH_Session()
    {
        // Session-based storage removed in favor of cart-based storage
    }
}

$txt = Tools::getValue('txt', '');
$id_cart = Tools::getValue('cart', '');
$method = Tools::getValue('metodo_nacex', '');
$aux = new CPuntoNacexShop();

if ($method == 'setSession') {
    $aux->setDatosNXSH_Session($txt, $id_cart);
} elseif ($method == 'getSession') {
    $aux->getDatosNXSH_Session();
} elseif ($method == 'unsetSession') {
    $aux->unsetDatosNXSH_Session();
}
