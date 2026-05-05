<?php

namespace Hardsystem\Correios\Tests\LogisticaReversa;

use Hardsystem\Correios\LogisticaReversa\LogisticaReversa;
use PHPUnit\Framework\TestCase;

final class DvLocalTest extends TestCase
{
    public function testExemploDoAnexo03(): void
    {
        // Anexo 03: e-ticket 15653829 → DV = 7 → 156538297
        self::assertSame('156538297', LogisticaReversa::dvLocal('15653829'));
    }

    public function testRestoZeroProduzDigitoCinco(): void
    {
        // 11111111: 1*8+1*6+1*4+1*2+1*3+1*5+1*9+1*7 = 44; 44 % 11 = 0 → DV = 5
        self::assertSame('111111115', LogisticaReversa::dvLocal('11111111'));
    }

    public function testRestoUmProduzDigitoZero(): void
    {
        // 10000010: 1*8+0+0+0+0+0+1*9+0 = 17; 17 % 11 = 6 → DV = 11-6 = 5  (não cobre)
        // Procurar caso com resto 1: 1*8 = 8, 8 % 11 = 8. Tente 10000000: 1*8 = 8 → resto 8.
        // 10000111: 8+0+0+0+0+5+9+7 = 29; 29 % 11 = 7.
        // 10000211: 8+0+0+0+0+10+9+7 = 34; 34 % 11 = 1 → DV = 0 ✓
        self::assertSame('100002110', LogisticaReversa::dvLocal('10000211'));
    }

    public function testNoveDigitosUsaTodosOsMultiplicadores(): void
    {
        // 9 dígitos válidos: 156538297 (com DV já anexado)
        // 1*8+5*6+6*4+5*2+3*3+8*5+2*9+9*7+7*3 = 8+30+24+10+9+40+18+63+21 = 223
        // 223 % 11 = 3 → DV = 8 → 1565382978
        self::assertSame('1565382978', LogisticaReversa::dvLocal('156538297'));
    }

    public function testTamanhoInvalidoLancaExcecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LogisticaReversa::dvLocal('123');
    }

    public function testCaracteresNaoNumericosLancamExcecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LogisticaReversa::dvLocal('1234567A');
    }
}
