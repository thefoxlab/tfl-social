<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers;

abstract class AbstractProvider implements ProviderInterface
{
    public function __construct(
        private readonly string $name
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }
}
