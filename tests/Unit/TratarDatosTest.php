<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TratarDatosTest extends TestCase
{
    public function testStringEscapaComillasSimples(): void
    {
        $tratar = new tratardatos();
        $input = "O'Brien";
        $tratar->string($input);

        $this->assertSame("O\\'Brien", $input);
    }

    public function testStringEscapaComillasDobles(): void
    {
        $tratar = new tratardatos();
        $input = 'Dijo "hola"';
        $tratar->string($input);

        $this->assertSame('Dijo \\"hola\\"', $input);
    }

    public function testStringEscapaBackslash(): void
    {
        $tratar = new tratardatos();
        $input = 'ruta\\archivo';
        $tratar->string($input);

        $this->assertSame('ruta\\\\archivo', $input);
    }

    public function testStringNoModificaTextoSinCaracteresEspeciales(): void
    {
        $tratar = new tratardatos();
        $input = 'Texto normal';
        $tratar->string($input);

        $this->assertSame('Texto normal', $input);
    }
}
