<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Models;

use CodeIgniter\Model;
use TheFoxLab\TflSocial\Entities\Sync;

class SyncModel extends Model
{
    protected $table = 'social_sync';
    
    protected $primaryKey = 'social_sync_id';
    
    protected $returnType = Sync::class;
    
    protected $allowedFields = [
        'social_connection_id',
        'status',
        'started_at',
        'finished_at',
        'items_created',
        'items_updated',
        'items_failed',
        'message',
    ];
    
    protected $useTimestamps = true;
    
    protected $dateFormat = 'datetime';
    
    protected $createdField = 'created_time';
    
    protected $updatedField = '';
}