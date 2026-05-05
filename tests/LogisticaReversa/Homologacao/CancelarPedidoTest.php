<?php

namespace Hardsystem\Correios\Tests\LogisticaReversa\Homologacao;

use Hardsystem\Correios\LogisticaReversa\Enum\TipoPedido;
use Hardsystem\Correios\LogisticaReversa\Exception\LogisticaReversaException;
use Hardsystem\Correios\LogisticaReversa\Exception\PedidoNaoCancelavelException;

final class CancelarPedidoTest extends BaseHomologacaoTestCase
{
    public function testCancelaAutorizacaoNoStatusAguardandoObjetoNaAgencia(): void
    {
        $numeroPedido = $this->criarAutorizacaoSimples();

        try {
            $cancelamento = $this->logisticaReversa->cancelarPedido(
                $numeroPedido,
                TipoPedido::AutorizacaoPostagem,
            );
        } catch (PedidoNaoCancelavelException $excecao) {
            self::markTestIncomplete(
                "HML retornou pedido recém-criado já em status não-cancelável: {$excecao->getMessage()}",
            );
        }

        self::assertSame($numeroPedido, $cancelamento->numeroPedido);
        self::assertNotEmpty($cancelamento->statusPedido);
    }

    public function testRecusaCancelarAutorizacaoQueJaFoiCancelada(): void
    {
        $numeroPedido = $this->criarAutorizacaoSimples();

        try {
            $this->logisticaReversa->cancelarPedido($numeroPedido, TipoPedido::AutorizacaoPostagem);
        } catch (PedidoNaoCancelavelException) {
            // HML pode entregar pedido recém-criado já em status não-cancelável; seguimos para a 2a tentativa.
        }

        try {
            $this->logisticaReversa->cancelarPedido($numeroPedido, TipoPedido::AutorizacaoPostagem);
            self::fail('esperava PedidoNaoCancelavelException ao tentar cancelar de novo');
        } catch (PedidoNaoCancelavelException $excecao) {
            self::assertSame(-9, $excecao->codigoCorreios);
        }
    }

    public function testRecusaCancelarAutorizacaoComTipoDeColetaIncorreto(): void
    {
        $numeroPedido = $this->criarAutorizacaoSimples();

        try {
            $this->logisticaReversa->cancelarPedido($numeroPedido, TipoPedido::Coleta);
            self::fail('esperava exceção ao usar tipo C em pedido criado como A');
        } catch (LogisticaReversaException $excecao) {
            self::assertSame(-5, $excecao->codigoCorreios, "esperava cod_erro -5, recebeu {$excecao->codigoCorreios}");
        }
    }

    public function testRecusaCancelarNumeroInexistente(): void
    {
        try {
            $this->logisticaReversa->cancelarPedido('1', TipoPedido::AutorizacaoPostagem);
            self::fail('esperava exceção para número inexistente');
        } catch (LogisticaReversaException $excecao) {
            self::assertSame(-5, $excecao->codigoCorreios, "esperava cod_erro -5, recebeu {$excecao->codigoCorreios}");
        }
    }
}
