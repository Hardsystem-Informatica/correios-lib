<?php

namespace Hardsystem\Correios\LogisticaReversa;

use Hardsystem\Correios\LogisticaReversa\Dto\Coleta;
use Hardsystem\Correios\LogisticaReversa\Dto\ColetaSimultanea;
use Hardsystem\Correios\LogisticaReversa\Dto\EnderecoDestinatario;
use Hardsystem\Correios\LogisticaReversa\Dto\EnderecoRemetente;
use Hardsystem\Correios\LogisticaReversa\Dto\EventoPedido;
use Hardsystem\Correios\LogisticaReversa\Dto\FaixaEtiquetas;
use Hardsystem\Correios\LogisticaReversa\Dto\ObjetoColeta;
use Hardsystem\Correios\LogisticaReversa\Dto\Pedido;
use Hardsystem\Correios\LogisticaReversa\Dto\Produto;
use Hardsystem\Correios\LogisticaReversa\Dto\ResultadoCancelamento;
use Hardsystem\Correios\LogisticaReversa\Dto\ResultadoSolicitacao;
use Hardsystem\Correios\LogisticaReversa\Dto\SolicitacaoReversa;
use Hardsystem\Correios\LogisticaReversa\Dto\SolicitacaoSimultanea;
use Hardsystem\Correios\LogisticaReversa\Enum\ServicoLR;
use Hardsystem\Correios\LogisticaReversa\Enum\StatusPedido;
use Hardsystem\Correios\LogisticaReversa\Enum\TipoBusca;
use Hardsystem\Correios\LogisticaReversa\Enum\TipoColeta;
use Hardsystem\Correios\LogisticaReversa\Enum\TipoPedido;
use Hardsystem\Correios\LogisticaReversa\Enum\TipoRange;
use Hardsystem\Correios\LogisticaReversa\Exception\LogisticaReversaException;
use Hardsystem\Correios\LogisticaReversa\Exception\PedidoNaoCancelavelException;

final class LogisticaReversa
{
    private \SoapClient $cliente;
    private Credenciais $credenciais;

    // ====================================================================
    // FACTORIES
    // ====================================================================

    public function __construct(Ambiente $ambiente, Credenciais $credenciais)
    {
        $this->credenciais = $credenciais;
        $this->cliente = self::criarSoapClient($ambiente->wsdl(), $credenciais);
    }

    public static function comWsdl(string $wsdl, Credenciais $credenciais): self
    {
        $instancia = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $instancia->credenciais = $credenciais;
        $instancia->cliente = self::criarSoapClient($wsdl, $credenciais);
        return $instancia;
    }

    public static function comCliente(\SoapClient $cliente, Credenciais $credenciais): self
    {
        $instancia = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $instancia->credenciais = $credenciais;
        $instancia->cliente = $cliente;
        return $instancia;
    }

    private static function criarSoapClient(string $wsdl, Credenciais $credenciais): \SoapClient
    {
        return new \SoapClient($wsdl, [
            'login' => $credenciais->usuario,
            'password' => $credenciais->senha,
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'connection_timeout' => 30,
            'trace' => false,
        ]);
    }

    // ====================================================================
    // OPERAÇÕES SOAP
    // ====================================================================

    /**
     * 3.4.1 — Solicitação de Autorização de Postagem ou Coleta Reversa.
     *
     * @return ResultadoSolicitacao[] Um item por solicitação dentro do batch (até 50).
     */
    public function solicitarPostagemReversa(SolicitacaoReversa $solicitacao): array
    {
        $resposta = $this->chamar('solicitarPostagemReversa', [
            'codAdministrativo' => $this->credenciais->codigoAdministrativo,
            'codigo_servico' => $solicitacao->codigoServico,
            'cartao' => $solicitacao->cartaoPostagem ?? $this->credenciais->cartaoPostagem,
            'destinatario' => $this->mapearDestinatario($solicitacao->destinatario),
            'coletas_solicitadas' => array_map(
                fn (Coleta $coleta) => $this->mapearColeta($coleta),
                $solicitacao->coletas,
            ),
        ]);

        $this->checarErroFatalSolicitacao($resposta);

        return array_map(
            fn (\stdClass $item) => $this->mapearResultadoSolicitacao($item),
            self::normalizarLista($resposta->resultado_solicitacao ?? null),
        );
    }

