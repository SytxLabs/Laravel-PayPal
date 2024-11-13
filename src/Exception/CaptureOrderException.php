<?php

namespace SytxLabs\PayPal\Exception;

use Exception;
use PaypalServerSDKLib\Http\ApiResponse;

class CaptureOrderException extends Exception
{
    protected ApiResponse $response;

    public function __construct(string $message, ApiResponse $response)
    {
        $this->response = $response;
        parent::__construct($message);
    }

    public function getResponse(): array
    {
        return [
            'status' => $this->response->getStatusCode(),
            'body' => $this->response->getBody(),
            'headers' => $this->response->getHeaders(),
            'reason' => $this->response->getReasonPhrase(),
            'result' => $this->response->getResult(),
        ];
    }
}
