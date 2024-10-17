<?php

namespace SytxLabs\PayPal\Models;

class Payee extends \PaypalServerSDKLib\Models\Payee
{
    public ?string $referenceId;
    public ?PayeeShippingDetail $shippingDetail;

    public function __construct(?string $email, ?string $merchantId = null, ?string $referenceId = null, ?PayeeShippingDetail $shippingDetail = null)
    {
        $this->setEmailAddress($email);
        $this->setMerchantId($merchantId);
        $this->referenceId = $referenceId;
        $this->shippingDetail = $shippingDetail;
    }
}