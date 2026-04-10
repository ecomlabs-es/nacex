<?php

//require_once(dirname(__FILE__) . '/CheckVersion.php');
require_once(dirname(__FILE__) . '/UserGuide.php');
require_once(dirname(__FILE__) . '/VInewServices.php');

function getFormularioConfiguracion($obj)
{
    $nacex = new nacex();
    $nacexDTO = new nacexDTO();
    $newServices = new VInewServices();

    $errores = $obj->getErroresConfiguracion();
    $paymentModules = Module::getPaymentModules();
    //Añadimos los módulos que pueden dar problemas a los métodos de pago
    nacexutils::getPaymentModulesExtra($paymentModules);

    $divpayment = '';

    if ($paymentModules) {
        $payment_methods = [];
        foreach ($paymentModules as $payment) {
            $module = Module::getInstanceByName($payment['name']);
            if (Validate::isLoadedObject($module) && $module->active) {
                $payment_methods[] = [
                    'label' => $module->displayName,
                    'value' => $module->name
                ];
            }
        }

        $paymentOptions = [];
        foreach ($payment_methods as $pm) {
            $paymentOptions[$pm['value']] = $pm['label'];
        }
        $divpayment .= nacexutils::renderCheckboxGroup('nacex_modulos_reembolso', 'NACEX_MODULOS_REEMBOLSO', '|', $paymentOptions);
    }

    // Recuperamos los servicios que hay creados
    $id_lang = nacexutils::getCurrentLang();
    $iso_code = Language::getIsoById($id_lang);
    $services = Carrier::getCarriers($id_lang);

    if ($services) {
        $serviceOptions = [];
        foreach ($services as $ser) {
            $serviceOptions[$ser['id_carrier']] = $ser['name'];
        }
        $divservices = nacexutils::renderCheckboxGroup('nacexshop_external_modules', 'NACEXSHOP_EXTERNAL_MODULES', '|', $serviceOptions);
    }

    $link = new Link();
    $pagoUrl = $link->getAdminLink('AdminPaymentPreferences', true, ['action' => 'list']);

    //$config_pdf = __PS_BASE_URI__ . "modules/nacex/docs/Nacex_Prestashop_Configuracion.pdf";

    /* Descargar manual por FTP */
    //$config_pdf = 'javascript:void(0)';
    $config_pdf = 'https://www.nacex.com/pages/img/ecommerce/prestashop/Manual%20Prestashop%20ES.pdf';
    //$ug = new UserGuide();

    $archivo_log = _PS_MODULE_DIR_ . 'nacex/log/nacex_' . date('Ymd') . '.log';
    $url_log = __PS_BASE_URI__ . 'modules/nacex/log/' . 'nacex_' . date('Ymd') . '.log';
    //$link_log = "";
    if (file_exists($archivo_log)) {
        //Se sustituye por la opción de gestión de logs.
        //$link_log = "<a href=\"$url_log\" target=\"_blank\"><img src=\"" . $nacexDTO->getPath() . "/img/lupa.gif\" title=\"Ver archivo de log\"  alt=\"Ver archivo de log\" style=\"right: 18px; position: relative;\"></a>";
    }

    $html = "<link type='text/css' rel='stylesheet' href='" . _MODULE_DIR_ . "nacex/css/nacex.css' />";
    // Incluimos el JS de las tablas
    $html .= '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.12.1/r-2.3.0/sl-1.4.0/datatables.min.css"/>';
    $html .= '<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.12.1/r-2.3.0/sl-1.4.0/datatables.min.js"></script>';
    $html .= '<script type="text/javascript" src="' . _MODULE_DIR_ . 'nacex/js/nacexCarriersTableFilter.js"></script>';

    $html .= '<script type="text/javascript" src="' . _MODULE_DIR_ . 'nacex/js/newServices.js"></script>
    <script type="text/javascript" src="' . _MODULE_DIR_ . 'nacex/js/nacex.js"></script>
        <script type="text/javascript">
            $(document).ready(function(){
                $("#configForm").on("keypress", function(e) {
                    var code = e.keyCode || e.which; 
                    if (code  == 13) {               
                        e.preventDefault();
                        return false;
                    }
                });
                
                var impMinGrat = "' . Tools::getValue('nacex_importe_min_grat_val', Configuration::get('NACEX_IMP_MIN_GRAT_VAL')) . '";
                var impMinGratShp = "' . Tools::getValue('nacexshop_importe_min_grat_val', Configuration::get('NACEXSHOP_IMP_MIN_GRAT_VAL')) . '";
                var impMinGratInt = "' . Tools::getValue('nacexint_importe_min_grat_val', Configuration::get('NACEXINT_IMP_MIN_GRAT_VAL')) . '";
                                                        
                // Habilitamos o deshabilitamos campos según la opción que tengamos marcada al principio
                if($("select[name=\'nacex_calculo_importe_std\']").val() == "flat_rate") {
                    enableValor(\'nacex_importe_fijo_val\', false);
                    //disableImpMinGrat(\'nacex_importe_min_grat\',\'nacex_importe_min_grat_val\');                                                
                } else {
                    disableValor(\'nacex_importe_fijo_val\');
                    //enableImpMinGrat(\'nacex_importe_min_grat\',\'nacex_importe_min_grat_val\', impMinGrat);
                }
                if($("select[name=\'nacex_calculo_importe_shp\']").val() == "flat_rate") {
                    enableValor(\'nacexshop_importe_fijo_val\', false);
                    //disableImpMinGrat(\'nacexshop_importe_min_grat\',\'nacexshop_importe_min_grat_val\');                                                
                } else {
                    disableValor(\'nacexshop_importe_fijo_val\');
                    //enableImpMinGrat(\'nacexshop_importe_min_grat\',\'nacexshop_importe_min_grat_val\', impMinGratShp);
                }
                if($("select[name=\'nacex_calculo_importe_int\']").val() == "flat_rate") {
                    enableValor(\'nacexint_importe_fijo_val\', false);
                    //disableImpMinGrat(\'nacexint_importe_min_grat\',\'nacexint_importe_min_grat_val\');                                                
                } else {
                    disableValor(\'nacexint_importe_fijo_val\');
                    //enableImpMinGrat(\'nacexint_importe_min_grat\',\'nacexint_importe_min_grat_val\', impMinGratInt);
                }
                
                // Habilitamos o deshabilitamos campos al seleccionar una opción
                $("select[name=\'nacex_calculo_importe_std\']").on("change", function() {
                    if(this.value == "flat_rate") {
                        enableValor(\'nacex_importe_fijo_val\');
                        //disableImpMinGrat(\'nacex_importe_min_grat\',\'nacex_importe_min_grat_val\');                                                
                    } else {
                        disableValor(\'nacex_importe_fijo_val\');
                        //enableImpMinGrat(\'nacex_importe_min_grat\',\'nacex_importe_min_grat_val\', impMinGrat);
                    }
                });
                $("select[name=\'nacex_calculo_importe_shp\']").on("change", function() {
                    if(this.value == "flat_rate") {
                        enableValor(\'nacexshop_importe_fijo_val\');
                        //disableImpMinGrat(\'nacexshop_importe_min_grat\',\'nacexshop_importe_min_grat_val\');                                                
                    } else {
                        disableValor(\'nacexshop_importe_fijo_val\');
                        //enableImpMinGrat(\'nacexshop_importe_min_grat\',\'nacexshop_importe_min_grat_val\', impMinGratShp);
                    }
                });
                $("select[name=\'nacex_calculo_importe_int\']").on("change", function() {
                    if(this.value == "flat_rate") {
                        enableValor(\'nacexint_importe_fijo_val\');
                        //disableImpMinGrat(\'nacexint_importe_min_grat\',\'nacexint_importe_min_grat_val\');                                                
                    } else {
                        disableValor(\'nacexint_importe_fijo_val\');
                        //enableImpMinGrat(\'nacexint_importe_min_grat\',\'nacexint_importe_min_grat_val\', impMinGratInt);
                    }
                });
                
                // Inicializamos valor para *show developer options*
                if($("input[name=\'nacex_show_dev_ops\']:checked").val() === "SI") $("tr[data-depends=\'nacex_show_dev_ops\']").show();
                else $("tr[data-depends=\'nacex_show_dev_ops\']").hide();
                    
                // Mostramos u ocultamos las opciones de desarrollador
                $("input[name=\'nacex_show_dev_ops\']").on("change", function() {
                    $("tr[data-depends=\'nacex_show_dev_ops\']").toggle("display");
                });
                                    
                        
            });
            function disableValor(obj_name){
                $(\'input[name="\'+obj_name+\'"]\').val("");
                $(\'input[name="\'+obj_name+\'"]\').prop("disabled", true);
            }
            function enableValor(obj_name, focus = true) {
                $(\'input[name="\'+obj_name+\'"]\').prop("disabled", false);
                if(focus) $(\'input[name="\'+obj_name+\'"]\').focus();
            }
            function addValue(obj_name, value) {
                $(\'input[name="\'+obj_name+\'"]\').val(value);
            }
            function disablePrealerta(){
                $(\'input[name="nacex_preal_plus_txt"]\').val("");
                $(\'input[name="nacex_preal_plus_txt"]\').prop("disabled", true);	
                $(\'input[name="nacex_mod_preal"]\').each(function(i) {
                    if($(this).val()=="S"){
                        $(this).prop("checked", true);
                    }	
                    $(this).prop("disabled", true);	
                });
            }
            function enablePrealerta(){
                $(\'input[name="nacex_mod_preal"]\').each(function(i) {
                        $(this).prop("disabled", false);	
                });
            }
            function disableImpMinGrat(obj1, obj2){
                $(\'input[name="\'+obj2+\'"]\').val("");	
                //$(\'input[name="\'+obj1+\'"][value="NO"]\').prop("checked",true);
                $(\'input[name="\'+obj1+\'"]\').prop("disabled", true);
                $(\'input[name="\'+obj2+\'"]\').prop("disabled", true);
            }
            function enableImpMinGrat(obj1, obj2, val){
                //$(\'input[name="\'+obj1+\'"][value="NO"]\').prop("checked",true);
                $(\'input[name="\'+obj1+\'"]\').prop("disabled", false);
                if($(\'input[name="\'+obj1+\'"]:checked\').val() == "SI") {
                    $(\'input[name="\'+obj2+\'"]\').prop("disabled", false);
                    $(\'input[name="\'+obj2+\'"]\').val(val);
                } else {
                    $(\'input[name="\'+obj2+\'"]\').prop("disabled", true);
                }
            }
        </script>';

    if (Tools::isSubmit('submitSave')) {
        if (! count($errores)) {
            $html .= '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-success conf" style="width:auto">';
            $html .= '<strong>' . $obj->l('Module Nacex is configured and online!') . '</strong>';
            $html .= '</div></div>';
        } else {
            $keys = array_keys($errores);
            $html .= '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-danger error" style="width:auto">';
            $html .= '<strong>' . $obj->l('Module Nacex is not configured yet!') . '</strong><br>';
            $html .= $obj->l('Please, check possible mistakes and try to save configuration again.');
            $html .= '</div></div>';

            // Posiciona el scroll y el foco en el primer campo erróneo
            $html .= '<script type="text/javascript">
											$(document).ready(function(){
												$("html, body").animate({scrollTop: ($("#' . $keys[0] . '").offset().top)-100},600);
												$("input[name=\"' . $keys[0] . '\"]").focus();												
											});
										</script>';
        }
    }

    // Mediante este código validamos que la URL de impresión por defecto sea http://www.nacex.es/applets
    /* $url_imp = Tools::getValue('nacex_print_url');
     if (! $url_imp) {
         Configuration::updateValue('NACEX_PRINT_URL', $nacexDTO->getURL_PRO_Applets());
     }*/

    // Selección servicios desde el Frontend o desde el Backend
    $serv_back_or_front_B = 'checked="checked"';
    $serv_back_or_front_F = '';
    if (Tools::getValue('nacex_serv_back_or_front', Configuration::get('NACEX_SERV_BACK_OR_FRONT')) == 'B') {
        $serv_back_or_front_B = 'checked="checked"';
        $serv_back_or_front_F = '';
    } elseif (Tools::getValue('nacex_serv_back_or_front', Configuration::get('NACEX_SERV_BACK_OR_FRONT')) == 'F') {
        $serv_back_or_front_F = 'checked="checked"';
        $serv_back_or_front_B = '';
    }

    // Mostrar estado en la visualización del pedido por parte del cliente desde el Frontend
    $show_f_expe_state_no = '';
    $show_f_expe_state_si = 'checked="checked"';
    if (Tools::getValue('nacex_show_f_expe_state', Configuration::get('NACEX_SHOW_F_EXPE_STATE')) == 'SI') {
        $show_f_expe_state_si = 'checked="checked"';
        $show_f_expe_state_no = '';
    } elseif (Tools::getValue('nacex_show_f_expe_state', Configuration::get('NACEX_SHOW_F_EXPE_STATE')) == 'NO') {
        $show_f_expe_state_no = 'checked="checked"';
        $show_f_expe_state_si = '';
    }

    // Importe minimo gratuito servicios Nacex
    if (Tools::getValue('nacex_importe_min_grat', Configuration::get('NACEX_IMP_MIN_GRAT')) == 'SI') {
        $nacex_importe_min_grat_si = 'checked="checked"';
        $nacex_importe_min_grat_no = '';
        $nacex_importe_min_grat_DIS = '';
    } else {
        $nacex_importe_min_grat_no = 'checked="checked"';
        $nacex_importe_min_grat_si = '';
        $nacex_importe_min_grat_DIS = "disabled='true'";
    }
    $divInfoImpMinGrat = showDivInfo('info_nacex_importe_min_grat', $obj->l('Free minimum amount') . ':', $obj->l('The free minimum amount will be only applied to Nacex carriers and it will discard the Prestashop settings.'));

    // Importe minimo gratuito servicios NacexShop
    if (Tools::getValue('nacexshop_importe_min_grat', Configuration::get('NACEXSHOP_IMP_MIN_GRAT')) == 'SI') {
        $nacexshop_importe_min_grat_si = 'checked="checked"';
        $nacexshop_importe_min_grat_no = '';
        $nacexshop_importe_min_grat_DIS = '';
    } else {
        $nacexshop_importe_min_grat_no = 'checked="checked"';
        $nacexshop_importe_min_grat_si = '';
        $nacexshop_importe_min_grat_DIS = "disabled='true'";
    }

    $divInfoImpMinGratShop = showDivInfo('info_nacexshop_importe_min_grat', $obj->l('Free minimum amount') . ':', $obj->l('The free minimum amount will be only applied to Nacex carriers and it will discard the Prestashop settings.'));

    // Importe minimo gratuito servicios Nacex Internacional
    if (Tools::getValue('nacexint_importe_min_grat', Configuration::get('NACEXINT_IMP_MIN_GRAT')) == 'SI') {
        $nacexint_importe_min_grat_si = 'checked="checked"';
        $nacexint_importe_min_grat_no = '';
        $nacexint_importe_min_grat_DIS = '';
    } else {
        $nacexint_importe_min_grat_no = 'checked="checked"';
        $nacexint_importe_min_grat_si = '';
        $nacexint_importe_min_grat_DIS = "disabled='true'";
    }

    $divInfoImpMinGratInt = showDivInfo('info_nacexint_importe_min_grat', $obj->l('Free minimum amount') . ':', $obj->l('The free minimum amount will be only applied to Nacex carriers and it will discard the Prestashop settings.'));

    // Tipo de cobro
    $tip_cob_01 = 'checked="checked"';
    $tip_cob_02 = '';
    $tip_cob_03 = '';
    if (Tools::getValue('nacex_tip_cob', Configuration::get('NACEX_TIP_COB')) == 'O') {
        $tip_cob_01 = 'checked="checked"';
        $tip_cob_02 = '';
        $tip_cob_03 = '';
    } elseif (Tools::getValue('nacex_tip_cob', Configuration::get('NACEX_TIP_COB')) == 'D') {
        $tip_cob_02 = 'checked="checked"';
        $tip_cob_01 = '';
        $tip_cob_03 = '';
    } elseif (Tools::getValue('nacex_tip_cob', Configuration::get('NACEX_TIP_COB')) == 'T') {
        $tip_cob_03 = 'checked="checked"';
        $tip_cob_01 = '';
        $tip_cob_02 = '';
    }

    // Tipo de Reembolso
    $tip_ree_01 = '';
    $tip_ree_02 = 'checked="checked"';
    $tip_ree_03 = '';
    if (Tools::getValue('nacex_tip_ree', Configuration::get('NACEX_TIP_REE')) == 'O') {
        $tip_ree_01 = 'checked="checked"';
        $tip_ree_02 = '';
        $tip_ree_03 = '';
    } elseif (Tools::getValue('nacex_tip_ree', Configuration::get('NACEX_TIP_REE')) == 'D') {
        $tip_ree_02 = 'checked="checked"';
        $tip_ree_01 = '';
        $tip_ree_03 = '';
    } elseif (Tools::getValue('nacex_tip_ree', Configuration::get('NACEX_TIP_REE')) == 'T') {
        $tip_ree_03 = 'checked="checked"';
        $tip_ree_01 = '';
        $tip_ree_02 = '';
    }

    // Tipo de envase
    $tip_env_docs = '';
    $tip_env_bag = 'checked="checked"';
    $tip_env_paq = '';

    $tip_env_docs = Tools::getValue('nacex_tip_env', Configuration::get('NACEX_TIP_ENV')) == '0' ? 'checked="checked"' : '';
    $tip_env_bag = Tools::getValue('nacex_tip_env', Configuration::get('NACEX_TIP_ENV')) == '1' ? 'checked="checked"' : '';
    $tip_env_paq = Tools::getValue('nacex_tip_env', Configuration::get('NACEX_TIP_ENV')) == '2' ? 'checked="checked"' : '';

    $tip_env_mu = Tools::getValue('nacex_tip_env_int', Configuration::get('NACEX_TIP_ENV_INT')) == 'M' ? 'checked="checked"' : '';
    $tip_env_do = Tools::getValue('nacex_tip_env_int', Configuration::get('NACEX_TIP_ENV_INT')) == 'D' ? 'checked="checked"' : '';

    // Informar en intrucciones adicionales
    $ins_adi_q_r_no = 'checked="checked"';
    $ins_adi_q_r_si = '';
    if (Tools::getValue('nacex_ins_adi_q_r', Configuration::get('NACEX_INS_ADI_Q_R')) == 'SI') {
        $ins_adi_q_r_si = 'checked="checked"';
        $ins_adi_q_r_no = '';
    } elseif (Tools::getValue('nacex_ins_adi_q_r', Configuration::get('NACEX_INS_ADI_Q_R')) == 'NO') {
        $ins_adi_q_r_no = 'checked="checked"';
        $ins_adi_q_r_si = '';
    }

    // Añadir Instrucciones Adicionales Personalizadas a la etiqueta
    $ins_adi_pers_no = 'checked="checked"';
    $ins_adi_pers_si = '';
    $ins_adi_pers_DIS = "disabled='true'";
    $obs_DIS = "disabled='true'";
    if (Tools::getValue('ins_adi_pers', Configuration::get('NACEX_INST_PERS')) == 'SI') {
        $ins_adi_pers_si = 'checked="checked"';
        $ins_adi_pers_no = '';
        $ins_adi_pers_DIS = '';
        $obs_DIS = '';
    } elseif (Tools::getValue('ins_adi_pers', Configuration::get('NACEX_INST_PERS')) == 'NO') {
        $ins_adi_pers_no = 'checked="checked"';
        $ins_adi_pers_si = '';
        $ins_adi_pers_DIS = "disabled='true'";
        $obs_DIS = "disabled='true'";
    }

    // Añadir comentarios de cliente a la expedición
    $nacex_comentarios_cli_no = 'checked="checked"';
    $nacex_comentarios_cli_si = '';

    if (Tools::getValue('nacex_comentarios_cli_sino', Configuration::get('NACEX_COMENTARIOS_CLI_SINO')) == 'SI') {
        $nacex_comentarios_cli_si = 'checked="checked"';
        $nacex_comentarios_cli_no = '';

    } elseif (Tools::getValue('nacex_comentarios_cli_sino', Configuration::get('NACEX_COMENTARIOS_CLI_SINO')) == 'NO') {
        $nacex_comentarios_cli_no = 'checked="checked"';
        $nacex_comentarios_cli_si = '';
    }

    // Envio con retorno
    $ret_no = 'checked="checked"';
    $ret_si = '';
    if (Tools::getValue('nacex_ret', Configuration::get('NACEX_RET')) == 'SI') {
        $ret_si = 'checked="checked"';
        $ret_no = '';
    } elseif (Tools::getValue('nacex_ret', Configuration::get('NACEX_RET')) == 'NO') {
        $ret_no = 'checked="checked"';
        $ret_si = '';
    }

    // Referencia personaliada Si/NO
    $nacex_ref_pers_no = 'checked="checked"';
    $nacex_ref_pers_si = '';
    $nacex_ref_pers_DIS = '';
    if (Tools::getValue('nacex_ref_pers', Configuration::get('NACEX_REF_PERS')) == 'SI') {
        $nacex_ref_pers_si = 'checked="checked"';
        $nacex_ref_pers_no = '';
        $nacex_ref_pers_DIS = '';
    } elseif (Tools::getValue('nacex_ref_pers', Configuration::get('NACEX_REF_PERS')) == 'NO') {
        $nacex_ref_pers_no = 'checked="checked"';
        $nacex_ref_pers_si = '';
        $nacex_ref_pers_DIS = "disabled='true'";
    } else {
        $nacex_ref_pers_no = 'checked="checked"';
        $nacex_ref_pers_si = '';
        $nacex_ref_pers_DIS = "disabled='true'";
    }

    // Importe seguro
    $nacex_default_imp_seg_DIS = '';
    if (Tools::getValue('nacex_default_tip_seg', Configuration::get('NACEX_DEFAULT_TIP_SEG')) == 'N') {
        $nacex_default_imp_seg_DIS = "disabled='true'";
    }

    // Prealerta por defecto
    $nacex_tip_preal_s = '';
    $nacex_tip_preal_n = 'checked="checked"';
    $nacex_tip_preal_e = '';
    $nacex_tip_preal_DIS = '';
    if (Tools::getValue('nacex_tip_preal', Configuration::get('NACEX_TIP_PREAL')) == 'N') {
        $nacex_tip_preal_n = 'checked="checked"';
        $nacex_tip_preal_s = '';
        $nacex_tip_preal_e = '';
        $nacex_tip_preal_DIS = "disabled='true'";
    } elseif (Tools::getValue('nacex_tip_preal', Configuration::get('NACEX_TIP_PREAL')) == 'S') {
        $nacex_tip_preal_n = '';
        $nacex_tip_preal_s = 'checked="checked"';
        $nacex_tip_preal_e = '';
        $nacex_tip_preal_DIS = '';
    } elseif (Tools::getValue('nacex_tip_preal', Configuration::get('NACEX_TIP_PREAL')) == 'E') {
        $nacex_tip_preal_n = '';
        $nacex_tip_preal_s = '';
        $nacex_tip_preal_e = 'checked="checked"';
        $nacex_tip_preal_DIS = '';
    } else {
        $nacex_tip_preal_n = 'checked="checked"';
        $nacex_tip_preal_s = '';
        $nacex_tip_preal_e = '';
        $nacex_tip_preal_DIS = "disabled='true'";
    }

    // Modo prealerta por defecto
    $nacex_mod_preal_s = 'checked="checked"';
    $nacex_mod_preal_p = '';
    $nacex_mod_preal_r = '';
    $nacex_mod_preal_e = '';
    if (Tools::getValue('nacex_mod_preal', Configuration::get('NACEX_MOD_PREAL')) == 'S') {
        $nacex_mod_preal_s = 'checked="checked"';
        $nacex_mod_preal_p = '';
        $nacex_mod_preal_r = '';
        $nacex_mod_preal_e = '';
    } elseif (Tools::getValue('nacex_mod_preal', Configuration::get('NACEX_MOD_PREAL')) == 'P') {
        $nacex_mod_preal_s = '';
        $nacex_mod_preal_p = 'checked="checked"';
        $nacex_mod_preal_r = '';
        $nacex_mod_preal_e = '';
    } elseif (Tools::getValue('nacex_mod_preal', Configuration::get('NACEX_MOD_PREAL')) == 'R') {
        $nacex_mod_preal_s = '';
        $nacex_mod_preal_p = '';
        $nacex_mod_preal_r = 'checked="checked"';
        $nacex_mod_preal_e = '';
    } elseif (Tools::getValue('nacex_mod_preal', Configuration::get('NACEX_MOD_PREAL')) == 'E') {
        $nacex_mod_preal_s = '';
        $nacex_mod_preal_p = '';
        $nacex_mod_preal_r = '';
        $nacex_mod_preal_e = 'checked="checked"';
    } else {
        $nacex_mod_preal_s = 'checked="checked"';
        $nacex_mod_preal_p = '';
        $nacex_mod_preal_r = '';
        $nacex_mod_preal_e = '';
    }

    // Bultos fijos o por artículos de la cesta
    $nacex_bultos_fijo = '';
    $nacex_bultos_cesta = 'checked="checked"';
    $nacex_bultos_DIS = '';
    if (Tools::getValue('nacex_bultos', Configuration::get('NACEX_BULTOS')) == 'F') {
        $nacex_bultos_fijo = 'checked="checked"';
        $nacex_bultos_cesta = '';
        $nacex_bultos_DIS = '';
    } elseif (Tools::getValue('nacex_bultos', Configuration::get('NACEX_BULTOS')) == 'C') {
        $nacex_bultos_cesta = 'checked="checked"';
        $nacex_bultos_fijo = '';
        $nacex_bultos_DIS = "disabled='true'";
    } else {
        $nacex_bultos_cesta = 'checked="checked"';
        $nacex_bultos_fijo = '';
        $nacex_bultos_DIS = "disabled='true'";
    }

    // Peso fijo o por artículos de la cesta
    $nacex_peso_fijo = '';
    $nacex_peso_cesta = 'checked="checked"';
    $nacex_peso_cesta_DIS = '';
    if (Tools::getValue('nacex_peso', Configuration::get('NACEX_PESO')) == 'F') {
        $nacex_peso_fijo = 'checked="checked"';
        $nacex_peso_cesta = '';
        $nacex_peso_cesta_DIS = '';
    } elseif (Tools::getValue('nacex_peso', Configuration::get('NACEX_PESO')) == 'C') {
        $nacex_peso_cesta = 'checked="checked"';
        $nacex_peso_fijo = '';
        $nacex_peso_cesta_DIS = "disabled='true'";
    } else {
        $nacex_peso_cesta = 'checked="checked"';
        $nacex_peso_fijo = '';
        $nacex_peso_cesta_DIS = "disabled='true'";
    }

    // Mostrar transportistas con coste 0€
    $nacex_mostrar_coste0_si = 'checked="checked"';
    $nacex_mostrar_coste0_no = '';
    if (Tools::getValue('nacex_mostrar_coste0', Configuration::get('NACEX_MOSTRAR_COSTE_0')) == 'SI') {
        $nacex_mostrar_coste0_si = 'checked="checked"';
        $nacex_mostrar_coste0_no = '';
    } elseif (Tools::getValue('nacex_mostrar_coste0', Configuration::get('NACEX_MOSTRAR_COSTE_0')) == 'NO') {
        $nacex_mostrar_coste0_no = 'checked="checked"';
        $nacex_mostrar_coste0_si = '';
    } else {
        $nacex_mostrar_coste0_si = 'checked="checked"';
        $nacex_mostrar_coste0_no = '';
    }

    // Aplicar gastos de manipulacional coste del envio
    $nacex_gastos_manipulacion_no = 'checked="checked"';
    $nacex_gastos_manipulacion_si = '';
    $nacex_gastos_manipulacion_DIS = '';
    if (Tools::getValue('nacex_gastos_manipulacion', Configuration::get('NACEX_GASTOS_MANIPULACION')) == 'SI') {
        $nacex_gastos_manipulacion_si = 'checked="checked"';
        $nacex_gastos_manipulacion_no = '';
        $nacex_gastos_manipulacion_DIS = '';
    } elseif (Tools::getValue('nacex_gastos_manipulacion', Configuration::get('NACEX_GASTOS_MANIPULACION')) == 'NO') {
        $nacex_gastos_manipulacion_no = 'checked="checked"';
        $nacex_gastos_manipulacion_si = '';
        $nacex_gastos_manipulacion_DIS = "disabled='true'";
    } else {
        $nacex_gastos_manipulacion_no = 'checked="checked"';
        $nacex_gastos_manipulacion_si = '';
        $nacex_gastos_manipulacion_DIS = "disabled='true'";
    }
    $divInfoGastosMani = showDivInfo('info_nacex_gastos_manipulacion', $obj->l('Handling fee') . ':', $obj->l('Handling fee amount will be added to all NACEX and NACEXSHOP shipping cost in case of Web Service calculation.'));

    // Mostrar formulario Generar Expedición para cualquier transportista
    $force_genform_no = '';
    $force_genform_si = 'checked="checked"';
    if (Tools::getValue('nacex_force_genform', Configuration::get('NACEX_FORCE_GENFORM')) == 'SI') {
        $force_genform_si = 'checked="checked"';
        $force_genform_no = '';
    } elseif (Tools::getValue('nacex_force_genform', Configuration::get('NACEX_FORCE_GENFORM')) == 'NO') {
        $force_genform_no = 'checked="checked"';
        $force_genform_si = '';
    }

    // Configurar si existe módulo opc externo
    $nacex_opc_external_no = 'checked="checked"';
    $nacex_opc_external_si = '';
    $nacex_opc_id_divgeneral_DIS = "disabled='true'";
    $nacex_opc_id_boton_DIS = "disabled='true'";
    if (Tools::getValue('nacex_opc_external', Configuration::get('NACEX_OPC_EXTERNAL')) == 'SI') {
        $nacex_opc_external_si = 'checked="checked"';
        $nacex_opc_external_no = '';
        $nacex_opc_id_divgeneral_DIS = '';
        $nacex_opc_id_boton_DIS = '';
    } elseif (Tools::getValue('nacex_opc_external', Configuration::get('NACEX_OPC_EXTERNAL')) == 'NO') {
        $nacex_opc_external_no = 'checked="checked"';
        $nacex_opc_external_si = '';
        $nacex_opc_id_divgeneral_DIS = "disabled='true'";
        $nacex_opc_id_boton_DIS = "disabled='true'";
    }

    // Activar traza de log del módulo de Nacex
    $nacex_save_log_no = 'checked="checked"';
    $nacex_save_log_si = '';
    if (Tools::getValue('nacex_save_log', Configuration::get('NACEX_SAVE_LOG')) == 'SI') {
        $nacex_save_log_si = 'checked="checked"';
        $nacex_save_log_no = '';
    } elseif (Tools::getValue('nacex_save_log', Configuration::get('NACEX_SAVE_LOG')) == 'NO') {
        $nacex_save_log_no = 'checked="checked"';
        $nacex_save_log_si = '';
    }

    //Borrar configuración módulo
    if (Tools::getValue('nacex_borrar_configuracion', Configuration::get('NACEX_BORRAR_CONFIGURACION')) == 'SI') {
        $show_borrar_configuracion_si = 'checked="checked"';
        $show_borrar_configuracion_no = '';
    } else {
        $show_borrar_configuracion_no = 'checked="checked"';
        $show_borrar_configuracion_si = '';
    }

    // Mostrar configuraciones de Desarrollador
    $show_dev_ops_no = 'checked="checked"';
    $show_dev_ops_si = '';
    if (Tools::getValue('nacex_show_dev_ops', Configuration::get('NACEX_SHOW_DEV_OPS')) == 'SI') {
        $show_dev_ops_si = 'checked="checked"';
        $show_dev_ops_no = '';
    } elseif (Tools::getValue('nacex_show_dev_ops', Configuration::get('NACEX_SHOW_DEV_OPS')) == 'NO') {
        $show_dev_ops_no = 'checked="checked"';
        $show_dev_ops_si = '';
    }

    // Mostrar errores en tiempo de ejecución
    $show_errors_no = 'checked="checked"';
    $show_errors_si = '';
    if (Tools::getValue('nacex_show_errors', Configuration::get('NACEX_SHOW_ERRORS')) == 'SI') {
        $show_errors_si = 'checked="checked"';
        $show_errors_no = '';
    } elseif (Tools::getValue('nacex_show_errors', Configuration::get('NACEX_SHOW_ERRORS')) == 'NO') {
        $show_errors_no = 'checked="checked"';
        $show_errors_si = '';
    }

    // Actualizar atuomáticamente el tracking en el pedido
    $act_tracking_no = 'checked="checked"';
    $act_tracking_si = '';
    if (Tools::getValue('nacex_act_tracking', Configuration::get('NACEX_ACT_TRACKING')) == 'SI') {
        $act_tracking_si = 'checked="checked"';
        $act_tracking_no = '';
    } elseif (Tools::getValue('nacex_act_tracking', Configuration::get('NACEX_ACT_TRACKING')) == 'NO') {
        $act_tracking_no = 'checked="checked"';
        $act_tracking_si = '';
    }
    // Activar Servicio 44
    $nacex_servicio44_no = 'checked="checked"';
    $nacex_servicio44_si = '';
    if (Tools::getValue('nacex_servicio44', Configuration::get('NACEX_SERVICIO44')) == 'SI') {
        $nacex_servicio44_si = 'checked="checked"';
        $nacex_servicio44_no = '';
    } elseif (Tools::getValue('nacex_servicio44', Configuration::get('NACEX_SERVICIO44')) == 'NO') {
        $nacex_servicio44_no = 'checked="checked"';
        $nacex_servicio44_si = '';
    }

    // Activar Show Empresa, para
    $nacex_show_empresa_no = 'checked="checked"';
    $nacex_show_empresa_si = '';
    if (Tools::getValue('nacex_show_empresa', Configuration::get('NACEX_SHOW_EMPRESA')) == 'SI') {
        $nacex_show_empresa_si = 'checked="checked"';
        $nacex_show_empresa_no = '';
    } elseif (Tools::getValue('nacex_show_empresa', Configuration::get('NACEX_SHOW_EMPRESA')) == 'NO') {
        $nacex_show_empresa_no = 'checked="checked"';
        $nacex_show_empresa_si = '';
    }

    // Comprobar versiones del módulo
    //$chkversion = new CheckVersion();
    // Utilización de SMTP para el envío de correos
    if (Tools::getValue('nacex_feedback_smtp', Configuration::get('NACEX_FEEDBACK_SMTP')) == 'SI') {
        $nacex_feedback_smtp_si = 'checked="checked"';
        $nacex_feedback_smtp_no = '';
    } else {
        $nacex_feedback_smtp_no = 'checked="checked"';
        $nacex_feedback_smtp_si = '';
    }

    $nacex_path_log = Tools::getValue('nacex_path_log', Configuration::get('NACEX_PATH_LOG'));
    if (empty($nacex_path_log)) {
        $nacex_path_log = _PS_MODULE_DIR_ . 'nacex/log/';
    }

    $html .= '
            <script type="text/javascript">
                    function probarConnexionWebservice(){
                        var msg="";
                        
                        $.ajax({
                            type: "POST",
                            url: "' . $nacexDTO->getPath() . 'TestWSConnection.php",
                            async: false,
                            data: {ws_url:$("[name=\"nacex_ws_url\"]").val(), usr:$("[name=\"nacex_wsusername\"]").val(), pass:$("[name=\"nacex_wspassword\"]").val()},
                            beforeSend: function(){
                                $("#connectionResult").html(\'<img src= \"../modules/nacex/images/loading.gif\" style=\"width:30px\">\');
                            },
                            success: function(msg) {
                                $("#connectionResult").html(msg);
                            }
                        });
                    }
                    function modulosPHP(){
                        
                        if (!$("#modulosResult").is(":visible")){ 
                            
                             // Mostramos el div
                             $("#modulosResult").toggle();
                             
                            $.ajax({
                                type: "POST",
                                url: "' . $nacexDTO->getPath() . 'TestModulos.php",
                                async: false,
                                beforeSend: function(){
                                    $("#modulosResult").html(\'<img src= \"../modules/nacex/images/loading.gif\" style=\"width:30px\">\');
                                },
                                success: function(msg) {
                                    $("#modulosResult").addClass("alert alert-info").html(msg);
                                },
                                complete: function () {
                                    let text = "<p><em>' . $obj->l('For more server information, please go to') . ' <strong>' . $obj->l('Advanced Parameters -> System information') . '</strong>' . '</em></p>";
                                    $("#modulosResult").append(text);
                                }
                            })
                        } else {
                             // Ocultamos el div
                             $("#modulosResult").removeClass().toggle();
                        }
                    }
                    function inizializarZonas() {
                        $.ajax({
                            type: "POST",
                            url: "' . $nacexDTO->getPath() . 'initZones.php",
                            beforeSend: function(){
                                $("#initZonasResult").html(\'<img src= \"' . $nacexDTO->getPath() . 'images/loading.gif\" style=\"width:30px\">\');
                            },
                            success: function(msg) {
                                $("#initZonasResult").html(msg);
                            },
                            error: function() {
                                $("#initZonasResult").html(\'<div class="alert alert-danger">Error al inicializar las zonas</div>\');
                            }
                        });
                    }
                    function reinstalarHooks() {
                        $.ajax({
                            type: "POST",
                            url: "' . $nacexDTO->getPath() . 'reinstallHooks.php",
                            beforeSend: function(){
                                $("#reinstallHooksResult").html(\'<img src= \"' . $nacexDTO->getPath() . 'images/loading.gif\" style=\"width:30px\">\');
                            },
                            success: function(msg) {
                                $("#reinstallHooksResult").html(msg);
                            },
                            error: function() {
                                $("#reinstallHooksResult").html(\'<div class="alert alert-danger">Error</div>\');
                            }
                        });
                    }
                    function revisarTablaCarriers() {
                        var msg="";
                        
                        $.ajax({
                            type: "POST",
                            url: "' . $nacexDTO->getPath() . 'revisarTablaCarriers.php",
                            async: false,
                            beforeSend: function(){
                                $("#nacex_tableCarriersResult").html(\'<img src= \"../modules/nacex/images/loading.gif\" style=\"width:30px\">\');
                            },
                            success: function(msg) {
                                $("#nacex_tableCarriersResult").html(msg);
                                initJsTables();
                            }
                        });
                    }
                    function editarFila() {
                        var idCarrier = $("#edit_carrierId").val();
                        var is_module = $("#edit_isModule").val();
                        var shipping_external = $("#edit_shihppingExternal").val();
                        var external_module_name = $("#edit_externalModuleName").val();
                        var ncx = $("#edit_ncx").val();
                        var tip_serv = $("#edit_tip_serv").val();
                        
                        $.ajax({
                            type: "POST",
                            url: "' . $nacexDTO->getPath() . 'revisarTablaCarriers.php",
                            data: {editarCarrier:1,idCarrier:idCarrier,is_module:is_module,shipping_external:shipping_external,external_module_name:external_module_name,ncx:ncx,tip_serv:tip_serv},
                            async: false,
                            beforeSend: function(){
                                $("#nacex_tableCarriersResult").html(\'<img src= \"../modules/nacex/images/loading.gif\" style=\"width:30px\">\');
                            },
                            success: function(msg) {
                                $("#nacex_tableCarriersResult").html(msg);
                            }
                        });
                    }
                </script>
								
								<form action="' . htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') . '" method="post" id="configForm">
								<div class="panel">
									<div class="panel-heading" style="display:flex;align-items:center;justify-content:space-between;">
										<div style="display:flex;align-items:center;gap:1em;">
											<a target="_blank" href="https://www.nacex.es">
												<img style="width:130px;height:auto;" src="' . $nacexDTO->getPath() . 'images/logos/nacex_logista.png" />
											</a>
											<span style="font-size:1.1em;">' . $obj->l('Module configuration') . ' <small style="color:#999;">v' . nacexutils::nacexVersion . '</small></span>
										</div>
										<a href="' . $config_pdf . '" target="_blank" class="btn btn-default btn-sm">
											<i class="material-icons" style="font-size:14px;vertical-align:middle;">picture_as_pdf</i> ' . $obj->l('User manual') . '
										</a>
									</div>
									<div class="panel-body">
									<input type="hidden" name="nacex_ws_url" value="' . nacexDTO::$url_ws . '"/>
								<div class="panel">
									<div class="panel-heading">' . $obj->l('Connection settings') . '</div>
									<div class="panel-body">
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Iona URL') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacex_print_iona') . '
												<input type="text" class="form-control" name="nacex_print_iona" value="' . nacexDTO::$url_iona . '" readonly/>
												<small class="form-text text-muted">
													<a href="https://www.nacex.es/files/iona/iona.zip" style="color:#ff5100;">' . $obj->l('Download IONA') . '</a>
													&nbsp;|&nbsp;
													<a href="https://www.nacex.es/files/iona/Manual_ioNA.pdf" style="color:#ff5100;">' . $obj->l('IONA user guide') . '</a>
												</small>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Labeller model') . '</label>
											<div class="col-lg-9">
												<select class="form-control" name="nacex_print_model" id="nacex_print_model" style="max-width:335px;">
													<option disabled="disabled" selected></option>
													<optgroup label="' . $obj->l('Labeller model') . '">';

    $mod = Configuration::get('NACEX_PRINT_MODEL');
    foreach ($nacexDTO->getModelosEtiquetadoras() as $m) {
        $selected = ($mod !== false && $m['value'] == $mod) ? ' selected' : '';
        $html .= '<option value="' . $m['value'] . '"' . $selected . '>' . $m['label'] . '</option>';
    }
    $html .= '</optgroup>
												</select>
												<small class="form-text text-muted">' . $obj->l('Ex:') . ' TECSV4, TEC472, ZEBRA, LASER</small>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Printer') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacex_print_et') . '
												<input type="text" class="form-control" name="nacex_print_et" value="' . Tools::getValue('nacex_print_et', Configuration::get('NACEX_PRINT_ET')) . '" style="max-width:335px;" />
												<small class="form-text text-muted">' . $obj->l('Physical printer name') . '. ' . $obj->l('Ex:') . ' Kyocera FS-1350DN KX</small>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Web Service user') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacex_wsusername') . '
												<input type="text" class="form-control" name="nacex_wsusername" value="' . Tools::getValue('nacex_wsusername', Configuration::get('NACEX_WSUSERNAME')) . '" style="max-width:335px;" />
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Web Service password') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacex_wspassword') . '
												<input type="password" class="form-control" name="nacex_wspassword" value="' . Tools::getValue('nacex_wspassword', Configuration::get('NACEX_WSPASSWORD_ORIGINAL')) . '" style="max-width:335px;" />
											</div>
										</div>
										<div class="form-group row">
											<div class="col-lg-9 col-lg-offset-3">
												<button type="button" class="btn btn-default" onclick="probarConnexionWebservice();">' . $obj->l('Test Web Service connection') . '</button>
												<div id="connectionResult" style="margin-top:0.5em;"></div>
												<button type="button" class="btn btn-default" onclick="modulosPHP();" style="margin-top:0.5em;">' . $obj->l('PHP modules') . '</button>
												<div id="modulosResult" style="display:none;margin-top:0.5em;"></div>
											</div>
										</div>
									</div>
								</div>
									
								<div class="panel">
									<div class="panel-heading">' . $obj->l('Subscriber settings') . '</div>
									<div class="panel-body">
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Agencies/Customers') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacex_agcli') . '
												<input type="text" class="form-control" name="nacex_agcli" value="' . Tools::getValue('nacex_agcli', Configuration::get('NACEX_AGCLI')) . '" style="max-width:335px;" />
												<small class="form-text text-muted">' . $obj->l('Comma separated agency/customer codes') . '. ' . $obj->l('Ex:') . ' 1234/01234,4321/04321</small>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Customer departaments') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacex_departamentos') . '
												<input type="text" class="form-control" name="nacex_departamentos" value="' . Tools::getValue('nacex_departamentos', Configuration::get('NACEX_DEPARTAMENTOS')) . '" style="max-width:335px;" />
												<small class="form-text text-muted">' . $obj->l('Comma separated customer departaments. The first one will be used as default') . '. ' . $obj->l('Ex:') . ' DEPT1, 2DEPARTAMENTO</small>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Shipment pickup postcode') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacex_cprec') . '
												<input type="text" class="form-control" name="nacex_cprec" value="' . Tools::getValue('nacex_cprec', Configuration::get('NACEX_CP_REC')) . '" style="max-width:335px;" />
												<small class="form-text text-muted">' . $obj->l('Pickup postcode or shipment order origin necessary to calculate shipping cost or service estimation for applying to shipping cost') . '</small>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Do you want to install our zones?') . '</label>
											<div class="col-lg-9">
												<div class="alert alert-warning" style="margin-bottom:0.5em;">' . $obj->l('This will create shipping zones, assign countries/states to them, and configure Nacex carriers.') . '<br>' . $obj->l('If you already have zones configured, review your carrier zone assignments after this operation.') . '</div>
												<button type="button" class="btn btn-default" onclick="inizializarZonas();">' . $obj->l('Install and initialize zones') . '</button>
												<div id="initZonasResult" style="margin-top:0.5em;"></div>
												<small class="form-text text-muted">' . $obj->l('Creates NCX zones and assigns Nacex carriers to them. Does not affect other carriers or zone assignments.') . '</small>
											</div>
										</div>
																					' . nacexutils::getRadioHTML('Allow specific service selection to customer', 'nacex_serv_back_or_front', 'B', $serv_back_or_front_B, 'F', $serv_back_or_front_F, 'Allow frontend specific Nacex or Nacex Shop service selection to customer from frontend')
        . '
											' . nacexutils::getRadioHTML('Show expedition status in frontend', 'nacex_show_f_expe_state', 'NO', $show_f_expe_state_no, 'SI', $show_f_expe_state_si, 'Show expedition status in frontend when customer see his/her order details')
        . '
											' . nacexutils::getRadioHTML('Update order tracking automatically', 'nacex_act_tracking', 'NO', $act_tracking_no, 'SI', $act_tracking_si, 'If it updates automatically, admin user will be able to know the order status')
        . '
											' . nacexutils::getRadioHTML('Enable return labels service (Service 44)?', 'nacex_servicio44', 'NO', $nacex_servicio44_no, 'SI', $nacex_servicio44_si, 'Customers won\'t be able to print the return label from frontend account. The generated return labels will be removed after 7 days.')
        . '
											' . nacexutils::getRadioHTML('Enable Show Empresa', 'nacex_show_empresa', 'NO', $nacex_show_empresa_no, 'SI', $nacex_show_empresa_si, 'If enabled, the order that generates the shipment will use the 2 observation fields to add the company name of the order shipping address.')
        . '
									</div>
								</div>

								<div class="panel">
									<div class="panel-heading">' . $obj->l('Nacex Standard services') . '</div>
									<div class="panel-body">
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Text for generic service') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacex_gen_serv_name') . '
												<input type="text" class="form-control" name="nacex_gen_serv_name" value="' . Tools::getValue('nacex_gen_serv_name', Configuration::get('NACEX_GEN_SERV_NAME')) . '" style="max-width:335px;" />
												<small class="form-text text-muted">' . $obj->l('Text to describe Nacex Generic service') . '</small>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Standard service types') . '</label>
											<div class="col-lg-6">
												' . showError($errores, 'nacex_available_tip_ser') . '
';
    $stdOptions = [];
    foreach ($nacexDTO->getServiciosNacex() as $serv => $value) {
        $stdOptions[$serv] = $serv . $nacexDTO->getServSeparador() . $value['nombre'];
    }
    $html .= nacexutils::renderCheckboxGroup('nacex_available_tip_ser', 'NACEX_AVAILABLE_TIP_SER', '|', $stdOptions) . '
												<small class="form-text text-muted">' . $obj->l('Available service types') . '</small>
											</div>
											<div class="col-lg-3">';
    $html .= $newServices->printNewServiceButtons('Std');
    $html .= $newServices->printAddNewService('Std');
    $html .= $newServices->printRemoveNewService('Std', $nacexDTO);
    $html .= $newServices->printEditNewService('Std', $nacexDTO);
    $html .= '
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Default service type') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacex_default_tip_ser') . '
												<select class="form-control" name="nacex_default_tip_ser" style="max-width:335px;">
													<optgroup label="' . $obj->l('Default service type') . '">';
    foreach ($nacexDTO->getServiciosNacex() as $serv => $value) {
        $servname = $value['nombre'];
        $html .= '<option ' . nacexutils::markSelectedOption('nacex_default_tip_ser', 'NACEX_DEFAULT_TIP_SER', $serv) . ' value="' . $serv . '">' . $serv . $nacexDTO->getServSeparador() . $servname . '</option>';
    }
    $html .= '</optgroup>
												</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Amount calculation method') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacex_calculo_importe_std') . '
												<select class="form-control" name="nacex_calculo_importe_std" style="max-width:335px;">';
    foreach ($nacexDTO->getMetodosCalculo() as $serv => $value) {
        $typevalue = $value['value'];
        $typelabel = $value['label'];
        $html .= '<option ' . nacexutils::markSelectedOption('nacex_calculo_importe_std', 'NACEX_CALCULO_IMPORTE_STD', $typevalue) . ' value="' . $typevalue . '">' . $typelabel . '</option>';
    }
    $html .= '</select>
												<small class="form-text text-muted">' . $obj->l('Indicates the carrier amount calculation method.') . '</small>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Shipping flat amount') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacex_importe_fijo_val') . '
												<input type="text" class="form-control" name="nacex_importe_fijo_val" value="' . Tools::getValue('nacex_importe_fijo_val', Configuration::get('NACEX_IMP_FIJO_VAL')) . '" onkeypress="return soloNumeros(event);" onblur="ValidarNum(this.value,this,7,2);" style="max-width:200px;" />
												<small class="form-text text-muted">' . $obj->l('Shipping flat amount to apply to a carrier.') . '<br><em>' . $obj->l('It will be used also on Web Service communication error to assign a default price to the carrier.') . '</em></small>
											</div>
										</div>
																					' . nacexutils::getRadioHTML('Enable free minimum amount', 'nacex_importe_min_grat', 'NO', $nacex_importe_min_grat_no, 'SI', $nacex_importe_min_grat_si, 'It enables the free minimum amount setting', "javascript:disableValor('nacex_importe_min_grat_val')", "javascript:enableValor('nacex_importe_min_grat_val')")
        . '
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Free minimum amount') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacex_importe_min_grat_val') . '
												<input type="number" step="0.01" min="0" lang="en" class="form-control" ' . $nacex_importe_min_grat_DIS . ' name="nacex_importe_min_grat_val" value="' . Tools::getValue('nacex_importe_min_grat_val', Configuration::get('NACEX_IMP_MIN_GRAT_VAL')) . '" style="max-width:200px;" />
												' . $divInfoImpMinGrat . '
												<small class="form-text text-muted">' . $obj->l('Order amount from which shipping costs will be free') . '</small>
											</div>
										</div>
									</div>
								</div>
								
								<div class="panel">
									<div class="panel-heading">' . $obj->l('NacexShop services') . '</div>
									<div class="panel-body">
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Text for generic service') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacexshop_gen_serv_name') . '
												<input type="text" class="form-control" name="nacexshop_gen_serv_name" value="' . Tools::getValue('nacexshop_gen_serv_name', Configuration::get('NACEXSHOP_GEN_SERV_NAME')) . '" style="max-width:335px;" />
												<small class="form-text text-muted">' . $obj->l('Text to describe Nacex Generic service') . '</small>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('NacexShop service types') . '</label>
											<div class="col-lg-6">
';
    $shopOptions = [];
    foreach ($nacexDTO->getServiciosNacexShop() as $serv => $value) {
        $shopOptions[$serv] = $serv . $nacexDTO->getServSeparador() . $value['nombre'];
    }
    $html .= nacexutils::renderCheckboxGroup('nacex_available_tip_nxshop_ser', 'NACEX_AVAILABLE_TIP_NXSHOP_SER', '|', $shopOptions) . '
												<small class="form-text text-muted">' . $obj->l('Available service types') . '</small>
											</div>
											<div class="col-lg-3">';
    $html .= $newServices->printNewServiceButtons('Shp');
    $html .= $newServices->printAddNewService('Shp');
    $html .= $newServices->printRemoveNewService('Shp', $nacexDTO);
    $html .= $newServices->printEditNewService('Shp', $nacexDTO);
    $html .= '
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Default service type') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacex_default_tip_nxshop_ser') . '
												<select class="form-control" name="nacex_default_tip_nxshop_ser" style="max-width:335px;">
													<optgroup label="' . $obj->l('Default service type') . '">';
    foreach ($nacexDTO->getServiciosNacexShop() as $serv => $value) {
        $servname = $value['nombre'];
        $html .= '<option ' . nacexutils::markSelectedOption('nacex_default_tip_nxshop_ser', 'NACEX_DEFAULT_TIP_NXSHOP_SER', $serv) . ' value="' . $serv . '">' . $serv . $nacexDTO->getServSeparador() . $servname . '</option>';
    }
    $html .= '</optgroup>
												</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Amount calculation method') . '</label>
											<div class="col-lg-9">
												' . showError($errores, 'nacex_calculo_importe_shp') . '
												<select class="form-control" name="nacex_calculo_importe_shp" style="max-width:335px;">';
    foreach ($nacexDTO->getMetodosCalculo() as $serv => $value) {
        $typevalue = $value['value'];
        $typelabel = $value['label'];
        $html .= '<option ' . nacexutils::markSelectedOption('nacex_calculo_importe_shp', 'NACEX_CALCULO_IMPORTE_SHP', $typevalue) . ' value="' . $typevalue . '">' . $typelabel . '</option>';
    }
    $html .= '</select>
												<small class="form-text text-muted">' . $obj->l('Indicates the carrier amount calculation method.') . '</small>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Shipping flat amount') . '</label>
											<div class="col-lg-9">
												<input type="text" class="form-control" name="nacexshop_importe_fijo_val" value="' . Tools::getValue('nacexshop_importe_fijo_val', Configuration::get('NACEXSHOP_IMP_FIJO_VAL')) . '" onkeypress="return soloNumeros(event);" onblur="ValidarNum(this.value,this,7,2);" style="max-width:200px;" />
												<small class="form-text text-muted">' . $obj->l('Shipping flat amount to apply to a carrier.') . '<br><em>' . $obj->l('It will be used also on Web Service communication error to assign a default price to the carrier.') . '</em></small>
											</div>
										</div>
																					' . nacexutils::getRadioHTML('Enable free minimum amount', 'nacexshop_importe_min_grat', 'NO', $nacexshop_importe_min_grat_no, 'SI', $nacexshop_importe_min_grat_si, 'It enables the free minimum amount setting', "javascript:disableValor('nacexshop_importe_min_grat_val')", "javascript:enableValor('nacexshop_importe_min_grat_val')")
        . '
										<div class="form-group row">
											<label class="col-lg-3 col-form-label">' . $obj->l('Free minimum amount') . '</label>
											<div class="col-lg-9">
												<input type="number" step="0.01" min="0" lang="en" class="form-control" ' . $nacexshop_importe_min_grat_DIS . ' name="nacexshop_importe_min_grat_val" value="' . Tools::getValue('nacexshop_importe_min_grat_val', Configuration::get('NACEXSHOP_IMP_MIN_GRAT_VAL')) . '" style="max-width:200px;" />
												' . $divInfoImpMinGratShop . '
												<small class="form-text text-muted">' . $obj->l('Order amount from which shipping costs will be free') . '</small>
											</div>
										</div>
									</div>
								</div>
									  
									  <fieldset>
											<legend><img src="' . $nacexDTO->getPath() . 'images/logos/NACEX_logo.svg" alt="" style="width:75px;height: 18px;"/> ' . $obj->l('Nacex International') . '</legend>
								      	    <table style="border: 0px;"> 
								      	    	<tr>			   
													<td class="columna1"></td>
													<td class="columna2">' . $obj->l('To use international services you must create a carrier for every service and define weight scaled rates for each country you want to ship') . '</td>								
												</tr>
									  			<tr>
									  				<td class="columna1">' . $obj->l('Text for generic service') . ': </td>
														<td class="columna2" id="nacexint_gen_serv_name">
															' . showError($errores, 'nacexint_gen_serv_name') . '
															<input type="text" size="50" name="nacexint_gen_serv_name" value="' . Tools::getValue('nacexint_gen_serv_name', Configuration::get('NACEXINT_GEN_SERV_NAME')) . '" />
															<p class="tip">' . $obj->l('Text to describe Nacex Generic service') . '</p>
														</td>
									  			</tr> 
                                                <tr>			   
                                                    <td class="columna1">' . $obj->l('International service types') . ':</td>
                                                    <td class="columna2" id="nacex_available_tip_ser_int">
                                                    ' . showError($errores, 'nacex_available_tip_ser_int') . '
';
    $intOptions = [];
    foreach ($nacexDTO->getServiciosNacexInt() as $serv => $value) {
        $intOptions[$serv] = $serv . $nacexDTO->getServSeparador() . $value['nombre'];
    }
    $html .= nacexutils::renderCheckboxGroup('nacex_available_tip_ser_int', 'NACEX_AVAILABLE_TIP_SER_INT', '|', $intOptions) . '
						   									<p class="tip">' . $obj->l('Available service types') . '</p>
															</td>								
                                                </tr>
                                                <tr>
															<td class="columna1">' . $obj->l('Default service type') . ' : </td> 
															<td class="columna2" id="nacex_default_tip_ser_int">
																' . showError($errores, 'nacex_default_tip_ser_int') . '
																<select name="nacex_default_tip_ser_int" style="width:335px">
																	<optgroup label="' . $obj->l('Default service type') . ' ">';
    foreach ($nacexDTO->getServiciosNacexInt() as $serv => $value) {
        $servname = $value['nombre'];
        $servdesc = $value['descripcion'];
        $html .= '<option ' . nacexutils::markSelectedOption('nacex_default_tip_ser_int', 'NACEX_DEFAULT_TIP_SER_INT', $serv) . ' value="' . $serv . '">' . $serv . $nacexDTO->getServSeparador() . $servname . '</option>';
    }
    $html .= '</optgroup>
																</select>
										 						<p class="tip">' . $obj->l('Default service type') . ' </p>
															</td>									  				
										 		</tr>
										 		<tr>
															<td class="columna1">' . $obj->l('Amount calculation method') . ': </td>
															<td class="columna2" id="nacex_calculo_importe_int">
															' . showError($errores, 'nacex_calculo_importe_int') . '
                                                            <select name="nacex_calculo_importe_int" style="width:335px">';
    foreach ($nacexDTO->getMetodosCalculo() as $serv => $value) {
        $typevalue = $value['value'];
        $typelabel = $value['label'];
        $html .= '<option ' . nacexutils::markSelectedOption('nacex_calculo_importe_int', 'NACEX_CALCULO_IMPORTE_INT', $typevalue) . ' value="' . $typevalue . '">' . $typelabel . '</option>';
    }
    $html .= '</select>
                                                            <p class="tip">' . $obj->l('Indicates the carrier amount calculation method.') . '</p>
									  				</td>
									  			</tr>
									  			<tr>
														<td class="columna1">' . $obj->l('Shipping flat amount') . ': </td>
														<td class="columna2" id="nacexint_importe_fijo_val">
									  					<input type="text" onfocus="javascript:$(\'#info_nacexint_importe_fijo\').fadeIn(400);" onblur="javascript:$(\'#info_nacexint_importe_fijo\').fadeOut(400);" size="50" name="nacexint_importe_fijo_val" value="' . Tools::getValue('nacexint_importe_fijo_val', Configuration::get('NACEXINT_IMP_FIJO_VAL')) . '" onkeypress="javascript:return soloNumeros(event);" onblur="javascript:ValidarNum(this.value, this,7,2);"/>
                                                        <p class="tip">' . $obj->l('Shipping flat amount to apply to a carrier.') . '</p>
                                                        <p class="tip"><em>' . $obj->l('It will be used also on Web Service communication error to assign a default price to the carrier.') . '</em></p>
									  				</td>
									  			</tr>
									  			' . nacexutils::getRadioHTML('Enable free minimum amount', 'nacexint_importe_min_grat', 'NO', $nacexint_importe_min_grat_no, 'SI', $nacexint_importe_min_grat_si, 'It enables the free minimum amount setting', "javascript:disableValor('nacexint_importe_min_grat_val')", "javascript:enableValor('nacexint_importe_min_grat_val')")
        . '
									  			<tr>
									  				<td class="columna1">' . $obj->l('Free minimum amount') . ': </td>
									  				<td class="columna2" id="nacexint_importe_min_grat_val">
									  					<input type="number" step="0.01" min="0" lang="en" ' . $nacexint_importe_min_grat_DIS . ' onfocus="javascript:$(\'#info_nacexint_importe_min_grat\').fadeIn(400);" onblur="javascript:$(\'#info_nacexint_importe_min_grat\').fadeOut(400);" size="50" name="nacexint_importe_min_grat_val" value="' . Tools::getValue('nacexint_importe_min_grat_val', Configuration::get('NACEXINT_IMP_MIN_GRAT_VAL')) . '"/>
									  					' . $divInfoImpMinGratInt . '
									  					<p class="tip">' . $obj->l('Order amount from which shipping costs will be free') . '</p>
									  				</td>
									  			</tr>
												<tr>						
										 			<td class="columna1">
										 				<p style="margin-left:5px;">' . $obj->l('Default shipping content') . '</p>								
										 			</td>
													<td  class="columna2">
										 				<select id="nacex_default_contenido" name="nacex_default_contenido" value="' . Tools::getValue('NACEX_DEFAULT_CONTENIDO', '') . '" style="width:375px; margin-left:15px;" length="1" maxlength="38">';
    foreach ($nacexDTO->getContenidos() as $value) {
        $html .= '<option ' . nacexutils::markSelectedOption('nacex_default_contenido', 'NACEX_DEFAULT_CONTENIDO', $value) . ' value="' . $value . '">' . $value . '</option>';
    }
    $estados = new OrderState(1);
    $html .= '</select>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="columna1">' . $obj->l('Default Nacex International shipment type') . ':</td>
                                                    <td class="columna2" id="nacex_tip_env_int">
                                                        <input type="radio" name="nacex_tip_env_int" value="M" ' . $tip_env_mu . '/>M - ' . $obj->l('MUESTRAS') . '
                                                        <input type="radio" name="nacex_tip_env_int" value="D" ' . $tip_env_do . '/>D - ' . $obj->l('DOCUMENTOS') . '
                                                    </td>
                                                </tr>
                                            </table>
                                      </fieldset>
                                  
                                      <br/>
									 
									<fieldset>
											<legend> ' . $obj->l('Backend form') . '</legend>
								      	<table style="border: 0px;">			
					<tr>
					    <td class="columna1">' . $obj->l('Update order status on printing label') . ' :</td>
						    <td id="cambiar_estado_imprimir" class="columna2">';
    $sel_cambiar = Configuration::get('NACEX_CAMBIAR_ESTADO_IMPRIMIR');
    $estados = $estados->getOrderStates($id_lang);
    $seleccionado = '';
    array_push($estados, ['name' => 'NONE']);
    $html .= '<select name="cambiar_estado_imprimir">';
    $html .= '<option value="">' . $obj->l('None') . '</option>';
    foreach ($estados as $estado) {
        if (isset($estado['id_order_state'])) {
            $id_estado = $estado['id_order_state'];
            $valor = $estado['name'];
            if ($sel_cambiar !== false && $id_estado == $sel_cambiar) {
                $seleccionado = 'selected';
            } elseif ($sel_cambiar == false && $valor == 'NONE') {
                $seleccionado = 'selected';
            }
            $html .= '<option value="' . $id_estado . '"' . $seleccionado . '> ' . $valor . '</option>';
        }
        $seleccionado = '';
    }
    $html .= '</select>    
          </td>
		  </tr>																
		  <tr>';
    $html .= '</td>						
		 </tr>
		 <tr>
		    <td class="columna1">' . $obj->l('Update order status on cancel expedition') . ':</td>
			<td id="cambiar_estado_cancelar" class="columna2">';
    $sel_cambiar = Configuration::get('NACEX_CAMBIAR_ESTADO_CANCELAR');
    $html .= '<select name="cambiar_estado_cancelar">';
    $html .= '<option value="">' . $obj->l('None') . '</option>';
    foreach ($estados as $estado) {
        if (isset($estado['id_order_state'])) {
            $id_estado = $estado['id_order_state'];
            $valor = $estado['name'];
            if ($sel_cambiar !== false && $id_estado == $sel_cambiar) {
                $seleccionado = 'selected';
            } elseif ($sel_cambiar == false && $valor == 'NONE') {
                $seleccionado = 'selected';
            }
            $html .= '<option value="' . $id_estado . '"' . $seleccionado . '> ' . $valor . '</option>';
        }
        $seleccionado = '';
    }
    $html .= '</select>    
          </td>
		  </tr>    
		  </tr>																
	      <tr>';
    $html .= '</td>						
		 </tr>
		 <tr>
		 <td class="columna1">' . $obj->l('Update order status on documenting expedition') . ':</td>
		 <td id="cambiar_estado_generar" class="columna2">';
    $sel_cambiar = Configuration::get('NACEX_CAMBIAR_ESTADO_GENERAR');
    $html .= '<select name="cambiar_estado_generar">';
    $html .= '<option value="">' . $obj->l('None') . '</option>';
    foreach ($estados as $estado) {
        if (isset($estado['id_order_state'])) {
            $id_estado = $estado['id_order_state'];
            $valor = $estado['name'];
            if ($sel_cambiar !== false && $id_estado == $sel_cambiar) {
                $seleccionado = 'selected';
            } elseif ($sel_cambiar == false && $valor == 'NONE') {
                $seleccionado = 'selected';
            }
        }
        $html .= '<option value="' . $id_estado . '"' . $seleccionado . '> ' . $valor . '</option>';
        $seleccionado = '';
    }
    $html .= '</select>    
                                      </td>
                                      </tr>
                                      <tr>
                                    <td class="columna1">' . $obj->l('Update order status when shipment is delivered') . ':</td>
                                        <td id="cambiar_estado_ok" class="columna2">';
    $sel_cambiar = Configuration::get('NACEX_CAMBIAR_ESTADO_OK');
    $html .= '<select name="cambiar_estado_ok">';
    $html .= '<option value="">' . $obj->l('None') . '</option>';
    foreach ($estados as $estado) {
        if (isset($estado['id_order_state'])) {
            $id_estado = $estado['id_order_state'];
            $valor = $estado['name'];
            if ($sel_cambiar !== false && $id_estado == $sel_cambiar) {
                $seleccionado = 'selected';
            } elseif ($sel_cambiar == false && $valor == 'NONE') {
                $seleccionado = 'selected';
            }
            $html .= '<option value="' . $id_estado . '"' . $seleccionado . '> ' . $valor . '</option>';
        }
        $seleccionado = '';
    }
    $html .= '</select>
            </td>
          </tr>
          <tr>
            <td class="columna1">' . $obj->l('Update order status when shipment is in transit') . ':</td>
            <td id="cambiar_estado_transito" class="columna2">';
    $sel_cambiar = Configuration::get('NACEX_CAMBIAR_ESTADO_TRANSITO');
    $html .= '<select name="cambiar_estado_transito">';
    $html .= '<option value="">' . $obj->l('None') . '</option>';
    foreach ($estados as $estado) {
        if (isset($estado['id_order_state'])) {
            $id_estado = $estado['id_order_state'];
            $valor = $estado['name'];
            $seleccionado = ($sel_cambiar !== false && $id_estado == $sel_cambiar) ? 'selected' : '';
            $html .= '<option value="' . $id_estado . '"' . $seleccionado . '> ' . $valor . '</option>';
        }
    }
    $html .= '</select>
            </td>
          </tr>
          <tr>
            <td class="columna1">' . $obj->l('Update order status when shipment is out for delivery') . ':</td>
            <td id="cambiar_estado_reparto" class="columna2">';
    $sel_cambiar = Configuration::get('NACEX_CAMBIAR_ESTADO_REPARTO');
    $html .= '<select name="cambiar_estado_reparto">';
    $html .= '<option value="">' . $obj->l('None') . '</option>';
    foreach ($estados as $estado) {
        if (isset($estado['id_order_state'])) {
            $id_estado = $estado['id_order_state'];
            $valor = $estado['name'];
            $seleccionado = ($sel_cambiar !== false && $id_estado == $sel_cambiar) ? 'selected' : '';
            $html .= '<option value="' . $id_estado . '"' . $seleccionado . '> ' . $valor . '</option>';
        }
    }
    $html .= '</select>
            </td>
          </tr>
          <tr>
            <td class="columna1">' . $obj->l('Update order status when shipment has an incident') . ':</td>
            <td id="cambiar_estado_incidencia" class="columna2">';
    $sel_cambiar = Configuration::get('NACEX_CAMBIAR_ESTADO_INCIDENCIA');
    $html .= '<select name="cambiar_estado_incidencia">';
    $html .= '<option value="">' . $obj->l('None') . '</option>';
    foreach ($estados as $estado) {
        if (isset($estado['id_order_state'])) {
            $id_estado = $estado['id_order_state'];
            $valor = $estado['name'];
            $seleccionado = ($sel_cambiar !== false && $id_estado == $sel_cambiar) ? 'selected' : '';
            $html .= '<option value="' . $id_estado . '"' . $seleccionado . '> ' . $valor . '</option>';
        }
    }
    $html .= '</select>
            </td>
          </tr>
          <tr>
            <td class="columna1">' . $obj->l('Do not update order status when your order has any of these statuses') . ':</td>
                <td id="no_cambiar_estado_ok" class="columna2" >';
    $estadoOptions = [];
    foreach ($estados as $value => $estado) {
        $idOrderState = !isset($estado['id_order_state']) ? '' : $value;
        $estadoOptions[$idOrderState] = $estado['name'];
    }
    $html .= nacexutils::renderCheckboxGroup('no_cambiar_estado_ok', 'NACEX_NO_CAMBIAR_ESTADO_A_OK', '|', $estadoOptions) . '
          </td>
          </tr>
          <tr>						
                <td colspan="2"><hr><br>
		  </td>						
          </tr>    
          </tr>																
          <tr>';
    $html .= '								  				<td class="columna1">' . $obj->l('Default Charge Type') . ':</td>
									  				<td class="columna2" id="nacex_tip_cob">
									  					<input type="radio" name="nacex_tip_cob" value="O" ' . $tip_cob_01 . '/>O - ' . $obj->l('Origin') . '
									  					&nbsp;
									  					<input type="radio" name="nacex_tip_cob" value="D" ' . $tip_cob_02 . '/>D - ' . $obj->l('Destiny') . '
									  					&nbsp;
									  					<input type="radio" name="nacex_tip_cob" value="T" ' . $tip_cob_03 . '/>T - ' . $obj->l('Third') . ' 
									  					<p class="tip"><span class="resaltado bold">' . $obj->l('In International shipping ONLY available in ORIGIN') . '</span></p>
									  				</td>
									  			</tr>
									  			<tr>
									  				<td class="columna1">' . $obj->l('Cash on delivery modules') . ': </td>
									  				<td class="columna2" id="nacex_modulos_reembolso">
									  					' . showError($errores, 'nacex_modulos_reembolso') . '
															' . $divpayment . '
									  					<p class="tip">' . $obj->l('Select cash on delivery payment modules enabled. For multiple selection press Ctrl + click') . '<br/>
                                                            <span class="resaltado"><strong>' . $obj->l('You must assign manually the payment methods to the carriers. The cash on delivery MUST NOT be enabled for carriers PLUS BAG (04) nor INTERNATIONALS (G y H).') . '</strong><br/>
                                                            ' . $obj->l('You can do it') . ' <a href="' . $pagoUrl . '" target="_blank" style="color:#ff5100;">' . $obj->l('clicking here') . '</a> ' . $obj->l('in CARRIER RESTRICTIONS area') . '.</span>
									  					</p>									  					
									  				</td>
									  			</tr>
									  			<tr>
														<td class="columna1">' . $obj->l('Default refund type') . ':</td>
														<td class="columna2" id="nacex_tip_ree">
															<input type="radio" name="nacex_tip_ree" value="O" ' . $tip_ree_01 . '/>O - ' . $obj->l('Origen') . '
															&nbsp;
															<input type="radio" name="nacex_tip_ree" value="D" ' . $tip_ree_02 . '/>D -  ' . $obj->l('Destino') . '
															&nbsp;
															<input type="radio" name="nacex_tip_ree" value="T" ' . $tip_ree_03 . '/>T - ' . $obj->l('Tercera') . '
															<p class="tip"><span class="resaltado bold">' . $obj->l('In Internacional shipping REFUND is NOT available') . '</span></p>
														</td>
													</tr>
													<tr>
														<td class="columna1">' . $obj->l('Default shipment type') . ': </td>
														<td class="columna2" id="nacex_tip_env">
															<input type="radio" name="nacex_tip_env" value="0" ' . $tip_env_docs . '/>0 - DOCS
															&nbsp;
															<input type="radio" name="nacex_tip_env" value="1" ' . $tip_env_bag . '/>1 - BAG
															&nbsp;
															<input type="radio" name="nacex_tip_env" value="2" ' . $tip_env_paq . '/>2 - PAQ
															<p class="tip">' . $obj->l('Shipment type for Spain, Portugal and Andorra') . '</p>
														</td>
													</tr>
													' . nacexutils::getRadioHTML('Report quantity and reference in Additional Instructions', 'nacex_ins_adi_q_r', 'NO', $ins_adi_q_r_no, 'SI', $ins_adi_q_r_si, 'Report product quantity in the order and its references. They will be added in Additional Instructions field')
        . '
													' . nacexutils::getRadioHTML('Add Additional Instructions to label', 'nacex_ins_pers', 'NO', $ins_adi_pers_no, 'SI', $ins_adi_pers_si, 'It allows adding comments to shipment by additional instructions.', "javascript:disableValor('nacex_custom_inst_pers');disableValor('nacex_custom_obs');", "javascript:enableValor('nacex_custom_inst_pers');enableValor('nacex_custom_obs');")
        . '
													<tr>
														<td class="columna1">' . $obj->l('Additional Instructions text') . ': </td>
														<td class="columna2" id="nacex_custom_inst_pers">
															<input type="text" ' . $ins_adi_pers_DIS . ' size="50" maxlength="600" name="nacex_custom_inst_pers" value="' . Tools::getValue('nacex_inst_adi', Configuration::get('NACEX_CUSTOM_INST_ADI')) . '"/>
															<p class="tip">' . $obj->l('Additional Instructions content that will be added on generating all shipments by default') . '<br>' . $obj->l('Max. size: 600 chars.') . '</p>
														</td>
													</tr>
													<tr>
														<td class="columna1">' . $obj->l('Observations text') . ': </td>
														<td class="columna2" id="nacex_custom_obs">
															<input type="text" ' . $obs_DIS . ' size="50" maxlength="76" name="nacex_custom_obs" value="' . Tools::getValue('nacex_custom_obs', Configuration::get('NACEX_CUSTOM_OBS')) . '"/>
															<p class="tip">' . $obj->l('Observations content that will be added on generating all shipments by default') . '<br>' . $obj->l('Max. size: 76 chars.') . '</p>
														</td>
													</tr>
													
													' . nacexutils::getRadioHTML('Customer feedback capture', 'nacex_comentarios_cli_sino', 'NO', $nacex_comentarios_cli_no, 'SI', $nacex_comentarios_cli_si)
        . '
															<p class="tip"><span class="resaltado bold">' . $obj->l('If this field is enabled without enabling Add Additional Instructions to label option, feedback is added within additional instructions. This way we avoid the 76 char limitation from Observations field.') . '<br>
                                                            ' . $obj->l('If is enabled Add Additional Instructions to label option and the field Observations text is empty, feedback is added to observations and all the text that exceeds 76 characters is filled in additional instructions. If observation text field is filled, feedback goes into additional instructions.') . '</span></p>
														</td>
													</tr>
													
													' . nacexutils::getRadioHTML('Default Return shipment', 'nacex_ret', 'NO', $ret_no, 'SI', $ret_si)
        . '
															<p class="tip">' . $obj->l('Default Return shipment') . '. <span class="resaltado bold">' . $obj->l('In International shipments is NOT available management RETURN shipments') . '</span></p>
														</td>
													</tr>
													' . nacexutils::getRadioHTML('Custom reference', 'nacex_ref_pers', 'NO', $nacex_ref_pers_no, 'SI', $nacex_ref_pers_si, 'The reference is composed by a custom prefix and an ID (order id). Reference must have 20 digit max.', "javascript:disableValor('nacex_ref_pers_prefijo')", "javascript:enableValor('nacex_ref_pers_prefijo')")
        . '
                                                    <tr>
                                                        <td class="columna1">' . $obj->l('Custom reference prefix') . ': </td>
                                                        <td class="columna2" id="nacex_ref_pers_prefijo">
                                                            ' . showError($errores, 'nacex_ref_pers_prefijo') . '	
                                                            <input type="text" placeholder="' . $obj->l('Reference') . '" ' . $nacex_ref_pers_DIS . ' size="50" name="nacex_ref_pers_prefijo" value="' . Tools::getValue('nacex_ref_pers_prefijo', Configuration::get('NACEX_REF_PERS_PREFIJO')) . '" />
                                                            <p class="tip">' . $obj->l('Shipment reference prefix. It doesn\'t have to contain blank spaces nor special chars.') . '<br>' . $obj->l('Ex:') . $obj->l(' NACEX_') . '</p>
                                                        </td>
                                                    </tr>
                                                    <tr>
														<td class="columna1">' . $obj->l('Default insurance type') . ': </td>
														<td class="columna2">
																<select name="nacex_default_tip_seg" size="1">';
    foreach ($nacexDTO->getSeguros() as $seg => $value) {
        $segname = $value['nombre'];
        $segdesc = $value['descripcion'];

        $html .= '<option ' . nacexutils::markSelectedOption('nacex_default_tip_seg', 'NACEX_DEFAULT_TIP_SEG', $seg) . ' value="' . $seg . '">' . $segname . '</option>';
    }
    $html .= '</select>
															   <p class="tip">' . $obj->l('Default insurance type') . '</p>
														</td>
													</tr>
													<tr>
														<td class="columna1">' . $obj->l('Default insured amount') . ': </td>
														<td class="columna2">
															' . showError($errores, 'nacex_default_imp_seg') . '
															<input type="text" placeholder="' . $obj->l('€') . '" ' . $nacex_default_imp_seg_DIS . ' id="nacex_default_imp_seg" name="nacex_default_imp_seg" value="' . Tools::getValue('nacex_default_imp_seg', Configuration::get('NACEX_DEFAULT_IMP_SEG')) . '"  onkeypress="javascript:return soloNumeros(event);" onblur="javascript:ValidarNum(this.value, this,7,2);"/>
														<p class="tip">' . $obj->l('Default insured amount') . ' (€)</p>
														</td>
													</tr>
													<script>
														$(function() {
															$("select[name=\"nacex_default_tip_seg\"]").change(function(index) {
										    					if ($(this).val() != "N"){
										    						$("#nacex_default_imp_seg").removeAttr("disabled");
										    						$("#nacex_default_imp_seg").attr("value","' . Tools::getValue('nacex_default_imp_seg', Configuration::get('NACEX_DEFAULT_IMP_SEG')) . '");
										    						$("#nacex_default_imp_seg").focus();
										    					}
										    					else{
										    						$("#nacex_default_imp_seg").attr("disabled","disabled");
										    						$("#nacex_default_imp_seg").attr("value","");
										    					}
															});
														});
													</script>
									  			<tr>
														<td class="columna1">' . $obj->l('Default prealert type') . ':</td>
														<td class="columna2" id="nacex_tip_preal">
															<input type="radio" onchange="javascript:disablePrealerta();" name="nacex_tip_preal" value="N" ' . $nacex_tip_preal_n . '/>' . $obj->l('No prealerta') . '
															&nbsp;
															<input type="radio" onchange="javascript:enablePrealerta();" name="nacex_tip_preal" value="S" ' . $nacex_tip_preal_s . '/>' . $obj->l('SMS') . '
															&nbsp;
															<input type="radio" onchange="javascript:enablePrealerta();" name="nacex_tip_preal" value="E" ' . $nacex_tip_preal_e . '/>' . $obj->l('E-mail') . '
															<p class="tip">' . $obj->l('All orders will be generated with selected prealert') . '</p>
														</td>
													</tr>
													<tr>
														<td class="columna1">M' . $obj->l('Default prealert mode') . ':</td>
														<td class="columna2" id="nacex_mod_preal">
															<input type="radio" ' . $nacex_tip_preal_DIS . ' onchange="javascript:disableValor(\'nacex_preal_plus_txt\')" name="nacex_mod_preal" value="S" ' . $nacex_mod_preal_s . '/>' . $obj->l('Standard') . '
															&nbsp;
															<input type="radio" ' . $nacex_tip_preal_DIS . ' onchange="javascript:enableValor(\'nacex_preal_plus_txt\')" name="nacex_mod_preal" value="P" ' . $nacex_mod_preal_p . '/>' . $obj->l('Plus') . '
															&nbsp;
															<input type="radio" ' . $nacex_tip_preal_DIS . ' onchange="javascript:disableValor(\'nacex_preal_plus_txt\')" name="nacex_mod_preal" value="R" ' . $nacex_mod_preal_r . '/>' . $obj->l('Reparto') . '
															&nbsp;
															<input type="radio" ' . $nacex_tip_preal_DIS . ' onchange="javascript:enableValor(\'nacex_preal_plus_txt\')" name="nacex_mod_preal" value="E" ' . $nacex_mod_preal_e . '/>' . $obj->l('Reparto Plus') . '
															<p class="tip">' . $obj->l('Type of message to send:') . '<br>&nbsp;&nbsp;' . $obj->l('- Standard: Basic alert message') . '<br>&nbsp;&nbsp;' . $obj->l('- Plus: "Standard" message type with added text') . '<br>&nbsp;&nbsp;' . $obj->l(nacexutils::toUtf8('- Reparto: "Standard" message type sent when package arrives to destiny agency.')) . '<br>&nbsp;&nbsp;' . $obj->l('- Reparto Plus: "Reparto" message type with added text.') . '</p>
														</td>
													</tr>
													<tr>
									  				<td class="columna1">' . $obj->l('Prealert Plus message') . ': </td>
									  				<td class="columna2" id="nacex_preal_plus_txt">
									  					' . showError($errores, 'nacex_preal_plus_txt') . '	
									  					<input type="text" placeholder="' . $obj->l('Message') . '" ' . $nacex_tip_preal_DIS . ' size="50" name="nacex_preal_plus_txt" value="' . Tools::getValue('nacex_preal_plus_txt', Configuration::get('NACEX_PREAL_PLUS_TXT')) . '" />
									  					<p class="tip">' . $obj->l('Additional prealert text. Max. 720 chars.') . '</p>
									  				</td>
									  			</tr>
                                                <tr>
                                                    <td class="columna1">' . $obj->l('Shipping packages') . ':</td>
                                                    <td class="columna2" id="nacex_bultos">
                                                        <input type="radio" name="nacex_bultos" onchange="javascript:disableValor(\'nacex_bultos_numero\')" value="C" ' . $nacex_bultos_cesta . '/>' . $obj->l('Cart items') . '
                                                        <input type="radio" name="nacex_bultos" onchange="javascript:enableValor(\'nacex_bultos_numero\')"  value="F" ' . $nacex_bultos_fijo . '/>' . $obj->l('Fixed packages') . '
                                                        <p class="tip">' . $obj->l('Way to calculate shipping packages number.') . '</p>
                                                    </td>
                                                </tr>
									  			<tr>
									  				<td class="columna1">' . $obj->l('Fixed shipping packages') . ': </td>
									  				<td class="columna2" id="nacex_bultos_numero">
									  					<input type="text" placeholder="' . $obj->l('Packages') . '" ' . $nacex_bultos_DIS . ' size="50" name="nacex_bultos_numero" value="' . Tools::getValue('nacex_bultos_numero', Configuration::get('NACEX_BULTOS_NUMERO')) . '" onkeypress="javascript:return soloNumeros(event);" onblur="javascript:ValidarNum(this.value, this,7,0);"/>
									  					<p class="tip">' . $obj->l('Put shipping packages number for all shipments') . '</p>
									  				</td>
									  			</tr>
									  			<tr>
                                                    <td class="columna1">' . $obj->l('Shipping weight') . ':</td>
                                                    <td class="columna2" id="nacex_peso">
                                                        <input type="radio" name="nacex_peso" onchange="javascript:disableValor(\'nacex_peso_numero\')" value="C" ' . $nacex_peso_cesta . '/>' . $obj->l('Cart items') . '
                                                        <input type="radio" name="nacex_peso" onchange="javascript:enableValor(\'nacex_peso_numero\')"  value="F" ' . $nacex_peso_fijo . '/>' . $obj->l('Fixed weight') . '
                                                        <p class="tip">' . $obj->l('Way to calculate shipping weight.') . '</p>
                                                    </td>
									  			</tr>
									  			<tr>
									  				<td class="columna1">' . $obj->l('Fixed kilos number') . ': </td>
									  				<td class="columna2" id="nacex_peso_numero">
									  					<input type="text" placeholder="' . $obj->l('Kilos') . '"  ' . $nacex_peso_cesta_DIS . ' size="50" name="nacex_peso_numero" value="' . Tools::getValue('nacex_peso_numero', Configuration::get('NACEX_PESO_NUMERO')) . '" onkeypress="javascript:return soloNumeros(event);" onblur="javascript:ValidarNum(this.value, this,7,2);"/>
									  					<p class="tip">' . $obj->l('Put weight for all shipments') . '</p>
									  				</td>
									  			</tr>
									  			' . nacexutils::getRadioHTML('Apply handling fee', 'nacex_gastos_manipulacion', 'NO', $nacex_gastos_manipulacion_no, 'SI', $nacex_gastos_manipulacion_si, 'It allows to add extra charges to shipping costs.', "javascript:disableValor('nacex_gastos_manipulacion_val')", "javascript:enableValor('nacex_gastos_manipulacion_val')")
        . '
									  			<tr>
									  				<td class="columna1">' . $obj->l('Handling fee') . ': </td>
									  				<td class="columna2" id="nacex_gastos_manipulacion_val">
									  					' . showError($errores, 'nacex_gastos_manipulacion_val') . '
									  					<input type="text" ' . $nacex_gastos_manipulacion_DIS . ' onfocus="javascript:$(\'#info_nacex_gastos_manipulacion\').fadeIn(400);" onblur="javascript:$(\'#info_nacex_gastos_manipulacion\').fadeOut(400);"  size="50" name="nacex_gastos_manipulacion_val" value="' . Tools::getValue('nacex_gastos_manipulacion_val', Configuration::get('NACEX_GASTOS_MANIPULACION_VAL')) . '" onkeypress="javascript:return soloNumeros(event);" onblur="javascript:ValidarNum(this.value, this,7,2);"/>
									  					' . $divInfoGastosMani . '
									  					<p class="tip">' . $obj->l('Amount added in shipping costs for handling expenses') . '</p>
									  				</td>
									  			</tr>							
									  		</table>
									  </fieldset>
										
									  
										<br/>
									
										<fieldset>
											<legend> ' . $obj->l('Frontend form') . '</legend>
								      	<table style="border: 0px;">
					      					<tr>				
                                                <td class="columna1">' . $obj->l('Frontend services logo\'s width') . ': </td>
                                                <td class="columna2">
                                                    <input type="text" size="1" maxlength="3" placeholder="px" name="nacex_logoservs_width" value="' . Tools::getValue('nacex_logoservs_width', Configuration::get('NACEX_LOGOSERVS_WIDTH')) . '" />
                                                    <p class="tip">' . $obj->l('Leave blank for original size') . '</p>				
                                                </td>			
                                            </tr>
                                            ' . nacexutils::getRadioHTML('Show 0€ cost rates', 'nacex_mostrar_coste0', 'NO', $nacex_mostrar_coste0_no, 'SI', $nacex_mostrar_coste0_si, 'It let show or hide carriers which cost is 0€')
        . '
								      		' . nacexutils::getRadioHTML('Show Generate Expedition form to 3rd party carriers', 'nacex_force_genform', 'NO', $force_genform_no, 'SI', $force_genform_si, 'Show Generate Expedition form to 3rd party carriers')
        . '
								      		<tr>
                                                <td class="columna1">' . $obj->l('Los portes se añaden') . ': </td>
                                                <td class="columna2" id="nacex_cobro_portes">
                                                    <select id="nacex_cobro_portes" name="nacex_cobro_portes">
                                                        <option ' . nacexutils::markSelectedOption('nacex_cobro_portes', 'NACEX_COBRO_PORTES', 'D') . ' value="D">' . $obj->l('Después de aplicar IVA al pedido') . '</option>
                                                        <option ' . nacexutils::markSelectedOption('nacex_cobro_portes', 'NACEX_COBRO_PORTES', 'A') . ' value="A">' . $obj->l('Antes de aplicar IVA') . '</option>
                                                    </select>
                                                    <p class="tip">' . $obj->l('Añadir el precio del envío al pedido') . '</p>
                                                </td>
                                            </tr>
								      		' . nacexutils::getRadioHTML('Is there any third-party OPC (One Page Checkout)?', 'nacex_opc_external', 'NO', $nacex_opc_external_no, 'SI', $nacex_opc_external_si, null, "javascript:disableValor('nacex_opc_id_divgeneral');disableValor('nacex_opc_id_boton');", "javascript:enableValor('nacex_opc_id_divgeneral');enableValor('nacex_opc_id_boton');")
        . '
								      		<tr>
                                                <td class="columna1">' . $obj->l('ID of the general div of the checkout') . ': </td>
                                                <td class="columna2" id="nacex_opc_id_divgeneral">
                                                    <input type="text" ' . $nacex_opc_id_divgeneral_DIS . ' name="nacex_opc_id_divgeneral" value="' . Tools::getValue('nacex_opc_id_divgeneral', Configuration::get('NACEX_OPC_ID_DIVGENERAL')) . '" />
                                                    <p class="tip">' . $obj->l('The id containing the container div of all checkout process') . '. <em>' . $obj->l('Ex:') . ' onepagecheckoutps</em></p>	
                                            </tr>
								      		<tr>
                                                <td class="columna1">' . $obj->l('Submit checkout button ID') . ': </td>
                                                <td class="columna2" id="nacex_opc_id_boton">
                                                    <input type="text" ' . $nacex_opc_id_boton_DIS . ' name="nacex_opc_id_boton" value="' . Tools::getValue('nacex_opc_id_boton', Configuration::get('NACEX_OPC_ID_BOTON')) . '" />
                                                    <p class="tip">' . $obj->l('The id containing the submit checkout button') . '. <em>' . $obj->l('Ex:') . ' btn_place_order</em></p>				
                                                </td>
                                            </tr>
                                            
								      		<tr>
                                                <td class="columna1">' . $obj->l('Selecciona los servicios Nacexshop creados a mano') . ': </td>
                                                <td class="columna2" id="nacexshop_external_modules">
									  					' . showError($errores, 'nacexshop_external_modules') . '
															' . $divservices . '
                                                </td>	
                                            </tr>
								      	</table>
								    </fieldset>
										
									  
										<br/>
									
										<fieldset>
											<legend> ' . $obj->l('Debug') . '</legend>
								      	<table style="border: 0px;">
													' . nacexutils::getRadioHTML('Enable Nacex logs', 'nacex_save_log', 'NO', $nacex_save_log_no, 'SI', $nacex_save_log_si, 'Save a diary log trace in folder nacex/log/aaaa-mm-dd-nacex.log. They can see from "Nacex > See Logs" menu option')
        . '
													' . nacexutils::getRadioHTML('Cleaning Data Base when disabling module', 'nacex_borrar_configuracion', 'NO', $show_borrar_configuracion_no, 'SI', $show_borrar_configuracion_si, 'When disabling Nacex module, remove all data generated on data base.')
        . '
													<!-- Opciones de desarrollador ocultas para el cliente -->
													' . nacexutils::getRadioHTML('Enable developer options', 'nacex_show_dev_ops', 'NO', $show_dev_ops_no, 'SI', $show_dev_ops_si, 'ONLY FOR DEVELOPERS!')
        . '';
    //if(Configuration::get("NACEX_SHOW_DEV_OPS") === 'SI'):
    $html .= '<tr data-depends="nacex_show_dev_ops">' . nacexutils::getRadioHTML('Show runtime errors', 'nacex_show_errors', 'NO', $show_errors_no, 'SI', $show_errors_si, 'For debugging purposes only when necessary.')
        . '
                                                    <tr data-depends="nacex_show_dev_ops">
                                                        <td class="columna1">' . $obj->l('Review carriers table') . '</td>
                                                        <td class="columna2" id="nacex_tableCarriers">
                                                            <input style="cursor: pointer;padding: 7px;width: 250px;" class="ncx_button" onclick="javascript:revisarTablaCarriers();" type="button" name="nacex_tableCarriers" id="nacex_tableCarriers" value="' . $obj->l('Review carriers') . '"/>
                                                            <div id="nacex_tableCarriersResult" class="dataTables_wrapper" style="margin-top: 5px;"></div>
                                                        </td>
                                                    </tr>
                                                    <tr data-depends="nacex_show_dev_ops">
                                                        <td class="columna1">' . $obj->l('Reinstall hooks') . '</td>
                                                        <td class="columna2">
                                                            <input style="cursor: pointer;padding: 7px;width: 250px;" class="ncx_button" onclick="javascript:reinstalarHooks();" type="button" value="' . $obj->l('Reinstall hooks') . '"/>
                                                            <div id="reinstallHooksResult" style="margin-top: 5px;"></div>
                                                            <p class="tip">' . $obj->l('Re-registers all hooks for PS 1.7.8+ compatibility. Use if expedition forms or status updates are not working.') . '</p>
                                                        </td>
                                                    </tr>';
    //endif;
    $html .= '</table>
								    </fieldset>
										
                                    <br/>
                                
                                    <fieldset>
                                        <legend> ' . $obj->l('News and Updates') . '</legend>
								      	<table style="border: 0px;">
													<tr>
														<td class="columna1">' . $obj->l('Feedback form sender email') . ':</td>
														<td class="columna2">
															<input type="email" name="nacex_feedback_sender" id="nacex_feedback_sender"
															    style="width: 75%;"
                                                                value="' . Tools::getValue('nacex_feedback_sender', Configuration::get('NACEX_FEEDBACK_SENDER')) . '"/>
                                                            <p class="tip">' . $obj->l('Email sender for feedback form') . '</p>
														</td>
													</tr>
													' . nacexutils::getRadioHTML('SMTP is used to send emails?', 'nacex_feedback_smtp', 'NO', $nacex_feedback_smtp_no, 'SI', $nacex_feedback_smtp_si)
        . '
								      	</table>
								    </fieldset>

								    <div style="text-align:center;margin-top:1em;">
											<button class="btn btn-primary" type="submit" name="submitSave" onclick="procesando()">
												<i class="material-icons" style="font-size:14px;vertical-align:middle;">save</i> ' . $obj->l('Save config') . '
											</button>
										</div>

									</div>
								</div>
								</form>';

    return $html;
}

function validarFormularioConfiguracion($obj)
{
    $errores = [];

    $nacex_print_model = Tools::getValue('nacex_print_model');
    if (empty($nacex_print_model)) {
        $errores['nacex_print_model'] = $obj->l(nacexutils::toUtf8('You must inform about a labeller model compatible'));
    }

    $nacex_print_et = Tools::getValue('nacex_print_et');
    if (empty($nacex_print_et)) {
        $errores['nacex_print_et'] = $obj->l(nacexutils::toUtf8('You must inform about used printer name'));
    }

    $nacex_wsusername = Tools::getValue('nacex_wsusername');
    if (empty($nacex_wsusername)) {
        $errores['nacex_wsusername'] = $obj->l(nacexutils::toUtf8('You must inform about Web Service username'));
    }

    $nacex_wspassword = Tools::getValue('nacex_wspassword');
    if (empty($nacex_wspassword)) {
        $errores['nacex_wspassword'] = $obj->l(nacexutils::toUtf8('You must inform about Web Service password'));
    }

    $nacex_agcli = Tools::getValue('nacex_agcli');
    if (empty($nacex_agcli)) {
        $errores['nacex_agcli'] = $obj->l(nacexutils::toUtf8('You must inform about agency and customer'));
    } else {
        $agclis_arr = explode(',', $nacex_agcli);
        foreach ($agclis_arr as $agcli) {
            $aux = explode('/', $agcli);
            if (strlen($aux[0]) != 4 || strlen($aux[1]) != 5) {
                $errores['nacex_agcli'] = $obj->l(nacexutils::toUtf8('Wrong agency and customer format'));
                break;
            }
        }
    }

    $nacex_departamentos = Tools::getValue('nacex_departamentos');
    if ($nacex_departamentos) {
        $array_dpt = explode(',', $nacex_departamentos);
        foreach ($array_dpt as $dpt) {
            if (strlen($dpt) > 10) {
                $errores['nacex_departamentos'] = $obj->l(nacexutils::toUtf8('Department name cannot exceed 10 characters'));
                break;
            }
        }
    }

    $nacex_cprec = Tools::getValue('nacex_cprec');
    if (empty($nacex_cprec)) {
        $errores['nacex_cprec'] = $obj->l(nacexutils::toUtf8('You must inform about pickup zip code'));
    }

    // Estándar
    $nacex_gen_serv_name = Tools::getValue('nacex_gen_serv_name');
    if (empty($nacex_gen_serv_name)) {
        $errores['nacex_gen_serv_name'] = $obj->l(nacexutils::toUtf8('You must inform about name of Nacex generic carrier'));
    }

    $nacex_available_tip_ser = Tools::getValue('nacex_available_tip_ser');
    if (! $nacex_available_tip_ser) {
        $errores['nacex_available_tip_ser'] = $obj->l(nacexutils::toUtf8('You must select at least one Nacex standard service'));
    } else {

        $nacex_default_tip_ser = Tools::getValue('nacex_default_tip_ser');
        if (! in_array($nacex_default_tip_ser, $nacex_available_tip_ser)) {
            $errores['nacex_default_tip_ser'] = $obj->l(nacexutils::toUtf8('This service is not been selected as available service'));
        }
    }

    // Cambiamos funcionalidad porque no va más por SÍ/NO si no que va por desplegable
    $nacex_importe_fijo = Tools::getValue('nacex_calculo_importe_std');
    if ($nacex_importe_fijo == 'flat_rate') {
        $nacex_importe_fijo_val = Tools::getValue('nacex_importe_fijo_val');
        if (empty($nacex_importe_fijo_val) && ! is_numeric($nacex_importe_fijo_val)) {
            $errores['nacex_importe_fijo_val'] = $obj->l('You must inform about fixed price');
        } else {
            $nacex_importe_fijo_val = str_replace('.', '', $nacex_importe_fijo_val);
            $nacex_importe_fijo_val = str_replace(',', '.', $nacex_importe_fijo_val);
            $importe = number_format($nacex_importe_fijo_val, 2, ',', '.');
            if ($importe < 0) {
                $errores['nacex_importe_fijo_val'] = $obj->l('Wrong amount');
            }
        }
    }

    $nacex_importe_min_grat = Tools::getValue('nacex_importe_min_grat');
    if ($nacex_importe_min_grat == 'SI') {
        $nacex_importe_min_grat_val = Tools::getValue('nacex_importe_min_grat_val');
        if (empty($nacex_importe_min_grat_val) && ! is_numeric($nacex_importe_min_grat_val)) {
            $errores['nacex_importe_min_grat_val'] = $obj->l('You must inform about minimum free amount');
        } else {
            $nacex_importe_min_grat_val = str_replace('.', '', $nacex_importe_min_grat_val);
            $nacex_importe_min_grat_val = str_replace(',', '.', $nacex_importe_min_grat_val);
            $importe = number_format($nacex_importe_min_grat_val, 2, ',', '.');
            if ($importe < 0) {
                $errores['nacex_importe_min_grat_val'] = $obj->l('Wrong amount');
            }
        }
    }

    // NacexShop
    $nacexshop_gen_serv_name = Tools::getValue('nacexshop_gen_serv_name');
    if (empty($nacexshop_gen_serv_name)) {
        $errores['nacexshop_gen_serv_name'] = $obj->l('You must inform about name of NacexShop generic carrier');
    }

    $nacex_available_tip_nxshop_ser = Tools::getValue('nacex_available_tip_nxshop_ser');
    if ($nacex_available_tip_nxshop_ser) {
        $nacex_default_tip_nxshop_ser = Tools::getValue('nacex_default_tip_nxshop_ser');
        if (! in_array($nacex_default_tip_nxshop_ser, $nacex_available_tip_nxshop_ser)) {
            $errores['nacex_default_tip_nxshop_ser'] = $obj->l('This service is not been selected as available service');
        }
    }

    // Cambiamos funcionalidad porque no va más por SÍ/NO si no que va por desplegable
    $nacexshop_importe_fijo = Tools::getValue('nacex_calculo_importe_shp');
    if ($nacexshop_importe_fijo == 'flat_rate') {
        $nacexshop_importe_fijo_val = Tools::getValue('nacexshop_importe_fijo_val');
        if (empty($nacexshop_importe_fijo_val) && ! is_numeric($nacexshop_importe_fijo_val)) {
            $errores['nacexshop_importe_fijo_val'] = $obj->l('You must inform about fixed price');
        } else {
            $nacexshop_importe_fijo_val = str_replace('.', '', $nacexshop_importe_fijo_val);
            $nacexshop_importe_fijo_val = str_replace(',', '.', $nacexshop_importe_fijo_val);
            $importe = number_format($nacexshop_importe_fijo_val, 2, ',', '.');
            if ($importe < 0) {
                $errores['nacexshop_importe_fijo_val'] = $obj->l('Wrong amount');
            }
        }
    }

    $nacexshop_importe_min_grat = Tools::getValue('nacexshop_importe_min_grat');
    if ($nacexshop_importe_min_grat == 'SI') {
        $nacexshop_importe_min_grat_val = Tools::getValue('nacexshop_importe_min_grat_val');
        if (empty($nacexshop_importe_min_grat_val) && ! is_numeric($nacexshop_importe_min_grat_val)) {
            $errores['nacexshop_importe_min_grat_val'] = $obj->l('You must inform about minimum free amount');
        } else {
            $nacexshop_importe_min_grat_val = str_replace('.', '', $nacexshop_importe_min_grat_val);
            $nacexshop_importe_min_grat_val = str_replace(',', '.', $nacexshop_importe_min_grat_val);
            $importe = number_format($nacexshop_importe_min_grat_val, 2, ',', '.');
            if ($importe < 0) {
                $errores['nacexshop_importe_min_grat_val'] = $obj->l('Wrong amount');
            }
        }
    }

    // internacional
    $nacex_available_tip_ser_int = Tools::getValue('nacex_available_tip_ser_int');
    /*
     * if(!$nacex_available_tip_ser_int){
     * $errores['nacex_available_tip_ser_int'] = $obj->l(nacexutils::toUtf8('Debe seleccionar al menos un servicio Nacex internacional.'));
     * }else{
     */
    $nacex_default_tip_ser_int = Tools::getValue('nacex_default_tip_ser_int');
    if (is_array($nacex_available_tip_ser_int) && !in_array($nacex_default_tip_ser_int, $nacex_available_tip_ser_int)) {
        $errores['nacex_default_tip_ser_int'] = $obj->l('This service is not been selected as available service');
    }
    // }

    $nacexint_gen_serv_name = Tools::getValue('nacexint_gen_serv_name');
    if (empty($nacexint_gen_serv_name)) {
        $errores['nacexint_gen_serv_name'] = $obj->l('You must inform about name of Nacex International generic carrier');
    }

    // Cambiamos funcionalidad porque no va más por SÍ/NO si no que va por desplegable
    $nacexint_importe_fijo = Tools::getValue('nacex_calculo_importe_int');
    if ($nacexint_importe_fijo == 'flat_rate') {
        $nacexint_importe_fijo_val = Tools::getValue('nacexint_importe_fijo_val');
        if (empty($nacexint_importe_fijo_val) && ! is_numeric($nacexint_importe_fijo_val)) {
            $errores['nacexint_importe_fijo_val'] = $obj->l('You must inform about fixed price');
        } else {
            $nacexint_importe_fijo_val = str_replace('.', '', $nacexint_importe_fijo_val);
            $nacexint_importe_fijo_val = str_replace(',', '.', $nacexint_importe_fijo_val);
            $importe = number_format($nacexint_importe_fijo_val, 2, ',', '.');
            if ($importe < 0) {
                $errores['nacexint_importe_fijo_val'] = $obj->l('Wrong amount');
            }
        }
    }

    $nacexint_importe_min_grat = Tools::getValue('nacexint_importe_min_grat');
    if ($nacexint_importe_min_grat == 'SI') {
        $nacexint_importe_min_grat_val = Tools::getValue('nacexint_importe_min_grat_val');
        if (empty($nacexint_importe_min_grat_val) && ! is_numeric($nacexint_importe_min_grat_val)) {
            $errores['nacexint_importe_min_grat_val'] = $obj->l('You must inform about minimum free amount');
        } else {
            $nacexint_importe_min_grat_val = str_replace('.', '', $nacexint_importe_min_grat_val);
            $nacexint_importe_min_grat_val = str_replace(',', '.', $nacexint_importe_min_grat_val);
            $importe = number_format($nacexint_importe_min_grat_val, 2, ',', '.');
            if ($importe < 0) {
                $errores['nacexint_importe_min_grat_val'] = $obj->l('Wrong amount');
            }
        }
    }

    // Si informan de módulo de reembolso, comprobamos que exista
    $nacex_modulos_reembolso = Tools::getValue('nacex_modulos_reembolso');
    if (! empty($nacex_modulos_reembolso)) {
        $array_payment_modules = Module::getPaymentModules();
        //Añadimos los módulos que pueden dar problemas a los métodos de pago
        nacexutils::getPaymentModulesExtra($array_payment_modules);

        $array_modulos_reembolso = $nacex_modulos_reembolso;
        $errormodules = '';

        foreach ($array_modulos_reembolso as $mod_ree) {
            $found = false;

            foreach ($array_payment_modules as $module) {
                if (strtolower($mod_ree) == strtolower($module['name'])) {
                    $found = true;
                }
            }

            if (! $found) {
                $errormodules .= '<b>' . $mod_ree . '</b>,';
            }
        }

        if (! empty($errormodules)) {
            $errormodules = substr($errormodules, 0, -1);
            $errores['nacex_modulos_reembolso'] = $errormodules . ' - ' . $obj->l(nacexutils::toUtf8('this cash on delivery module is not installed or is wrong'));
        }
    }

    $nacex_ref_pers = Tools::getValue('nacex_ref_pers');
    if ($nacex_ref_pers == 'SI') {
        $nacex_ref_pers_prefijo = Tools::getValue('nacex_ref_pers_prefijo');
        if (empty($nacex_ref_pers_prefijo)) {
            $errores['nacex_ref_pers_prefijo'] = $obj->l(nacexutils::toUtf8('You must inform about a prefix for custom reference'));
        } elseif (strpos($nacex_ref_pers_prefijo, '&')) {
            $errores['nacex_ref_pers_prefijo'] = $obj->l(nacexutils::toUtf8('Check if there is some blank spaces or special characters in the fields and remove them'));
        } elseif (strpos($nacex_ref_pers_prefijo, ' ')) {
            $errores['nacex_ref_pers_prefijo'] = $obj->l(nacexutils::toUtf8('Check if there is some blank spaces or special characters in the fields and remove them'));
        }
    }

    $nacex_default_tip_seg = Tools::getValue('nacex_default_tip_seg');
    if ($nacex_default_tip_seg != 'N') {
        $nacex_default_imp_seg = Tools::getValue('nacex_default_imp_seg');
        if (empty($nacex_default_imp_seg) && ! is_numeric($nacex_default_imp_seg)) {
            $errores['nacex_default_imp_seg'] = $obj->l(nacexutils::toUtf8('You must inform about insurance amount'));
        } else {
            $nacex_default_imp_seg = str_replace('.', '', $nacex_default_imp_seg);
            $nacex_default_imp_seg = str_replace(',', '.', $nacex_default_imp_seg);
            $importe = number_format($nacex_default_imp_seg, 2, ',', '.');
            if ($importe < 0) {
                $errores['nacex_default_imp_seg'] = $obj->l('Wrong amount');
            }
        }
    }

    $nacex_gastos_manipulacion = Tools::getValue('nacex_gastos_manipulacion');
    if ($nacex_gastos_manipulacion != 'NO') {
        $nacex_gastos_manipulacion_val = Tools::getValue('nacex_gastos_manipulacion_val');
        if (empty($nacex_gastos_manipulacion_val) && ! is_numeric($nacex_gastos_manipulacion_val)) {
            $errores['nacex_gastos_manipulacion_val'] = $obj->l(nacexutils::toUtf8('You must inform about handling fee amount'));
        } else {
            $nacex_gastos_manipulacion_val = str_replace('.', '', $nacex_gastos_manipulacion_val);
            $nacex_gastos_manipulacion_val = str_replace(',', '.', $nacex_gastos_manipulacion_val);
            $importe = number_format($nacex_gastos_manipulacion_val, 2, ',', '.');
            if ($importe < 0) {
                $errores['nacex_gastos_manipulacion_val'] = $obj->l('Wrong amount');
            }
        }
    }

    return $errores;
}

function showError($errors, $key)
{
    if (isset($errors[$key]) && ! empty($errors[$key])) {
        return '<p class="tip"><font color="#E50000">** ' . $errors[$key] . '</font></p>';
    }
}

function showDivInfo($id, $titulo = null, $mensaje = null)
{
    $div = '<div class="optionsDescription" id="' . $id . '" style="display:none;opacity:0.85;position:absolute;margin-bottom:6px;width:450px;height:auto;padding:5px 5px 5px 50px;background-color:#CAE1FF;border:1px solid #89A9CC;border-radius:4px">';
    if ($titulo) {
        $div .= '<b>' . $titulo . '</b>';
        $div .= '<br>';
    }
    $div .= $mensaje;
    $div .= '</div>';

    return $div;
}

function guardarConfiguracion()
{
    /**
     * Configuración de conexiones
     */
    if (! Configuration::updateValue('NACEX_WS_URL', nacexdto::$url_ws)) {
        return false;
    }
    /* if (! Configuration::updateValue('NACEX_PRINT_URL', Tools::getValue('nacex_print_url'))) {
         return false;
     }*/
    if (!Configuration::updateValue('NACEX_PRINT_IONA', nacexdto::$url_iona)) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_PRINT_MODEL', Tools::getValue('nacex_print_model'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_PRINT_ET', Tools::getValue('nacex_print_et'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_GOOGLE_API', Tools::getValue('nacex_google_api'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_WSUSERNAME', Tools::getValue('nacex_wsusername'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_WSPASSWORD_ORIGINAL', Tools::getValue('nacex_wspassword'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_WSPASSWORD', strtoupper(md5(Tools::getValue('nacex_wspassword'))))) {
        return false;
    }

    /**
     * Configuració del abonado
     */
    if (! Configuration::updateValue('NACEX_AGCLI', Tools::getValue('nacex_agcli'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_DEPARTAMENTOS', Tools::getValue('nacex_departamentos'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_CP_REC', Tools::getValue('nacex_cprec'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_SERV_BACK_OR_FRONT', Tools::getValue('nacex_serv_back_or_front'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_SHOW_F_EXPE_STATE', Tools::getValue('nacex_show_f_expe_state'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_ACT_TRACKING', Tools::getValue('nacex_act_tracking'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_SERVICIO44', Tools::getValue('nacex_servicio44'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_SHOW_EMPRESA', Tools::getValue('nacex_show_empresa'))) {
        return false;
    }

    /**
     * Nacex servicios estándard
     */
    if (!Configuration::updateValue('NACEX_GEN_SERV_NAME', Tools::getValue('nacex_gen_serv_name'))) {
        return false;
    }

    // A partir de la version 1.5.4.4 se unifican los servicios de backend y frontend pero se siguen guardando
    // por separado por compatibilidad con otras versiones anteriores del módulo

    // Nacional
    // BACKEND
    $nacex_available_tip_ser = Tools::getValue('nacex_available_tip_ser');
    if (!is_array($nacex_available_tip_ser)) {
        $nacex_available_tip_ser = [];
    }
    if (! Configuration::updateValue('NACEX_AVAILABLE_TIP_SER', implode('|', $nacex_available_tip_ser))) {
        return false;
    }

    // FRONTEND
    $nacex_available_servs_f = Tools::getValue('nacex_available_tip_ser');
    if (!is_array($nacex_available_servs_f)) {
        $nacex_available_servs_f = [];
    }
    if (! Configuration::updateValue('NACEX_AVAILABLE_SERVS_F', implode('|', $nacex_available_servs_f))) {
        return false;
    }

    // Descripción servicios NACEX FRONTEND -- Deprecated a partir de la version 1.5.4.4
    /*
     * $nacex_available_servs_fd = Tools::getValue('nacex_available_servs_fd');
     * $ava_servs_f_desc_piped = "";
     * for ($i = 0; $i < count($nacex_available_servs_f); $i++){
     * $ava_servs_f_desc_piped = $ava_servs_f_desc_piped . $nacex_available_servs_fd[$i];
     * if($i < count($nacex_available_servs_fd) -1) $ava_servs_f_desc_piped = $ava_servs_f_desc_piped . "|";
     * }
     * if(!Configuration::updateValue('NACEX_AVAILABLE_SERVS_FD', $ava_servs_f_desc_piped)){
     * return false;
     * }
     */

    if (! Configuration::updateValue('NACEX_DEFAULT_TIP_SER', Tools::getValue('nacex_default_tip_ser'))) {
        return false;
    }

    if (! Configuration::updateValue('NACEX_CALCULO_IMPORTE_STD', Tools::getValue('nacex_calculo_importe_std'))) {
        return false;
    }

    if (! Configuration::updateValue('NACEX_IMP_FIJO_VAL', Tools::getValue('nacex_importe_fijo_val'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_IMP_MIN_GRAT', Tools::getValue('nacex_importe_min_grat'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_IMP_MIN_GRAT_VAL', Tools::getValue('nacex_importe_min_grat_val'))) {
        return false;
    }

    /**
     * NacexShop servicios
     */
    if (! Configuration::updateValue('NACEXSHOP_GEN_SERV_NAME', Tools::getValue('nacexshop_gen_serv_name'))) {
        return false;
    }

    // Descripción servicios NACEXSHOP FRONTEND -- Deprecated a partir de la version 1.5.4.4
    /*
     * $nacex_available_servs_nxshop_fd = Tools::getValue('nacex_available_servs_nxshop_fd');
     * $ava_servs_nxshop_f_desc_piped = "";
     * for ($i = 0; $i < count($nacex_available_servs_nxshop_fd); $i++){
     * $ava_servs_nxshop_f_desc_piped = $ava_servs_nxshop_f_desc_piped . $nacex_available_servs_nxshop_fd[$i];
     * if($i < count($nacex_available_servs_nxshop_fd) -1) $ava_servs_nxshop_f_desc_piped = $ava_servs_nxshop_f_desc_piped . "|";
     * }
     * if(!Configuration::updateValue('NACEX_AVAILABLE_SERVS_NXSHOP_FD', $ava_servs_nxshop_f_desc_piped)){
     * return false;
     * }
     */
    if (! Configuration::updateValue('NACEX_DEFAULT_TIP_NXSHOP_SER', Tools::getValue('nacex_default_tip_nxshop_ser'))) {
        return false;
    }

    if (! Configuration::updateValue('NACEX_CALCULO_IMPORTE_SHP', Tools::getValue('nacex_calculo_importe_shp'))) {
        return false;
    }

    if (! Configuration::updateValue('NACEXSHOP_IMP_FIJO_VAL', Tools::getValue('nacexshop_importe_fijo_val'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEXSHOP_IMP_MIN_GRAT', Tools::getValue('nacexshop_importe_min_grat'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEXSHOP_IMP_MIN_GRAT_VAL', Tools::getValue('nacexshop_importe_min_grat_val'))) {
        return false;
    }

    // BACKEND
    $nacex_available_tip_nxshop_ser = Tools::getValue('nacex_available_tip_nxshop_ser');
    if (!is_array($nacex_available_tip_nxshop_ser)) {
        $nacex_available_tip_nxshop_ser = [];
    }
    if (! Configuration::updateValue('NACEX_AVAILABLE_TIP_NXSHOP_SER', implode('|', $nacex_available_tip_nxshop_ser))) {
        return false;
    }

    // FRONTEND
    $nacex_available_servs_nxshop_f = Tools::getValue('nacex_available_tip_nxshop_ser');
    if (!is_array($nacex_available_servs_nxshop_f)) {
        $nacex_available_servs_nxshop_f = [];
    }
    if (! Configuration::updateValue('NACEX_AVAILABLE_SERVS_NXSHOP_F', implode('|', $nacex_available_servs_nxshop_f))) {
        return false;
    }

    /**
     ** Se ha separado los campos de Internacional del backend y tiene su apartado propio
     ** También se ha hecho que para cada método puedan elegir el tipo de cálculo para el importe del envío independientemente
     **/

    /**
     * Nacex Internacional servicios
     */

    // BACKEND
    $nacex_available_tip_ser_int = Tools::getValue('nacex_available_tip_ser_int');
    if (!is_array($nacex_available_tip_ser_int)) {
        $nacex_available_tip_ser_int = [];
    }
    if (! Configuration::updateValue('NACEX_AVAILABLE_TIP_SER_INT', implode('|', $nacex_available_tip_ser_int))) {
        return false;
    }

    if (! Configuration::updateValue('NACEX_CALCULO_IMPORTE_INT', Tools::getValue('nacex_calculo_importe_int'))) {
        return false;
    }

    if (! Configuration::updateValue('NACEXINT_GEN_SERV_NAME', Tools::getValue('nacexint_gen_serv_name'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_DEFAULT_TIP_SER_INT', Tools::getValue('nacex_default_tip_ser_int'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_DEFAULT_CONTENIDO', Tools::getValue('nacex_default_contenido'))) {
        return false;
    }

    // FRONTEND
    $nacex_available_servs_int_f = Tools::getValue('nacex_available_tip_ser_int');
    if (!is_array($nacex_available_servs_int_f)) {
        $nacex_available_servs_int_f = [];
    }
    if (! Configuration::updateValue('NACEX_AVAILABLE_SERVS_INT_F', implode('|', $nacex_available_servs_int_f))) {
        return false;
    }

    if (! Configuration::updateValue('NACEXINT_IMP_FIJO_VAL', Tools::getValue('nacexint_importe_fijo_val'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEXINT_IMP_MIN_GRAT', Tools::getValue('nacexint_importe_min_grat'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEXINT_IMP_MIN_GRAT_VAL', Tools::getValue('nacexint_importe_min_grat_val'))) {
        return false;
    }

    /**
     * Formulario backend
     */
    if (!Configuration::updateValue('NACEX_TIP_COB', Tools::getValue('nacex_tip_cob'))) {
        return false;
    }

    $nacex_modulos_reembolso = Tools::getValue('nacex_modulos_reembolso');
    // Revisamos si el campo tiene contenido; si han seleccionado un método contrareembolso
    if (is_array($nacex_modulos_reembolso)) { $nacex_modulos_reembolso = implode('|', $nacex_modulos_reembolso); }
    if (!Configuration::updateValue('NACEX_MODULOS_REEMBOLSO', $nacex_modulos_reembolso)) {
        return false;
    }

    if (!Configuration::updateValue('NACEX_TIP_REE', Tools::getValue('nacex_tip_ree'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_TIP_ENV', Tools::getValue('nacex_tip_env'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_TIP_ENV_INT', Tools::getValue('nacex_tip_env_int'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_INS_ADI_Q_R', Tools::getValue('nacex_ins_adi_q_r'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_INST_PERS', Tools::getValue('nacex_ins_pers'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_CUSTOM_INST_ADI', Tools::getValue('nacex_custom_inst_pers'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_CUSTOM_OBS', Tools::getValue('nacex_custom_obs'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_COMENTARIOS_CLI_SINO', Tools::getValue('nacex_comentarios_cli_sino'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_RET', Tools::getValue('nacex_ret'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_REF_PERS', Tools::getValue('nacex_ref_pers'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_REF_PERS_PREFIJO', Tools::getValue('nacex_ref_pers_prefijo'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_DEFAULT_TIP_SEG', Tools::getValue('nacex_default_tip_seg'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_DEFAULT_IMP_SEG', Tools::getValue('nacex_default_imp_seg'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_TIP_PREAL', Tools::getValue('nacex_tip_preal'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_MOD_PREAL', Tools::getValue('nacex_mod_preal'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_PREAL_PLUS_TXT', Tools::getValue('nacex_preal_plus_txt'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_BULTOS', Tools::getValue('nacex_bultos'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_BULTOS_NUMERO', Tools::getValue('nacex_bultos_numero'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_PESO', Tools::getValue('nacex_peso'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_PESO_NUMERO', Tools::getValue('nacex_peso_numero'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_GASTOS_MANIPULACION', Tools::getValue('nacex_gastos_manipulacion'))) {
        return false;
    }
    if (! Configuration::updateValue('NACEX_GASTOS_MANIPULACION_VAL', Tools::getValue('nacex_gastos_manipulacion_val'))) {
        return false;
    }

    /**
     * Formulario frontend
     */
    if (!Configuration::updateValue('NACEX_LOGOSERVS_WIDTH', Tools::getValue('nacex_logoservs_width'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_MOSTRAR_COSTE_0', Tools::getValue('nacex_mostrar_coste0'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_COBRO_PORTES', Tools::getValue('nacex_cobro_portes'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_FORCE_GENFORM', Tools::getValue('nacex_force_genform'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_SAVE_LOG', Tools::getValue('nacex_save_log'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_SHOW_DEV_OPS', Tools::getValue('nacex_show_dev_ops'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_SHOW_ERRORS', Tools::getValue('nacex_show_errors'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_BORRAR_CONFIGURACION', Tools::getValue('nacex_borrar_configuracion'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_OPC_EXTERNAL', Tools::getValue('nacex_opc_external'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_OPC_ID_BOTON', Tools::getValue('nacex_opc_id_boton'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_OPC_ID_DIVGENERAL', Tools::getValue('nacex_opc_id_divgeneral'))) {
        return false;
    }

    $nacexshop_external_modules = Tools::getValue('nacexshop_external_modules');
    if (is_array($nacexshop_external_modules)) { $nacexshop_external_modules = implode('|', $nacexshop_external_modules); }
    if (!Configuration::updateValue('NACEXSHOP_EXTERNAL_MODULES', $nacexshop_external_modules)) {
        return false;
    }

    /** Añadir a la base de datos */

    /**
     * Novedades y actualizaciones
     */
    if (!Configuration::updateValue('NACEX_FEEDBACK_SENDER', Tools::getValue('nacex_feedback_sender'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_FEEDBACK_SMTP', Tools::getValue('nacex_feedback_smtp'))) {
        return false;
    }

    //UPDATES STATUS
    if (!Configuration::updateValue('NACEX_CAMBIAR_ESTADO_IMPRIMIR', Tools::getValue('cambiar_estado_imprimir'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_CAMBIAR_ESTADO_CANCELAR', Tools::getValue('cambiar_estado_cancelar'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_CAMBIAR_ESTADO_GENERAR', Tools::getValue('cambiar_estado_generar'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_CAMBIAR_ESTADO_OK', Tools::getValue('cambiar_estado_ok'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_CAMBIAR_ESTADO_TRANSITO', Tools::getValue('cambiar_estado_transito'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_CAMBIAR_ESTADO_REPARTO', Tools::getValue('cambiar_estado_reparto'))) {
        return false;
    }
    if (!Configuration::updateValue('NACEX_CAMBIAR_ESTADO_INCIDENCIA', Tools::getValue('cambiar_estado_incidencia'))) {
        return false;
    }

    $noCambiarEstadoOk = Tools::getValue('no_cambiar_estado_ok');
    if (is_array($noCambiarEstadoOk)) { $noCambiarEstadoOk = implode('|', $noCambiarEstadoOk); }
    if (!Configuration::updateValue('NACEX_NO_CAMBIAR_ESTADO_A_OK', $noCambiarEstadoOk)) {
        return false;
    }
    return true;
}
