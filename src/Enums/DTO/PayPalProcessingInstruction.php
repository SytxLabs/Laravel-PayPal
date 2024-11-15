<?php

namespace SytxLabs\PayPal\Enums\DTO;

enum PayPalProcessingInstruction: string
{
    case ORDER_COMPLETE_ON_PAYMENT_APPROVAL = 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL';
    case NO_INSTRUCTION = 'NO_INSTRUCTION';
}
