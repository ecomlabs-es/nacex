<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class NacexDAOTest extends TestCase
{
    // --- sanitizeAddressField ---

    public function sanitizeProvider(): array
    {
        return [
            'texto limpio' => ['Barcelona', 128, 'Barcelona'],
            'con caracteres invalidos' => ['City<script>', 128, 'Cityscript'],
            'con todos los chars prohibidos' => ['A<B>C=D{E}F#G', 128, 'ABCDEFG'],
            'vacio' => ['', 128, ''],
            'null' => [null, 128, ''],
            'trunca al maxLength' => ['ABCDEFGHIJ', 5, 'ABCDE'],
            'con espacios alrededor' => ['  Madrid  ', 128, 'Madrid'],
            'tildes se mantienen' => ['LOGROÑO', 128, 'LOGROÑO'],
            'caracteres utf8 validos' => ['São Paulo', 128, 'São Paulo'],
            'guiones y puntos se mantienen' => ['Saint-Denis 1.2', 128, 'Saint-Denis 1.2'],
        ];
    }

    /**
     * @dataProvider sanitizeProvider
     */
    public function testSanitizeAddressField($value, int $maxLength, string $expected): void
    {
        $this->assertSame($expected, nacexDAO::sanitizeAddressField($value, $maxLength));
    }

    public function testSanitizeAddressFieldDefaultMaxLength(): void
    {
        $longString = str_repeat('A', 200);
        $result = nacexDAO::sanitizeAddressField($longString);
        $this->assertSame(128, mb_strlen($result));
    }
}
