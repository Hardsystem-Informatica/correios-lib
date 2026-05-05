<?php

namespace Hardsystem\Correios\LogisticaReversa\Enum;

enum ChecklistTipo: int
{
    case Celular = 2;
    case Eletronico = 4;
    case Documento = 5;
    case Conteudo = 7;
}
