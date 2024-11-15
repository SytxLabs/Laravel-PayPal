<?php

namespace SytxLabs\PayPal\Exception;

use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;

class CaptureOrderException extends Exception
{
    protected PromiseInterface|Response $response;

    public function __construct(string $message, PromiseInterface|Response $response)
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
        ];
    }
}
