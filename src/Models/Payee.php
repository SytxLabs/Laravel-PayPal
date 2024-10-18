<?php

namespace SytxLabs\PayPal\Models;

use Illuminate\Support\Str;
use InvalidArgumentException;

class Payee extends \PaypalServerSDKLib\Models\Payee
{
    public ?string $referenceId;
    public ?PayeeShippingDetail $shippingDetail;

    public function __construct(?string $email, ?string $merchantId = null, ?string $referenceId = null, ?PayeeShippingDetail $shippingDetail = null)
    {
        if ($email !== null && preg_match('/^[\w-]+(?:\.[\w-]+)*@(?:[\w-]+\.)+[a-zA-Z]{2,7}$/', $email) !== 1) {
            throw new InvalidArgumentException('Invalid email address');
        }
        if ($merchantId !== null && preg_match('/^[2-9A-HJ-NP-Z]/', $merchantId) !== 1) {
            throw new InvalidArgumentException('Invalid merchant ID');
        }
        $this->setEmailAddress($email);
        $this->setMerchantId($merchantId);

        $this->referenceId = Str::snake($referenceId);
        $this->shippingDetail = $shippingDetail;
    }
}
