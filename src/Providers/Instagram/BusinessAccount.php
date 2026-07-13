<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Instagram;

use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;
use TheFoxLab\TflSocial\Traits\ArrayableTrait;

final class BusinessAccount implements Arrayable, JsonSerializable
{
    use ArrayableTrait;

    public function __construct(
        private readonly string $accountId,
        private readonly ?string $username = null,
        private readonly ?string $name = null,
        private readonly ?string $profilePicture = null
    ) {
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function username(): ?string
    {
        return $this->username;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function profilePicture(): ?string
    {
        return $this->profilePicture;
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
