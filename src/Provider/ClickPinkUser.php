<?php

namespace Azuracom\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class ClickPinkUser implements ResourceOwnerInterface
{

    public function __construct(protected array $data) {}

    public function getId(): ?int
    {
        return $this->data['id'];
    }

    public function getEmail(): ?string
    {
        return $this->data['email'];
    }

    public function getFirstname(): ?string
    {
        return $this->data['firstname'];
    }

    public function getLastname(): ?string
    {
        return $this->data['lastname'];
    }

    public function getLocale(): ?string
    {
        return $this->data['locale'];
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
