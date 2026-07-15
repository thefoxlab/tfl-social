<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Database\Migrations;

use CodeIgniter\Database\Migration;
use TheFoxLab\TflSocial\Entities\Account;
use TheFoxLab\TflSocial\Entities\Connection;
use TheFoxLab\TflSocial\Entities\Post;

class CreateSocialTables extends Migration
{
    public function up(): void
    {
        $this->createSocialAccountTable();
        $this->createSocialConnectionTable();
        $this->createSocialPostTable();
        $this->createSocialMediaTable();
        $this->createSocialSyncTable();
    }

    public function down(): void
    {
        $this->forge->dropTable('social_sync', true);
        $this->forge->dropTable('social_media', true);
        $this->forge->dropTable('social_post', true);
        $this->forge->dropTable('social_connection', true);
        $this->forge->dropTable('social_account', true);
    }

    private function createSocialAccountTable(): void
    {
        $this->forge->addField([
            'social_account_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => Account::STATUS_ACTIVE,
            ],
            'metadata' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('social_account_id', true);
        $this->forge->addKey('status');
        $this->forge->createTable('social_account', true);
    }

    private function createSocialConnectionTable(): void
    {
        $this->forge->addField([
            'social_connection_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'social_account_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null' => true,
            ],
            'parent_connection_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null' => true,
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 191,
            ],
            'external_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'access_token' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'refresh_token' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'token_expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'permissions' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => Connection::STATUS_ACTIVE,
            ],
            'connected_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_synced_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'metadata' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('social_connection_id', true);
        $this->forge->addKey('social_account_id');
        $this->forge->addKey('parent_connection_id');
        $this->forge->addKey('provider');
        $this->forge->addKey('status');
        $this->forge->addUniqueKey(['provider', 'external_id']);
        $this->forge->addForeignKey('social_account_id', 'social_account', 'social_account_id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey(
            'parent_connection_id',
            'social_connection',
            'social_connection_id',
            'CASCADE',
            'SET NULL'
        );
        $this->forge->createTable('social_connection', true);
    }

    private function createSocialPostTable(): void
    {
        $this->forge->addField([
            'social_post_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'social_connection_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 191,
            ],
            'parent_external_id' => [
                'type' => 'VARCHAR',
                'constraint' => 191,
                'null' => true,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'caption' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'permalink' => [
                'type' => 'VARCHAR',
                'constraint' => 2048,
                'null' => true,
            ],
            'published_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'sync_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'metrics' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'raw_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => Post::STATUS_ACTIVE,
            ],
            'created_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('social_post_id', true);
        $this->forge->addKey('social_connection_id');
        $this->forge->addKey('provider');
        $this->forge->addKey('external_id');
        $this->forge->addKey('parent_external_id');
        $this->forge->addKey('published_at');
        $this->forge->addKey('sync_time');
        $this->forge->addKey('status');
        $this->forge->addUniqueKey(['social_connection_id', 'external_id']);
        $this->forge->addForeignKey(
            'social_connection_id',
            'social_connection',
            'social_connection_id',
            'CASCADE',
            'CASCADE'
        );
        $this->forge->createTable('social_post', true);
    }

    private function createSocialMediaTable(): void
    {
        $this->forge->addField([
            'social_media_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'social_post_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'url' => [
                'type' => 'VARCHAR',
                'constraint' => 2048,
                'null' => true,
            ],
            'thumbnail_url' => [
                'type' => 'VARCHAR',
                'constraint' => 2048,
                'null' => true,
            ],
            'alt_text' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
            ],
            'metadata' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('social_media_id', true);
        $this->forge->addKey('social_post_id');
        $this->forge->addForeignKey('social_post_id', 'social_post', 'social_post_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('social_media', true);
    }

    private function createSocialSyncTable(): void
    {
        $this->forge->addField([
            'social_sync_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'social_account_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null' => true,
            ],
            'social_connection_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null' => true,
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'pending',
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'finished_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'items_created' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
            ],
            'items_updated' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
            ],
            'items_failed' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
            ],
            'message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'raw_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('social_sync_id', true);
        $this->forge->addKey('social_account_id');
        $this->forge->addKey('social_connection_id');
        $this->forge->addKey('provider');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('social_account_id', 'social_account', 'social_account_id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey(
            'social_connection_id',
            'social_connection',
            'social_connection_id',
            'CASCADE',
            'SET NULL'
        );
        $this->forge->createTable('social_sync', true);
    }
}
