<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Contracts;

interface ConnectorInterface
{
    public function provider(string $provider): self;
}
