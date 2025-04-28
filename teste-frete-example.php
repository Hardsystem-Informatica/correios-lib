<?php

require 'vendor/autoload.php';

use Hardsystem\Correios\Correios;

$correios = new Correios(
    '',           
    '',         
    ''     
);

try {
    $dados = [
        'cepOrigem' => '01001000',
        'cepDestino' => '20010000',
        'peso' => 1,
        'comprimento' => 20,
        'largura' => 20,
        'altura' => 20,
        'diametro' => 0,
        'servicos' => ['04014', '04510'] // SEDEX e PAC
    ];

    $resultados = $correios->calcularFrete($dados);
    
    echo "=== Dados do Frete ===\n";
    foreach ($resultados as $resultado) {
        echo "\nServiço: " . $resultado['coProduto'] . "\n";
        echo "Preço Base: R$ " . $resultado['pcBase'] . "\n";
        echo "Preço Final: R$ " . $resultado['pcFinal'] . "\n";
        echo "Peso Cobrado: " . $resultado['psCobrado'] . " kg\n";
        echo "Seguro Automático: R$ " . $resultado['vlSeguroAutomatico'] . "\n";
        echo "-------------------\n";
    }
} catch (\Hardsystem\Correios\Exceptions\CalculoFreteException $e) {
    echo "Erro ao calcular frete: " . $e->getMessage() . "\n";
} 