<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class HashTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
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
        $rand1 = hash::hash_form('100');
        $_POST['order_id'] = '100';
        $rand2 = hash::hash_form('100');

        $this->assertCount(1, $_SESSION['rand']);
        $this->assertSame($rand2, $_SESSION['rand'][0]['HASH']);
    }

    public function testValidateHashDevuelveTrueConDatosCorrectos(): void
    {
        $rand = hash::hash_form('100');
        $_POST['order_id'] = '100';
        $_POST['hash'] = $rand;

        $hashObj = new hash();
        $this->assertTrue($hashObj->validate_hash());
    }

    public function testValidateHashDevuelveFalseConHashIncorrecto(): void
    {
        hash::hash_form('100');
        $_POST['order_id'] = '100';
        $_POST['hash'] = 'hash_incorrecto';

        $hashObj = new hash();
        $this->assertFalse($hashObj->validate_hash());
    }

    public function testValidateHashDevuelveFalseSinSesion(): void
    {
        $_POST['order_id'] = '100';
        $_POST['hash'] = '12345';

        $hashObj = new hash();
        // Tras el fix, ya no lanza error con sesion vacia
        $this->assertFalse($hashObj->validate_hash());
    }

    public function testValidateHashDevuelveFalseSinPost(): void
    {
        hash::hash_form('100');

        $hashObj = new hash();
        $this->assertFalse($hashObj->validate_hash());
    }
}
