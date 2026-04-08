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

    public function paisesProvider(): array
    {
        return [
            'nacional' => [nacexDTO::NACIONAL, ['ES', 'PT', 'AD', 'GI']],
            'internacional1' => [nacexDTO::INTERNACIONAL1, ['FR', 'DE', 'IT', 'NL', 'GB', 'LU', 'BE']],
        ];
    }

    /**
     * @dataProvider paisesProvider
     */
    public function testClasificacionPaises(array $actual, array $expected): void
    {
        $this->assertSame($expected, $actual);
    }

    public function testInternacional2IncluyePaisesEuropeos(): void
    {
        foreach (['AT', 'PL', 'SE', 'CH'] as $pais) {
            $this->assertContains($pais, nacexDTO::INTERNACIONAL2);
        }
    }

    // --- PROVINCIAS_ES ---

    public function testProvinciasEsTieneTodas52Provincias(): void
    {
        $this->assertCount(52, nacexDTO::PROVINCIAS_ES);
    }

    public function provinciasCodigoProvider(): array
    {
        return [
            'Madrid' => ['MADRID', '28'],
            'Barcelona' => ['BARCELONA', '08'],
            'Ceuta' => ['CEUTA', '51'],
            'Melilla' => ['MELILLA', '52'],
        ];
    }

    /**
     * @dataProvider provinciasCodigoProvider
     */
    public function testProvinciasEsCodigo(string $provincia, string $codigo): void
    {
        $this->assertSame($codigo, nacexDTO::PROVINCIAS_ES[$provincia]['Codigo']);
    }

    // --- Servicios ---

    public function testGetAllServiciosNacexIncluyeTodosLosTipos(): void
    {
        $todos = $this->dto->getAllServiciosNacex();
        // Nacionales
        $this->assertArrayHasKey('08', $todos);
        $this->assertSame('NACEX 19:00H', $todos['08']['nombre']);
        // NacexShop
        $this->assertArrayHasKey('31', $todos);
        $this->assertSame('E-NACEXSHOP', $todos['31']['nombre']);
        // Internacionales
        $this->assertArrayHasKey('G', $todos);
        $this->assertArrayHasKey('H', $todos);
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
        $this->assertContains('OTROS', $contenidos);
        $this->assertContains('MEDICAMENTOS', $contenidos);
    }

    // --- Métodos de cálculo ---

    public function testGetMetodosCalculoIncluyeTiposPrincipales(): void
    {
        $valores = array_column($this->dto->getMetodosCalculo(), 'value');
        foreach (['flat_rate', 'web_service', 'table_rates'] as $tipo) {
            $this->assertContains($tipo, $valores);
        }
    }

    // --- Modelos etiquetadoras ---

    public function testGetModelosEtiquetadorasIncluyePDF(): void
    {
        $valores = array_column($this->dto->getModelosEtiquetadoras(), 'value');
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
