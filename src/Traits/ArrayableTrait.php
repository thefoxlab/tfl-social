<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Traits;

use TheFoxLab\TflSocial\Contracts\Arrayable;

trait ArrayableTrait
{
    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function toArray(): array
    {
        return $this->arrayableValue(get_object_vars($this));
    }

    private function arrayableValue(mixed $value): mixed
    {
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->arrayableValue($item);
            }
        }

        return $value;
    }
}
