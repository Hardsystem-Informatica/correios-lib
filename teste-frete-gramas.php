<?php

require 'vendor/autoload.php';

use Hardsystem\Correios\Correios;

// ==========================================
// CONFIGURAÇÃO - EDITE AQUI SUAS CREDENCIAIS
// ==========================================
$correios = new Correios(
    'classicadist',           
    'HTCeM1bzt4IlHg9VxjrntQWMXoxqKiGwN4EDAe4Y',         
    '0067785328'     
);

// ==========================================
// DADOS DO TESTE - EDITE AQUI OS VALORES
// ==========================================
$dados = [
    'cepOrigem' => '31140520',
    'cepDestino' => '71961540',
    'peso' => 8.2, // 500 gramas
    'comprimento' => 20,
    'largura' => 15,
    'altura' => 10,
    'diametro' => 0,
    'servicos' => ['3220'] // Apenas SEDEX
];

// ==========================================
// EXECUÇÃO DO TESTE
// ==========================================
echo "=== TESTE DE CÁLCULO DE FRETE ===\n";
echo "Peso: " . $dados['peso'] . " kg (" . ($dados['peso'] * 1000) . " gramas)\n";
echo "Dimensões: " . $dados['comprimento'] . "x" . $dados['largura'] . "x" . $dados['altura'] . " cm\n";
echo "Origem: " . $dados['cepOrigem'] . " → Destino: " . $dados['cepDestino'] . "\n";

try {
    $resultados = $correios->calcularFrete($dados);
    
    foreach ($resultados as $resultado) {
        echo "Serviço: " . ($resultado['coProduto'] ?? 'N/A') . "\n";
        echo "Preço Base: R$ " . ($resultado['pcBase'] ?? 'N/A') . "\n";
        echo "Preço Final: R$ " . ($resultado['pcFinal'] ?? 'N/A') . "\n";
        echo "Peso Cobrado: " . ($resultado['psCobrado'] ?? 'N/A') . " kg\n";
        echo "Seguro Automático: R$ " . ($resultado['vlSeguroAutomatico'] ?? 'N/A') . "\n";
        if (isset($resultado['prazo'])) {
            $p = $resultado['prazo'];
            // a API pode retornar campos diferentes; tentamos os mais comuns
            $dias = $p['prazoEntrega'] ?? $p['prazo'] ?? $p['qtDias'] ?? null;
            echo "Prazo: " . ($dias !== null ? $dias . " dia(s)" : json_encode($p)) . "\n";
        }
        echo "-------------------\n";
    }
} catch (\Hardsystem\Correios\Exceptions\CalculoFreteException $e) {
    echo "Erro ao calcular frete: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Erro inesperado: " . $e->getMessage() . "\n";
} 