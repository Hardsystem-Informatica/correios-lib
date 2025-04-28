<?php

namespace Hardsystem\Correios\Exceptions;

class ConsultaCepException extends \Exception
{
    public static function cepInvalido(string $cep): self
    {
        return new self("CEP inválido: {$cep}");
    }

    public static function cepNaoEncontrado(string $cep): self
    {
        return new self("CEP não encontrado: {$cep}");
    }

    public static function erroNaConsulta(string $mensagem): self
    {
        return new self("Erro na consulta de CEP: {$mensagem}");
    }
} 