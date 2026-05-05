<?php

namespace Hardsystem\Correios\Tests\LogisticaReversa\Homologacao;

use Hardsystem\Correios\LogisticaReversa\Enum\TipoBusca;
use Hardsystem\Correios\LogisticaReversa\Enum\TipoPedido;
use Hardsystem\Correios\LogisticaReversa\Exception\LogisticaReversaException;

final class AcompanharPedidoTest extends BaseHomologacaoTestCase
{
    public function testRetornaApenasUltimoEventoQuandoTipoBuscaEUltimo(): void
    {
        $numeroPedido = $this->criarAutorizacaoSimples();

        $pedido = $this->logisticaReversa->acompanharPedido(
            $numeroPedido,
            TipoPedido::AutorizacaoPostagem,
            TipoBusca::UltimoEvento,
        );

        self::assertSame($numeroPedido, $pedido->numeroPedido);
        self::assertNotNull($pedido->ultimoStatus, 'esperava ultimoStatus preenchido');
    }

    public function testRetornaHistoricoCompletoQuandoTipoBuscaEHistorico(): void
    {
        $numeroPedido = $this->criarAutorizacaoSimples();

        $pedido = $this->logisticaReversa->acompanharPedido(
            $numeroPedido,
            TipoPedido::AutorizacaoPostagem,
            TipoBusca::Historico,
        );

        self::assertSame($numeroPedido, $pedido->numeroPedido);
        self::assertNotEmpty($pedido->historico, 'esperava ao menos 1 evento no histórico');
    }

    public function testLancaExcecaoParaNumeroDePedidoInexistente(): void
    {
        try {
            $this->logisticaReversa->acompanharPedido(
                '1',
                TipoPedido::AutorizacaoPostagem,
                TipoBusca::Historico,
            );
            self::fail('esperava exceção para pedido inexistente');
        } catch (LogisticaReversaException $excecao) {
            self::assertSame(-5, $excecao->codigoCorreios, "esperava cod_erro -5, recebeu {$excecao->codigoCorreios}");
        }
    }

    public function testListaPedidosComMovimentoNoDiaAtual(): void
    {
        $this->criarAutorizacaoSimples();

        $pedidos = $this->logisticaReversa->acompanharPedidoPorData(
            new \DateTimeImmutable('today'),
            TipoPedido::AutorizacaoPostagem,
        );

        self::assertNotEmpty($pedidos, 'esperava ao menos 1 pedido na data atual após criar uma autorização');
    }

    public function testRetornaListaVaziaOuExcecaoParaDataAntigaSemMovimento(): void
    {
        try {
            $pedidos = $this->logisticaReversa->acompanharPedidoPorData(
                new \DateTimeImmutable('2010-01-01'),
                TipoPedido::AutorizacaoPostagem,
            );
            self::assertEmpty($pedidos, 'esperava lista vazia para data sem movimento');
        } catch (LogisticaReversaException $excecao) {
            self::assertSame(-13, $excecao->codigoCorreios, "esperava cod_erro -13 (sem informação), recebeu {$excecao->codigoCorreios}");
        }
    }
}
