<?php

namespace Hardsystem\Correios\LogisticaReversa\Dto;

use Hardsystem\Correios\LogisticaReversa\Enum\StatusPedido;

final class EventoPedido
{
    public function __construct(
        public readonly StatusPedido $status,
        public readonly string $descricaoStatus,
        public readonly \DateTimeImmutable $dataAtualizacao,
        public readonly string $horaAtualizacao,
        public readonly ?string $observacao = null,
    ) {
    }
}
