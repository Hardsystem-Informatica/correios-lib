<?php

namespace Hardsystem\Correios\LogisticaReversa\Dto;

final class ResultadoCancelamento
{
    public function __construct(
        public readonly string $codigoAdministrativo,
        public readonly string $numeroPedido,
        public readonly string $statusPedido,
        public readonly \DateTimeImmutable $dataHoraCancelamento,
    ) {
    }
}
