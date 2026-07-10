<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial;

use TheFoxLab\TflSocial\Contracts\ConnectorInterface;

final class Connector implements ConnectorInterface
{
    private ?string $provider = null;

    public function provider(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }
}
