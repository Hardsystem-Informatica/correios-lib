<?php

namespace Hardsystem\Correios\Tests\LogisticaReversa\Homologacao;

use Hardsystem\Correios\LogisticaReversa\Ambiente;
use Hardsystem\Correios\LogisticaReversa\Credenciais;
use Hardsystem\Correios\LogisticaReversa\Dto\Coleta;
use Hardsystem\Correios\LogisticaReversa\Dto\EnderecoDestinatario;
use Hardsystem\Correios\LogisticaReversa\Dto\EnderecoRemetente;
use Hardsystem\Correios\LogisticaReversa\Dto\ObjetoColeta;
use Hardsystem\Correios\LogisticaReversa\Dto\SolicitacaoReversa;
use Hardsystem\Correios\LogisticaReversa\Enum\TipoColeta;
use Hardsystem\Correios\LogisticaReversa\LogisticaReversa;
use PHPUnit\Framework\TestCase;

abstract class BaseHomologacaoTestCase extends TestCase
{
    protected LogisticaReversa $logisticaReversa;
    protected string $codigoServico;

    protected function setUp(): void
    {
        if (getenv('LR_RUN_HOMOLOGACAO') !== '1') {
            $this->markTestSkipped('Defina LR_RUN_HOMOLOGACAO=1 para executar contra o WS de homologação.');
        }
        if (!extension_loaded('soap')) {
            $this->markTestSkipped('ext-soap não está disponível.');
        }

        $this->logisticaReversa = new LogisticaReversa(
            Ambiente::Homologacao,
            new Credenciais(
                usuario: getenv('LR_USUARIO') ?: 'empresacws',
                senha: getenv('LR_SENHA') ?: '123456',
                codigoAdministrativo: getenv('LR_COD_ADMINISTRATIVO') ?: '17000190',
                contrato: getenv('LR_CONTRATO') ?: '9992157880',
                cartaoPostagem: getenv('LR_CARTAO') ?: '0011111111',
            ),
        );
        $this->codigoServico = getenv('LR_CODIGO_SERVICO') ?: '04677';
    }

    protected function destinatarioPadrao(): EnderecoDestinatario
    {
        return new EnderecoDestinatario(
            nome: 'Fulano',
            logradouro: 'SBN',
            numero: '10',
            bairro: 'Plano Piloto',
            cidade: 'Brasília',
            uf: 'DF',
            cep: '70002900',
            cienciaConteudoProibido: true,
            complemento: 'Bloco A',
            ddd: '61',
            telefone: '34261111',
            email: 'fulano@mail.com',
        );
    }

    protected function remetentePadrao(): EnderecoRemetente
    {
        return new EnderecoRemetente(
            nome: 'Ciclano',
            logradouro: 'Rua 35',
            numero: '10',
            bairro: 'Águas Claras',
            cidade: 'Brasília',
            uf: 'DF',
            cep: '71931180',
            ddd: '61',
            telefone: '34262222',
            email: 'ciclano@mail.com',
            restricaoAnac: true,
            identificacao: '12312312387',
        );
    }

    protected function criarAutorizacaoSimples(): string
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
        if ($resultados[0]->temErro()) {
            $this->fail("Falha ao criar autorização-base: [{$resultados[0]->codigoErro}] {$resultados[0]->descricaoErro}");
        }
        return $resultados[0]->numeroColeta;
    }
}
