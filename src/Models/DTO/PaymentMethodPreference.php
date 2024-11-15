<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Enums\DTO\PaymentSource\PayPalPayeePaymentMethodPreference;
use SytxLabs\PayPal\Enums\DTO\PayPalStandardEntryClassCode;

class PaymentMethodPreference implements JsonSerializable
{
    private ?PayPalPayeePaymentMethodPreference $payeePreferred = PayPalPayeePaymentMethodPreference::UNRESTRICTED;
    private ?PayPalStandardEntryClassCode $standardEntryClassCode = PayPalStandardEntryClassCode::WEB;

    public function getPayeePreferred(): ?PayPalPayeePaymentMethodPreference
    {
        return $this->payeePreferred;
    }

    public function setPayeePreferred(?PayPalPayeePaymentMethodPreference $payeePreferred): self
    {
        $this->payeePreferred = $payeePreferred;
        return $this;
    }

    public function getStandardEntryClassCode(): ?PayPalStandardEntryClassCode
    {
        return $this->standardEntryClassCode;
    }

    public function setStandardEntryClassCode(?PayPalStandardEntryClassCode $standardEntryClassCode): self
    {
        $this->standardEntryClassCode = $standardEntryClassCode;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->payeePreferred)) {
            $json['payee_preferred'] = $this->payeePreferred->value;
        }
        if (isset($this->standardEntryClassCode)) {
            $json['standard_entry_class_code'] = $this->standardEntryClassCode->value;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
