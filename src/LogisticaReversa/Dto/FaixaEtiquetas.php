<?php

namespace Hardsystem\Correios\LogisticaReversa\Dto;

final class FaixaEtiquetas
{
    public function __construct(
        public readonly \DateTimeImmutable $emitidoEm,
        public readonly string $faixaInicial,
        public readonly string $faixaFinal,
    ) {
    }
}
