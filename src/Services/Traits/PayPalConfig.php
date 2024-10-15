<?php

namespace SytxLabs\PayPal\Services\Traits;

use RuntimeException;
use SytxLabs\PayPal\Enums\PayPalModeType;

trait PayPalConfig
{
    private array $config;
    public PayPalModeType $mode = PayPalModeType::Sandbox;

    protected string $currency = 'USD';

    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    private function setConfig(array $config): void
    {
        $configFile = function_exists('config') ? config('paypal') : [];
        $this->config = array_replace_recursive($configFile, $config);
        if (empty($this->config)) {
            throw new RuntimeException('PayPal configuration is not set.');
        }
        $mode = PayPalModeType::tryFrom(strtolower($this->config['mode'] ?? ''));
        if ($mode === null) {
            throw new RuntimeException('Invalid PayPal mode.');
        }
        $this->mode = $mode;
        if (empty($this->config['client_id']) || empty($this->config['client_secret'])) {
            if (empty($this->config[$this->mode->value])) {
                throw new RuntimeException("{$this->mode->value} configuration missing from the provided configuration.");
            }
            $config_params = ['client_id', 'client_secret'];
            foreach ($config_params as $item) {
                if (empty($this->config[$this->mode->value][$item])) {
                    throw new RuntimeException("{$item} missing from the provided configuration. Please add your application {$item}.");
                }
            }
            collect($this->config[$this->mode->value])->map(function ($value, $key) {
                $this->config[$key] = $value;
            });
        }
    }
}
