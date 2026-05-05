<?php

namespace Hardsystem\Correios\LogisticaReversa\Dto;

final class SolicitacaoReversa
{
    /**
     * @param Coleta[] $coletas Até 50 coletas por chamada.
     */
    public function __construct(
        public readonly string $codigoServico,
        public readonly EnderecoDestinatario $destinatario,
        public readonly array $coletas,
        public readonly ?string $cartaoPostagem = null,
    ) {
    }
}
