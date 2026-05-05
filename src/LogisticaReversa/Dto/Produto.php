<?php

namespace Hardsystem\Correios\LogisticaReversa\Dto;

final class Produto
{
    public function __construct(
        public readonly int $codigo,
        public readonly int $tipo,
        public readonly int $quantidade,
    ) {
    }
}
