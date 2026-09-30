<?php

namespace SytxLabs\PayPal\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Stand-in for an application model (e.g. User) that owns a subscription.
 */
class FakeSubscriber extends Model
{
    protected $table = 'fake_subscribers';
    protected $guarded = [];
    public $timestamps = false;
}
