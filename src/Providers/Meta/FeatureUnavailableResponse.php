<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Meta;

final class FeatureUnavailableResponse
{
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
}
