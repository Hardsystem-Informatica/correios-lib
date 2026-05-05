<?php

namespace Hardsystem\Correios\LogisticaReversa\Enum;

enum TipoColeta: string
{
    case ColetaDomiciliarComFallback = 'CA';
    case ColetaDomiciliar = 'C';
    case AutorizacaoPostagem = 'A';
}
