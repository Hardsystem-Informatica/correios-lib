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
// DADOS DO TESTE - EDITE AQUI O CÓDIGO DE RASTREAMENTO
// ==========================================
$codigoRastreamento = 'AA123456789BR'; // Substitua pelo código real

// ==========================================
// EXECUÇÃO DO TESTE
// ==========================================
echo "=== TESTE DE RASTREAMENTO ===\n";
echo "Código de Rastreamento: " . $codigoRastreamento . "\n\n";

try {
    $resultado = $correios->rastrearEncomenda($codigoRastreamento);
    
    echo "Status da consulta: " . ($resultado['status'] ?? 'N/A') . "\n";
    
    if (isset($resultado['objetos']) && is_array($resultado['objetos'])) {
        foreach ($resultado['objetos'] as $objeto) {
            echo "\n--- Dados do Objeto ---\n";
            echo "Código: " . ($objeto['codObjeto'] ?? 'N/A') . "\n";
            echo "Tipo: " . ($objeto['tipoPostal'] ?? 'N/A') . "\n";
            echo "Status: " . ($objeto['eventos'] ?? 'N/A') . "\n";
            
            if (isset($objeto['eventos']) && is_array($objeto['eventos'])) {
                echo "\n--- Histórico de Eventos ---\n";
                foreach ($objeto['eventos'] as $evento) {
                    echo "Data/Hora: " . ($evento['dtHrCriado'] ?? 'N/A') . "\n";
                    echo "Tipo: " . ($evento['tipo'] ?? 'N/A') . "\n";
                    echo "Status: " . ($evento['descricao'] ?? 'N/A') . "\n";
                    echo "Unidade: " . ($evento['unidade'] ?? 'N/A') . "\n";
                    echo "---\n";
                }
            }
        }
    } else {
        echo "Resposta completa:\n";
        print_r($resultado);
    }
    
} catch (\Hardsystem\Correios\Exceptions\CalculoFreteException $e) {
    echo "Erro ao rastrear encomenda: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Erro inesperado: " . $e->getMessage() . "\n";
}