    /** 3.4.2 — Cancelamento de solicitação. */
    public function cancelarPedido(string $numeroPedido, TipoPedido $tipo): ResultadoCancelamento
    {
        $resposta = $this->chamar('cancelarPedido', [
            'codAdministrativo' => $this->credenciais->codigoAdministrativo,
            'numeroPedido' => $numeroPedido,
            'tipo' => $tipo->value,
        ]);

        $codigoErro = isset($resposta->cod_erro) ? (int) $resposta->cod_erro : 0;
        if ($codigoErro === -9) {
            throw new PedidoNaoCancelavelException(
                $numeroPedido,
                self::valorString($resposta, 'msg_erro') ?? 'status não permite cancelamento',
            );
        }
        $this->checarErroFatal($resposta);

        $objetoPostal = $resposta->objeto_postal;
        return new ResultadoCancelamento(
            codigoAdministrativo: (string) $resposta->codigo_administrativo,
            numeroPedido: (string) $objetoPostal->numero_pedido,
            statusPedido: (string) $objetoPostal->status_pedido,
            dataHoraCancelamento: self::parseDataHora((string) $objetoPostal->datahora_cancelamento),
        );
    }

    /** 3.4.3 — Acompanhar pelo número da solicitação. */
    public function acompanharPedido(string $numeroPedido, TipoPedido $tipo, TipoBusca $busca): Pedido
    {
        $resposta = $this->chamar('acompanharPedido', [
            'codAdministrativo' => $this->credenciais->codigoAdministrativo,
            'tipoBusca' => $busca->value,
            'tipoSolicitacao' => $tipo->value,
            'numeroPedido' => $numeroPedido,
        ]);

        $coletas = self::normalizarLista($resposta->coleta ?? null);
        if ($coletas === []) {
            throw LogisticaReversaException::deCodigoCorreios(-5, "Pedido {$numeroPedido} não encontrado");
        }

        return $this->mapearPedidoDeColeta(
            $coletas[0],
            TipoPedido::from((string) $resposta->tipo_solicitacao),
            (string) $resposta->codigo_administrativo,
        );
    }

    /**
     * 3.4.4 — Acompanhar por data. Retorna todos os pedidos com atualização na data.
     *
     * @return Pedido[]
     */
    public function acompanharPedidoPorData(\DateTimeImmutable $data, TipoPedido $tipo): array
    {
        $resposta = $this->chamar('acompanharPedidoPorData', [
            'codAdministrativo' => $this->credenciais->codigoAdministrativo,
            'tipoSolicitacao' => $tipo->value,
            'data' => $data->format('d/m/Y'),
        ]);

        $codigoAdministrativo = (string) ($resposta->codigo_administrativo ?? $this->credenciais->codigoAdministrativo);
        $tipoResposta = isset($resposta->tipo_solicitacao)
            ? TipoPedido::from((string) $resposta->tipo_solicitacao)
            : $tipo;

        return array_map(
            fn (\stdClass $coleta) => $this->mapearPedidoDeColeta($coleta, $tipoResposta, $codigoAdministrativo),
            self::normalizarLista($resposta->coleta ?? null),
        );
    }

    /** 3.4.5 — Revalidar prazo de Autorização de Postagem (entre 5 e 30 dias). */
    public function revalidarPrazoAutorizacaoPostagem(string $numeroPedido, int $dias): \DateTimeImmutable
    {
        $resposta = $this->chamar('revalidarPrazoAutorizacaoPostagem', [
            'codAdministrativo' => $this->credenciais->codigoAdministrativo,
            'numeroPedido' => $numeroPedido,
            'qtdeDias' => $dias,
        ]);

        $this->checarErroFatal($resposta);
        return self::parseData((string) $resposta->prazo);
    }

