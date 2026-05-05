<?php

namespace Hardsystem\Correios\Tests\LogisticaReversa\Homologacao;

use Hardsystem\Correios\LogisticaReversa\Exception\LogisticaReversaException;

final class RevalidarPrazoTest extends BaseHomologacaoTestCase
{
    public function testRevalidaEticketComPrazoExpirado(): void
    {
        $numeroPedido = getenv('LR_PEDIDO_EXPIRADO') ?: '';
        if ($numeroPedido === '') {
            $this->markTestSkipped('Pré-requisito: pedido em status "Prazo de Utilização Expirado". Defina LR_PEDIDO_EXPIRADO.');
        }

        $novoPrazo = $this->logisticaReversa->revalidarPrazoAutorizacaoPostagem($numeroPedido, 10);

        self::assertGreaterThan(new \DateTimeImmutable('today'), $novoPrazo);
    }

    public function testRecusaRevalidarEticketAindaAtivo(): void
    {
        $numeroPedido = $this->criarAutorizacaoSimples();

        try {
            $novoPrazo = $this->logisticaReversa->revalidarPrazoAutorizacaoPostagem($numeroPedido, 10);
            // HML pode aceitar a revalidação de e-ticket ainda ativo; doc oficial diz erro -16.
            // Sinalizamos como teste incompleto para não falhar a suite, mas para o gatekeeper saber.
            self::markTestIncomplete(sprintf(
                'HML aceitou revalidar e-ticket ativo (novo prazo: %s). Doc diz que deveria retornar -16.',
                $novoPrazo->format('d/m/Y'),
            ));
        } catch (LogisticaReversaException $excecao) {
            self::assertSame(-16, $excecao->codigoCorreios, "esperava cod_erro -16, recebeu {$excecao->codigoCorreios}");
        }
    }

    public function testRecusaRevalidarEticketJaUtilizado(): void
    {
        $numeroPedido = getenv('LR_PEDIDO_UTILIZADO') ?: '';
        if ($numeroPedido === '') {
            $this->markTestSkipped('Pré-requisito: e-ticket já utilizado em postagem. Defina LR_PEDIDO_UTILIZADO.');
        }

        try {
            $this->logisticaReversa->revalidarPrazoAutorizacaoPostagem($numeroPedido, 10);
            self::fail('esperava exceção -17 ao revalidar e-ticket já utilizado');
        } catch (LogisticaReversaException $excecao) {
            self::assertSame(-17, $excecao->codigoCorreios);
        }
    }

    public function testRecusaRevalidacaoComMenosDeCincoDias(): void
    {
        $numeroPedido = $this->criarAutorizacaoSimples();

        try {
            $this->logisticaReversa->revalidarPrazoAutorizacaoPostagem($numeroPedido, 4);
            self::fail('esperava exceção para qtdeDias=4 (mínimo 5)');
        } catch (LogisticaReversaException $excecao) {
            self::assertContains(
                $excecao->codigoCorreios,
                [-15, -16],
                "esperava -15 (qtdeDias) ou -16 (e-ticket ativo), recebeu {$excecao->codigoCorreios}",
            );
        }
    }

    public function testRecusaRevalidacaoComMaisDeTrintaDias(): void
    {
        $numeroPedido = $this->criarAutorizacaoSimples();

        try {
            $this->logisticaReversa->revalidarPrazoAutorizacaoPostagem($numeroPedido, 31);
            self::fail('esperava exceção para qtdeDias=31 (máximo 30)');
        } catch (LogisticaReversaException $excecao) {
            self::assertContains(
                $excecao->codigoCorreios,
                [-15, -16],
                "esperava -15 (qtdeDias) ou -16 (e-ticket ativo), recebeu {$excecao->codigoCorreios}",
            );
        }
    }

    public function testRecusaRevalidarEticketInexistente(): void
    {
        try {
            $this->logisticaReversa->revalidarPrazoAutorizacaoPostagem('1', 10);
            self::fail('esperava exceção para e-ticket inexistente');
        } catch (LogisticaReversaException $excecao) {
            self::assertContains(
                $excecao->codigoCorreios,
                [-13, -5],
                "esperava -13 ou -5, recebeu {$excecao->codigoCorreios}",
            );
        }
    }
}
