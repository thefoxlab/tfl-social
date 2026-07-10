<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers;

use InvalidArgumentException;

final class ProviderRegistry
{
    /**
     * @var array<string, ProviderInterface>
     */
    private array $providers = [];

    public function register(ProviderInterface $provider): self
    {
        $name = $this->normalizeName($provider->name());

        if ($name === '') {
            throw new InvalidArgumentException('Provider name cannot be empty.');
        }

        if (isset($this->providers[$name])) {
            throw new InvalidArgumentException(sprintf('Provider [%s] is already registered.', $name));
        }

        $this->providers[$name] = $provider;

        return $this;
    }

    public function resolve(string $name): ProviderInterface
    {
        $name = $this->normalizeName($name);

        if (! isset($this->providers[$name])) {
            throw new InvalidArgumentException(sprintf('Provider [%s] is not registered.', $name));
        }

        return $this->providers[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->providers[$this->normalizeName($name)]);
    }

    /**
     * @return list<string>
     */
    public function available(): array
    {
        return array_keys($this->providers);
    }

    /**
     * @return array<string, ProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }

    private function normalizeName(string $name): string
    {
        return strtolower(trim($name));
    }
}