    /** 3.4.6 — Reserva de faixa de numeração de e-ticket (range). */
    public function solicitarRange(TipoRange $tipo, int $quantidade, ?ServicoLR $servico = null): FaixaEtiquetas
    {
        $resposta = $this->chamar('solicitarRange', [
            'codAdministrativo' => $this->credenciais->codigoAdministrativo,
            'tipo' => $tipo->value,
            'servico' => $servico?->value ?? '',
            'quantidade' => $quantidade,
        ]);

        $this->checarErroFatal($resposta);

        return new FaixaEtiquetas(
            emitidoEm: self::parseDataHora(
                (string) $resposta->data . ' ' . (string) $resposta->hora,
            ),
            faixaInicial: (string) $resposta->faixa_inicial,
            faixaFinal: (string) $resposta->faixa_final,
        );
    }

    /**
     * 3.4.6 — Cálculo do dígito verificador do e-ticket via web service.
     *
     * Para evitar uma chamada de rede, prefira `LogisticaReversa::dvLocal()` — usa o
     * mesmo algoritmo do Anexo 03 e dispensa autenticação.
     */
    public function calcularDigitoVerificador(string $numero): string
    {
        $resposta = $this->chamar('calcularDigitoVerificador', [
            'numero' => $numero,
        ]);

        $this->checarErroFatal($resposta);
        return (string) $resposta->numero;
    }

    /** 3.4.7 — Logística Reversa Simultânea com Coleta. */
    public function solicitarPostagemSimultanea(SolicitacaoSimultanea $solicitacao): ResultadoSolicitacao
    {
        $resposta = $this->chamar('solicitarPostagemSimultanea', [
            'codAdministrativo' => $this->credenciais->codigoAdministrativo,
            'codigo_servico' => $solicitacao->codigoServico,
            'cartao' => $solicitacao->cartaoPostagem ?? $this->credenciais->cartaoPostagem,
            'destinatario' => $this->mapearDestinatario($solicitacao->destinatario),
            'coletas_solicitadas' => $this->mapearColetaSimultanea($solicitacao->coleta),
        ]);

        $this->checarErroFatalSolicitacao($resposta);

        $resultados = self::normalizarLista($resposta->resultado_solicitacao ?? null);
        if ($resultados === []) {
            throw LogisticaReversaException::deCodigoCorreios(-8, 'Resposta sem resultado_solicitacao');
        }
        return $this->mapearResultadoSolicitacao($resultados[0]);
    }

    /**
     * Cálculo local do dígito verificador (Anexo 03). Não consome rede.
     *
     * @param string $numero E-ticket de 8 ou 9 dígitos numéricos.
     * @return string E-ticket original concatenado com o DV.
     */
    public static function dvLocal(string $numero): string
    {
        $tamanho = strlen($numero);
        if ($tamanho !== 8 && $tamanho !== 9) {
            throw new \InvalidArgumentException("E-ticket deve ter 8 ou 9 dígitos, recebido: {$numero}");
        }
        if (!ctype_digit($numero)) {
            throw new \InvalidArgumentException("E-ticket deve conter apenas dígitos: {$numero}");
        }

        $multiplicadores = [8, 6, 4, 2, 3, 5, 9, 7, 3];
        $soma = 0;
        for ($i = 0; $i < $tamanho; $i++) {
            $soma += ((int) $numero[$i]) * $multiplicadores[$i];
        }

        $resto = $soma % 11;
        $digito = match (true) {
            $resto === 0 => '5',
            $resto === 1 => '0',
            default => (string) (11 - $resto),
        };

        return $numero . $digito;
    }

    // ====================================================================
    // TRANSPORTE
    // ====================================================================

    /** @param array<string, mixed> $argumentos */
    private function chamar(string $metodo, array $argumentos): \stdClass
    {
        try {
            $resposta = $this->cliente->__soapCall($metodo, [$argumentos]);
        } catch (\SoapFault $falha) {
            throw LogisticaReversaException::falhaTransporte($falha->getMessage(), $falha);
        }

        if (is_object($resposta) && isset($resposta->{$metodo})) {
            $resposta = $resposta->{$metodo};
        }
        if (!$resposta instanceof \stdClass) {
            $resposta = (object) (is_array($resposta) ? $resposta : ['retorno' => $resposta]);
        }
        return $resposta;
    }

