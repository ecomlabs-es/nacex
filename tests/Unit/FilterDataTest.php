<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class FilterDataTest extends TestCase
{
    public function testStringEliminaCaracteresEspeciales(): void
    {
        $filter = new filterdata();
        $input = 'Hola & "mundo" <script>';
        $filter->string($input);

        $this->assertStringNotContainsString('&', $input);
        $this->assertStringNotContainsString('"', $input);
        $this->assertStringNotContainsString('<', $input);
        $this->assertStringNotContainsString('>', $input);
    }

    public function testStringConTextoLimpio(): void
    {
        $filter = new filterdata();
        $input = 'Texto limpio sin caracteres especiales';
        $original = $input;
        $filter->string($input);

        $this->assertSame($original, $input);
    }

    public function testStringEliminaPipes(): void
    {
        $filter = new filterdata();
        $input = 'dato1|dato2';
        $filter->string($input);

        $this->assertStringNotContainsString('|', $input);
    }
}
