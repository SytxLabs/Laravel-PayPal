<?php

namespace SytxLabs\PayPal\Models;

use Illuminate\Support\Str;

class Payee extends \PaypalServerSDKLib\Models\Payee
{
    public ?string $referenceId;
    public ?PayeeShippingDetail $shippingDetail;

    public function __construct(?string $email, ?string $merchantId = null, ?string $referenceId = null, ?PayeeShippingDetail $shippingDetail = null)
    {
        if ($email !== null && preg_match('/^[\w-]+(?:\.[\w-]+)*@(?:[\w-]+\.)+[a-zA-Z]{2,7}$/', $email) !== 1) {
            throw new \InvalidArgumentException('Invalid email address');
        }
        $this->setEmailAddress($email);
        $this->setMerchantId($merchantId);

        $this->referenceId = Str::snake($referenceId);
        $this->shippingDetail = $shippingDetail;
    }
}
