/** Crea un nuevo servicio **/
function saveNewNacexService(Base_uri, tipo) {

    jQuery(".form-group").removeClass("has-error");
    jQuery(".help-block").remove();

    var formData = {
        newCodigo: jQuery("#newCodigo" + tipo).val(),
        newName: jQuery("#newName" + tipo).val(),
        newServiceType: tipo,
        action: 'add',
    };
    var url = Base_uri + "modules/nacex/COnewServices.php";
    var divId = 'add' + tipo + 'Service';

    jQuery.ajax({
        type: "POST",
        url: url,
        data: formData,
        dataType: "json",
        encode: true,
        beforeSend: function () {
            jQuery('html, body').css("cursor", "wait");
        },
        complete: function () {
            jQuery('html, body').css("cursor", "auto");
        }
    }).done(function (data) {
        console.log(data);

        if (!data.success) {
            if (data.errors.newCodigo) {
                jQuery("#codigo-group" + tipo).addClass("has-error").append(
                    '<div class="help-block">' + data.errors.newCodigo + "</div>"
                );
            }

            if (data.errors.newName) {
                jQuery("#name-group" + tipo).addClass("has-error").append(
                    '<div class="help-block">' + data.errors.newName + "</div>"
                );
            }
        } else {
            jQuery("#" + divId).append(
                '<div class="alert alert-success">' + data.message + "</div>"
            );

            jQuery("#" + divId + " .alert.alert-success").delay(3500).fadeOut(function () {
                jQuery(this).remove();

                //Refrescamos la página
                location.reload();
            });
        }

    }).fail(function (data) {
        console.log(data);

        jQuery("#" + divId).append(
            '<div class="alert alert-danger">No se ha podido añadir el nuevo servicio.</div>'
        );

        jQuery("#" + divId + " .alert.alert-danger").delay(3500).fadeOut(function () {
            jQuery(this).remove();
        });
    });
}

/** Elimina un nuevo servicio **/
function removeNewNacexService(Base_uri, tipo) {

    var formData = {
        selectedOptions: jQuery('select#remove' + tipo + 'ServiceSelect').val(),
        newServiceType: tipo,
        action: 'remove',
    };
    var url = Base_uri + "modules/nacex/COnewServices.php";
    var divId = 'remove' + tipo + 'Service';

    jQuery.ajax({
        type: "POST",
        url: url,
        data: formData,
        dataType: "json",
        encode: true,
        beforeSend: function () {
            jQuery('html, body').css("cursor", "wait");
        },
        complete: function () {
            jQuery('html, body').css("cursor", "auto");
        }
    }).done(function (data) {
        console.log(data);

        if (!data.success) {
            if (data.errors.selectedOptions) {
                jQuery("#multiselect-group" + tipo).addClass("has-error").append(
                    '<div class="help-block">' + data.errors.selectedOptions + "</div>"
                );
            }
        } else {
            jQuery("#" + divId).append(
                '<div class="alert alert-success">' + data.message + "</div>"
            );

            jQuery("#" + divId + " .alert.alert-success").delay(3500).fadeOut(function () {
                jQuery(this).remove();

                //Refrescamos la página
                location.reload();
            });
        }
    }).fail(function (data) {
        console.log(data);

        jQuery("#" + divId).append(
            '<div class="alert alert-danger">No se han podido eliminar los servicios seleccionados.</div>'
        );

        jQuery("#" + divId + " .alert.alert-danger").delay(3500).fadeOut(function () {
            jQuery(this).remove();
        });
    });
}

/** Modifica un nuevo servicio **/
function toEditData(data) {
    let datos = data.split(';');
    let code = datos[0];
    let name = datos[1];
    let tipo = datos[2];

    // Elimino el texto previo que hay y añado el nuevo
    jQuery('span#editedCode').contents().remove();
    jQuery('span#editedCode').append(code);
    jQuery('#editName' + tipo).val(name);
}

function editNewNacexService(Base_uri, tipo) {

    jQuery(".form-group").removeClass("has-error");
    jQuery(".help-block").remove();

    var formData = {
        code: jQuery('select#edit' + tipo + 'ServiceSelect').val(),
        editName: jQuery("#editName" + tipo).val(),
        newServiceType: tipo,
        action: 'edit',
    };
    var url = Base_uri + "modules/nacex/COnewServices.php";
    var divId = 'edit' + tipo + 'Service';

    jQuery.ajax({
        type: "POST",
        url: url,
        data: formData,
        dataType: "json",
        encode: true,
        beforeSend: function () {
            jQuery('html, body').css("cursor", "wait");
        },
        complete: function () {
            jQuery('html, body').css("cursor", "auto");
        }
    }).done(function (data) {
        console.log(data);

        if (!data.success) {
            if (data.errors.code) {
                jQuery("#select-group" + tipo).addClass("has-error").append(
                    '<div class="help-block">' + data.errors.code + "</div>"
                );
            }
            if (data.errors.editName) {
                jQuery("#name-group" + tipo).addClass("has-error").append(
                    '<div class="help-block">' + data.errors.editName + "</div>"
                );
            }
        } else {
            jQuery("#" + divId).append(
                '<div class="alert alert-success">' + data.message + "</div>"
            );

            jQuery("#" + divId + " .alert.alert-success").delay(3500).fadeOut(function () {
                jQuery(this).remove();

                //Refrescamos la página
                location.reload();
            });
        }
    }).fail(function (data) {
        console.log(data);

        jQuery("#" + divId).append(
            '<div class="alert alert-danger">No se ha podido editar el servicio.</div>'
        );

        jQuery("#" + divId + " .alert.alert-danger").delay(3500).fadeOut(function () {
            jQuery(this).remove();
        });
    });
}