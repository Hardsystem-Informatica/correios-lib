<?php

namespace Hardsystem\Correios\LogisticaReversa\Enum;

enum DocumentoChecklist: int
{
    case AtestadoAntecedentesCriminais = 1;
    case CPF = 2;
    case CNPJ = 3;
    case RG = 4;
    case CarteiraIdentidadeProfissional = 5;
    case CTPS = 6;
    case CarteiraAposentado = 7;
    case CNH = 8;
    case PASEP = 9;
    case PIS = 10;
    case CertidaoBatismo = 11;
    case CertidaoCasamento = 12;
    case CertidaoNascimento = 13;
    case CertidaoObito = 14;
    case CertidaoNegativaDebitos = 15;
    case CRLV = 16;
    case CertificadoReservista = 17;
    case ComprovanteMatricula = 18;
    case ComprovantePagamentoBoleto = 19;
    case ComprovantePagamentoAnuidade = 20;
    case ContaAgua = 21;
    case ContaGas = 22;
    case ContaLuz = 23;
    case ContaTelefone = 24;
    case ContratoAssinado = 25;
    case Escritura = 26;
    case ExtratoBancario = 27;
    case ExtratoBeneficios = 28;
    case ExtratoFGTS = 29;
    case FaturaCartaoCredito = 30;
    case Holerite = 31;
    case NotaFiscal = 32;
    case Passaporte = 33;
    case Procuracao = 34;
    case PropostaAssinada = 35;
    case ReciboAluguel = 36;
    case ReciboDeclaracaoImpostoRenda = 37;
    case TituloEleitor = 38;
}
