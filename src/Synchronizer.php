<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial;

use TheFoxLab\TflSocial\Contracts\SynchronizerInterface;

final class Synchronizer implements SynchronizerInterface
{
    private ?int $account = null;

    public function account(int $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function all(): void
    {
        $this->account = null;
    }

    public function run(): void
    {
    }
}
