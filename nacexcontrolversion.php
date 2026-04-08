<?php

/**
 * @deprecated Clase sin implementación real. No se usa activamente.
 * Pendiente de eliminar.
 */

class nacexcontrolversion
{
    public function existeversion()
    {
        return Configuration::get('NACEX_VERSION') == null;
    }
}
