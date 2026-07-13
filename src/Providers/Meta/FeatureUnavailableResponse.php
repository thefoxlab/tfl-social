<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Meta;

use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;
use TheFoxLab\TflSocial\Traits\ArrayableTrait;

final class FeatureUnavailableResponse implements Arrayable, JsonSerializable
{
    use ArrayableTrait;

    public function __construct(
        private readonly string $feature,
        private readonly string $reason
    ) {
    }

    public function feature(): string
    {
        return $this->feature;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
