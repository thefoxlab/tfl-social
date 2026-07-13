<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Contracts;

interface Arrayable
{
    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function toArray(): array;
}
