<?php

namespace SytxLabs\PayPal\Enums\DTO;

enum PayPalTaxIdType: string
{
    case BR_CPF = 'BR_CPF';
    case BR_CNPJ = 'BR_CNPJ';
}
