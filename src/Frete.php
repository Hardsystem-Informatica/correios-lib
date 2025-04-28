<?php

namespace Hardsystem\Correios;

class Frete
{
    public function __construct(
        private string $codigoServico,
        private string $valor,
        private string $prazoEntrega,
        private string $valorMaoPropria,
        private string $valorAvisoRecebimento,
        private string $valorValorDeclarado,
        private string $entregaDomiciliar,
        private string $entregaSabado,
        private string $erro,
        private string $msgErro
    ) {
    }

    public function getCodigoServico(): string
    {
        return $this->codigoServico;
    }

    public function getValor(): string
    {
        return $this->valor;
    }

    public function getPrazoEntrega(): string
    {
        return $this->prazoEntrega;
    }

    public function getValorMaoPropria(): string
    {
        return $this->valorMaoPropria;
    }

    public function getValorAvisoRecebimento(): string
    {
        return $this->valorAvisoRecebimento;
    }

    public function getValorValorDeclarado(): string
    {
        return $this->valorValorDeclarado;
    }

    public function getEntregaDomiciliar(): string
    {
        return $this->entregaDomiciliar;
    }

    public function getEntregaSabado(): string
    {
        return $this->entregaSabado;
    }

    public function getErro(): string
    {
        return $this->erro;
    }

    public function getMsgErro(): string
    {
        return $this->msgErro;
    }

    public function toArray(): array
    {
        return [
            'codigoServico' => $this->codigoServico,
            'valor' => $this->valor,
            'prazoEntrega' => $this->prazoEntrega,
            'valorMaoPropria' => $this->valorMaoPropria,
            'valorAvisoRecebimento' => $this->valorAvisoRecebimento,
            'valorValorDeclarado' => $this->valorValorDeclarado,
            'entregaDomiciliar' => $this->entregaDomiciliar,
            'entregaSabado' => $this->entregaSabado,
            'erro' => $this->erro,
            'msgErro' => $this->msgErro
        ];
    }
} 