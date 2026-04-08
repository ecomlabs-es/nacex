<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class NacexUtilsTest extends TestCase
{
    protected function setUp(): void
    {
        Configuration::reset();
        Tools::reset();
    }

    // --- getDefValue ---

    public function testGetDefValueReturnsValueWhenKeyExists(): void
    {
        $array = ['foo' => 'bar'];
        $this->assertSame('bar', nacexutils::getDefValue($array, 'foo', 'default'));
    }

    public function testGetDefValueReturnsDefaultWhenKeyMissing(): void
    {
        $array = ['foo' => 'bar'];
        $this->assertSame('default', nacexutils::getDefValue($array, 'missing', 'default'));
    }

    public function testGetDefValueReturnsValueEvenIfFalsy(): void
    {
        $array = ['zero' => 0, 'empty' => '', 'null' => null];
        $this->assertSame(0, nacexutils::getDefValue($array, 'zero', 'default'));
        $this->assertSame('', nacexutils::getDefValue($array, 'empty', 'default'));
        // null pasa isset() como false, así que devuelve default
        $this->assertSame('default', nacexutils::getDefValue($array, 'null', 'default'));
    }

    // --- normalizarDecimales ---

    public function testNormalizarDecimalesFormateaCorrectamente(): void
    {
        $result = nacexutils::normalizarDecimales(1234.5, 2, ',', '.', false, false);
        $this->assertSame('1.234,50', $result);
    }

    public function testNormalizarDecimalesDevuelveVacioSiCeroYNoPrintIfZero(): void
    {
        $result = nacexutils::normalizarDecimales('0', 2, ',', '.', true, false);
        $this->assertSame('', $result);
    }

    public function testNormalizarDecimalesDevuelveVacioSiNullYNoPrintIfZero(): void
    {
        $result = nacexutils::normalizarDecimales(null, 2, ',', '.', true, false);
        $this->assertSame('', $result);
    }

    public function testNormalizarDecimalesDevuelveValorSinFormatearSiNoPrintIfCeroDec(): void
    {
        $result = nacexutils::normalizarDecimales(42.5, 2, ',', '.', false, true);
        $this->assertSame(42.5, $result);
    }

    public function testNormalizarDecimalesSinSeparadorDeMiles(): void
    {
        $result = nacexutils::normalizarDecimales(1234.5, 2, '.', '', false, false);
        $this->assertSame('1234.50', $result);
    }

    // --- getMapCambios ---

    public function testGetMapCambiosParseaCorrectamente(): void
    {
        $input = 'nombre=Juan|ciudad=Madrid|edad=30';
        $expected = ['nombre' => 'Juan', 'ciudad' => 'Madrid', 'edad' => '30'];
        $this->assertSame($expected, nacexutils::getMapCambios($input));
    }

    public function testGetMapCambiosConUnSoloPar(): void
    {
        $this->assertSame(['key' => 'value'], nacexutils::getMapCambios('key=value'));
    }

    public function testGetMapCambiosConCadenaVacia(): void
    {
        $this->assertSame([], nacexutils::getMapCambios(''));
    }

    public function testGetMapCambiosIgnoraSegmentosVacios(): void
    {
        $input = 'a=1||b=2';
        $result = nacexutils::getMapCambios($input);
        $this->assertSame('1', $result['a']);
        $this->assertSame('2', $result['b']);
        $this->assertCount(2, $result);
    }

    // --- cutupString ---

    public function testCutupStringCortaEnFragmentos(): void
    {
        $result = nacexutils::cutupString('ABCDEFGHIJ', 3, 3);
        $this->assertSame('ABC', $result[0]);
        $this->assertSame('DEF', $result[1]);
        $this->assertSame('GHI', $result[2]);
    }

    public function testCutupStringConTextoCorto(): void
    {
        $result = nacexutils::cutupString('AB', 5, 2);
        $this->assertSame('AB', $result[0]);
        $this->assertSame('', $result[1]);
    }

    public function testCutupStringEliminaSaltosDeLinea(): void
    {
        $result = nacexutils::cutupString("ABC\nDEF\rGHI", 3, 3);
        $this->assertSame('ABC', $result[0]);
        $this->assertSame('DEF', $result[1]);
        $this->assertSame('GHI', $result[2]);
    }

    // --- explodeShopData ---

    public function testExplodeShopDataParseaCompleto(): void
    {
        $input = 'COD001|Alias1|Nombre Tienda|Calle Falsa 123|28001|Madrid|Madrid';
        $result = nacexutils::explodeShopData($input);

        $this->assertSame('COD001', $result['shop_codigo']);
        $this->assertSame('Alias1', $result['shop_alias']);
        $this->assertSame('Nombre Tienda', $result['shop_nombre']);
        $this->assertSame('Calle Falsa 123', $result['shop_direccion']);
        $this->assertSame('28001', $result['shop_cp']);
        $this->assertSame('Madrid', $result['shop_poblacion']);
        $this->assertSame('Madrid', $result['shop_provincia']);
    }

    public function testExplodeShopDataConDatosParciales(): void
    {
        $input = 'COD001|Alias1';
        $result = nacexutils::explodeShopData($input);

        $this->assertSame('COD001', $result['shop_codigo']);
        $this->assertSame('Alias1', $result['shop_alias']);
        $this->assertNull($result['shop_nombre']);
        $this->assertNull($result['shop_direccion']);
    }

    public function testExplodeShopDataConNull(): void
    {
        $result = nacexutils::explodeShopData(null);
        $this->assertNull($result['shop_codigo']);
        $this->assertNull($result['shop_alias']);
    }

    public function testExplodeShopDataTrimeaEspacios(): void
    {
        $input = ' COD001 | Alias1 | Nombre ';
        $result = nacexutils::explodeShopData($input);
        $this->assertSame('COD001', $result['shop_codigo']);
        $this->assertSame('Alias1', $result['shop_alias']);
        $this->assertSame('Nombre', $result['shop_nombre']);
    }

    // --- arrayFlatten ---

    public function testArrayFlattenAplanaCorrectamente(): void
    {
        $input = [
            ['name' => 'Juan'],
            ['name' => 'Maria'],
            ['name' => 'Pedro'],
        ];
        $this->assertSame(['Juan', 'Maria', 'Pedro'], nacexutils::arrayFlatten($input));
    }

    // --- provincia ---

    public function testProvinciaEncuentraMadridPorCodigoPostal(): void
    {
        $prov = '';
        nacexutils::provincia('28001', $prov);
        $this->assertSame('MADRID', $prov);
    }

    public function testProvinciaEncuentraBarcelona(): void
    {
        $prov = '';
        nacexutils::provincia('08015', $prov);
        $this->assertSame('BARCELONA', $prov);
    }

    public function testProvinciaEncuentraCeuta(): void
    {
        $prov = '';
        nacexutils::provincia('51001', $prov);
        $this->assertSame('CEUTA', $prov);
    }

    public function testProvinciaNoModificaSiCPNoCoincide(): void
    {
        $prov = 'original';
        nacexutils::provincia('99999', $prov);
        $this->assertSame('original', $prov);
    }

    // --- print_messages ---

    public function testPrintMessagesGeneraAlertaError(): void
    {
        $response = '';
        nacexutils::print_messages($response, 'ERROR', 'Algo falló');
        $this->assertStringContainsString('alert-danger', $response);
        $this->assertStringContainsString('Algo falló', $response);
    }

    public function testPrintMessagesGeneraAlertaSuccess(): void
    {
        $response = '';
        nacexutils::print_messages($response, 'SUCCESS', 'Todo bien');
        $this->assertStringContainsString('alert-success', $response);
        $this->assertStringContainsString('Todo bien', $response);
    }

    public function testPrintMessagesGeneraMensajeTip(): void
    {
        $response = '';
        nacexutils::print_messages($response, 'MESSAGE', 'Info');
        $this->assertStringContainsString('<p class=\'tip\'>', $response);
        $this->assertStringContainsString('Info', $response);
    }

    // --- getModuleName ---

    public function testGetModuleNameDevuelveNacex(): void
    {
        $this->assertSame('nacex', nacexutils::getModuleName());
    }

    // --- getReferenciaGeneral ---

    public function testGetReferenciaGeneralUsaPrefijoPersonalizado(): void
    {
        Configuration::set('NACEX_REF_PERS', 'SI');
        Configuration::set('NACEX_REF_PERS_PREFIJO', 'MI_TIENDA_');

        $this->assertSame('MI_TIENDA_', nacexutils::getReferenciaGeneral());
    }

    public function testGetReferenciaGeneralUsaPrefijoPorDefecto(): void
    {
        Configuration::set('NACEX_REF_PERS', 'NO');

        $this->assertSame('pedido_', nacexutils::getReferenciaGeneral());
    }

    // --- toUtf8 ---

    public function testToUtf8ConvierteLatin1AUtf8(): void
    {
        // "ñ" en ISO-8859-1 es byte 0xF1
        $latin1 = "\xF1";
        $result = nacexutils::toUtf8($latin1);
        $this->assertSame('ñ', $result);
    }

    public function testToUtf8ConCadenaVacia(): void
    {
        $this->assertSame('', nacexutils::toUtf8(''));
    }

    public function testToUtf8ConNull(): void
    {
        $this->assertSame('', nacexutils::toUtf8(null));
    }

    public function testToUtf8ConTextoAscii(): void
    {
        $this->assertSame('Hello', nacexutils::toUtf8('Hello'));
    }

    // --- checkInstalledModule (fix variable no inicializada) ---

    public function testCheckInstalledModuleDevuelveNullSinModulos(): void
    {
        $result = nacexutils::checkInstalledModule(['modulo_inexistente']);
        $this->assertNull($result);
    }

    public function testCheckEnabledModuleDevuelveFalseSinModulos(): void
    {
        $result = nacexutils::checkEnabledModule(['modulo_inexistente']);
        $this->assertFalse($result);
    }
}
