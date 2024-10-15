<?php

namespace SytxLabs\PayPal\Services;

use Exception;
use GuzzleHttp\Utils;
use Illuminate\Support\Str as IlluminateStr;

class Str extends IlluminateStr
{
    public static function isJson($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (function_exists('json_validate')) {
            return json_validate($value);
        }
        try {
            Utils::jsonDecode($value, false, 512, 4194304);
        } catch (Exception) {
            return false;
        }
        return true;
    }
}
