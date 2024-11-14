<?php

namespace SytxLabs\PayPal\Models;

use PaypalServerSdkLib\Models\Address;
use PaypalServerSdkLib\Models\Builders\AddressBuilder;
use PaypalServerSdkLib\Models\Builders\ShippingDetailsBuilder;
use PaypalServerSdkLib\Models\Builders\ShippingNameBuilder;
use PaypalServerSdkLib\Models\PhoneNumberWithCountryCode;
use PaypalServerSdkLib\Models\ShippingDetails;
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
        return ShippingDetailsBuilder::init()->name(ShippingNameBuilder::init()->fullName($this->name)->build())
            ->type($this->type->value)
            ->address($this->address)
            ->phoneNumber($this->phoneNumber)
            ->build();
    }
}
