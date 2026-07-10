<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Contracts;

use TheFoxLab\TflSocial\Providers\ProviderInterface;

interface ProviderManagerInterface
{
    /**
     * @return array<string, ProviderInterface>
     */
    public function all(): array;
}
