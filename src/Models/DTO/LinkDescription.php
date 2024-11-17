<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\LinkHTTPMethod;
use SytxLabs\PayPal\Models\DTO\Traits\ArrayMappingAttribute;
use SytxLabs\PayPal\Models\DTO\Traits\FromArray;

class LinkDescription implements JsonSerializable
{
    use FromArray;

    #[ArrayMappingAttribute('method', LinkHTTPMethod::class)]
    private ?LinkHTTPMethod $method;
    #[ArrayMappingAttribute('href')]
    private string $href;
    #[ArrayMappingAttribute('rel')]
    private string $rel;

    public function __construct(string $href, string $rel)
    {
        $this->href = $href;
        $this->rel = $rel;
    }

    public function getHref(): string
    {
        return $this->href;
    }

    public function setHref(string $href): self
    {
        $this->href = $href;
        return $this;
    }

    public function getRel(): string
    {
        return $this->rel;
    }

    public function setRel(string $rel): self
    {
        $this->rel = $rel;
        return $this;
    }

    public function getMethod(): ?LinkHTTPMethod
    {
        return $this->method;
    }

    public function setMethod(?LinkHTTPMethod $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function jsonSerialize(): array
    {
        $json = [
            'href' => $this->href,
            'rel' => $this->rel,
        ];
        if (isset($this->method)) {
            $json['method'] = $this->method;
        }

        return $json;
    }

    public static function fromArray(array $data): static
    {
        return self::fromArrayInternal(new self($data['href'], $data['rel']), $data);
    }
}
