<?php

require 'vendor/autoload.php';

use Hardsystem\Correios\Correios;

// ==========================================
// CONFIGURAÇÃO - EDITE AQUI SUAS CREDENCIAIS
// ==========================================
$correios = new Correios(
    'SEU_USUARIO_AQUI',           
    'SUA_SENHA_AQUI',         
    'SEU_CARTAO_POSTAL_AQUI'     
);

// ==========================================
// DADOS DO TESTE - EDITE AQUI OS VALORES
// ==========================================
$dados = [
    'cepOrigem' => '01001000',      // CEP de origem
    'cepDestino' => '20010000',     // CEP de destino
    'peso' => 0.5,                  // Peso em KG (será convertido para gramas)
    'comprimento' => 20,            // Comprimento em cm
    'largura' => 15,                // Largura em cm
    'altura' => 10,                 // Altura em cm
    'diametro' => 0,                // Diâmetro em cm (0 para caixas)
    'servicos' => ['04014', '04510'] // SEDEX e PAC
];

// ==========================================
// EXECUÇÃO DO TESTE
// ==========================================
echo "=== TESTE DE CÁLCULO DE FRETE ===\n";
echo "Peso: " . $dados['peso'] . " kg (" . ($dados['peso'] * 1000) . " gramas)\n";
echo "Dimensões: " . $dados['comprimento'] . "x" . $dados['largura'] . "x" . $dados['altura'] . " cm\n";
echo "Origem: " . $dados['cepOrigem'] . " → Destino: " . $dados['cepDestino'] . "\n\n";

try {
    $resultados = $correios->calcularFrete($dados);
    
    foreach ($resultados as $resultado) {
        echo "Serviço: " . ($resultado['coProduto'] ?? 'N/A') . "\n";
        echo "Preço Base: R$ " . ($resultado['pcBase'] ?? 'N/A') . "\n";
        echo "Preço Final: R$ " . ($resultado['pcFinal'] ?? 'N/A') . "\n";
        echo "Peso Cobrado: " . ($resultado['psCobrado'] ?? 'N/A') . " kg\n";
        echo "Seguro Automático: R$ " . ($resultado['vlSeguroAutomatico'] ?? 'N/A') . "\n";
        echo "-------------------\n";
    }
} catch (\Hardsystem\Correios\Exceptions\CalculoFreteException $e) {
    echo "Erro ao calcular frete: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Erro inesperado: " . $e->getMessage() . "\n";
} 