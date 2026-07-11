<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Instagram;

final class BusinessAccount
{
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
}
