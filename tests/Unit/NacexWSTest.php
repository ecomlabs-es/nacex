<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class NacexWSTest extends TestCase
{
    protected function setUp(): void
    {
        Configuration::reset();
        // Limpiar cache de carrier
        $ref = new ReflectionClass('nacexDTO');
        $prop = $ref->getProperty('carrierCache');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    // --- resolveNacexShopData ---

    public function testResolveNacexShopDataConNcxIgualA1(): void
    {
        $datospedido = [['ncx' => '1', 'id_carrier' => 99]];
        $result = nacexWS::resolveNacexShopData($datospedido);

        $this->assertFalse($result['is_nxshop']);
        $this->assertSame('', $result['shop_codigo']);
        $this->assertNull($result['ncxshop_xml']);
        $this->assertEmpty($result['array_shop_data']);
    }

    public function testResolveNacexShopDataConNcxVacio(): void
    {
        $datospedido = [['ncx' => '', 'id_carrier' => 99]];
        $result = nacexWS::resolveNacexShopData($datospedido);

        $this->assertFalse($result['is_nxshop']);
    }

    public function testResolveNacexShopDataConDatosCompletos(): void
    {
        // Simular que el carrier es NacexShop
        $carrierId = 28;
        $mockCarrier = [
            'id_carrier' => $carrierId,
            'ncx' => 'nacexshop',
            'external_module_name' => 'nacex',
            'active' => 1,
        ];

        // Inyectar en cache de carrier para evitar DB
        $ref = new ReflectionClass('nacexDTO');
        $prop = $ref->getProperty('carrierCache');
        $prop->setAccessible(true);
        $prop->setValue(null, [$carrierId => $mockCarrier]);

        $ncx = '9301|2601-108|DBT|JUAN XXIII 19|26003|LOGROÑO|LA RIOJA';
        $datospedido = [['ncx' => $ncx, 'id_carrier' => $carrierId]];

        $result = nacexWS::resolveNacexShopData($datospedido);

        $this->assertTrue($result['is_nxshop']);
        $this->assertSame('9301', $result['shop_codigo']);
        $this->assertSame('2601-108', $result['shop_alias']);
        $this->assertSame('DBT', $result['shop_nombre']);
        $this->assertSame('JUAN XXIII 19', $result['shop_direccion']);
        $this->assertSame('<arrayOfString_3>shop_codigo=9301</arrayOfString_3>', $result['ncxshop_xml']);
        $this->assertCount(7, $result['array_shop_data']);
    }

    public function testResolveNacexShopDataConCarrierNoShop(): void
    {
        // Carrier nacex estándar (no shop)
        $carrierId = 23;
        $mockCarrier = [
            'id_carrier' => $carrierId,
            'ncx' => 'nacex',
            'external_module_name' => 'nacex',
            'active' => 1,
        ];

        $ref = new ReflectionClass('nacexDTO');
        $prop = $ref->getProperty('carrierCache');
        $prop->setAccessible(true);
        $prop->setValue(null, [$carrierId => $mockCarrier]);

        $datospedido = [['ncx' => '9301', 'id_carrier' => $carrierId]];
        $result = nacexWS::resolveNacexShopData($datospedido);

        $this->assertFalse($result['is_nxshop']);
    }

    // --- getSystemInfo ---

    public function testGetSystemInfoContieneVersionPHP(): void
    {
        $info = nacexWS::getSystemInfo();
        $this->assertStringContainsString('environment=', $info);
        $this->assertStringContainsString(phpversion(), $info);
    }

    public function testGetSystemInfoContienePrestaShop(): void
    {
        $info = nacexWS::getSystemInfo();
        $this->assertStringContainsString('PrestaShop-', $info);
    }

    public function testGetSystemInfoContieneVersionModulo(): void
    {
        $info = nacexWS::getSystemInfo();
        $this->assertStringContainsString(nacexutils::nacexVersion, $info);
    }
}
