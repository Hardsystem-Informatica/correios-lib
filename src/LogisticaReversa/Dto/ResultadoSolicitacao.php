<?php

namespace Hardsystem\Correios\LogisticaReversa\Dto;

use Hardsystem\Correios\LogisticaReversa\Enum\StatusPedido;
use Hardsystem\Correios\LogisticaReversa\Enum\TipoColeta;

final class ResultadoSolicitacao
{
    public function __construct(
        public readonly TipoColeta $tipo,
        public readonly string $numeroColeta,
        public readonly ?string $idCliente = null,
        public readonly ?string $numeroEtiqueta = null,
        public readonly ?string $idObjeto = null,
        public readonly ?StatusPedido $statusObjeto = null,
        public readonly ?\DateTimeImmutable $prazo = null,
        public readonly ?\DateTimeImmutable $dataSolicitacao = null,
        public readonly ?string $horaSolicitacao = null,
        public readonly ?int $codigoErro = null,
        public readonly ?string $descricaoErro = null,
    ) {
    }

    public function temErro(): bool
    {
        return $this->codigoErro !== null && $this->codigoErro !== 0;
    }
}
