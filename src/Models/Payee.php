<?php

namespace SytxLabs\PayPal\Models;

class Payee extends \PaypalServerSDKLib\Models\Payee
{
    public ?string $referenceId;

    public function __construct(?string $email, ?string $merchantId = null, ?string $referenceId = null)
    {
        $this->setEmailAddress($email);
        $this->setMerchantId($merchantId);
        $this->referenceId = $referenceId;
    }
}
