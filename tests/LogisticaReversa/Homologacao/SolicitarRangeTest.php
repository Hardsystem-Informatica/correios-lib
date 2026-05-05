<?php

namespace Hardsystem\Correios\Tests\LogisticaReversa\Homologacao;

use Hardsystem\Correios\LogisticaReversa\Enum\TipoRange;
use Hardsystem\Correios\LogisticaReversa\Exception\LogisticaReversaException;

final class SolicitarRangeTest extends BaseHomologacaoTestCase
{
    public function testReservaFaixaDeDezEticketsParaAutorizacaoPostagem(): void
    {
        try {
            $faixa = $this->logisticaReversa->solicitarRange(TipoRange::AutorizacaoPostagem, 10);
        } catch (LogisticaReversaException $excecao) {
            if ($excecao->codigoCorreios === 247) {
                self::markTestSkipped('Range anterior em HML compartilhado ainda não atingiu 80% de uso (cod_erro 247).');
            }
            throw $excecao;
        }

        self::assertNotEmpty($faixa->faixaInicial);
        self::assertNotEmpty($faixa->faixaFinal);
        self::assertNotNull($faixa->emitidoEm);
    }

    public function testRecusaQuantidadeAcimaDoLimiteDeCinquentaMil(): void
    {
        try {
            $this->logisticaReversa->solicitarRange(TipoRange::AutorizacaoPostagem, 50001);
            self::fail('esperava exceção para quantidade acima do limite de 50.000');
        } catch (LogisticaReversaException $excecao) {
            self::assertNotNull($excecao->codigoCorreios, 'esperava cod_erro preenchido');
        }
    }

    public function testRecusaQuantidadeIgualAZero(): void
    {
        try {
            $this->logisticaReversa->solicitarRange(TipoRange::AutorizacaoPostagem, 0);
            self::fail('esperava exceção para quantidade zero');
        } catch (LogisticaReversaException $excecao) {
            self::assertSame(226, $excecao->codigoCorreios, "esperava cod_erro 226, recebeu {$excecao->codigoCorreios}");
        }
    }

    public function testRecusaNovoRangeQuandoAnteriorNaoFoiConsumido(): void
    {
        if (getenv('LR_RUN_RANGE_NAO_CONSUMIDO') !== '1') {
            $this->markTestSkipped('Pré-requisito: range anterior reservado e não consumido. Defina LR_RUN_RANGE_NAO_CONSUMIDO=1.');
        }

        try {
            $this->logisticaReversa->solicitarRange(TipoRange::AutorizacaoPostagem, 100);
            self::fail('esperava exceção 247 (consumir 80% antes de novo range)');
        } catch (LogisticaReversaException $excecao) {
            self::assertSame(247, $excecao->codigoCorreios);
        }
    }
}
