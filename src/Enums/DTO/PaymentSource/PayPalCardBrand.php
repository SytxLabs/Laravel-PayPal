<?php

namespace SytxLabs\PayPal\Enums\DTO\PaymentSource;

enum PayPalCardBrand: string
{
    case VISA = 'VISA';
    case MASTERCARD = 'MASTERCARD';
    case DISCOVER = 'DISCOVER';
    case AMEX = 'AMEX';
    case SOLO = 'SOLO';
    case JCB = 'JCB';
    case STAR = 'STAR';
    case DELTA = 'DELTA';
    case SWITCH_ = 'SWITCH';
    case MAESTRO = 'MAESTRO';
    case CB_NATIONALE = 'CB_NATIONALE';
    case CONFIGOGA = 'CONFIGOGA';
    case CONFIDIS = 'CONFIDIS';
    case ELECTRON = 'ELECTRON';
    case CETELEM = 'CETELEM';
    case CHINA_UNION_PAY = 'CHINA_UNION_PAY';
    case DINERS = 'DINERS';
    case ELO = 'ELO';
    case HIPER = 'HIPER';
    case HIPERCARD = 'HIPERCARD';
    case RUPAY = 'RUPAY';
    case GE = 'GE';
    case SYNCHRONY = 'SYNCHRONY';
    case EFTPOS = 'EFTPOS';
    case UNKNOWN = 'UNKNOWN';
}
