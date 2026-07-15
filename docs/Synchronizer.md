# TFL Social Synchronizer

## Overview

The Synchronizer imports provider data into the local database.

Applications should never synchronize data manually.

The Synchronizer is responsible for:

- Fetching provider data
- Mapping provider responses
- Normalizing entities
- UPSERT operations
- Media synchronization
- Synchronization history

---

# Current Scope

## Facebook

Synchronize:

- Profile
- Feed

The Facebook Feed already includes:

- Status posts
- Photos
- Videos
- Albums
- Links

Do not synchronize Photos, Videos or Albums separately.

---

## Instagram

Synchronize:

- Profile
- Media

Media already includes:

- Photos
- Reels
- Carousel Posts

Do not synchronize:

- Stories
- Hashtag Search
- Recent Hashtag Media

These remain live API features.

---

# Synchronization Flow

```
Connection

↓

Verify Token

↓

Fetch Provider Data

↓

Normalize

↓

UPSERT social_post

↓

UPSERT social_media

↓

Update last_synced_at

↓

Insert social_sync record
```

---

# UPSERT Rules

Posts are uniquely identified by:

```
social_connection_id
+
external_id
```

If found:

- Update
- Refresh sync_time

Otherwise:

- Insert

Never duplicate posts.

Never truncate tables.

Never delete posts.

---

# Media

Each media item becomes one record.

Update changed media.

Insert new media.

Remove media no longer referenced by the post.

Never delete the parent post.

---

# Metrics

Store engagement counts only.

Example

```json
{
    "likes": 52,
    "comments": 14,
    "shares": 3
}
```

Store inside:

```
social_post.metrics
```

---

# Raw Payload

Always preserve the complete provider response.

Store inside:

```
social_post.raw_json
```

Applications should never depend on provider response structures.

---

# Synchronization Log

Each synchronization creates one history record.

Populate:

- started_at
- finished_at
- items_created
- items_updated
- items_failed
- status
- message

Update:

```
social_connection.last_synced_at
```

---

# Design Rules

- UPSERT only
- No duplicate posts
- No duplicate media
- No provider-specific tables
- No direct SQL inside Services
- Repositories are the only database layer

---

# Future

Future providers should plug into the same Synchronizer without requiring schema changes.