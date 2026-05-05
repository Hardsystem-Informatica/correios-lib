<?php

namespace Hardsystem\Correios\LogisticaReversa\Enum;

enum ServicoLR: string
{
    case PAC = 'LE';
    case Sedex = 'LS';
    case eSedex = 'LV';
}
