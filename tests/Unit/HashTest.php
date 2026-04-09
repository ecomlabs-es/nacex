<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class HashTest extends TestCase
{
    protected function setUp(): void
    {
        Context::reset();
        Tools::reset();
    }

    protected function tearDown(): void
    {
        Context::reset();
        Tools::reset();
    }

    public function testHashFormDevuelveUnEntero(): void
    {
        $result = hash::hash_form('100');
        $this->assertIsInt($result);
    }

    public function testHashFormAlmacenaEnCookie(): void
    {
        $rand = hash::hash_form('100');

        $cookie = Context::getContext()->cookie;
        $data = json_decode($cookie->__get('nacex_hashes'), true);
        $this->assertCount(1, $data);
        $this->assertSame($rand, $data[0]['HASH']);
        $this->assertSame('100', $data[0]['ORDER_ID']);
    }

    public function testHashFormAgregaNuevoOrderId(): void
    {
        hash::hash_form('100');
        hash::hash_form('200');

        $data = json_decode(Context::getContext()->cookie->__get('nacex_hashes'), true);
        $this->assertCount(2, $data);
    }

    public function testHashFormActualizaHashParaMismoOrderId(): void
    {
        hash::hash_form('100');
        $rand2 = hash::hash_form('100');

        $data = json_decode(Context::getContext()->cookie->__get('nacex_hashes'), true);
        $this->assertCount(1, $data);
        $this->assertSame($rand2, $data[0]['HASH']);
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

    public function testValidateHashDevuelveFalseSinDatos(): void
    {
        hash::hash_form('100');

        $hashObj = new hash();
        $this->assertFalse($hashObj->validate_hash());
    }

    public function testValidateHashNoCruzaParesDeDistintosOrders(): void
    {
        $rand1 = hash::hash_form('100');
        hash::hash_form('200');

        Tools::set('order_id', '200');
        Tools::set('hash', (string)$rand1);

        $hashObj = new hash();
        $this->assertFalse($hashObj->validate_hash());
    }
}
