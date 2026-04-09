<?php
/**
 * Bootstrap para tests unitarios del módulo Nacex.
 *
 * Define stubs mínimos de las clases del core de PrestaShop
 * para poder ejecutar tests sin un entorno real.
 *
 * Estrategia: definir constantes y clases ANTES de que los archivos
 * del módulo intenten cargar el core de PrestaShop, y luego cargar
 * solo las clases testeables de forma controlada.
 */

// --- Constantes de PrestaShop ---

if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}
if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '8.1.0');
}
if (!defined('_PS_MODULE_DIR_')) {
    define('_PS_MODULE_DIR_', dirname(__DIR__, 2) . '/');
}
if (!defined('__PS_BASE_URI__')) {
    define('__PS_BASE_URI__', '/');
}
if (!defined('_PS_ROOT_DIR_')) {
    define('_PS_ROOT_DIR_', dirname(__DIR__, 4));
}
if (!defined('_PS_CORE_DIR_')) {
    define('_PS_CORE_DIR_', _PS_ROOT_DIR_);
}
if (!defined('_PS_CACHE_DIR_')) {
    define('_PS_CACHE_DIR_', _PS_ROOT_DIR_ . '/var/cache/');
}
if (!defined('_THEME_DIR_')) {
    define('_THEME_DIR_', '/themes/');
}

// --- Stubs del core de PrestaShop ---

