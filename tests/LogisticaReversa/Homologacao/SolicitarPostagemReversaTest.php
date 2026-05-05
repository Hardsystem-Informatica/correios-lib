<?php

namespace Hardsystem\Correios\Tests\LogisticaReversa\Homologacao;

use Hardsystem\Correios\LogisticaReversa\Dto\Coleta;
use Hardsystem\Correios\LogisticaReversa\Dto\EnderecoRemetente;
use Hardsystem\Correios\LogisticaReversa\Dto\ObjetoColeta;
use Hardsystem\Correios\LogisticaReversa\Dto\SolicitacaoReversa;
use Hardsystem\Correios\LogisticaReversa\Enum\TipoColeta;

final class SolicitarPostagemReversaTest extends BaseHomologacaoTestCase
{
    public function testGeraEticketParaUmObjetoSemServicosAdicionais(): void
    {
        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            new SolicitacaoReversa(
                codigoServico: $this->codigoServico,
                destinatario: $this->destinatarioPadrao(),
                coletas: [
                    new Coleta(
                        tipo: TipoColeta::AutorizacaoPostagem,
                        remetente: $this->remetentePadrao(),
                        objetos: [new ObjetoColeta(item: 1)],
                        idCliente: 'TEST-' . uniqid(),
                        diasValidade: 10,
                    ),
                ],
            ),
        );

