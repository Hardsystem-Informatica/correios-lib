<?php

require 'vendor/autoload.php';

use Hardsystem\Correios\Correios;

$correios = new Correios();

try {
    // Teste com o CEP da Praça da Sé em São Paulo
    $endereco = $correios->consultarCep('01001000');
    
    echo "=== Dados do Endereço ===\n";
    echo "CEP: " . $endereco->getCep() . "\n";
    echo "Logradouro: " . $endereco->getLogradouro() . "\n";
    echo "Complemento: " . $endereco->getComplemento() . "\n";
    echo "Bairro: " . $endereco->getBairro() . "\n";
    echo "Cidade: " . $endereco->getLocalidade() . "\n";
    echo "UF: " . $endereco->getUf() . "\n";
    echo "DDD: " . $endereco->getDdd() . "\n";
    echo "IBGE: " . $endereco->getIbge() . "\n";
    echo "GIA: " . $endereco->getGia() . "\n";
    echo "SIAFI: " . $endereco->getSiafi() . "\n";
    
    echo "\n=== Dados em Array ===\n";
    print_r($endereco->toArray());
} catch (\Hardsystem\Correios\Exceptions\ConsultaCepException $e) {
    echo "Erro ao consultar CEP: " . $e->getMessage() . "\n";
} 