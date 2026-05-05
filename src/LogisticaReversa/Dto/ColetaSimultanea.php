<?php

namespace Hardsystem\Correios\LogisticaReversa\Dto;

use Hardsystem\Correios\LogisticaReversa\Enum\ChecklistTipo;
use Hardsystem\Correios\LogisticaReversa\Enum\DocumentoChecklist;

final class ColetaSimultanea
{
    /**
     * @param Produto[]            $produtos   Embalagens solicitadas junto à coleta.
     * @param DocumentoChecklist[] $documentos Tipos de documento quando checklist = Documento. Máx. 8.
     */
    public function __construct(
        public readonly EnderecoRemetente $remetente,
        public readonly string $numeroEtiquetaIda,
        public readonly ?string $idCliente = null,
        public readonly ?float $valorDeclarado = null,
        public readonly ?string $descricao = null,
        public readonly ?ChecklistTipo $checklist = null,
        public readonly array $documentos = [],
        public readonly array $produtos = [],
        public readonly ?string $observacao = null,
    ) {
    }
}
