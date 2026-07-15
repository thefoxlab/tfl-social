# TFL Social Data Model

## Overview

TFL Social stores normalized social media data independent of the provider.

Applications never interact directly with provider APIs or provider-specific data structures.

The package stores normalized entities while preserving the complete provider payload for future compatibility.

---

# Entities

## Account

Represents the logical owner of one or more social connections.

Examples:

- Company
- Lodge
- Hotel
- Website
- Brand

One Account may contain multiple Connections.

---

## Connection

Represents a connected social media account.

Examples:

- Facebook Page
- Instagram Business Account

Each Connection belongs to one Account.

Connections manage:

- Access Tokens
- Token Refresh
- Synchronization State

---

## Post

Represents one normalized social media post.

Each Post belongs to one Connection.

Posts are uniquely identified by:

```
social_connection_id + external_id
```

The Synchronizer always performs UPSERT operations.

---

## Media

Represents media attached to a Post.

Examples:

- Image
- Video
- Carousel Item

Each Post may contain zero or more Media records.

---

## Sync

Represents one synchronization execution.

A Sync belongs to one Connection.

It stores synchronization history and statistics.

---

# Relationships

```
Account
    │
    ▼
Connection
    │
    ▼
Post
    │
    ▼
Media

Connection
    │
    ▼
Sync
```

---

# Physical Tables

## social_account

Stores logical application accounts.

---

## social_connection

Stores provider connections.

Each connection contains:

- Provider
- External ID
- Access Token
- Token Expiry
- Last Sync Time

---

## social_post

Stores normalized provider posts.

Current schema:

```
social_post_id

social_connection_id

provider
external_id
parent_external_id

type

message

permalink

published_at
sync_time

metrics
raw_json

status

created_time
updated_time
deleted_time
```

---

## social_media

Stores media belonging to a post.

Current schema.

```
social_media_id

social_post_id

type
url
thumbnail_url
alt_text

sort_order

metadata

created_time
updated_time
deleted_time
```

---

## social_sync

Stores synchronization history.

Current schema.

```
social_sync_id

social_connection_id

status

started_at
finished_at

items_created
items_updated
items_failed

message

created_time
```

---

# Provider Independence

The database must never contain provider-specific tables.

Avoid tables such as:

- facebook_posts
- instagram_posts
- linkedin_posts

Every provider maps into the same normalized schema.

---

# Multiple Accounts

One Account

↓

Many Connections

↓

Many Posts

↓

Many Media

---

# Multiple Providers

One Account may contain multiple providers.

Current:

- Facebook
- Instagram

Future:

- LinkedIn
- YouTube
- Threads
- TikTok
- X (Twitter)

---

# Storage Principles

Normalize commonly used fields.

Preserve the complete provider payload inside:

```
raw_json
```

Store engagement counts inside:

```
metrics
```

Applications should never depend directly on provider response structures.

---

# Synchronization

Synchronization is always performed per Connection.

The Synchronizer:

- Inserts new posts
- Updates existing posts
- Updates media
- Never creates duplicates
- Never deletes posts

---

# Indexing

Important indexes include:

- social_connection_id
- provider
- external_id
- parent_external_id
- published_at
- sync_time
- status

---

# Future Compatibility

The data model is provider-independent.

Adding a new provider should require:

- Provider implementation
- Mapping layer

The database schema should remain unchanged.

---

# Version

Data Model Version: 2.0