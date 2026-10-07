<?php

namespace SytxLabs\PayPal\Enums;

/**
 * Outcome of asking PayPal to verify the signature of a webhook.
 */
enum PayPalWebhookSignatureStatus: string
{
    /** PayPal confirmed the signature. */
    case Valid = 'valid';

    /** The request is not what PayPal sends, or PayPal says the signature is wrong. Answer 400, do not retry. */
    case Invalid = 'invalid';

    /** PayPal could not be asked or could not decide (outage, token, unknown webhook id). Answer 503 so PayPal delivers again. */
    case Unavailable = 'unavailable';
}
