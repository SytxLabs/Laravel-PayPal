<?php

namespace SytxLabs\PayPal\Enums\DTO;

enum LinkHTTPMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case CONNECT = 'CONNECT';
    case OPTIONS = 'OPTIONS';
    case PATCH = 'PATCH';
}
