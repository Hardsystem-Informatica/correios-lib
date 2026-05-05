<?php

namespace Hardsystem\Correios\LogisticaReversa\Enum;

enum TipoPedido: string
{
    case Coleta = 'C';
    case AutorizacaoPostagem = 'A';
}
