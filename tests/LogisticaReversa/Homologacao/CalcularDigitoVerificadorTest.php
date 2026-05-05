<?php

namespace Hardsystem\Correios\Tests\LogisticaReversa\Homologacao;

use Hardsystem\Correios\LogisticaReversa\Exception\LogisticaReversaException;
use Hardsystem\Correios\LogisticaReversa\LogisticaReversa;

final class CalcularDigitoVerificadorTest extends BaseHomologacaoTestCase
{
    public function testCalculaDigitoParaExemploDoAnexo03(): void
    {
        // Anexo 03: e-ticket 15653829 → DV 7 → 156538297
        $comDigito = $this->logisticaReversa->calcularDigitoVerificador('15653829');

        self::assertSame('156538297', $comDigito);
    }

    public function testDigitoCalculadoRemotamenteBateComCalculoLocal(): void
    {
        $remoto = $this->logisticaReversa->calcularDigitoVerificador('15653829');
        $local = LogisticaReversa::dvLocal('15653829');

        self::assertSame($local, $remoto);
    }

    public function testRecusaNumeroComMenosDeOitoDigitos(): void
    {
        try {
            $this->logisticaReversa->calcularDigitoVerificador('1234567');
            self::fail('esperava exceção para número com 7 dígitos');
        } catch (LogisticaReversaException $excecao) {
            self::assertNotNull($excecao->codigoCorreios, 'esperava cod_erro preenchido');
        }
    }
}
