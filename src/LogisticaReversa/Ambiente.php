<?php

namespace Hardsystem\Correios\LogisticaReversa;

enum Ambiente: string
{
    case Producao = 'producao';
    case Homologacao = 'homologacao';

    public function wsdl(): string
    {
        return match ($this) {
            self::Producao => 'https://apps.correios.com.br/logisticaReversaWS/logisticaReversaService/logisticaReversaWS?wsdl',
            self::Homologacao => 'https://apphom.correios.com.br/logisticaReversaWS/logisticaReversaService/logisticaReversaWS?wsdl',
        };
    }
}
