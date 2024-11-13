<?php

namespace SytxLabs\PayPal\Exception;

use Exception;
use PaypalServerSdkLib\Http\ApiResponse;

class CaptureOrderException extends Exception
{
    protected ApiResponse $response;

    public function __construct(string $message, ApiResponse $response)
    {
        $this->response = $response;
        parent::__construct($message);
    }
}
