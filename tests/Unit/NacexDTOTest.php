<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class NacexDTOTest extends TestCase
{
    private nacexDTO $dto;

    protected function setUp(): void
    {
        $this->dto = new nacexDTO();
    }

    // --- Constantes de clasificación de países ---

    public function testNacionalIncluyeEspanaPortugalAndorraGibraltar(): void
    {
        $this->assertSame(['ES', 'PT', 'AD', 'GI'], nacexDTO::NACIONAL);
    }

    public function testInternacional1IncluyePaisesEuropeosPrincipales(): void
    {
        $expected = ['FR', 'DE', 'IT', 'NL', 'GB', 'LU', 'BE'];
        $this->assertSame($expected, nacexDTO::INTERNACIONAL1);
    }

    public function testInternacional2IncluyeRestoPaisesEuropeos(): void
    {
        $this->assertContains('AT', nacexDTO::INTERNACIONAL2);
        $this->assertContains('PL', nacexDTO::INTERNACIONAL2);
        $this->assertContains('SE', nacexDTO::INTERNACIONAL2);
        $this->assertContains('CH', nacexDTO::INTERNACIONAL2);
    }

    // --- PROVINCIAS_ES ---

    public function testProvinciasEsTieneTodas52Provincias(): void
    {
        // 50 provincias + Ceuta + Melilla
        $this->assertCount(52, nacexDTO::PROVINCIAS_ES);
    }

    public function testProvinciasEsCodigoMadridEs28(): void
    {
        $this->assertSame('28', nacexDTO::PROVINCIAS_ES['MADRID']['Codigo']);
    }

    public function testProvinciasEsCodigoBarcelonaEs08(): void
    {
        $this->assertSame('08', nacexDTO::PROVINCIAS_ES['BARCELONA']['Codigo']);
    }

    public function testProvinciasEsCodigosCeutaMelilla(): void
    {
        $this->assertSame('51', nacexDTO::PROVINCIAS_ES['CEUTA']['Codigo']);
        $this->assertSame('52', nacexDTO::PROVINCIAS_ES['MELILLA']['Codigo']);
    }

    // --- Servicios ---

    public function testGetServiciosNacexDevuelveArrayConServicios(): void
    {
        $servicios = $this->dto->getServiciosNacex();
        $this->assertIsArray($servicios);
        $this->assertNotEmpty($servicios);
        // Debe incluir los servicios base
        $this->assertArrayHasKey('08', $servicios);
        $this->assertSame('NACEX 19:00H', $servicios['08']['nombre']);
    }

    public function testGetServiciosNacexShopDevuelveENacexShop(): void
    {
        $servicios = $this->dto->getServiciosNacexShop();
        $this->assertArrayHasKey('31', $servicios);
        $this->assertSame('E-NACEXSHOP', $servicios['31']['nombre']);
    }

    public function testGetServiciosNacexIntDevuelveInternacionales(): void
    {
        $servicios = $this->dto->getServiciosNacexInt();
        $this->assertArrayHasKey('G', $servicios);
        $this->assertArrayHasKey('H', $servicios);
    }

    public function testGetAllServiciosNacexIncluyeTodosLosTipos(): void
    {
        $todos = $this->dto->getAllServiciosNacex();
        // Servicios nacionales
        $this->assertArrayHasKey('08', $todos);
        // Servicios NacexShop
        $this->assertArrayHasKey('31', $todos);
        // Servicios internacionales
        $this->assertArrayHasKey('G', $todos);
    }

    // --- Seguros y contenidos ---

    public function testGetSegurosDevuelveArrayCompleto(): void
    {
        $seguros = $this->dto->getSeguros();
        $this->assertArrayHasKey('N', $seguros);
        $this->assertSame('Sin seguro', $seguros['N']['nombre']);
        $this->assertArrayHasKey('A', $seguros);
    }

    public function testGetContenidosDevuelveArrayNoVacio(): void
    {
        $contenidos = $this->dto->getContenidos();
        $this->assertIsArray($contenidos);
        $this->assertContains('OTROS', $contenidos);
        $this->assertContains('MEDICAMENTOS', $contenidos);
    }

    // --- Métodos de cálculo ---

    public function testGetMetodosCalculoIncluyeFlatRateYWebService(): void
    {
        $metodos = $this->dto->getMetodosCalculo();
        $valores = array_column($metodos, 'value');
        $this->assertContains('flat_rate', $valores);
        $this->assertContains('web_service', $valores);
        $this->assertContains('table_rates', $valores);
    }

    // --- Modelos etiquetadoras ---

    public function testGetModelosEtiquetadorasIncluyePDF(): void
    {
        $modelos = $this->dto->getModelosEtiquetadoras();
        $valores = array_column($modelos, 'value');
        $this->assertContains('PDF_B', $valores);
    }

    // --- Prefijo referencia ---

    public function testPrefijoReferenciaDefecto(): void
    {
        $this->assertSame('pedido_', nacexDTO::$PREFIJO_REFERENCIA);
    }

    // --- getModuleNacexName ---

    public function testGetModuleNacexNameDevuelveNacex(): void
    {
        $this->assertSame('nacex', nacexDTO::getModuleNacexName());
    }
}
