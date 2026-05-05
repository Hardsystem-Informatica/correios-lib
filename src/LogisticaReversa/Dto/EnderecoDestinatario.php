<?php

namespace Hardsystem\Correios\LogisticaReversa\Dto;

final class EnderecoDestinatario
{
    public function __construct(
        public readonly string $nome,
        public readonly string $logradouro,
        public readonly string $numero,
        public readonly string $bairro,
        public readonly string $cidade,
        public readonly string $uf,
        public readonly string $cep,
        public readonly bool $cienciaConteudoProibido,
        public readonly ?string $complemento = null,
        public readonly ?string $referencia = null,
        public readonly ?string $ddd = null,
        public readonly ?string $telefone = null,
        public readonly ?string $celular = null,
        public readonly ?string $dddCelular = null,
        public readonly ?string $email = null,
        public readonly ?string $identificacao = null,
    ) {
    }
}
