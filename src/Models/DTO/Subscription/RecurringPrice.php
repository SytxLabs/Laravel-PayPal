<?php

namespace SytxLabs\PayPal\Models\DTO\Subscription;

use SytxLabs\PayPal\Enums\DTO\Subscription\IntervalUnit;
use SytxLabs\PayPal\Enums\DTO\Subscription\TenureType;
use SytxLabs\PayPal\Models\DTO\Money;

/**
 * Value object describing a single recurring price (one plan billing cycle).
 * Multiple of these can be added to a subscription to build tiered/trial + regular plans.
 */
class RecurringPrice
{
    public function __construct(
        public Money $price,
        public IntervalUnit $intervalUnit,
        public int $intervalCount = 1,
        public int $totalCycles = 0,
        public TenureType $tenureType = TenureType::REGULAR,
    ) {
    }

    public function toBillingCycle(int $sequence): BillingCycle
    {
        return (new BillingCycle())
            ->setTenureType($this->tenureType)
            ->setSequence($sequence)
            ->setTotalCycles($this->totalCycles)
            ->setFrequency(new Frequency($this->intervalUnit, $this->intervalCount))
            ->setPricingScheme((new PricingScheme())->setFixedPrice($this->price));
    }
}
