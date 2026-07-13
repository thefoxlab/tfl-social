<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial;

use TheFoxLab\TflSocial\Contracts\ProviderManagerInterface;
use TheFoxLab\TflSocial\Providers\Facebook\FacebookProvider;
use TheFoxLab\TflSocial\Providers\ProviderInterface;
use TheFoxLab\TflSocial\Providers\ProviderRegistry;

final class ProviderManager implements ProviderManagerInterface
{
    private ProviderRegistry $registry;

    public function __construct(?ProviderRegistry $registry = null)
    {
        $this->registry = $registry ?? $this->defaultRegistry();
    }

    public function register(ProviderInterface $provider): self
    {
        $this->registry->register($provider);

        return $this;
    }

    public function resolve(string $name): ProviderInterface
    {
        return $this->registry->resolve($name);
    }

    /**
     * @return list<string>
     */
    public function available(): array
    {
        return $this->registry->available();
    }

    /**
     * @return array<string, ProviderInterface>
     */
    public function all(): array
    {
        return $this->registry->all();
    }

    private function defaultRegistry(): ProviderRegistry
    {
        return (new ProviderRegistry())
            ->register(new FacebookProvider());
    }
}
