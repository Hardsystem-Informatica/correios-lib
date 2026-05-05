<?php

namespace Hardsystem\Correios\LogisticaReversa\Exception;

class PedidoNaoCancelavelException extends LogisticaReversaException
{
    public function __construct(string $numeroPedido, string $statusAtual)
    {
        parent::__construct(
            "Pedido {$numeroPedido} não pode ser cancelado no status atual: {$statusAtual}",
            -9,
        );
    }
}
