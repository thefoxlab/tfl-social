# TFL Social Data Model

## Overview

TFL Social uses a provider-independent normalized schema to store social media accounts, connections, posts, media items, and synchronization logs in a relational database.

Applications interact with this data via Repositories and Services, ensuring direct SQL and provider-specific details remain isolated.

---

# Entity Relationships

```
social_account (1) ───< social_connection (N) [parent_connection_id self-ref]
                              │
                              ├───< social_post (N) ───< social_media (N)
                              │
                              └───< social_sync (N)
```

---

# Physical Tables & Schema Definitions

Migration file: `src/Database/Migrations/2026-07-10-000001_CreateSocialTables.php`

### 1. `social_account`

Stores logical application accounts (e.g. client, brand, lodge, website owner).

| Field | Type | Attributes | Description |
|---|---|---|---|
| `social_account_id` | INT(10) | UNSIGNED, AUTO_INCREMENT, PRIMARY KEY | Unique account identifier |
| `name` | VARCHAR(255) | NOT NULL | Account name |
| `status` | VARCHAR(50) | DEFAULT 'active', KEY | Account status (`active`) |
| `metadata` | TEXT | NULLABLE | JSON or text metadata |
| `created_time` | DATETIME | NULLABLE | Record creation timestamp |
| `updated_time` | DATETIME | NULLABLE | Record update timestamp |
| `deleted_time` | DATETIME | NULLABLE | Soft delete timestamp |

---

### 2. `social_connection`

Stores connected provider accounts (e.g. Facebook Page, Instagram Business Account).

| Field | Type | Attributes | Description |
|---|---|---|---|
| `social_connection_id` | INT(10) | UNSIGNED, AUTO_INCREMENT, PRIMARY KEY | Unique connection identifier |
| `social_account_id` | INT(10) | UNSIGNED, NULLABLE, FK(social_account) | Owner account ID |
| `parent_connection_id` | INT(10) | UNSIGNED, NULLABLE, FK(social_connection) | Parent connection ID (e.g. Facebook Page parent for Instagram) |
| `provider` | VARCHAR(50) | NOT NULL, KEY | Provider slug (`facebook`, `instagram`) |
| `external_id` | VARCHAR(191) | NOT NULL | Provider external account ID |
| `external_name` | VARCHAR(255) | NULLABLE | Display name / username |
| `access_token` | TEXT | NULLABLE | OAuth access token |
| `refresh_token` | TEXT | NULLABLE | OAuth refresh token |
| `token_expires_at` | DATETIME | NULLABLE | Token expiry timestamp |
| `permissions` | JSON | NULLABLE | Granted OAuth permissions |
| `status` | VARCHAR(50) | DEFAULT 'active', KEY | Connection status (`active`, `expired`, `inactive`, `disconnected`) |
| `connected_at` | DATETIME | NULLABLE | Connection date |
| `last_synced_at` | DATETIME | NULLABLE | Last successful synchronization timestamp |
| `metadata` | TEXT | NULLABLE | JSON metadata (category, picture, profile_picture) |
| `created_time` | DATETIME | NULLABLE | Record creation timestamp |
| `updated_time` | DATETIME | NULLABLE | Record update timestamp |
| `deleted_time` | DATETIME | NULLABLE | Soft delete timestamp |

*Indexes:*
- UNIQUE KEY on `(provider, external_id)`

---

### 3. `social_post`

Stores normalized social posts from all platforms.

| Field | Type | Attributes | Description |
|---|---|---|---|
| `social_post_id` | INT(10) | UNSIGNED, AUTO_INCREMENT, PRIMARY KEY | Unique post identifier |
| `social_connection_id` | INT(10) | UNSIGNED, NOT NULL, FK(social_connection) | Associated connection ID |
| `provider` | VARCHAR(50) | NOT NULL, KEY | Provider slug (`facebook`, `instagram`) |
| `external_id` | VARCHAR(191) | NOT NULL, KEY | External provider post/item ID |
| `parent_external_id` | VARCHAR(191) | NULLABLE, KEY | Parent post/item external ID |
| `type` | VARCHAR(50) | NULLABLE | Normalized item type (`post`, `profile`, `image`, `video`, `carousel_album`, etc.) |
| `message` | TEXT | NULLABLE | Post text content / message |
| `caption` | TEXT | NULLABLE | Alternative caption field |
| `permalink` | VARCHAR(2048) | NULLABLE | Direct URL to original social post |
| `published_at` | DATETIME | NULLABLE, KEY | Publication timestamp |
| `sync_time` | DATETIME | NULLABLE, KEY | Last UPSERT sync timestamp |
| `metrics` | TEXT | NULLABLE | JSON string storing engagement metrics (e.g. `shares`, `like_count`, `comments_count`) |
| `raw_json` | TEXT | NULLABLE | Complete original JSON payload from Graph API |
| `status` | VARCHAR(50) | DEFAULT 'active', KEY | Post status (`active`) |
| `created_time` | DATETIME | NULLABLE | Record creation timestamp |
| `updated_time` | DATETIME | NULLABLE | Record update timestamp |
| `deleted_time` | DATETIME | NULLABLE | Soft delete timestamp |

