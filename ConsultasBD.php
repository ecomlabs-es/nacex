<?php

function getCarrierById($id_carrier) {
    $query = 'SELECT * 
				  FROM ' . _DB_PREFIX_ . "carrier
				  where active=1 
				  and deleted = 0
				  and (upper(ncx) = 'NACEX' || upper(ncx)='NACEXSHOP')
				  and id_carrier = " . $id_carrier;

    return $datoscarrier = Db::getInstance()->ExecuteS($query);
}
function getDatosExpedicion($id_pedido) {
    $query = 'SELECT id_envio_order, agcli, fecha_alta, ref,
						 exp_cod, ag_cod_num_exp, color, ent_ruta, 
						 ent_cod, ent_nom, ent_tlf, serv_cod,
						 serv,  hora_entrega, barcode, fecha_objetivo, 
						 cambios, bultos, shop_codigo, shop_alias,	
						 shop_nombre, shop_direccion, shop_cp, shop_poblacion,
						 shop_provincia, shop_telefono, ret, estado,
						 fecha_estado, imp_ree 
				  FROM ' . _DB_PREFIX_ . 'nacex_expediciones
				  where id_envio_order = ' . $id_pedido;

    return $datosexpedicion = Db::getInstance()->ExecuteS($query);
}
function getDatosPedido($id_order) {
    return Db::getInstance()->ExecuteS('SELECT o.id_order,o.module,u.email,a.firstname,
				a.lastname,a.address1,a.postcode,a.city,a.phone,a.phone_mobile,z.iso_code,c.ncx,o.id_carrier,
				case when o.total_paid_real > 0 
					then o.total_paid_real
				else
					o.total_paid
				end as total_paid_real 
				FROM ' . _DB_PREFIX_ . 'orders AS o
				JOIN ' . _DB_PREFIX_ . 'customer AS u
				JOIN ' . _DB_PREFIX_ . 'cart AS c
				JOIN ' . _DB_PREFIX_ . 'address a
				JOIN ' . _DB_PREFIX_ . 'country AS z
				WHERE o.id_order = "' . $id_order . '"
				AND u.id_customer = o.id_customer
				AND c.id_cart = o.id_cart
				AND a.id_address = o.id_address_delivery
				AND a.id_country = z.id_country');
}
function cancelarExpedicion($id_order, $cod_exp) {
    writeNacexLog('----');
    writeNacexLog('INI cancelarExpedicion :: id_order: ' . $id_order .' cod_exp: '.$cod_exp);

    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . "nacex_expediciones 
                                   SET
                        				fecha_baja = '" . date('Y-m-d H:i:s') . "',
                        				estado = 'BAJA',
                        				fecha_estado='" . date('Y-m-d H:i:s') . "'
                    			    WHERE id_envio_order = '" . $id_order . "' and cod_exp = '" . $cod_exp . "'");

    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . "orders 
                                   SET shipping_number=null 
	                               WHERE id_order = '" . $id_order . "' and shipping_number = '" . $cod_exp . "'");

    writeNacexLog('UPDATE cancelarExpedicion :: Updateada expedicion en nacex_expediciones');
    writeNacexLog('----');
    writeNacexLog('FIN cancelarExpedicion :: id_order: ' . $id_order .' cod_exp: '.$cod_exp);
}

function createTablesIfNotExists() {

    if (isset($_SESSION['NCX_checked_tables']) && $_SESSION['NCX_checked_tables']) {
        return true; }

    writeNacexLog('----');
    writeNacexLog('INI createTablesIfNotExists');

    // Creamos la tabla para las expediciones
    $query = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'nacex_expediciones (
			id_envio_order int(11) NOT NULL,
			agcli varchar(10),
			fecha_alta datetime NOT NULL,
			fecha_baja datetime,
			ref varchar(20),
			exp_cod varchar(255),
			ag_cod_num_exp varchar(255),
			color varchar(255),
			ent_ruta varchar(255),
			ent_cod varchar(255),
			ent_nom varchar(255),
			ent_tlf varchar(255),
			serv_cod varchar(2),
			serv varchar(255),
			hora_entrega varchar(255),
			barcode varchar(255),
			fecha_objetivo varchar(255),
			cambios varchar(255),
			bultos varchar(3),			 
			shop_codigo 	varchar(6),
			shop_alias 	varchar(20),
			shop_nombre 	varchar(60),
			shop_direccion 	varchar(60),			
			shop_cp 	varchar(7),
			shop_poblacion varchar(60),
			shop_provincia 	varchar(60),
			shop_telefono 	varchar(60),
			ret 	varchar(2),
			estado varchar(20),
			fecha_estado datetime,
			imp_ree decimal(8,3),
		PRIMARY KEY (`id_envio_order`)
		) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

    if (! Db::getInstance()->Execute($query)) {
        writeNacexLog('createTablesIfNotExists :: Error al crear tabla nacex_expediciones');
        return false;
    }

    // Creamos la tabla para el historico de expediciones
    $query = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'nacex_expediciones_his (
			id_envio_order int(11) NOT NULL,
			agcli varchar(10),
			fecha_alta datetime NOT NULL,
			fecha_baja datetime,
			ref varchar(20),
			exp_cod varchar(255),
			ag_cod_num_exp varchar(255),
			color varchar(255),
			ent_ruta varchar(255),
			ent_cod varchar(255),
			ent_nom varchar(255),
			ent_tlf varchar(255),
			serv_cod varchar(2),
			serv varchar(255),
			hora_entrega varchar(255),
			barcode varchar(255),
			fecha_objetivo varchar(255),
			cambios varchar(255),
			bultos varchar(3),			 
			shop_codigo 	varchar(6),
			shop_alias 	varchar(20),
			shop_nombre 	varchar(60),
			shop_direccion 	varchar(60),			
			shop_cp 	varchar(7),
			shop_poblacion varchar(60),
			shop_provincia 	varchar(60),
			shop_telefono 	varchar(60),
			ret 	varchar(2),
			estado varchar(20),
			fecha_estado datetime,
			imp_ree decimal(8,3),
		PRIMARY KEY (`id_envio_order`,`ag_cod_num_exp`)
		) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

    if (! Db::getInstance()->Execute($query)) {
        writeNacexLog('createTablesIfNotExists :: Error al crear tabla nacex_expediciones_his');
        return false;
    }

    // if(!Db::getInstance()->execute("select ncx from "._DB_PREFIX_."cart limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "cart LIKE 'ncx'");
    if (Db::getInstance()->Affected_Rows() == 0) {

        // A�adimos campo adicional Nacex, en CART
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'cart ADD COLUMN ncx varchar (350)';
        try {
            Db::getInstance()->Execute($query);
        } catch (Exception $e) {
            writeNacexLog('createTablesIfNotExists :: Error al alter table [ncx] en ' . _DB_PREFIX_ . 'cart');
        }
    }
    // Forzamos a aumentar tama�o en este campo ncx de Cart para versiones anteriores con menor tama�o.
    // En este campo guardamos info NacexShop
    $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'cart MODIFY ncx varchar (350)';
    Db::getInstance()->Execute($query);

    /* VERSIONES ANTERIORES */
    // A�adimos campos adicionales
    // if(!Db::getInstance()->execute("select agcli from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'agcli'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN agcli varchar (10)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [agcli][varchar(10)]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }
    // if(!Db::getInstance()->execute("select bultos from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'bultos'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN bultos varchar (3)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [bultos][varchar(3)]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }
    // if(!Db::getInstance()->execute("select shop_codigo from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'shop_codigo'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN shop_codigo varchar (6)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [shop_codigo][varchar(6)]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }
    // if(!Db::getInstance()->execute("select shop_alias from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'shop_alias'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN shop_alias varchar (20)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [shop_alias][varchar(20)]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }
    // if(!Db::getInstance()->execute("select shop_nombre from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'shop_nombre'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN shop_nombre varchar (60)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [shop_nombre][varchar(60)]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }
    // if(!Db::getInstance()->execute("select shop_direccion from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'shop_direccion'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN shop_direccion varchar (60)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [shop_direccion][varchar(60)]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }

    // if(!Db::getInstance()->execute("select shop_cp from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'shop_cp'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN shop_cp varchar (7)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [shop_cp][varchar(7)]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }
    // if(!Db::getInstance()->execute("select shop_poblacion from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'shop_poblacion'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN shop_poblacion varchar (60)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [shop_poblacion][varchar(60)]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }
    // if(!Db::getInstance()->execute("select shop_provincia from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'shop_provincia'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN shop_provincia varchar (60)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [shop_provincia][varchar(60)]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }
    // if(!Db::getInstance()->execute("select shop_telefono from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'shop_telefono'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN shop_telefono varchar (60)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [shop_telefono][varchar(60)]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }
    // if(!Db::getInstance()->execute("select ret from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'ret'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN ret varchar (2)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [ret][varchar(2)]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }
    // Creamos columna ncx en Carrier
    // if(!Db::getInstance()->execute("select ncx from "._DB_PREFIX_."carrier limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "carrier LIKE 'ncx'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'carrier ADD COLUMN ncx varchar (50)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [ncx][varchar(50)]en ' . _DB_PREFIX_ . 'carrier');
        } catch (Exception $e) {
        }
    }
    // Creamos campo serv_cod para documentacion nacex c@mbio
    // if(!Db::getInstance()->execute("select serv_cod from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'serv_cod'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN serv_cod varchar (2)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [serv_cod][varchar(2)]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }
    // Creamos campo estado para documentacion nacex c@mbio
    // if(!Db::getInstance()->execute("select estado from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'estado'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN estado varchar (20)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [estado][varchar(20)]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }
    // Creamos campo estado para documentacion nacex c@mbio
    // if(!Db::getInstance()->execute("select fecha_estado from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'fecha_estado'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN fecha_estado datetime';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [fecha_estado][datetime]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }
    // A�adimos columna en la tabla carrier para guardar el id del servicio nacex correspondiente a cada carrier.
    // if(!Db::getInstance()->execute("select tip_serv from "._DB_PREFIX_."carrier limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "carrier LIKE 'tip_serv'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'carrier ADD COLUMN tip_serv varchar (2)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [tip_serv][varchar(2)]en ' . _DB_PREFIX_ . 'carrier');
        } catch (Exception $e) {
        }
    }
    // Creamos campo imp_ree en tabla nacex_expediciones
    // if(!Db::getInstance()->execute("select imp_ree from "._DB_PREFIX_."nacex_expediciones limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones LIKE 'imp_ree'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD COLUMN imp_ree decimal(8,3)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [imp_ree][decimal(5,3)]en ' . _DB_PREFIX_ . 'nacex_expediciones');
        } catch (Exception $e) {
        }
    }
    // Creamos campo imp_ree en tabla nacex_expediciones_his
    // if(!Db::getInstance()->execute("select imp_ree from "._DB_PREFIX_."nacex_expediciones_his limit 0")) {
    Db::getInstance()->Execute('SHOW COLUMNS FROM ' . _DB_PREFIX_ . "nacex_expediciones_his LIKE 'imp_ree'");
    if (Db::getInstance()->Affected_Rows() == 0) {
        $query = 'ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones_his ADD COLUMN imp_ree decimal(8,3)';
        try {
            Db::getInstance()->Execute($query);
            writeNacexLog('createTablesIfNotExists :: Alter table [imp_ree][decimal(5,3)]en ' . _DB_PREFIX_ . 'nacex_expediciones_his');
        } catch (Exception $e) {
        }
    }

    //update V2.1.0.3

    $existe_constrain = Db::getInstance()->ExecuteS("select CONSTRAINT_NAME
                                   from information_schema.KEY_COLUMN_USAGE
                                   where TABLE_NAME = '" . _DB_PREFIX_ . "nacex_expediciones' and 
                                   CONSTRAINT_NAME ='UC_NCX_expedicion'
                                   limit 1");

    if (! $existe_constrain) {
        try {
            Db::getInstance()->Execute('ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones DROP PRIMARY KEY ');
            Db::getInstance()->Execute('ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD INDEX (id_envio_order)');
            Db::getInstance()->Execute('ALTER TABLE ' . _DB_PREFIX_ . 'nacex_expediciones ADD CONSTRAINT UC_NCX_expedicion UNIQUE (id_envio_order,exp_cod) ');

            writeNacexLog('createTablesIfNotExists : update V2.1.0.3');
        } catch (Exception $e) {
            writeNacexLog('ERROR createTablesIfNotExists : update V2.1.0.3');
        }
    }

    writeNacexLog('FIN createTablesIfNotExists');
    writeNacexLog('----');

    $_SESSION['NCX_checked_tables'] = true;
}
