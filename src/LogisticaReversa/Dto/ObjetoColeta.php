<?php

namespace Hardsystem\Correios\LogisticaReversa\Dto;

final class ObjetoColeta
{
    public function __construct(
        public readonly int $item,
        public readonly ?string $id = null,
        public readonly ?string $descricao = null,
        public readonly ?string $entrega = null,
        public readonly ?string $numero = null,
    ) {
    }
}
