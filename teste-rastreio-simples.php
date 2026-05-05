<?php

require 'vendor/autoload.php';

use Hardsystem\Correios\Correios;

// ==========================================
// CONFIGURAÇÃO - EDITE AQUI SUAS CREDENCIAIS
// ==========================================
$correios = new Correios(
    '',           
    '',         
    ''     
);

// ==========================================
// DADOS DO TESTE - EDITE AQUI O CÓDIGO DE RASTREAMENTO
// ==========================================
$codigoRastreamento = 'AA123456789BR'; // Substitua pelo código real

// ==========================================
// EXECUÇÃO DO TESTE
// ==========================================
echo "=== RASTREAMENTO DE ENCOMENDA ===\n";
echo "Código: " . $codigoRastreamento . "\n\n";

try {
    $resultado = $correios->rastrearEncomenda($codigoRastreamento);
    
    // Exibe o resultado completo para debug
    echo "Resultado completo:\n";
    print_r($resultado);
    
} catch (\Hardsystem\Correios\Exceptions\CalculoFreteException $e) {
    echo "Erro ao rastrear: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Erro inesperado: " . $e->getMessage() . "\n";
}