    private function checarErroFatal(\stdClass $resposta): void
    {
        $codigo = isset($resposta->cod_erro) ? (int) $resposta->cod_erro : 0;
        if ($codigo !== 0) {
            throw LogisticaReversaException::deCodigoCorreios(
                $codigo,
                self::valorString($resposta, 'msg_erro'),
            );
        }
    }

    private function checarErroFatalSolicitacao(\stdClass $resposta): void
    {
        $statusProcessamento = isset($resposta->status_processamento)
            ? (int) $resposta->status_processamento
            : 1;
        $codigoErro = isset($resposta->cod_erro) ? (int) $resposta->cod_erro : 0;

        if ($statusProcessamento !== 1 || $codigoErro !== 0) {
            throw LogisticaReversaException::deCodigoCorreios(
                $codigoErro,
                self::valorString($resposta, 'msg_erro'),
            );
        }
    }

    /** @return array<int, \stdClass> */
    private static function normalizarLista(mixed $valor): array
    {
        if ($valor === null || $valor === '') {
            return [];
        }
        if (is_array($valor)) {
            return array_values(array_map(
                fn ($item) => $item instanceof \stdClass ? $item : (object) $item,
                $valor,
            ));
        }
        return [$valor instanceof \stdClass ? $valor : (object) $valor];
    }

    private static function valorString(\stdClass $obj, string $campo): ?string
    {
        if (!isset($obj->{$campo})) {
            return null;
        }
        $valor = $obj->{$campo};
        if (is_object($valor) || is_array($valor)) {
            return null;
        }
        $valor = trim((string) $valor);
        return $valor === '' ? null : $valor;
    }

    // ====================================================================
    // INPUT MAPPERS (DTO → array para SoapClient)
    // ====================================================================

    /** @return array<string, mixed> */
    private function mapearDestinatario(EnderecoDestinatario $endereco): array
    {
        return array_filter([
            'nome' => $endereco->nome,
            'logradouro' => $endereco->logradouro,
            'numero' => $endereco->numero,
            'complemento' => $endereco->complemento,
            'bairro' => $endereco->bairro,
            'referencia' => $endereco->referencia,
            'cidade' => $endereco->cidade,
            'uf' => $endereco->uf,
            'cep' => $endereco->cep,
            'ddd' => $endereco->ddd,
            'telefone' => $endereco->telefone,
            'celular' => $endereco->celular,
            'ddd_celular' => $endereco->dddCelular,
            'email' => $endereco->email,
            'identificacao' => $endereco->identificacao,
            'ciencia_conteudo_proibido' => self::boolSN($endereco->cienciaConteudoProibido),
        ], fn ($valor) => $valor !== null);
    }

    /** @return array<string, mixed> */
    private function mapearRemetente(EnderecoRemetente $endereco): array
    {
        return array_filter([
            'nome' => $endereco->nome,
            'logradouro' => $endereco->logradouro,
            'numero' => $endereco->numero,
            'complemento' => $endereco->complemento,
            'bairro' => $endereco->bairro,
            'referencia' => $endereco->referencia,
            'cidade' => $endereco->cidade,
            'uf' => $endereco->uf,
            'cep' => $endereco->cep,
            'ddd' => $endereco->ddd,
            'telefone' => $endereco->telefone,
            'email' => $endereco->email,
            'celular' => $endereco->celular,
            'ddd_celular' => $endereco->dddCelular,
            'sms' => self::boolSN($endereco->sms),
            'identificacao' => $endereco->identificacao,
            'documento_estrangeiro' => $endereco->documentoEstrangeiro,
            'restricao_anac' => self::boolSN($endereco->restricaoAnac),
        ], fn ($valor) => $valor !== null);
    }

