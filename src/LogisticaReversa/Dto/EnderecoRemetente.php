<?php

namespace Hardsystem\Correios\LogisticaReversa\Dto;

final class EnderecoRemetente
{
    public function __construct(
        public readonly string $nome,
        public readonly string $logradouro,
        public readonly string $numero,
        public readonly string $bairro,
        public readonly string $cidade,
        public readonly string $uf,
        public readonly string $cep,
        public readonly string $ddd,
        public readonly string $telefone,
        public readonly string $email,
        public readonly bool $restricaoAnac,
        public readonly ?string $complemento = null,
        public readonly ?string $referencia = null,
        public readonly ?string $celular = null,
        public readonly ?string $dddCelular = null,
        public readonly bool $sms = false,
        public readonly ?string $identificacao = null,
        public readonly ?string $documentoEstrangeiro = null,
    ) {
    }
}