*Indexes:*
- UNIQUE KEY on `(social_connection_id, external_id)`

---

### 4. `social_media`

Stores media items (images, videos, thumbnails) associated with a post.

| Field | Type | Attributes | Description |
|---|---|---|---|
| `social_media_id` | INT(10) | UNSIGNED, AUTO_INCREMENT, PRIMARY KEY | Unique media identifier |
| `social_post_id` | INT(10) | UNSIGNED, NOT NULL, FK(social_post) | Parent post ID |
| `type` | VARCHAR(50) | NULLABLE | Media type (`image`, `video`) |
| `url` | VARCHAR(2048) | NULLABLE | Direct media asset URL |
| `thumbnail_url` | VARCHAR(2048) | NULLABLE | Video or item thumbnail URL |
| `alt_text` | VARCHAR(255) | NULLABLE | Title or alternative text |
| `sort_order` | INT(10) | UNSIGNED, DEFAULT 0 | Media sequence order within post |
| `metadata` | TEXT | NULLABLE | JSON metadata for attachment details |
| `created_time` | DATETIME | NULLABLE | Record creation timestamp |
| `updated_time` | DATETIME | NULLABLE | Record update timestamp |
| `deleted_time` | DATETIME | NULLABLE | Soft delete timestamp |

---

### 5. `social_sync`

Stores synchronization history logs and execution metrics.

| Field | Type | Attributes | Description |
|---|---|---|---|
| `social_sync_id` | INT(10) | UNSIGNED, AUTO_INCREMENT, PRIMARY KEY | Unique sync log identifier |
| `social_account_id` | INT(10) | UNSIGNED, NULLABLE, FK(social_account) | Target account ID |
| `social_connection_id` | INT(10) | UNSIGNED, NULLABLE, FK(social_connection) | Target connection ID |
| `provider` | VARCHAR(50) | NULLABLE, KEY | Provider slug |
| `status` | VARCHAR(50) | DEFAULT 'pending', KEY | Execution status (`running`, `finished`, `failed`) |
| `started_at` | DATETIME | NULLABLE | Execution start time |
| `finished_at` | DATETIME | NULLABLE | Execution finish time |
| `items_created` | INT(10) | UNSIGNED, DEFAULT 0 | Count of newly inserted posts |
| `items_updated` | INT(10) | UNSIGNED, DEFAULT 0 | Count of updated posts |
| `items_failed` | INT(10) | UNSIGNED, DEFAULT 0 | Count of failed post operations |
| `message` | TEXT | NULLABLE | Log or error message |
| `raw_json` | TEXT | NULLABLE | Diagnostic JSON payload |
| `created_time` | DATETIME | NULLABLE | Record creation timestamp |
| `updated_time` | DATETIME | NULLABLE | Record update timestamp |

---

# Foreign Key Cascade Rules

1. `social_connection.social_account_id` ➔ `social_account.social_account_id` (`ON DELETE CASCADE`, `ON UPDATE SET NULL`)
2. `social_connection.parent_connection_id` ➔ `social_connection.social_connection_id` (`ON DELETE CASCADE`, `ON UPDATE SET NULL`)
3. `social_post.social_connection_id` ➔ `social_connection.social_connection_id` (`ON DELETE CASCADE`, `ON UPDATE CASCADE`)
4. `social_media.social_post_id` ➔ `social_post.social_post_id` (`ON DELETE CASCADE`, `ON UPDATE CASCADE`)
5. `social_sync.social_account_id` ➔ `social_account.social_account_id` (`ON DELETE CASCADE`, `ON UPDATE SET NULL`)
6. `social_sync.social_connection_id` ➔ `social_connection.social_connection_id` (`ON DELETE CASCADE`, `ON UPDATE SET NULL`)

---

# Version

- **Data Model Version**: 2.0