<?php

include dirname(__FILE__) . '/../../config/config.inc.php';
include dirname(__FILE__) . '/../../init.php';

// Verificar admin
$cookie = new Cookie('psAdmin');
if (!$cookie->id_employee) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

require_once dirname(__FILE__) . '/nacexutils.php';

$action = Tools::getValue('action', '');
$repo = 'ecomlabs-es/nacex';

header('Content-Type: application/json');

switch ($action) {
    case 'check':
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/' . $repo . '/releases/latest');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PrestaShop-Nacex/' . nacexutils::nacexVersion);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            echo json_encode(['error' => 'Could not check for updates']);
            break;
        }

        $release = json_decode($response, true);
        $latestTag = isset($release['tag_name']) ? $release['tag_name'] : '';
        $currentVersion = nacexutils::nacexVersion;
        $zipUrl = '';

        if (!empty($release['assets'][0]['browser_download_url'])) {
            $zipUrl = $release['assets'][0]['browser_download_url'];
        }

        $hasUpdate = ($latestTag !== '' && $latestTag !== $currentVersion);

        echo json_encode([
            'current' => $currentVersion,
            'latest' => $latestTag,
            'has_update' => $hasUpdate,
            'zip_url' => $zipUrl,
            'release_url' => isset($release['html_url']) ? $release['html_url'] : '',
        ]);
        break;

    case 'update':
        $zipUrl = Tools::getValue('zip_url', '');
        $latestTag = Tools::getValue('tag', '');

        if (empty($zipUrl)) {
            echo json_encode(['error' => 'No zip URL provided']);
            break;
        }

        $modulePath = dirname(__FILE__);
        $tmpZip = _PS_CACHE_DIR_ . 'nacex_update.zip';
        $tmpDir = _PS_CACHE_DIR_ . 'nacex_update/';

        // Descargar zip
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zipUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PrestaShop-Nacex/' . nacexutils::nacexVersion);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $zipData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$zipData) {
            echo json_encode(['error' => 'Failed to download update (HTTP ' . $httpCode . ')']);
            break;
        }

        file_put_contents($tmpZip, $zipData);

        // Extraer
        $zip = new ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            unlink($tmpZip);
            echo json_encode(['error' => 'Failed to open zip']);
            break;
        }

        // Limpiar directorio temporal
        if (is_dir($tmpDir)) {
            self_rmdir($tmpDir);
        }
        mkdir($tmpDir, 0755, true);

        $zip->extractTo($tmpDir);
        $zip->close();
        unlink($tmpZip);

        // Buscar el directorio nacex/ dentro del zip
        $extractedDir = $tmpDir . 'nacex/';
        if (!is_dir($extractedDir)) {
            // Buscar subdirectorio
            $dirs = glob($tmpDir . '*/nacex/', GLOB_ONLYDIR);
            if (!empty($dirs)) {
                $extractedDir = $dirs[0];
            } else {
                $dirs = glob($tmpDir . '*/', GLOB_ONLYDIR);
                $extractedDir = !empty($dirs) ? $dirs[0] : $tmpDir;
            }
        }

        // Copiar archivos sobre el módulo actual (preservar log/ y files/)
        $result = copy_dir($extractedDir, $modulePath, ['log', 'files', '.git']);
        self_rmdir($tmpDir);

        if ($result) {
            // Limpiar cache de Smarty y opcache
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            Tools::clearSmartyCache();

            echo json_encode(['success' => true, 'version' => $latestTag]);
        } else {
            echo json_encode(['error' => 'Failed to copy files']);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}

function copy_dir($src, $dst, $exclude = [])
{
    $dir = opendir($src);
    if (!$dir) {
        return false;
    }
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        if (in_array($file, $exclude)) {
            continue;
        }
        $srcPath = $src . DIRECTORY_SEPARATOR . $file;
        $dstPath = $dst . DIRECTORY_SEPARATOR . $file;
        if (is_dir($srcPath)) {
            if (!is_dir($dstPath)) {
                mkdir($dstPath, 0755, true);
            }
            copy_dir($srcPath, $dstPath, $exclude);
        } else {
            copy($srcPath, $dstPath);
        }
    }
    closedir($dir);
    return true;
}

function self_rmdir($dir)
{
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        is_dir($path) ? self_rmdir($path) : unlink($path);
    }
    return rmdir($dir);
}
