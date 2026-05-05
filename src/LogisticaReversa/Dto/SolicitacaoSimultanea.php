<?php

namespace Hardsystem\Correios\LogisticaReversa\Dto;

final class SolicitacaoSimultanea
{
    public function __construct(
        public readonly string $codigoServico,
        public readonly EnderecoDestinatario $destinatario,
        public readonly ColetaSimultanea $coleta,
        public readonly ?string $cartaoPostagem = null,
    ) {
    }
}
