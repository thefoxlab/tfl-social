<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Entities;

use CodeIgniter\Entity\Entity;
use JsonSerializable;
use TheFoxLab\TflSocial\Contracts\Arrayable;
class Sync extends Entity implements Arrayable, JsonSerializable
{
    public const STATUS_RUNNING = 'running';
    
    public const STATUS_FINISHED = 'finished';
    
    public const STATUS_FAILED = 'failed';
    
    protected $attributes = [
        'social_sync_id' => null,
        'social_connection_id' => null,
        'status' => null,
        'started_at' => null,
        'finished_at' => null,
        'items_created' => null,
        'items_updated' => null,
        'items_failed' => null,
        'message' => null,
        'created_time' => null,
    ];
    
    protected $casts = [
        'social_sync_id' => '?integer',
        'social_connection_id' => '?integer',
    ];
    
    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