        self::assertCount(1, $resultados);
        $resultado = $resultados[0];
        self::assertFalse(
            $resultado->temErro(),
            "esperava sem erro mas veio [{$resultado->codigoErro}] {$resultado->descricaoErro}",
        );
        self::assertNotEmpty($resultado->numeroColeta);
        self::assertSame(TipoColeta::AutorizacaoPostagem, $resultado->tipo);
    }

    public function testGeraEticketParaCincoObjetos(): void
    {
        $objetos = [];
        for ($item = 1; $item <= 5; $item++) {
            $objetos[] = new ObjetoColeta(item: $item, descricao: "Produto {$item}");
        }

        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            $this->solicitacaoComObjetos($objetos),
        );

        self::assertFalse($resultados[0]->temErro(), "[{$resultados[0]->codigoErro}] {$resultados[0]->descricaoErro}");
        self::assertNotEmpty($resultados[0]->numeroColeta);
    }

    public function testGeraEticketParaDezObjetosNoLimiteMaximo(): void
    {
        $objetos = [];
        for ($item = 1; $item <= 10; $item++) {
            $objetos[] = new ObjetoColeta(item: $item);
        }

        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            $this->solicitacaoComObjetos($objetos),
        );

        self::assertFalse($resultados[0]->temErro(), "[{$resultados[0]->codigoErro}] {$resultados[0]->descricaoErro}");
    }

    public function testRecusaSolicitacaoComOnzeObjetosAcimaDoLimite(): void
    {
        $objetos = [];
        for ($item = 1; $item <= 11; $item++) {
            $objetos[] = new ObjetoColeta(item: $item);
        }

        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            $this->solicitacaoComObjetos($objetos),
        );

        self::assertTrue(
            $resultados[0]->temErro(),
            'esperava erro 228 (quantidade superior ao permitido) ou similar',
        );
        self::assertSame(228, $resultados[0]->codigoErro, "código de erro inesperado: {$resultados[0]->codigoErro}");
    }

    public function testGeraEticketSolicitandoAvisoDeRecebimento(): void
    {
        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            new SolicitacaoReversa(
                codigoServico: $this->codigoServico,
                destinatario: $this->destinatarioPadrao(),
                coletas: [
                    new Coleta(
                        tipo: TipoColeta::AutorizacaoPostagem,
                        remetente: $this->remetentePadrao(),
                        objetos: [new ObjetoColeta(item: 1)],
                        idCliente: 'TEST-AR-' . uniqid(),
                        diasValidade: 10,
                        solicitarAvisoRecebimento: true,
                    ),
                ],
            ),
        );

        self::assertFalse($resultados[0]->temErro(), "[{$resultados[0]->codigoErro}] {$resultados[0]->descricaoErro}");
    }

    public function testRecusaAvisoDeRecebimentoEmColetaDomiciliar(): void
    {
        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            new SolicitacaoReversa(
                codigoServico: $this->codigoServico,
                destinatario: $this->destinatarioPadrao(),
                coletas: [
                    new Coleta(
                        tipo: TipoColeta::ColetaDomiciliar,
                        remetente: $this->remetentePadrao(),
                        objetos: [new ObjetoColeta(item: 1)],
                        idCliente: 'TEST-AR-C-' . uniqid(),
                        solicitarAvisoRecebimento: true,
                    ),
                ],
            ),
        );

        self::assertTrue($resultados[0]->temErro(), 'esperava o servidor recusar a solicitação');
        if ($resultados[0]->codigoErro === 111) {
            self::markTestIncomplete(
                'CEP do remetente não tem coleta domiciliar disponível em HML — '
                . 'servidor retorna 111 antes de validar AR. Use um CEP com cobertura para validar erro 199.',
            );
        }
        self::assertSame(199, $resultados[0]->codigoErro, "esperava cod_erro 199, recebeu {$resultados[0]->codigoErro}");
    }

    public function testGeraEticketComValorDeclaradoValido(): void
    {
        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            $this->solicitacaoComValorDeclarado(100.00),
        );

        self::assertFalse($resultados[0]->temErro(), "[{$resultados[0]->codigoErro}] {$resultados[0]->descricaoErro}");
    }

    public function testRecusaValorDeclaradoAbaixoDoMinimo(): void
    {
        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            $this->solicitacaoComValorDeclarado(24.49),
        );

        self::assertTrue($resultados[0]->temErro());
        self::assertSame(211, $resultados[0]->codigoErro, 'esperava erro 211 (mínimo R$ 24,50)');
    }

    public function testRecusaValorDeclaradoAcimaDoMaximo(): void
    {
        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            $this->solicitacaoComValorDeclarado(10000.01),
        );

        self::assertTrue($resultados[0]->temErro());
        self::assertSame(108, $resultados[0]->codigoErro, 'esperava erro 108 (máximo R$ 10.000,00)');
    }

    public function testGeraEticketComValidadePadraoQuandoOmitida(): void
    {
        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            new SolicitacaoReversa(
                codigoServico: $this->codigoServico,
                destinatario: $this->destinatarioPadrao(),
                coletas: [
                    new Coleta(
                        tipo: TipoColeta::AutorizacaoPostagem,
                        remetente: $this->remetentePadrao(),
                        objetos: [new ObjetoColeta(item: 1)],
                        idCliente: 'TEST-AG-PADRAO-' . uniqid(),
                    ),
                ],
            ),
        );

        self::assertFalse($resultados[0]->temErro(), "[{$resultados[0]->codigoErro}] {$resultados[0]->descricaoErro}");
        self::assertNotNull($resultados[0]->prazo, 'esperava prazo preenchido (default 10 dias corridos)');
    }

    public function testGeraEticketComValidadeDeUmDia(): void
    {
        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            $this->solicitacaoComValidade(1),
        );

        self::assertFalse($resultados[0]->temErro(), "[{$resultados[0]->codigoErro}] {$resultados[0]->descricaoErro}");
    }

    public function testGeraEticketComValidadeMaximaDeNoventaDias(): void
    {
        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            $this->solicitacaoComValidade(90),
        );

        self::assertFalse($resultados[0]->temErro(), "[{$resultados[0]->codigoErro}] {$resultados[0]->descricaoErro}");
    }

    public function testRecusaValidadeAcimaDeNoventaDias(): void
    {
        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            $this->solicitacaoComValidade(91),
        );

        self::assertTrue($resultados[0]->temErro());
        self::assertSame(142, $resultados[0]->codigoErro, 'esperava erro 142 (valor inválido para ag)');
    }

    public function testRecusaValidadeIgualAZero(): void
    {
        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            $this->solicitacaoComValidade(0),
        );

        self::assertTrue($resultados[0]->temErro());
        self::assertSame(142, $resultados[0]->codigoErro, 'esperava erro 142 (valor inválido para ag)');
    }

    public function testProcessaLoteParcialQuandoAlgumItemTemRemetenteInvalido(): void
    {
        $remetenteInvalido = new EnderecoRemetente(
            nome: 'Ciclano CEP Errado',
            logradouro: 'Rua X',
            numero: '10',
            bairro: 'Centro',
            cidade: 'Brasília',
            uf: 'DF',
            cep: '00000000',
            ddd: '61',
            telefone: '34262222',
            email: 'ciclano@mail.com',
            restricaoAnac: true,
        );

        $solicitacao = new SolicitacaoReversa(
            codigoServico: $this->codigoServico,
            destinatario: $this->destinatarioPadrao(),
            coletas: [
                $this->coletaSimples('TEST-OK1-' . uniqid(), $this->remetentePadrao()),
                $this->coletaSimples('TEST-INV-' . uniqid(), $remetenteInvalido),
                $this->coletaSimples('TEST-OK2-' . uniqid(), $this->remetentePadrao()),
            ],
        );

        $resultados = $this->logisticaReversa->solicitarPostagemReversa($solicitacao);

        self::assertCount(3, $resultados);
        $comErro = array_filter($resultados, fn ($r) => $r->temErro());
        $semErro = array_filter($resultados, fn ($r) => !$r->temErro());
        self::assertCount(1, $comErro, 'esperava 1 item com erro');
        self::assertCount(2, $semErro, 'esperava 2 itens sem erro');
    }

    public function testProcessaLoteDeCinquentaSolicitacoesValidas(): void
    {
        if (getenv('LR_RUN_VOLUME') !== '1') {
            $this->markTestSkipped('Teste de volume — defina LR_RUN_VOLUME=1 para rodar.');
        }

        $coletas = [];
        for ($i = 1; $i <= 50; $i++) {
            $coletas[] = $this->coletaSimples("TEST-50-{$i}-" . uniqid(), $this->remetentePadrao());
        }

        $resultados = $this->logisticaReversa->solicitarPostagemReversa(
            new SolicitacaoReversa(
                codigoServico: $this->codigoServico,
                destinatario: $this->destinatarioPadrao(),
                coletas: $coletas,
            ),
        );

        self::assertCount(50, $resultados);
        $comErro = array_filter($resultados, fn ($r) => $r->temErro());
        self::assertCount(0, $comErro, 'esperava todas as 50 sem erro');
    }

    public function testRecusaLoteAcimaDoLimiteDeCinquentaSolicitacoes(): void
    {
        if (getenv('LR_RUN_VOLUME') !== '1') {
            $this->markTestSkipped('Teste de volume — defina LR_RUN_VOLUME=1 para rodar.');
        }

        $coletas = [];
        for ($i = 1; $i <= 51; $i++) {
            $coletas[] = $this->coletaSimples("TEST-51-{$i}-" . uniqid(), $this->remetentePadrao());
        }

        $solicitacao = new SolicitacaoReversa(
            codigoServico: $this->codigoServico,
            destinatario: $this->destinatarioPadrao(),
            coletas: $coletas,
        );

        // Registra qualquer comportamento — a doc não diz se vira erro fatal ou se trunca.
        try {
            $resultados = $this->logisticaReversa->solicitarPostagemReversa($solicitacao);
            self::assertLessThanOrEqual(50, count($resultados), 'esperava no máximo 50 resultados');
        } catch (\Hardsystem\Correios\LogisticaReversa\Exception\LogisticaReversaException $excecao) {
            self::assertNotNull($excecao->codigoCorreios, 'esperava codigoCorreios preenchido');
        }
    }

    /** @param ObjetoColeta[] $objetos */
    private function solicitacaoComObjetos(array $objetos): SolicitacaoReversa
    {
        return new SolicitacaoReversa(
            codigoServico: $this->codigoServico,
            destinatario: $this->destinatarioPadrao(),
            coletas: [
                new Coleta(
                    tipo: TipoColeta::AutorizacaoPostagem,
                    remetente: $this->remetentePadrao(),
                    objetos: $objetos,
                    idCliente: 'TEST-OBJ-' . uniqid(),
                    diasValidade: 10,
                ),
            ],
        );
    }

    private function solicitacaoComValorDeclarado(float $valor): SolicitacaoReversa
    {
        return new SolicitacaoReversa(
            codigoServico: $this->codigoServico,
            destinatario: $this->destinatarioPadrao(),
            coletas: [
                new Coleta(
                    tipo: TipoColeta::AutorizacaoPostagem,
                    remetente: $this->remetentePadrao(),
                    objetos: [new ObjetoColeta(item: 1)],
                    idCliente: 'TEST-VD-' . uniqid(),
                    diasValidade: 10,
                    valorDeclarado: $valor,
                ),
            ],
        );
    }

    private function solicitacaoComValidade(int $dias): SolicitacaoReversa
    {
        return new SolicitacaoReversa(
            codigoServico: $this->codigoServico,
            destinatario: $this->destinatarioPadrao(),
            coletas: [
                new Coleta(
                    tipo: TipoColeta::AutorizacaoPostagem,
                    remetente: $this->remetentePadrao(),
                    objetos: [new ObjetoColeta(item: 1)],
                    idCliente: 'TEST-AG-' . uniqid(),
                    diasValidade: $dias,
                ),
            ],
        );
    }

    private function coletaSimples(string $idCliente, EnderecoRemetente $remetente): Coleta
    {
        return new Coleta(
            tipo: TipoColeta::AutorizacaoPostagem,
            remetente: $remetente,
            objetos: [new ObjetoColeta(item: 1)],
            idCliente: $idCliente,
            diasValidade: 10,
        );
    }
}
