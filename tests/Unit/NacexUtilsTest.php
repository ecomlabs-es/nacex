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

    public function getDefValueProvider(): array
    {
        return [
            'clave existente' => [['foo' => 'bar'], 'foo', 'default', 'bar'],
            'clave inexistente' => [['foo' => 'bar'], 'missing', 'default', 'default'],
            'valor cero' => [['zero' => 0], 'zero', 'default', 0],
            'valor cadena vacia' => [['empty' => ''], 'empty', 'default', ''],
            'valor null (isset=false)' => [['null' => null], 'null', 'default', 'default'],
        ];
    }

    /**
     * @dataProvider getDefValueProvider
     */
    public function testGetDefValue(array $array, string $key, $default, $expected): void
    {
        $this->assertSame($expected, nacexutils::getDefValue($array, $key, $default));
    }

    // --- normalizarDecimales ---

    public function normalizarDecimalesProvider(): array
    {
        return [
            'formato normal' => [1234.5, 2, ',', '.', false, false, '1.234,50'],
            'cero sin printIfZero' => ['0', 2, ',', '.', true, false, ''],
            'null sin printIfZero' => [null, 2, ',', '.', true, false, ''],
            'sin formatear si noPrintIfCeroDec' => [42.5, 2, ',', '.', false, true, 42.5],
            'sin separador de miles' => [1234.5, 2, '.', '', false, false, '1234.50'],
        ];
    }

    /**
     * @dataProvider normalizarDecimalesProvider
     */
    public function testNormalizarDecimales($value, $dec, $decSep, $thousandSep, $noPrintIfZero, $noPrintIfCeroDec, $expected): void
    {
        $result = nacexutils::normalizarDecimales($value, $dec, $decSep, $thousandSep, $noPrintIfZero, $noPrintIfCeroDec);
        $this->assertSame($expected, $result);
    }

    // --- getMapCambios ---

    public function getMapCambiosProvider(): array
    {
        return [
            'multiples pares' => ['nombre=Juan|ciudad=Madrid|edad=30', ['nombre' => 'Juan', 'ciudad' => 'Madrid', 'edad' => '30']],
            'un solo par' => ['key=value', ['key' => 'value']],
            'cadena vacia' => ['', []],
        ];
    }

    /**
     * @dataProvider getMapCambiosProvider
     */
    public function testGetMapCambios(string $input, array $expected): void
    {
        $this->assertSame($expected, nacexutils::getMapCambios($input));
    }

    public function testGetMapCambiosIgnoraSegmentosVacios(): void
    {
        $result = nacexutils::getMapCambios('a=1||b=2');
        $this->assertSame(['a' => '1', 'b' => '2'], $result);
    }

    // --- cutupString ---

    public function testCutupStringCortaEnFragmentos(): void
    {
        $result = nacexutils::cutupString('ABCDEFGHIJ', 3, 3);
        $this->assertSame(['ABC', 'DEF', 'GHI'], array_slice($result, 0, 3));
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
        $this->assertSame(['ABC', 'DEF', 'GHI'], array_slice($result, 0, 3));
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
        $result = nacexutils::explodeShopData('COD001|Alias1');
        $this->assertSame('COD001', $result['shop_codigo']);
        $this->assertNull($result['shop_nombre']);
    }

    public function testExplodeShopDataConNull(): void
    {
        $result = nacexutils::explodeShopData(null);
        $this->assertNull($result['shop_codigo']);
    }

    public function testExplodeShopDataTrimeaEspacios(): void
    {
        $result = nacexutils::explodeShopData(' COD001 | Alias1 | Nombre ');
        $this->assertSame('COD001', $result['shop_codigo']);
        $this->assertSame('Nombre', $result['shop_nombre']);
    }

    // --- arrayFlatten ---

    public function testArrayFlattenAplanaCorrectamente(): void
    {
        $input = [['name' => 'Juan'], ['name' => 'Maria'], ['name' => 'Pedro']];
        $this->assertSame(['Juan', 'Maria', 'Pedro'], nacexutils::arrayFlatten($input));
    }

    // --- provincia ---

    public function provinciaProvider(): array
    {
        return [
            'Madrid' => ['28001', 'MADRID'],
            'Barcelona' => ['08015', 'BARCELONA'],
            'Ceuta' => ['51001', 'CEUTA'],
        ];
    }

    /**
     * @dataProvider provinciaProvider
     */
    public function testProvincia(string $cp, string $expected): void
    {
        $prov = '';
        nacexutils::provincia($cp, $prov);
        $this->assertSame($expected, $prov);
    }

    public function testProvinciaNoModificaSiCPNoCoincide(): void
    {
        $prov = 'original';
        nacexutils::provincia('99999', $prov);
        $this->assertSame('original', $prov);
    }

    // --- print_messages ---

    public function printMessagesProvider(): array
    {
        return [
            'error' => ['ERROR', 'Algo falló', 'alert-danger'],
            'success' => ['SUCCESS', 'Todo bien', 'alert-success'],
            'message' => ['MESSAGE', 'Info', '<p class=\'tip\'>'],
        ];
    }

    /**
     * @dataProvider printMessagesProvider
     */
    public function testPrintMessages(string $type, string $text, string $expectedHtml): void
    {
        $response = '';
        nacexutils::print_messages($response, $type, $text);
        $this->assertStringContainsString($expectedHtml, $response);
        $this->assertStringContainsString($text, $response);
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

    public function toUtf8Provider(): array
    {
        return [
            'latin1 ñ' => ["\xF1", 'ñ'],
            'cadena vacia' => ['', ''],
            'null' => [null, ''],
            'ascii puro' => ['Hello', 'Hello'],
        ];
    }

    /**
     * @dataProvider toUtf8Provider
     */
    public function testToUtf8($input, string $expected): void
    {
        $this->assertSame($expected, nacexutils::toUtf8($input));
    }

    // --- checkInstalledModule / checkEnabledModule ---

    public function testCheckInstalledModuleDevuelveNullSinModulos(): void
    {
        $this->assertNull(nacexutils::checkInstalledModule(['modulo_inexistente']));
    }

    public function testCheckEnabledModuleDevuelveFalseSinModulos(): void
    {
        $this->assertFalse(nacexutils::checkEnabledModule(['modulo_inexistente']));
    }
}
