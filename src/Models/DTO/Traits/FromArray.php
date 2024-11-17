<?php

namespace SytxLabs\PayPal\Models\DTO\Traits;

use ReflectionObject;

trait FromArray
{
    public static function fromArray(array $data): static
    {
        return self::fromArrayInternal(new static(), $data);
    }

    private static function fromArrayInternal(self $instance, array $data): static
    {
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
                    return self::castArrayValue($attribute->class, $item);
                }, $data[$key]));
                return;
            }
            $this->{$method->getName()} = self::castArrayValue($attribute->class, $data[$key]);
        } else {
            $this->{$method->getName()} = $data[$key];
        }
    }

    private static function castArrayValue($class, $value): mixed
    {
        if ($value instanceof $class || $value === null) {
            return $value;
        }
        if (enum_exists($class)) {
            return $class::tryFrom($value);
        }
        if (method_exists($class, 'fromArray')) {
            return $class::fromArray($value);
        }
        return $value;
    }

    public function toArray(): array
    {
        $reflection = new ReflectionObject($this);
        $array = [];
        foreach ($reflection->getProperties() as $method) {
            $attributes = $method->getAttributes(ArrayMappingAttribute::class);
            if (!isset($this->{$method->getName()})) {
                continue;
            }
            if (count($attributes) < 1) {
                $array[$method->getName()] = $this->{$method->getName()};
                continue;
            }
            $attribute = $attributes[0]->newInstance();
            $array[$attribute->key] = $this->valueToArray($attribute, $method);
        }

        return $array;
    }

    private function valueToArray($attribute, $method): mixed
    {
        if ($attribute->class !== null) {
            if ($attribute->isArray) {
                return array_map(static function ($item) use ($attribute) {
                    if (enum_exists($attribute->class)) {
                        return $item->value;
                    }
                    if (method_exists($item, 'toArray')) {
                        return $item->toArray();
                    }
                    return $item;
                }, $this->{$method->getName()});
            }
            if (enum_exists($attribute->class)) {
                return $this->{$method->getName()}->value;
            }
            if (method_exists($this->{$method->getName()}, 'toArray')) {
                return $this->{$method->getName()}->toArray();
            }
            return $this->{$method->getName()};
        }
        return $this->{$method->getName()};
    }
}
