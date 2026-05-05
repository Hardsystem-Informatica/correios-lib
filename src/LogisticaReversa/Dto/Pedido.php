<?php

namespace Hardsystem\Correios\LogisticaReversa\Dto;

use Hardsystem\Correios\LogisticaReversa\Enum\StatusPedido;
use Hardsystem\Correios\LogisticaReversa\Enum\TipoPedido;

final class Pedido
{
    /**
     * @param EventoPedido[] $historico Eventos do pedido em ordem cronológica conforme retornados pelos Correios.
     */
    public function __construct(
        public readonly string $codigoAdministrativo,
        public readonly TipoPedido $tipoSolicitacao,
        public readonly string $numeroPedido,
        public readonly array $historico,
        public readonly ?string $controleCliente = null,
        public readonly ?string $numeroEtiqueta = null,
        public readonly ?string $controleObjetoCliente = null,
        public readonly ?StatusPedido $ultimoStatus = null,
        public readonly ?string $descricaoUltimoStatus = null,
        public readonly ?\DateTimeImmutable $dataUltimaAtualizacao = null,
        public readonly ?string $horaUltimaAtualizacao = null,
    ) {
    }
}
