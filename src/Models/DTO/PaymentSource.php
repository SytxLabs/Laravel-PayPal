<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use stdClass;
use SytxLabs\PayPal\Models\DTO\PaymentSource\ApplePay;
use SytxLabs\PayPal\Models\DTO\PaymentSource\BancontactPayment;
use SytxLabs\PayPal\Models\DTO\PaymentSource\BLIKPayment;
use SytxLabs\PayPal\Models\DTO\PaymentSource\Card;
use SytxLabs\PayPal\Models\DTO\PaymentSource\GeneralPayment;
use SytxLabs\PayPal\Models\DTO\PaymentSource\GeneralPaymentWithEmail;
use SytxLabs\PayPal\Models\DTO\PaymentSource\GooglePay;
use SytxLabs\PayPal\Models\DTO\PaymentSource\IDEALPayment;
use SytxLabs\PayPal\Models\DTO\PaymentSource\PayPalWallet;
use SytxLabs\PayPal\Models\DTO\PaymentSource\Token;
use SytxLabs\PayPal\Models\DTO\PaymentSource\VenmoWallet;

class PaymentSource implements JsonSerializable
{
    private ?Card $card;
    private ?Token $token;
    private ?PayPalWallet $paypal;
    private ?BancontactPayment $bancontact;
    private ?BLIKPayment $blik;
    private ?GeneralPayment $eps;
    private ?GeneralPayment $giropay;
    private ?IDEALPayment $ideal;
    private ?GeneralPayment $mybank;
    private ?GeneralPaymentWithEmail $p24;
    private ?GeneralPayment $sofort;
    private ?GeneralPayment $trustly;
    private ?ApplePay $applePay;
    private ?GooglePay $googlePay;
    private ?VenmoWallet $venmo;

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(?Card $card): self
    {
        $this->card = $card;
        return $this;
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }

    public function setToken(?Token $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getPaypal(): ?PayPalWallet
    {
        return $this->paypal;
    }

    public function setPaypal(?PayPalWallet $paypal): self
    {
        $this->paypal = $paypal;
        return $this;
    }

    public function getBancontact(): ?BancontactPayment
    {
        return $this->bancontact;
    }

    public function setBancontact(?BancontactPayment $bancontact): self
    {
        $this->bancontact = $bancontact;
        return $this;
    }

    public function getBlik(): ?BLIKPayment
    {
        return $this->blik;
    }

    public function setBlik(?BLIKPayment $blik): self
    {
        $this->blik = $blik;
        return $this;
    }

    public function getEps(): ?GeneralPayment
    {
        return $this->eps;
    }

    public function setEps(?GeneralPayment $eps): self
    {
        $this->eps = $eps;
        return $this;
    }

    public function getGiropay(): ?GeneralPayment
    {
        return $this->giropay;
    }

    public function setGiropay(?GeneralPayment $giropay): self
    {
        $this->giropay = $giropay;
        return $this;
    }

    public function getIdeal(): ?IDEALPayment
    {
        return $this->ideal;
    }

    public function setIdeal(?IDEALPayment $ideal): self
    {
        $this->ideal = $ideal;
        return $this;
    }

    public function getMybank(): ?GeneralPayment
    {
        return $this->mybank;
    }

    public function setMybank(?GeneralPayment $mybank): self
    {
        $this->mybank = $mybank;
        return $this;
    }

    public function getP24(): ?GeneralPaymentWithEmail
    {
        return $this->p24;
    }

    public function setP24(?GeneralPaymentWithEmail $p24): self
    {
        $this->p24 = $p24;
        return $this;
    }

    public function getSofort(): ?GeneralPayment
    {
        return $this->sofort;
    }

    public function setSofort(?GeneralPayment $sofort): self
    {
        $this->sofort = $sofort;
        return $this;
    }

    public function getTrustly(): ?GeneralPayment
    {
        return $this->trustly;
    }

    public function setTrustly(?GeneralPayment $trustly): self
    {
        $this->trustly = $trustly;
        return $this;
    }

    public function getApplePay(): ?ApplePay
    {
        return $this->applePay;
    }

    public function setApplePay(?ApplePay $applePay): self
    {
        $this->applePay = $applePay;
        return $this;
    }

    public function getGooglePay(): ?GooglePay
    {
        return $this->googlePay;
    }

    public function setGooglePay(?GooglePay $googlePay): self
    {
        $this->googlePay = $googlePay;
        return $this;
    }

    public function getVenmo(): ?VenmoWallet
    {
        return $this->venmo;
    }

    public function setVenmo(?VenmoWallet $venmo): self
    {
        $this->venmo = $venmo;
        return $this;
    }

    public function jsonSerialize(bool $asArrayWhenEmpty = false): array|stdClass
    {
        $json = [];
        if (isset($this->card)) {
            $json['card'] = $this->card;
        }
        if (isset($this->token)) {
            $json['token'] = $this->token;
        }
        if (isset($this->paypal)) {
            $json['paypal'] = $this->paypal;
        }
        if (isset($this->bancontact)) {
            $json['bancontact'] = $this->bancontact;
        }
        if (isset($this->blik)) {
            $json['blik'] = $this->blik;
        }
        if (isset($this->eps)) {
            $json['eps'] = $this->eps;
        }
        if (isset($this->giropay)) {
            $json['giropay'] = $this->giropay;
        }
        if (isset($this->ideal)) {
            $json['ideal'] = $this->ideal;
        }
        if (isset($this->mybank)) {
            $json['mybank'] = $this->mybank;
        }
        if (isset($this->p24)) {
            $json['p24'] = $this->p24;
        }
        if (isset($this->sofort)) {
            $json['sofort'] = $this->sofort;
        }
        if (isset($this->trustly)) {
            $json['trustly'] = $this->trustly;
        }
        if (isset($this->applePay)) {
            $json['apple_pay'] = $this->applePay;
        }
        if (isset($this->googlePay)) {
            $json['google_pay'] = $this->googlePay;
        }
        if (isset($this->venmo)) {
            $json['venmo'] = $this->venmo;
        }

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
