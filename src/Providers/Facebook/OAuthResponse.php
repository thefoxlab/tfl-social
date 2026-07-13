<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Facebook;

use DateTimeImmutable;
use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;
use TheFoxLab\TflSocial\Traits\ArrayableTrait;

final class OAuthResponse implements Arrayable, JsonSerializable
{
    use ArrayableTrait;

    /**
     * @param list<string> $scopes
     */
    public function __construct(
        private readonly ?string $accessToken = null,
        private readonly ?string $tokenType = null,
        private readonly ?int $expiresIn = null,
        private readonly ?DateTimeImmutable $expiresAt = null,
        private readonly array $scopes = []
    ) {
    }

    public function accessToken(): ?string
    {
        return $this->accessToken;
    }

    public function tokenType(): ?string
    {
        return $this->tokenType;
    }

    public function expiresIn(): ?int
    {
        return $this->expiresIn;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
