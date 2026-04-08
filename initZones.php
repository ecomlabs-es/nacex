<?php
include_once dirname(__FILE__) . "/nacexDAO.php";
include_once dirname(__FILE__) . "/nacex.php";
//SET ENVIRONMENT
/*include('../../config/config.inc.php');
include('../../init.php');*/

include_once dirname(__FILE__) . '/../../config/config.inc.php';

//include_once dirname(__FILE__) . '/../../config/config.inc.php';
// Está habiendo un problema con la inclusión de este archivo: include_once dirname(__FILE__) . '/../../init.php';

session_start();

$nacex = new nacex();
nacexDAO::initNcxZones();
echo "<div class=\"bootstrap\" style=\"margin-top:10px\"><div class=\"alert alert-success conf\" style=\"width:auto\">" . $nacex->l('Zones created and initialised') . "</div></div>";
