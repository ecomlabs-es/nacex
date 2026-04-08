<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class HashTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        Tools::reset();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        Tools::reset();
    }

    public function testHashFormDevuelveUnEntero(): void
    {
        $result = hash::hash_form('100');
        $this->assertIsInt($result);
    }

    public function testHashFormAlmacenaEnSesion(): void
    {
        $rand = hash::hash_form('100');

        $this->assertArrayHasKey('rand', $_SESSION);
        $this->assertCount(1, $_SESSION['rand']);
        $this->assertSame($rand, $_SESSION['rand'][0]['HASH']);
        $this->assertSame('100', $_SESSION['rand'][0]['ORDER_ID']);
    }

    public function testHashFormAgregaNuevoOrderId(): void
    {
        hash::hash_form('100');
        hash::hash_form('200');

        $this->assertCount(2, $_SESSION['rand']);
    }

    public function testHashFormActualizaHashParaMismoOrderId(): void
    {
        hash::hash_form('100');
        $rand2 = hash::hash_form('100');

        $this->assertCount(1, $_SESSION['rand']);
        $this->assertSame($rand2, $_SESSION['rand'][0]['HASH']);
    }

    public function testValidateHashDevuelveTrueConDatosCorrectos(): void
    {
        $rand = hash::hash_form('100');
        Tools::set('order_id', '100');
        Tools::set('hash', (string)$rand);

        $hashObj = new hash();
        $this->assertTrue($hashObj->validate_hash());
    }

    public function testValidateHashConsumeTokenTrasPrimerUso(): void
    {
        $rand = hash::hash_form('100');
        Tools::set('order_id', '100');
        Tools::set('hash', (string)$rand);

        $hashObj = new hash();
        $this->assertTrue($hashObj->validate_hash());
        // Segundo intento con el mismo hash debe fallar (protección anti-F5)
        $this->assertFalse($hashObj->validate_hash());
    }

    public function testValidateHashDevuelveFalseConHashIncorrecto(): void
    {
        hash::hash_form('100');
        Tools::set('order_id', '100');
        Tools::set('hash', 'hash_incorrecto');

        $hashObj = new hash();
        $this->assertFalse($hashObj->validate_hash());
    }

    public function testValidateHashDevuelveFalseSinSesion(): void
    {
        Tools::set('order_id', '100');
        Tools::set('hash', '12345');

        $hashObj = new hash();
        $this->assertFalse($hashObj->validate_hash());
    }

    public function testValidateHashDevuelveFalseSinPost(): void
    {
        hash::hash_form('100');

        $hashObj = new hash();
        $this->assertFalse($hashObj->validate_hash());
    }

    public function testValidateHashNoCruzaParesDeDistintosOrders(): void
    {
        $rand1 = hash::hash_form('100');
        $rand2 = hash::hash_form('200');

        // Intentar usar hash del order 100 con order_id 200
        Tools::set('order_id', '200');
        Tools::set('hash', (string)$rand1);

        $hashObj = new hash();
        $this->assertFalse($hashObj->validate_hash());
    }
}
