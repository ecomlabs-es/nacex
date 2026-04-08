<?php

require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/LBnewService.php');

// Verificar que el usuario es un admin logueado
$context = Context::getContext();
if (!$context->employee || !$context->employee->id) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'errors' => ['auth' => 'Unauthorized']]));
}

$LBnewService = new LBnewService();
$errors = [];
$data = [];

$action = Tools::getValue('action');

if ($action) {

    // Añadimos un nuevo servicio
    if ($action == 'add') {
        $newCodigo = Tools::getValue('newCodigo');
        $newName = Tools::getValue('newName');

        if (empty($newCodigo)) {
            $errors['newCodigo'] = 'El código es obligatorio';
        } else {
            $codes = $LBnewService->getAllServices();
            if (array_key_exists($newCodigo, $codes)) {
                $errors['newCodigo'] = 'Ese código ya se está usando';
            }
        }

        if (empty($newName)) {
            $errors['newName'] = 'Hay que asignar un nombre';
        }

        if (!empty($errors)) {
            $data['success'] = false;
            $data['errors'] = $errors;
        } else {
            $data['success'] = true;
            $data['message'] = 'Se ha añadido el nuevo servicio';

            $LBnewService->manageCSV();
        }

    } elseif ($action == 'remove') {
        $selectedOptions = Tools::getValue('selectedOptions');

        if (empty($selectedOptions)) {
            $errors['selectedOptions'] = 'No hay ningún servicio seleccionado';
        }

        if (!empty($errors)) {
            $data['success'] = false;
            $data['errors'] = $errors;
        } else {
            $data['success'] = true;
            $data['message'] = 'Se han eliminado los servicios seleccionados';

            $LBnewService->removeServicesCSV($selectedOptions);
        }

    } elseif ($action == 'edit') {
        $code = Tools::getValue('code');
        $editName = Tools::getValue('editName');

        if (empty($code)) {
            $errors['code'] = 'No hay ningún servicio seleccionado';
        }

        if (empty($editName)) {
            $errors['editName'] = 'Hay que asignar un nombre';
        }

        if (!empty($errors)) {
            $data['success'] = false;
            $data['errors'] = $errors;
        } else {
            $data['success'] = true;
            $data['message'] = 'Se ha modificado el servicio';

            $LBnewService->editServiceCSV();
        }
    }

    header('Content-Type: application/json');
    die(json_encode($data));
}
