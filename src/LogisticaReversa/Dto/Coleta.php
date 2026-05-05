<?php

namespace Hardsystem\Correios\LogisticaReversa\Dto;

use Hardsystem\Correios\LogisticaReversa\Enum\ChecklistTipo;
use Hardsystem\Correios\LogisticaReversa\Enum\DocumentoChecklist;
use Hardsystem\Correios\LogisticaReversa\Enum\TipoColeta;

final class Coleta
{
    /**
     * @param ObjetoColeta[]         $objetos    Até 10 objetos por solicitação.
     * @param Produto[]              $produtos   Embalagens solicitadas junto à coleta.
     * @param DocumentoChecklist[]   $documentos Tipos de documento quando checklist = Documento. Máx. 8.
     */
    public function __construct(
        public readonly TipoColeta $tipo,
        public readonly EnderecoRemetente $remetente,
        public readonly array $objetos,
        public readonly ?string $numeroEticket = null,
        public readonly ?string $idCliente = null,
        public readonly ?\DateTimeImmutable $dataAgendamento = null,
        public readonly ?int $diasValidade = null,
        public readonly ?string $cartaoPostagem = null,
        public readonly ?float $valorDeclarado = null,
        public readonly ?string $descricao = null,
        public readonly bool $solicitarAvisoRecebimento = false,
        public readonly ?ChecklistTipo $checklist = null,
        public readonly array $documentos = [],
        public readonly array $produtos = [],
    ) {
    }
}
