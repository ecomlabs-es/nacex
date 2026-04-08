<?php

class VInewServices
{
    protected $storeURL;

    public function __construct()
    {
        $httpURL = Configuration::get('PS_SSL_ENABLED') ? 'https' : 'http';
        // Buscamos el http y lo reemplazamos por https o viceversa
        $this->storeURL = strpos(_PS_BASE_URL_, $httpURL) === false ? str_replace(substr(_PS_BASE_URL_, 0, strpos(_PS_BASE_URL_, ':')), $httpURL, _PS_BASE_URL_) : _PS_BASE_URL_;
        $this->storeURL .= __PS_BASE_URI__;
    }

    public function printNewServiceButtons($tipo)
    {
        $html = '
        <div class="actionsNewServiceNacex">
            <div id="newservice" title="Añadir servicio" class="addNewServiceNacex' . $tipo . '" onclick="$(\'#add' . $tipo . 'Service\').show();">
				<p class="ncx_button add-newservice"><i class="icon-plus"></i> <span>Añadir servicio</span></p>
			</div>
			<div id="newservice" title="Eliminar servicio" class="removeNewServiceNacex' . $tipo . '" onclick="$(\'#remove' . $tipo . 'Service\').show();">
				<p class="ncx_button remove-newservice"><i class="icon-trash"></i> <span>Eliminar servicio</span></p>
			</div>
			<div id="newservice" title="Editar servicio" class="editNewServiceNacex' . $tipo . '" onclick="$(\'#edit' . $tipo . 'Service\').show();">
				<p class="ncx_button edit-newservice"><i class="icon-pencil"></i> <span>Editar servicio</span></p>
			</div>
        </div>
			';

        return $html;
    }

    public function printAddNewService($tipo)
    {
        $html = '
			<div id="add' . $tipo . 'Service" style="display:none;">
			    <fieldset><legend>Añadir servicio</legend></fieldset>
				<div id="codigo-group' . $tipo . '" class="form-group">
					<label for="newCodigo' . $tipo . '">Código*: </label>
					<input id="newCodigo' . $tipo . '" type="text" maxlength="2" size="2"/>
				</div>
				<div id="name-group' . $tipo . '" class="form-group">
					<label for="newName' . $tipo . '">Nombre*: </label>
					<input id="newName' . $tipo . '" type="text" maxlength="50" placeholder="Nombre del servicio"/>
				</div>
				
				<input id="saveNewService" class="ncx_button_small" type="button" title="Guardar" value="Guardar" onclick="saveNewNacexService(\'' . $this->storeURL . '\',\'' . $tipo . '\')" />
				<input id="closeNewService" class="ncx_button_small inverse" type="button" title="Cancelar" value="Cancelar" onclick="$(\'#add' . $tipo . 'Service\').hide();$(\'.addNewServiceNacex' . $tipo . '\').show();"/>
			</div>';
        return $html;
    }

    public function printRemoveNewService($tipo, $nacexDTO)
    {
        $html = '
			<div id="remove' . $tipo . 'Service" style="display:none;">
			    <fieldset><legend>Eliminar servicio</legend></fieldset>
				<div id="multiselect-group' . $tipo . '" class="form-group">
					<select multiple="multiple" id="remove' . $tipo . 'ServiceSelect"';
        if ($nacexDTO->getNewServices($tipo) !== false) {
            $html .= '>';
            foreach ($nacexDTO->getNewServices($tipo) as $serv => $value) {
                $html .= '<option value="' . $serv . '">' . $serv . $nacexDTO->getServSeparador() . nacexutils::toUtf8($value) . '</option>';
            }
        } else $html .= ' disabled><option selected disabled>No hay servicios creados</option>';
        $html .= '</select>
				</div>
																	
				<input id="saveNewService" class="ncx_button_small" type="button" title="Eliminar" value="Eliminar" onclick="removeNewNacexService(\'' . $this->storeURL . '\',\'' . $tipo . '\')" />
				<input id="closeNewService" class="ncx_button_small inverse" type="button" title="Cancelar" value="Cancelar" onclick="$(\'#remove' . $tipo . 'Service\').hide();$(\'.removeNewServiceNacex' . $tipo . '\').show();"/>
			</div>';
        return $html;
    }

    public function printEditNewService($tipo, $nacexDTO)
    {
        $html = '
			<div id="edit' . $tipo . 'Service" style="display:none;">
			    <fieldset><legend>Editar servicio</legend></fieldset>
				<div id="select-group' . $tipo . '" class="form-group">
					<select id="edit' . $tipo . 'ServiceSelect"';
        if ($nacexDTO->getNewServices($tipo) !== false) {
            $html .= '><option value="">Selecciona un servicio</option>';
            foreach ($nacexDTO->getNewServices($tipo) as $serv => $value) {
                $html .= '<option value="' . $serv . '" onclick="toEditData(\'' . $serv . ';' . $value . ';' . $tipo . '\');$(\'#editForm' . $tipo . 'Service\').show();">' . $serv . $nacexDTO->getServSeparador() . nacexutils::toUtf8($value) . '</option>';
            }
        } else $html .= ' disabled><option selected disabled>No hay servicios creados</option>';
        $html .= '</select>
				</div>
			
				<div id="editForm' . $tipo . 'Service" style="display:none;">
					<div id="codigo-group' . $tipo . '" class="form-group">
						<label for="editCodigo' . $tipo . '">Código*: <span id="editedCode"></span></label>
					</div>
					<div id="name-group' . $tipo . '" class="form-group">
						<label for="editName' . $tipo . '">Nombre*: </label>
						<input id="editName' . $tipo . '" type="text" maxlength="50" value=""/>
					</div>
				</div>
				
				<input id="saveNewService" class="ncx_button_small" type="button" title="Guardar" value="Guardar" onclick="editNewNacexService(\'' . $this->storeURL . '\',\'' . $tipo . '\')" />
				<input id="closeNewService" class="ncx_button_small inverse" type="button" title="Cancelar" value="Cancelar" onclick="$(\'#edit' . $tipo . 'Service\').hide();$(\'.editNewServiceNacex' . $tipo . '\').show();"/>
			</div>';
        return $html;
    }
}