if (!class_exists('Db')) {
    class Db
    {
        private static $instance;
        private static $mockInstance;

        public static function setMockInstance($mock): void
        {
            self::$mockInstance = $mock;
        }

        public static function getInstance(): self
        {
            if (self::$mockInstance) {
                return self::$mockInstance;
            }
            if (!self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function ExecuteS($sql)
        {
            return [];
        }

        public function execute($sql)
        {
            return true;
        }

        public static function resetMock(): void
        {
            self::$mockInstance = null;
        }
    }
}

if (!class_exists('Configuration')) {
    class Configuration
    {
        private static $values = [];

        public static function set(string $key, $value): void
        {
            self::$values[$key] = $value;
        }

        public static function get(string $key)
        {
            return self::$values[$key] ?? false;
        }

        public static function updateValue(string $key, $value): void
        {
            self::$values[$key] = $value;
        }

        public static function reset(): void
        {
            self::$values = [];
        }
    }
}

if (!class_exists('Tools')) {
    class Tools
    {
        private static $values = [];

        public static function set(string $key, $value): void
        {
            self::$values[$key] = $value;
        }

        public static function getValue(string $key, $default = false)
        {
            return self::$values[$key] ?? $default;
        }

        public static function getAdminToken($string)
        {
            return md5($string);
        }

        public static function convertBytes($value)
        {
            return (int) $value;
        }

        public static function reset(): void
        {
            self::$values = [];
        }
    }
}

if (!class_exists('Cookie')) {
    class Cookie
    {
        private $data = [];

        public function __construct($name = '')
        {
        }

        public function __get($key)
        {
            return isset($this->data[$key]) ? $this->data[$key] : null;
        }

        public function __set($key, $value)
        {
            $this->data[$key] = $value;
        }

        public function __isset($key)
        {
            return isset($this->data[$key]);
        }

        public function __unset($key)
        {
            unset($this->data[$key]);
        }

        public function write()
        {
        }
    }
}

if (!class_exists('Context')) {
    class Context
    {
        public $shop;
        public $language;
        public $cookie;
        private static $instance;

        public static function getContext(): self
        {
            if (!self::$instance) {
                self::$instance = new self();
                self::$instance->shop = new Shop();
                self::$instance->cookie = new Cookie();
            }
            return self::$instance;
        }

        public static function reset(): void
        {
            if (self::$instance) {
                self::$instance->cookie = new Cookie();
            }
        }
    }
}

if (!class_exists('Shop')) {
    class Shop
    {
        public function getBaseURL($ssl = false): string
        {
            return 'https://test.ddev.site/';
        }
    }
}

if (!class_exists('Module')) {
    class Module
    {
        public function l($string)
        {
            return $string;
        }

        public static function getModulesInstalled()
        {
            return [];
        }

        public static function isEnabled($name)
        {
            return false;
        }

        public static function getPaymentModules()
        {
            return [];
        }
    }
}

if (!class_exists('CarrierModule')) {
    class CarrierModule extends Module
    {
    }
}

if (!class_exists('Language')) {
    class Language
    {
        public static function getIsoById($id)
        {
            return 'es';
        }
    }
}

if (!class_exists('Tab')) {
    class Tab
    {
        public static function getIdFromClassName($className)
        {
            return 1;
        }
    }
}

if (!class_exists('AdminController')) {
    class AdminController
    {
    }
}

if (!class_exists('Carrier')) {
    class Carrier
    {
        public $id;
        public $id_reference;
        public $name;
        public $external_module_name;
        public $active;
    }
}

if (!class_exists('Address')) {
    class Address
    {
        public $id;
        public $id_customer;
        public $id_manufacturer;
        public $id_supplier;
        public $id_country;
        public $id_state;
        public $alias;
        public $firstname;
        public $lastname;
        public $company;
        public $address1;
        public $address2;
        public $postcode;
        public $city;
        public $phone;
        public $phone_mobile;
        public $dni;
        public $date_add;
        public $date_upd;
        public $deleted;
        public $other;

        public function __construct($id = null)
        {
        }
        public function add()
        {
            return true;
        }
        public function update()
        {
            return true;
        }
    }
}

if (!class_exists('Order')) {
    class Order
    {
        public $id;
        public $id_carrier;
        public $id_address_delivery;
        public $id_address_invoice;
        public $id_lang = 1;
        public $id_cart;

        public function __construct($id = null)
        {
        }
        public function getCurrentState()
        {
            return 1;
        }
        public function getHistory($id_lang)
        {
            return [];
        }
        public function getFirstMessage()
        {
            return '';
        }
        public function update()
        {
            return true;
        }
    }
}

if (!class_exists('OrderHistory')) {
    class OrderHistory
    {
        public $id_order;
        public function changeIdOrderState($state, $order)
        {
        }
        public function addWithemail()
        {
        }
    }
}

if (!class_exists('State')) {
    class State
    {
        public static function getIdByName($name)
        {
            return 1;
        }
        public static function getNameById($id)
        {
            return 'Test';
        }
    }
}

if (!class_exists('Validate')) {
    class Validate
    {
        public static function isPostCode($postcode)
        {
            return empty($postcode) || preg_match('/^[a-zA-Z 0-9-]+$/', $postcode);
        }
    }
}

/**
 * Carga controlada de archivos del módulo.
 *
 * Los archivos del módulo usan require_once/include_once con rutas
 * relativas al core de PrestaShop. Como ya hemos definido las constantes
 * y stubs necesarios, cargamos los archivos directamente evitando
 * la cadena de includes que arrastraría todo el core.
 */
$moduleDir = dirname(__DIR__, 2);

// nacexutils.php hace require_once de defines.inc.php de PS.
// Lo cargamos con output buffering por si genera output.
// Primero necesitamos que defines.inc.php no falle. Lo que hacemos es
// cargar solo la clase, no el archivo completo que tiene el require.
$nacexutilsSource = file_get_contents($moduleDir . '/nacexutils.php');
// Eliminar el require_once de defines.inc.php para evitar cargar el core
$nacexutilsSource = preg_replace(
    '/require_once\s+dirname\(__FILE__\)\s*\.\s*\'\/\.\.\/\.\.\/config\/defines\.inc\.php\';/',
    '// [TEST BOOTSTRAP] require omitido',
    $nacexutilsSource
);
// Eliminar la etiqueta de apertura PHP
$nacexutilsSource = preg_replace('/^<\?php/', '', $nacexutilsSource);
eval($nacexutilsSource);

// nacexDTO.php carga AdminConfig.php y LBnewService.php que traen
// dependencias pesadas. Solo necesitamos la clase nacexDTO.
$nacexDTOSource = file_get_contents($moduleDir . '/nacexDTO.php');
// Eliminar los include_once del inicio
$nacexDTOSource = preg_replace('/include_once\s+dirname\(__FILE__\)\s*\.\s*"\/AdminConfig\.php";/', '// [TEST BOOTSTRAP] include omitido', $nacexDTOSource);
$nacexDTOSource = preg_replace('/include_once\s+dirname\(__FILE__\)\s*\.\s*"\/nacexutils\.php";/', '// [TEST BOOTSTRAP] include omitido', $nacexDTOSource);
$nacexDTOSource = preg_replace('/include_once\s+dirname\(__FILE__\)\s*\.\s*"\/LBnewService\.php";/', '// [TEST BOOTSTRAP] include omitido', $nacexDTOSource);
// Eliminar las etiquetas PHP de apertura y cierre
$nacexDTOSource = preg_replace('/^<\?php/', '', $nacexDTOSource);
$nacexDTOSource = preg_replace('/\?>\s*$/', '', $nacexDTOSource);
eval($nacexDTOSource);

// Estas clases son simples y no tienen includes problemáticos
require_once $moduleDir . '/hash.php';
require_once $moduleDir . '/filterdata.php';
require_once $moduleDir . '/tratardatos.php';

// nacexDAO.php — cargamos solo la clase, eliminando includes del core
$nacexDAOSource = file_get_contents($moduleDir . '/nacexDAO.php');
$nacexDAOSource = preg_replace('/^<\?php/', '', $nacexDAOSource);
// Eliminar includes de archivos del core/módulo
$nacexDAOSource = preg_replace('/(?:require_once|include_once|require|include)\s*\(?\s*dirname\(__FILE__\).*?;/s', '// [TEST BOOTSTRAP] include omitido', $nacexDAOSource);
// Eliminar clases PS no disponibles (Address, Order, OrderHistory, State, Shop, Carrier instanciados)
eval($nacexDAOSource);

// nacexWS.php — cargamos solo la clase, eliminando includes
if (!class_exists('nacexWS')) {
    $nacexWSSource = file_get_contents($moduleDir . '/nacexWS.php');
    $nacexWSSource = preg_replace('/^<\?php/', '', $nacexWSSource);
    $nacexWSSource = preg_replace('/(?:require_once|include_once|require|include)\s*\(?\s*dirname\(__FILE__\).*?;/s', '// [TEST BOOTSTRAP] include omitido', $nacexWSSource);
    eval($nacexWSSource);
}

// Stub de nacexshop (ROnacexshop) para tests que llamen a resolveNacexShopData
if (!class_exists('nacexshop')) {
    class nacexshop
    {
        public function getFileData($shop_codigo, $isShop)
        {
            return false;
        }
        public function checkFileDate()
        {
        }
    }
}
