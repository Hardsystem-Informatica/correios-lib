<?php

namespace Hardsystem\Correios\LogisticaReversa;

final class Credenciais
{
    public function __construct(
        public readonly string $usuario,
        public readonly string $senha,
        public readonly string $codigoAdministrativo,
        public readonly string $contrato,
        public readonly string $cartaoPostagem,
    ) {
    }
}
