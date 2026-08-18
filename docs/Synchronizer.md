# TFL Social Synchronizer

## Overview

The `Synchronizer` imports remote social provider data (profiles, posts, media assets) into the local normalized database schema.

It handles token verification, automatic token refresh, parent-child token inheritance, payload mapping, connection-scoped UPSERT operations, media attachment syncing, and detailed sync history logging.

---

# Entry Points & Invocation

```php
// Synchronize active connections for a specific logical account (social_account_id)
$social->sync()->account(15)->run();

// Synchronize a specific connection (social_connection_id)
$social->sync()->connection(5)->run();

// Synchronize all active connections across all accounts
$social->sync()->all();
```

---

# Execution Pipeline

```
Connection Target Resolution
         │
         ▼
Start Sync Log (SyncService->startSync)
         │
         ▼
Token Verification & Auto-Refresh (ensureValidToken)
         │
         ▼
Fetch Provider Data (FacebookGraphService / InstagramGraphService)
         │
         ▼
Normalize Payload into Post & Media Arrays
         │
         ▼
Post UPSERT (PostService->upsertPost)
 ├─ Insert new post (social_connection_id + external_id) ➔ created++
 └─ Update existing post ➔ updated++
         │
         ▼
Media Attachment Sync (MediaService->syncMedia)
 ├─ Upsert media items by sort_order
 └─ Detach/delete obsolete media no longer present
         │
         ▼
Update Connection last_synced_at Timestamp
         │
         ▼
Finish Sync Log (SyncService->finishSync / failSync)
```

---

# Provider Synchronization Scope

### Facebook Pages

- **Profile Item**: Unique external ID `profile:<facebook_page_id>`, type `profile`, message set to Page name, profile picture stored in `social_media`.
- **Feed Items**: Fetches `feed` edge. Maps post ID, message/story, permalink URL, publication timestamp (`Y-m-d H:i:s`), engagement metrics (`shares`), and full raw Graph response in `raw_json`.
- **Media Attachments**: Extracts attachment URLs from `attachments.data` (`media_type`, image/unshimmed URLs), falling back to `full_picture`, `picture`, or `source`.

### Instagram Business Accounts

- **Profile Item**: Unique external ID `profile:<instagram_account_id>`, type `profile`, username, engagement metrics (`followers_count`, `follows_count`, `media_count`), profile picture in `social_media`.
- **Media Items**: Fetches `media` edge. Maps media ID, caption, permalink, publication timestamp, engagement metrics (`like_count`, `comments_count`), type (`image`, `video`, `reels`, `carousel_album`), and full raw Graph response in `raw_json`.
- **Media Attachments**: Normalizes `media_url` and `thumbnail_url`.

---

# UPSERT Rules & Data Integrity

1. **Unique Identification**: Posts are uniquely identified per connection by the composite key `(social_connection_id, external_id)`.
2. **Non-Destructive Post Sync**: Existing posts are updated with fresh metrics and payloads; posts are never deleted during normal feed sync.
3. **Media Sync**: Media attachments are matched by `(social_post_id, sort_order)`. Obsolete media records no longer present in the updated post payload are deleted (`detachMedia`).
4. **Error Isolation**: An error during individual post upsert increments the `$failed` count without halting the outer connection sync loop.
5. **Sync Audit Log**: Every sync run creates a `social_sync` record tracking `started_at`, `finished_at`, `status` (`running`, `finished`, `failed`), `items_created`, `items_updated`, `items_failed`, and error messages.

---

# Token Refresh in Synchronization

Prior to fetching provider data, the Synchronizer calls `ensureValidToken()`:

- Uses a 5-minute safety buffer (`TOKEN_EXPIRY_BUFFER_SECONDS = 300`) against `token_expires_at`.
- For **Instagram connections**: resolves parent connection (`parent_connection_id`), validates the parent Facebook Page token, and synchronizes the refreshed token to the Instagram connection.
- For **Facebook connections**: exchanges the short-lived token via `FacebookOAuth`, updates `social_connection`, and automatically updates child Instagram connection tokens.
- If token refresh fails, the connection status is set to `inactive`, the sync is marked `failed`, and an exception is raised.