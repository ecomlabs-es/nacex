<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery('.accordion').click(function (el) {
            var id = el['delegateTarget']['id'].substr(el['delegateTarget']['id'].length - 1);
            jQuery('#tabContent'+id).slideToggle();
            jQuery('#tabTitle' + id + ' i').text() === 'keyboard_arrow_down' ?
                jQuery('#tabTitle' + id + ' i').text('keyboard_arrow_up') : jQuery('#tabTitle' + id + ' i').text('keyboard_arrow_down');
        });

        jQuery("#tipoId").val(jQuery('option:selected', this).attr('id'));
        jQuery('#tipo').on('change', function () {
            jQuery("#tipoId").val(jQuery('option:selected', this).attr('id'));
        });

        jQuery('.atencion-cliente').on('submit', function(e) {
            e.preventDefault();

            var form = jQuery(this);

            jQuery.ajax({
                url: form.attr('action'),
                type: 'post',
                data: form.serialize(),
                datatype: 'json',
                beforeSend: function () {
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
                    jQuery('#nacex-ac-error p').text('{l s='There has been an error while sending the message' mod="nacex"}');
                    jQuery('#nacex-ac-error').removeClass('nacex-success');
                    jQuery('#nacex-ac-error').addClass('notice-error');
                    jQuery('#nacex-ac-error').show().delay(2500).fadeOut();
                },
                complete: function (data) {
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
                        jQuery('[id="ac-loader-' + filename + '"]').show();
                        jQuery('[id="' + filename + '"]').hide();
                    },
                    success: function (response) {
                        alert('{l s='File removed!' mod="nacex"}');
                        location.reload();
                    },
                    complete: function (data) {
                        jQuery('[id="ac-loader-' + filename + '"]').hide();
                        jQuery('[id="' + filename + '"]').show();
                    }
                });
            } else
                return false;
        });
    });
</script>

<div class="panel">
    <div class="panel-heading" style="display:flex;align-items:center;gap:1em;">
        <a target="_blank" title="{l s='Go to Nacex web' mod="nacex"}" href="https://www.nacex.es">
            <img src="{$ncx_logo200url}" alt="Logotipo Nacex" style="width:130px;height:auto;"/>
        </a>
        <span style="font-size:1.1em;">{l s='Tell us what can we do for you' mod="nacex"}</span>
    </div>
    <div class="panel-body">

        {if $fb->filesExist()}
            <div class="panel" style="margin-bottom:1em;">
                <h3 id="tabTitle2" class="accordion" style="cursor:pointer;color:#ff5100;margin:0;">
                    <i class="material-icons" style="vertical-align:middle;">keyboard_arrow_down</i>
                    {l s='No sent emails' mod="nacex"}
                </h3>
                <div id="tabContent2" style="display:none;margin-top:1em;">
                    <table class="table table-bordered">
                        <tbody class="log-body">
                        {foreach $fb->filesExist() as $row}
                            <tr>
                                {$filename = substr(strrchr($row, '/'), 1)}
                                <td><a href="mailto:{$fb->getInfoFile($row)}">{$filename}</a></td>
                                <td style="width:40px;text-align:center;">
                                    <a href="{$fb->getFileUrl()|cat:$filename}" title="{l s='Download file' mod="nacex"}">
                                        <i class="material-icons">get_app</i>
                                    </a>
                                </td>
                                <td style="width:40px;text-align:center;">
                                    <a href="javascript:;" id="{$filename}" title="{l s='Delete file' mod="nacex"}" class="delete">
                                        <i class="material-icons">delete</i>
                                    </a>
                                    <img src='{$loader_img}' id='ac-loader-{$filename}' alt='loader' style='display:none;width:20px;'/>
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        {/if}

        <form action="{$module_root}/NacexFeedbackAjaxController.php" method="post" class="form atencion-cliente" style="width:100%;max-width:700px;">

            <div class="form-group">
                <label for="tipo">{l s='Choose your consultation type' mod="nacex"}</label>
                <select id="tipo" name="tipo" class="form-control" style="max-width:400px;">
                    {foreach $ndto->dropDownFormOptions() as $id => $name}
                        <option id="{$id}">{$name}</option>
                    {/foreach}
                </select>
                <input type="hidden" id="tipoId" name="tipoId" value=""/>
            </div>

            <fieldset style="border:1px solid #ff5100;padding:1em;margin:1em 0;">
                <legend style="color:#ff5100;font-size:14px;border:none;width:auto;padding:0 0.5em;">{l s='Fill in the following fields' mod="nacex"}</legend>
                <div class="form-group">
                    <input id="nombre" name="nombre" type="text" class="form-control" placeholder="*{l s='Full name' mod="nacex"}" required/>
                </div>
                <div class="form-group">
                    <input id="company" name="company" type="text" class="form-control" placeholder="*{l s='Company' mod="nacex"}" required/>
                </div>
                <div class="form-group">
                    <input id="email" name="email" type="email" class="form-control" placeholder="*{l s='Email' mod="nacex"}" required/>
                </div>
                <div class="form-group">
                    <input id="telf" name="telf" type="tel" class="form-control" placeholder="{l s='Phone' mod="nacex"}"/>
                </div>
                <div class="form-group">
                    <label for="consulta">{l s='Message' mod="nacex"}:</label>
                    <textarea id="consulta" name="consulta" class="form-control" rows="6"></textarea>
                </div>
            </fieldset>

            <div class="checkbox" style="margin-bottom:0.5em;">
                <label>
                    <input type="checkbox" name="copia" id="chk-copia"/>
                    <strong>{l s='I want to receive a copy in my e-mail' mod="nacex"}</strong>
                    <br><small class="text-muted">{l s='It is possible that the e-mail has some additional personal data of you in order to ease a more detailed and individualized consultation.' mod="nacex"}</small>
                </label>
            </div>
            <div class="checkbox" style="margin-bottom:1em;">
                <label>
                    <input type="checkbox" name="privacidad" id="privacidad" required/>
                    {l s='I agree with [1]privacy policy[/1]' tags=["<a href=\"https://www.nacex.es/irPolitica.do\" target=\"_blank\">"] mod="nacex"}
                </label>
            </div>

            <input type="hidden" name="action" value="enviar_mail_feedback">
            <button type="submit" class="btn btn-primary ac-submit">
                {l s='Send message' mod="nacex"}
            </button>
            <span id='ac-loader' style='display:none;margin-left:1em;'>
                <img src='{$loader_img}' style="width:20px;"/>
            </span>
        </form>

        <div class="bootstrap" id='nacex-ac-success' style="display:none;margin-top:10px">
            <div class="alert alert-success">{l s='Message sent successfully' mod="nacex"}</div>
        </div>
        <div class="bootstrap" id='nacex-ac-error' style="display:none;margin-top:10px">
            <div class="alert alert-danger">{l s='Couldn\'t sent message. Please, see Not sent email in this same page' mod="nacex"}</div>
        </div>

    </div>
</div>
