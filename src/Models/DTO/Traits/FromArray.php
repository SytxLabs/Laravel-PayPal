<?php

namespace SytxLabs\PayPal\Models\DTO\Traits;

use ReflectionObject;

trait FromArray
{
    public static function fromArray(array $data): static
    {
        $instance = new static();
        $reflection = new ReflectionObject($instance);
        foreach ($reflection->getProperties() as $method) {
            $attributes = $method->getAttributes(ArrayMappingAttribute::class);
            if (count($attributes) < 1) {
                $instance->{$method->getName()} ??= null;
                continue;
            }
            $attribute = $attributes[0]->newInstance();
            $instance->setValueFromArray($attribute->key, $data, $attribute, $method);
        }

        return $instance;
    }

    private function setValueFromArray(string $key, array $data, $attribute, $method): void
    {
        $keyExploded = explode('.', $key);
        if (!array_key_exists($keyExploded[0], $data)) {
            $this->{$method->getName()} ??= null;
            return;
        }
        if (count($keyExploded) > 1) {
            $data = $data[$keyExploded[0]];
            $key = implode('.', array_slice($keyExploded, 1));
            $this->setValueFromArray($key, $data, $attribute, $method);
            return;
        }
        if ($attribute->class !== null) {
            if ($attribute->isArray) {
                $this->{$method->getName()} = array_filter(array_map(static function ($item) use ($attribute) {
                    if (enum_exists($attribute->class)) {
                        return $attribute->class::tryFrom($item);
                    }
                    return $attribute->class::fromArray($item);
                }, $data[$key]));
                return;
            }
            if (enum_exists($attribute->class)) {
                $this->{$method->getName()} = $attribute->class::tryFrom($data[$key]);
            } else {
                $this->{$method->getName()} = $attribute->class::fromArray($data[$key]);
            }
        } else {
            $this->{$method->getName()} = $data[$key];
        }
    }
}
