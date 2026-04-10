<?php

class VInewServices
{
    protected $storeURL;
    protected $nacex;

    public function __construct()
    {
        $httpURL = Configuration::get('PS_SSL_ENABLED') ? 'https' : 'http';
        $this->storeURL = strpos(_PS_BASE_URL_, $httpURL) === false ? str_replace(substr(_PS_BASE_URL_, 0, strpos(_PS_BASE_URL_, ':')), $httpURL, _PS_BASE_URL_) : _PS_BASE_URL_;
        $this->storeURL .= __PS_BASE_URI__;
        $this->nacex = new nacex();
    }

    public function printNewServiceButtons($tipo)
    {
        return '
        <div class="actionsNewServiceNacex">
            <div id="newservice-add-' . $tipo . '" title="' . $this->nacex->l('Add service') . '" class="addNewServiceNacex' . $tipo . '" onclick="$(\'#add' . $tipo . 'Service\').show();">
                <p class="ncx_button add-newservice"><i class="material-icons" style="font-size:14px;vertical-align:middle;">add</i> <span>' . $this->nacex->l('Add service') . '</span></p>
            </div>
            <div id="newservice-remove-' . $tipo . '" title="' . $this->nacex->l('Remove service') . '" class="removeNewServiceNacex' . $tipo . '" onclick="$(\'#remove' . $tipo . 'Service\').show();">
                <p class="ncx_button remove-newservice"><i class="material-icons" style="font-size:14px;vertical-align:middle;">delete</i> <span>' . $this->nacex->l('Remove service') . '</span></p>
            </div>
            <div id="newservice-edit-' . $tipo . '" title="' . $this->nacex->l('Edit service') . '" class="editNewServiceNacex' . $tipo . '" onclick="$(\'#edit' . $tipo . 'Service\').show();">
                <p class="ncx_button edit-newservice"><i class="material-icons" style="font-size:14px;vertical-align:middle;">edit</i> <span>' . $this->nacex->l('Edit service') . '</span></p>
            </div>
        </div>';
    }

    public function printAddNewService($tipo)
    {
        return '
            <div id="add' . $tipo . 'Service" style="display:none;">
                <h4>' . $this->nacex->l('Add service') . '</h4>
                <div class="form-group">
                    <label for="newCodigo' . $tipo . '">' . $this->nacex->l('Code') . '*</label>
                    <input id="newCodigo' . $tipo . '" type="text" class="form-control" maxlength="2" style="max-width:80px;" />
                </div>
                <div class="form-group">
                    <label for="newName' . $tipo . '">' . $this->nacex->l('Name') . '*</label>
                    <input id="newName' . $tipo . '" type="text" class="form-control" maxlength="50" placeholder="' . $this->nacex->l('Service name') . '" style="max-width:250px;" />
                </div>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveNewNacexService(\'' . $this->storeURL . '\',\'' . $tipo . '\')">' . $this->nacex->l('Save') . '</button>
                <button type="button" class="btn btn-default btn-sm" onclick="$(\'#add' . $tipo . 'Service\').hide();$(\'.addNewServiceNacex' . $tipo . '\').show();">' . $this->nacex->l('Cancel') . '</button>
            </div>';
    }

    public function printRemoveNewService($tipo, $nacexDTO)
    {
        $html = '
            <div id="remove' . $tipo . 'Service" style="display:none;">
                <h4>' . $this->nacex->l('Remove service') . '</h4>
                <div class="form-group">
                    <select multiple="multiple" class="form-control" id="remove' . $tipo . 'ServiceSelect" style="max-width:335px;"';
        if ($nacexDTO->getNewServices($tipo) !== false) {
            $html .= '>';
            foreach ($nacexDTO->getNewServices($tipo) as $serv => $value) {
                $html .= '<option value="' . $serv . '">' . $serv . $nacexDTO->getServSeparador() . nacexutils::toUtf8($value) . '</option>';
            }
        } else {
            $html .= ' disabled><option selected disabled>' . $this->nacex->l('No services created') . '</option>';
        }
        $html .= '</select>
                </div>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeNewNacexService(\'' . $this->storeURL . '\',\'' . $tipo . '\')">' . $this->nacex->l('Remove') . '</button>
                <button type="button" class="btn btn-default btn-sm" onclick="$(\'#remove' . $tipo . 'Service\').hide();$(\'.removeNewServiceNacex' . $tipo . '\').show();">' . $this->nacex->l('Cancel') . '</button>
            </div>';
        return $html;
    }

    public function printEditNewService($tipo, $nacexDTO)
    {
        $html = '
            <div id="edit' . $tipo . 'Service" style="display:none;">
                <h4>' . $this->nacex->l('Edit service') . '</h4>
                <div class="form-group">
                    <label for="edit' . $tipo . 'ServiceSelect">' . $this->nacex->l('Select a service') . '</label>
                    <select class="form-control" id="edit' . $tipo . 'ServiceSelect" style="max-width:335px;"';
        if ($nacexDTO->getNewServices($tipo) !== false) {
            $html .= ' onchange="if(this.value){toEditData(this.value+\';\'+this.options[this.selectedIndex].dataset.name+\';\'+\'' . $tipo . '\');$(\'#editForm' . $tipo . 'Service\').show();}">
                        <option value="">' . $this->nacex->l('Select a service') . '</option>';
            foreach ($nacexDTO->getNewServices($tipo) as $serv => $value) {
                $html .= '<option value="' . $serv . '" data-name="' . htmlspecialchars($value, ENT_QUOTES) . '">' . $serv . $nacexDTO->getServSeparador() . nacexutils::toUtf8($value) . '</option>';
            }
        } else {
            $html .= ' disabled><option selected disabled>' . $this->nacex->l('No services created') . '</option>';
        }
        $html .= '</select>
                </div>
                <div id="editForm' . $tipo . 'Service" style="display:none;">
                    <div class="form-group">
                        <span>' . $this->nacex->l('Code') . '*: <strong id="editedCode' . $tipo . '"></strong></span>
                    </div>
                    <div class="form-group">
                        <label for="editName' . $tipo . '">' . $this->nacex->l('Name') . '*</label>
                        <input id="editName' . $tipo . '" type="text" class="form-control" maxlength="50" style="max-width:250px;" />
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-sm" onclick="editNewNacexService(\'' . $this->storeURL . '\',\'' . $tipo . '\')">' . $this->nacex->l('Save') . '</button>
                <button type="button" class="btn btn-default btn-sm" onclick="$(\'#edit' . $tipo . 'Service\').hide();$(\'.editNewServiceNacex' . $tipo . '\').show();">' . $this->nacex->l('Cancel') . '</button>
            </div>';
        return $html;
    }
}