    /** @return array<string, mixed> */
    private function mapearColeta(Coleta $coleta): array
    {
        $payload = [
            'tipo' => $coleta->tipo->value,
            'remetente' => $this->mapearRemetente($coleta->remetente),
            'obj_col' => array_map(
                fn (ObjetoColeta $objeto) => $this->mapearObjetoColeta($objeto),
                $coleta->objetos,
            ),
        ];

        if ($coleta->numeroEticket !== null) {
            $payload['numero'] = $coleta->numeroEticket;
        }
        if ($coleta->idCliente !== null) {
            $payload['id_cliente'] = $coleta->idCliente;
        }
        if ($coleta->dataAgendamento !== null) {
            $payload['ag'] = $coleta->dataAgendamento->format('d/m/Y');
        } elseif ($coleta->diasValidade !== null) {
            $payload['ag'] = (string) $coleta->diasValidade;
        }
        if ($coleta->cartaoPostagem !== null) {
            $payload['cartao'] = $coleta->cartaoPostagem;
        }
        if ($coleta->valorDeclarado !== null) {
            $payload['valor_declarado'] = number_format($coleta->valorDeclarado, 2, '.', '');
        }
        if ($coleta->descricao !== null) {
            $payload['descricao'] = $coleta->descricao;
        }
        if ($coleta->solicitarAvisoRecebimento) {
            $payload['ar'] = '1';
        }
        if ($coleta->checklist !== null) {
            $payload['cklist'] = $coleta->checklist->value;
            if ($coleta->documentos !== []) {
                $payload['documento'] = array_map(
                    fn ($documento) => $documento->value,
                    $coleta->documentos,
                );
            }
        }
        if ($coleta->produtos !== []) {
            $payload['produto'] = array_map(
                fn (Produto $produto) => $this->mapearProduto($produto),
                $coleta->produtos,
            );
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function mapearColetaSimultanea(ColetaSimultanea $coleta): array
    {
        $payload = [
            'tipo' => TipoColeta::ColetaDomiciliar->value,
            'remetente' => $this->mapearRemetente($coleta->remetente),
            'obj' => $coleta->numeroEtiquetaIda,
        ];

        if ($coleta->idCliente !== null) {
            $payload['id_cliente'] = $coleta->idCliente;
        }
        if ($coleta->valorDeclarado !== null) {
            $payload['valor_declarado'] = number_format($coleta->valorDeclarado, 2, '.', '');
        }
        if ($coleta->descricao !== null) {
            $payload['descricao'] = $coleta->descricao;
        }
        if ($coleta->checklist !== null) {
            $payload['cklist'] = $coleta->checklist->value;
            if ($coleta->documentos !== []) {
                $payload['documento'] = array_map(
                    fn ($documento) => $documento->value,
                    $coleta->documentos,
                );
            }
        }
        if ($coleta->produtos !== []) {
            $payload['produto'] = array_map(
                fn (Produto $produto) => $this->mapearProduto($produto),
                $coleta->produtos,
            );
        }
        if ($coleta->observacao !== null) {
            $payload['obs'] = $coleta->observacao;
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function mapearObjetoColeta(ObjetoColeta $objeto): array
    {
        return array_filter([
            'item' => str_pad((string) $objeto->item, 2, '0', STR_PAD_LEFT),
            'id' => $objeto->id,
            'desc' => $objeto->descricao,
            'entrega' => $objeto->entrega,
            'num' => $objeto->numero,
        ], fn ($valor) => $valor !== null);
    }

    /** @return array<string, mixed> */
    private function mapearProduto(Produto $produto): array
    {
        return [
            'codigo' => $produto->codigo,
            'tipo' => $produto->tipo,
            'qtd' => $produto->quantidade,
        ];
    }

    // ====================================================================
    // OUTPUT MAPPERS (response → DTO)
    // ====================================================================

    private function mapearResultadoSolicitacao(\stdClass $item): ResultadoSolicitacao
    {
        $statusObjeto = isset($item->status_objeto)
            ? StatusPedido::tryFrom((int) $item->status_objeto)
            : null;
        $codigoErro = isset($item->codigo_erro) ? (int) $item->codigo_erro : null;

        return new ResultadoSolicitacao(
            tipo: TipoColeta::from((string) $item->tipo),
            numeroColeta: self::valorString($item, 'numero_coleta') ?? '',
            idCliente: self::valorString($item, 'id_cliente'),
            numeroEtiqueta: self::valorString($item, 'numero_etiqueta'),
            idObjeto: self::valorString($item, 'id_obj'),
            statusObjeto: $statusObjeto,
            prazo: self::parseDataOpcional(self::valorString($item, 'prazo')),
            dataSolicitacao: self::parseDataOpcional(self::valorString($item, 'data_solicitacao')),
            horaSolicitacao: self::valorString($item, 'hora_solicitacao'),
            codigoErro: $codigoErro,
            descricaoErro: self::valorString($item, 'descricao_erro'),
        );
    }

    private function mapearPedidoDeColeta(\stdClass $coleta, TipoPedido $tipo, string $codigoAdministrativo): Pedido
    {
        $historico = array_map(
            fn (\stdClass $evento) => $this->mapearEvento($evento),
            self::normalizarLista($coleta->historico ?? null),
        );

        $objeto = isset($coleta->objeto) && $coleta->objeto instanceof \stdClass
            ? $coleta->objeto
            : null;

        return new Pedido(
            codigoAdministrativo: $codigoAdministrativo,
            tipoSolicitacao: $tipo,
            numeroPedido: (string) $coleta->numero_pedido,
            historico: $historico,
            controleCliente: self::valorString($coleta, 'controle_cliente'),
            numeroEtiqueta: $objeto !== null ? self::valorString($objeto, 'numero_etiqueta') : null,
            controleObjetoCliente: $objeto !== null ? self::valorString($objeto, 'controle_objeto_cliente') : null,
            ultimoStatus: $objeto !== null && isset($objeto->ultimo_status)
                ? StatusPedido::tryFrom((int) $objeto->ultimo_status)
                : null,
            descricaoUltimoStatus: $objeto !== null ? self::valorString($objeto, 'descricao_status') : null,
            dataUltimaAtualizacao: $objeto !== null
                ? self::parseDataOpcional(self::valorString($objeto, 'data_ultima_atualizacao'))
                : null,
            horaUltimaAtualizacao: $objeto !== null ? self::valorString($objeto, 'hora_ultima_atualizacao') : null,
        );
    }

    private function mapearEvento(\stdClass $evento): EventoPedido
    {
        return new EventoPedido(
            status: StatusPedido::from((int) $evento->status),
            descricaoStatus: (string) $evento->descricao_status,
            dataAtualizacao: self::parseData((string) $evento->data_atualizacao),
            horaAtualizacao: (string) $evento->hora_atualizacao,
            observacao: self::valorString($evento, 'observacao'),
        );
    }

    // ====================================================================
    // HELPERS
    // ====================================================================

    private static function parseData(string $data): \DateTimeImmutable
    {
        $data = trim($data);
        foreach (['d/m/Y', 'd-m-Y'] as $formato) {
            $resultado = \DateTimeImmutable::createFromFormat("!{$formato}", $data);
            if ($resultado !== false) {
                return $resultado;
            }
        }
        throw new \InvalidArgumentException("Data inválida: {$data}");
    }

    private static function parseDataOpcional(?string $data): ?\DateTimeImmutable
    {
        return ($data === null || $data === '') ? null : self::parseData($data);
    }

    private static function parseDataHora(string $dataHora): \DateTimeImmutable
    {
        $dataHora = trim($dataHora);
        foreach (['d/m/Y H:i:s', 'd-m-Y H:i:s', 'd/m/Y H:i', 'd-m-Y H:i'] as $formato) {
            $resultado = \DateTimeImmutable::createFromFormat($formato, $dataHora);
            if ($resultado !== false) {
                return $resultado;
            }
        }
        throw new \InvalidArgumentException("Data/hora inválida: {$dataHora}");
    }

    private static function boolSN(bool $valor): string
    {
        return $valor ? 'S' : 'N';
    }
}
