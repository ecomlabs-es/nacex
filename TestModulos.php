<?php

$modules = get_loaded_extensions();
sort($modules);
$html = '<h4>PHP: ' . phpversion() . '</h4><p>';

foreach ($modules as $m) {
    $html .= $m . ' | ';
}
$html .= '</p>';

// Eliminamos la última ocurrencia del separador
$html = substr($html, 0, strrpos($html, ' | '));

echo $html;
