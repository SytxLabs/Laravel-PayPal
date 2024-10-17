<?php

namespace SytxLabs\PayPal\Models;

use PaypalServerSDKLib\Models\Address;
use PaypalServerSDKLib\Models\Builders\AddressBuilder;
use PaypalServerSDKLib\Models\Builders\ShippingDetailsBuilder;
use PaypalServerSDKLib\Models\PhoneNumberWithCountryCode;
use PaypalServerSDKLib\Models\ShippingDetails;
use SytxLabs\PayPal\Enums\PayPalFulfillmentType;

class PayeeShippingDetail
{
    public ?Address $address = null;
    public ?PhoneNumberWithCountryCode $phoneNumber = null;

    public function __construct(public ?string $name, private ?PayPalFulfillmentType $type = null)
    {
        if ($this->type === PayPalFulfillmentType::Digital) {
            $this->type = null;
        }
    }

    public function setAddress(?AddressBuilder $address): self
    {
        $this->address = $address?->build();
        return $this;
    }

    public function setPhoneNumber(string $countryCode, string $number): self
    {
        $countryCode = preg_replace('/\D/', '', $countryCode);
        $number = preg_replace('/\D/', '', $number);
        $this->phoneNumber = new PhoneNumberWithCountryCode($countryCode, $number);
        return $this;
    }

    public function build(): ShippingDetails
    {
        return ShippingDetailsBuilder::init()->name($this->name)
            ->type($this->type->value)
            ->address($this->address)
            ->phoneNumber($this->phoneNumber)
            ->build();
    }
}
