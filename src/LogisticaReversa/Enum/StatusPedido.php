<?php

namespace Hardsystem\Correios\LogisticaReversa\Enum;

enum StatusPedido: int
{
    case AguardandoObjetoEntrega = 0;
    case AColetar = 1;
    case Coletando = 3;
    case PrimeiraTentativaColeta = 4;
    case SegundaTentativaColetaCancelada = 5;
    case Coletado = 6;
    case Entregue = 7;
    case ColetaCancelada = 8;
    case DesistenciaCliente = 9;
    case ObjetoNaoColetado = 10;
    case ObjetoSinistrado = 11;
    case ColetadoComDH = 15;
    case ColetaTransferida = 35;
    case Reiterado = 53;
    case AguardandoObjetoNaAgencia = 55;
    case PrazoUtilizacaoExpirado = 57;
    case EntregueNaAgencia = 60;
    case TransformadoEmEticket = 65;
    case AutorizacaoPostagemCancelada = 68;
    case ObjetoRetiradoNaoColetado = 69;
    case ObjetoInseridoNaoColetado = 70;
    case AguardandoConfirmacaoCliente = 80;
    case Ativo = 81;
    case SuspensoTemporariamentePeloCliente = 82;
    case BloqueadoTemporariamentePelosCorreios = 83;
    case PrazoUtilizacaoExpiradoSCPP = 84;
    case LimiteAtendimentosAlcancado = 85;
    case CanceladoDefinitivamente = 86;
    case PendenteAutorizacao = 90;
    case AutorizadoAguardandoPostagem = 91;
    case AutorizadoEPostado = 92;
    case NaoAutorizado = 93;
    case PrazoExpirado = 94;

    public function descricao(): string
    {
        return match ($this) {
            self::AguardandoObjetoEntrega => 'Aguardando Objeto de Entrega',
            self::AColetar => 'A Coletar',
            self::Coletando => 'Coletando',
            self::PrimeiraTentativaColeta => '1a Tentativa de Coleta',
            self::SegundaTentativaColetaCancelada => '2a Tentativa / Coleta Cancelada',
            self::Coletado => 'Coletado',
            self::Entregue => 'Entregue',
            self::ColetaCancelada => 'Coleta Cancelada',
            self::DesistenciaCliente => 'Desistência do Cliente ECT',
            self::ObjetoNaoColetado => 'Objeto não coletado',
            self::ObjetoSinistrado => 'Objeto Sinistrado',
            self::ColetadoComDH => 'Coletado com DH',
            self::ColetaTransferida => 'Coleta Transferida',
            self::Reiterado => 'Reiterado',
            self::AguardandoObjetoNaAgencia => 'Aguardando Objeto na Agência',
            self::PrazoUtilizacaoExpirado => 'Prazo de Utilização Expirado',
            self::EntregueNaAgencia => 'Entregue na Agência',
            self::TransformadoEmEticket => 'Transformado em e-ticket',
            self::AutorizacaoPostagemCancelada => 'Autorização de Postagem Cancelada',
            self::ObjetoRetiradoNaoColetado => 'Objeto Retirado - Rel. Obj. Não Coletado',
            self::ObjetoInseridoNaoColetado => 'Objeto Inserido - Rel. Obj. Não Coletado',
            self::AguardandoConfirmacaoCliente => 'Aguardando confirmação cliente',
            self::Ativo => 'Ativo',
            self::SuspensoTemporariamentePeloCliente => 'Suspenso Temporariamente pelo Cliente',
            self::BloqueadoTemporariamentePelosCorreios => 'Bloqueado Temporariamente pelos Correios',
            self::PrazoUtilizacaoExpiradoSCPP => 'Prazo de utilização expirado',
            self::LimiteAtendimentosAlcancado => 'Limite atendimentos permitidos alcançado',
            self::CanceladoDefinitivamente => 'Cancelado Definitivamente',
            self::PendenteAutorizacao => 'Pendente de Autorização',
            self::AutorizadoAguardandoPostagem => 'Autorizado Aguardando Postagem',
            self::AutorizadoEPostado => 'Autorizado e Postado',
            self::NaoAutorizado => 'Não Autorizado',
            self::PrazoExpirado => 'Prazo Expirado',
        };
    }
}
