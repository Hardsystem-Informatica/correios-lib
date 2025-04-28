<?php

namespace Hardsystem\Correios\Exceptions;

class CalculoFreteException extends \Exception
{
    public static function credenciaisInvalidas(): self
    {
        return new self('Credenciais inválidas. Verifique seu usuário e senha.');
    }

    public static function erroNoCalculo(string $mensagem): self
    {
        return new self("Erro no cálculo de frete: {$mensagem}");
    }

    public static function servicoIndisponivel(string $servico): self
    {
        return new self("Serviço indisponível: {$servico}");
    }
} 