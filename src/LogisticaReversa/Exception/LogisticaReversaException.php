<?php

namespace Hardsystem\Correios\LogisticaReversa\Exception;

use Hardsystem\Correios\LogisticaReversa\Enum\CodigoErroLR;

class LogisticaReversaException extends \Exception
{
    public function __construct(
        string $mensagem,
        public readonly ?int $codigoCorreios = null,
        ?\Throwable $anterior = null,
    ) {
        parent::__construct($mensagem, 0, $anterior);
    }

    public static function deCodigoCorreios(int $codigo, ?string $mensagemServidor = null): self
    {
        $descricao = $mensagemServidor ?? CodigoErroLR::descricaoDe($codigo) ?? 'Erro desconhecido retornado pelos Correios';
        return new self("[{$codigo}] {$descricao}", $codigo);
    }

    public static function falhaTransporte(string $mensagem, ?\Throwable $anterior = null): self
    {
        return new self("Falha de comunicação com o web service de Logística Reversa: {$mensagem}", null, $anterior);
    }
}
