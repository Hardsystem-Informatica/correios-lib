<?php

namespace Hardsystem\Correios;

class Endereco
{
    public function __construct(
        private string $cep,
        private string $logradouro,
        private string $complemento,
        private string $bairro,
        private string $localidade,
        private string $uf,
        private string $ibge,
        private string $gia,
        private string $ddd,
        private string $siafi
    ) {
    }

    public function getCep(): string
    {
        return $this->cep;
    }

    public function getLogradouro(): string
    {
        return $this->logradouro;
    }

    public function getComplemento(): string
    {
        return $this->complemento;
    }

    public function getBairro(): string
    {
        return $this->bairro;
    }

    public function getLocalidade(): string
    {
        return $this->localidade;
    }

    public function getUf(): string
    {
        return $this->uf;
    }

    public function getIbge(): string
    {
        return $this->ibge;
    }

    public function getGia(): string
    {
        return $this->gia;
    }

    public function getDdd(): string
    {
        return $this->ddd;
    }

    public function getSiafi(): string
    {
        return $this->siafi;
    }

    public function toArray(): array
    {
        return [
            'cep' => $this->cep,
            'logradouro' => $this->logradouro,
            'complemento' => $this->complemento,
            'bairro' => $this->bairro,
            'localidade' => $this->localidade,
            'uf' => $this->uf,
            'ibge' => $this->ibge,
            'gia' => $this->gia,
            'ddd' => $this->ddd,
            'siafi' => $this->siafi
        ];
    }
} 