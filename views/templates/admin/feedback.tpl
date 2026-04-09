{*<script src="{$module_root}/js/feedback.js" type="text/javascript"></script>*}
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery('.accordion').click(function (el) {
            var id = el['delegateTarget']['id'].substr(el['delegateTarget']['id'].length - 1);
            jQuery('#tabContent'+id).slideToggle();
            //jQuery('#tabTitle' + id + ' i').toggleClass('keyboard_arrow_up');
            jQuery('#tabTitle' + id + ' i').text() === 'keyboard_arrow_down' ?
                jQuery('#tabTitle' + id + ' i').text('keyboard_arrow_up') : jQuery('#tabTitle' + id + ' i').text('keyboard_arrow_down');
        });

        jQuery("#tipoId").val(jQuery('option:selected', this).attr('id'));
        jQuery('#tipo').on('change', function () {
            jQuery("#tipoId").val(jQuery('option:selected', this).attr('id'));
        });

        // Enviamos el formulario con AJAX
        jQuery('.atencion-cliente').on('submit', function(e) {
            e.preventDefault();

            var form = jQuery(this);

            jQuery.ajax({
                url: form.attr('action'),
                type: 'post',
                data: form.serialize(),
                datatype: 'json',
                beforeSend: function () {
                    // Show image container
                    jQuery('.ac-submit').css('float', 'left');
                    jQuery('#ac-loader').show();
                },
                success: function (response) {
                    var success = jQuery.parseJSON(response).success;

                    if (success)
                        jQuery('#nacex-ac-success').show().delay(2500).fadeOut();
                    else
                        jQuery('#nacex-ac-error').show().delay(2500).fadeOut();
                },
                error: function (error) {
                    //Muestra mensaje de error
                    jQuery('#nacex-ac-error p').text('{l s='There has been an error while sending the message' mod="nacex"}');
                    jQuery('#nacex-ac-error').removeClass('nacex-success');
                    jQuery('#nacex-ac-error').addClass('notice-error');
                    jQuery('#nacex-ac-error').show().delay(2500).fadeOut();
                },
                complete: function (data) {
                    // Hide image container
                    jQuery('.ac-submit').css('float', 'none');
                    jQuery('#ac-loader').hide();
                }
            });
        });

        jQuery('.log-body .delete').on('click', function () {
            if (confirm('{l s='Are you sure you want to delete this file?' mod="nacex"}')) {
                var filename = jQuery(this).attr('id');
                var customurl = '{$module_root}' + "/NacexFeedbackAjaxController.php";

                jQuery.ajax({
                    url: customurl,
                    type: 'post',
                    data: 'action=enviar_mail_feedback&filename=' + filename,
                    beforeSend: function () {
                        // Show image container
                        jQuery('[id="ac-loader-' + filename + '"]').show();
                        jQuery('[id="' + filename + '"]').hide();
                    },
                    success: function (response) {
                        alert('{l s='File removed!' mod="nacex"}');
                        location.reload();
                    },
                    complete: function (data) {
                        // Hide image container
                        jQuery('[id="ac-loader-' + filename + '"]').hide();
                        jQuery('[id="' + filename + '"]').show();
                    }
                });
            } else
                return false;
        });
    });
</script>
<div class="ncx_feedback_container">
    <div class="ncx_feedback_logo" align="center">
        <a target="_blank" title="{l s='Go to Nacex web' mod="nacex"}" href="https://www.nacex.es">
            <img src="{$ncx_logo200url}" alt="Logotipo Nacex" style="width: 200px;height: auto;"/>
        </a>
    </div>
    <div id="ncx_feedback_table_container">
        {if $fb->filesExist()}
            <div id="accordion2">
                <h2 id="tabTitle2" class="accordion"><i
                            class="material-icons keyboard_arrow_down">keyboard_arrow_down</i>{l s='No sent emails' mod="nacex"}
                </h2>
                <div id="tabContent2" style="display: none;">
                    <table class="table table-bordered feedback">
                        <tbody class="log-body">
                        {foreach $fb->filesExist() as $row}
                            <tr>
                                {$filename = substr(strrchr($row, '/'), 1)}
                                <td><span><a href="mailto:{$fb->getInfoFile($row)}">{$filename}</a></span></td>
                                <td><span class="action-children"><a href="{$fb->getFileUrl()|cat:$filename}"
                                                                     title="{l s='Download file' mod="nacex"}"><i
                                                    class="material-icons get_app">get_app</i></a></span></td>
                                <td>
                            <span class="action-children">
                                <a href="javascript:;" id="{$filename}" title="{l s='Delete file' mod="nacex"}"
                                   class="delete"><i class="material-icons">delete</i></a>
                                <img src='{$loader_img}' id='ac-loader-{$filename}' alt='ac-loader-{$filename}'
                                     style='display: none;width: 20px;'/>
                            </span>
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        {/if}

        <h2>{l s='Tell us what can we do for you' mod="nacex"}</h2>

        <form action="{$module_root}/NacexFeedbackAjaxController.php" method="post" class="form atencion-cliente">

            <label for="tipo">{l s='Choose your consultation type' mod="nacex"}</label>
            <select id="tipo" name="tipo">
                {foreach $ndto->dropDownFormOptions() as $id => $name}
                    <option id="{$id}">{$name}</option>
                {/foreach}
            </select>
            <input type="hidden" id="tipoId" name="tipoId" value=""/>

            <fieldset>
                <legend>{l s='Fill in the following fields' mod="nacex"}</legend>
                <input id="nombre" name="nombre" type="text" placeholder="*{l s='Full name' mod="nacex"}" required/>
                <input id="company" name="company" type="text" placeholder="*{l s='Company' mod="nacex"}" required/>
                <input id="email" name="email" type="email" placeholder="*{l s='Email' mod="nacex"}" required/>
                <input id="telf" name="telf" type="tel" placeholder="{l s='Company' mod="nacex"}"/>
                <label for="consulta" class="consulta">{l s='Message' mod="nacex"}:</label>
                <textarea id="consulta" name="consulta" cols="75" rows="8"></textarea>
            </fieldset>
            <div class="copia">
                <input type="checkbox" name="copia"
                       id="chk-copia"/><strong>{l s='I want to receive a copy in my e-mail' mod="nacex"}</strong>
                <span class="mini">{l s='It is possible that the e-mail has some additional personal data of you in order to ease a more detailed and individualized consultation.' mod="nacex"}</span>
            </div>
            <div class="copia">
                <input type="checkbox" name="privacidad" id="privacidad" required/>
                {l
                s='I agree with [1]privacy policy[/1]'
                tags=["<a href=\"https://www.nacex.es/irPolitica.do\" target=\"_blank\">"]
                mod="nacex"
                }
            </div>
            <input type="hidden" name="action" value="enviar_mail_feedback">
            <input type="submit" class="ac-submit" value="{l s='Send message' mod="nacex"}"/>
            <div id='ac-loader' style='display: none;'>
                <img src='{$loader_img}'/>
            </div>
        </form>

        <div class="bootstrap" id='nacex-ac-success' style="display:none;margin-top:10px">
            <div class="alert alert-success conf" style="width:auto">
                <p>{l s='Message sent successfully' mod="nacex"}</p>
            </div>
        </div>

        <div class="bootstrap" id='nacex-ac-error' style="display:none;margin-top:10px">
            <div class="alert alert-danger error" style="width:auto">
                <p>{l s='Couldn\'t sent message. Please, see Not sent email in this same page' mod="nacex"}</p>
            </div>
        </div>

    </div>
</div>