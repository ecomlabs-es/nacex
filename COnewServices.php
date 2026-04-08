<?php
require_once(dirname(__FILE__) . '/LBnewService.php');

$LBnewService = new LBnewService();
$errors = [];
$data = [];

if (isset($_POST['action'])) {

    // Añadimos un nuevo servicio
    if ($_POST['action'] == 'add') {
        if (empty($_POST['newCodigo'])) {
            $errors['newCodigo'] = 'El código es obligatorio';
        } else {
            $codes = $LBnewService->getAllServices();
            if (array_key_exists($_POST['newCodigo'], $codes)) {
                $errors['newCodigo'] = 'Ese código ya se está usando';
            }
        }

        if (empty($_POST['newName'])) {
            $errors['newName'] = 'Hay que asignar un nombre';
        }

        if (!empty($errors)) {
            $data['success'] = false;
            $data['errors'] = $errors;
        } else {
            $data['success'] = true;
            $data['message'] = 'Se ha añadido el nuevo servicio';

            // Como no hay errores, creamos/modificamos el CSV con el método creado y lo añadimos
            $LBnewService->manageCSV();
        }

    } elseif ($_POST['action'] == 'remove') {

        if (empty($_POST['selectedOptions'])) {
            $errors['selectedOptions'] = 'No hay ningún servicio seleccionado';
        }

        if (!empty($errors)) {
            $data['success'] = false;
            $data['errors'] = $errors;
        } else {
            $data['success'] = true;
            $data['message'] = 'Se han eliminado los servicios seleccionados';

            $LBnewService->removeServicesCSV($_POST['selectedOptions']);
        }

    } elseif ($_POST['action'] == 'edit') {

        if (empty($_POST['code'])) {
            $errors['code'] = 'No hay ningún servicio seleccionado';
        }

        if (empty($_POST['editName'])) {
            $errors['editName'] = 'Hay que asignar un nombre';
        }

        if (!empty($errors)) {
            $data['success'] = false;
            $data['errors'] = $errors;
        } else {
            $data['success'] = true;
            $data['message'] = 'Se ha modificado el servicio';

            // Como no hay errores, creamos/modificamos el CSV con el método creado y lo añadimos
            $LBnewService->editServiceCSV();
        }
    }

    header('Content-Type: application/json');
    die(json_encode($data));
}