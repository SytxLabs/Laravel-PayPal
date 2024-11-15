<?php

namespace SytxLabs\PayPal\Models\DTO;

use JsonSerializable;
use SytxLabs\PayPal\Enums\DTO\LinkHTTPMethod;

class LinkDescription implements JsonSerializable
{
    private ?LinkHTTPMethod $method;

    public function __construct(private string $href, private string $rel)
    {
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

    public static function fromArray(array $data): self
    {
        $link = new self($data['href'], $data['rel']);
        if (isset($data['method'])) {
            $link->setMethod(LinkHTTPMethod::tryFrom($data['method']));
        }
        return $link;
    }
}
