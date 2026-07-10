<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial;

final class Manager
{
    public function __construct(
        private readonly TflSocial $social = new TflSocial()
    ) {
    }

    public function social(): TflSocial
    {
        return $this->social;
    }
}
