# TFL Social Architecture

## Overview

TFL Social is a provider-based PHP package that aggregates social media content from multiple platforms into a unified local database.

The package is designed primarily for CodeIgniter 4 but remains framework-friendly and reusable in any PHP application.

Applications never communicate directly with provider APIs. They communicate only with the TflSocial Manager.

---

# Goals

- Simple public API
- Provider-based architecture
- Database-first design
- Multiple accounts
- Multiple connections per account
- Multiple providers
- Normalized data model
- Automatic synchronization
- Automatic token management
- Extensible
- Composer installable
- PSR-12 compliant

---

# Current Providers

- Facebook Pages
- Instagram Business

---

# Planned Providers

- LinkedIn
- YouTube
- Threads
- TikTok
- X (Twitter)

---

# Architecture

```
Application
        │
        ▼
 TflSocial Manager
        │
        ├────────────────────────────┐
        ▼                            ▼
 Connection Manager           Feed Builder
        │                            │
        ▼                            ▼
 Provider Drivers           Local Database
        │                            ▲
        └──────────────┬─────────────┘
                       ▼
                 Synchronizer
                       │
                       ▼
                Meta Graph API
```

---

# Design Principles

Applications never communicate directly with providers.

Applications never communicate directly with the Graph API.

Every provider returns normalized entities.

All providers share the same synchronization pipeline.

The Feed Builder always reads from the local database.

---

# Public API

```php
$social = service('tflSocial');
```

### Account

```php
$social->account('thefoxlab');
```

### Connect

```php
$social->connect();
```

### Synchronize

```php
$social->sync();
```

### Feed

```php
$social->feed();
```

### Accounts

```php
$social->accounts();
```

### Providers

```php
$social->providers();
```

---

# Synchronization

Current synchronization scope.

Facebook

- Profile
- Feed

Instagram

- Profile
- Media

The synchronizer performs UPSERT operations.

Existing records are updated.

New records are inserted.

Records are never duplicated.

Posts are never deleted.

---

# Feed Builder

The Feed Builder never calls provider APIs.

It always reads from the local database.

Example:

```php
$posts = $social
    ->feed()
    ->accounts([12,25])
    ->platform(['facebook','instagram'])
    ->latest()
    ->limit(20)
    ->get();
```

Supported filters.

- account()
- accounts()
- all()
- platform()
- type()
- from()
- to()
- latest()
- oldest()
- limit()

---

# Normalized Entity

Every provider is mapped into the same structure.

```
Post

id

provider

external_id

parent_external_id

type

message

permalink

published_at

metrics

media[]

raw_json
```

---

# Multiple Accounts

Each account may contain multiple provider connections.

Example.

```
TheFoxLab
    Facebook Page
    Instagram Business

Client A
    Facebook Page

Client B
    Facebook Page
    Instagram Business
```

---

# Database

The package uses normalized tables.

- social_account
- social_connection
- social_post
- social_media
- social_sync

Provider-specific tables must never be created.

---

# Token Management

Providers are responsible for token management.

The package automatically refreshes expired tokens before making Graph requests.

Applications never manually refresh tokens.

---

# Provider Responsibilities

Each provider is responsible for:

- OAuth
- Token refresh
- Graph requests
- Mapping provider responses
- Synchronization support

Providers must never communicate with Models.

---

# Repository Responsibilities

Repositories are the only layer that communicates with Models.

Services must never access Models directly.

Providers must never access Models.

Architecture.

```
Application
    ↓
TflSocial
    ↓
Services
    ↓
Repositories
    ↓
Models
    ↓
Database
```

---

# Future Extensibility

Adding a new provider should only require:

- Provider implementation
- OAuth implementation
- Mapper
- Configuration

No changes should be required to:

- Feed Builder
- Synchronizer
- Public API
- Database schema

---

# Coding Standards

- PHP 8.2+
- Strict typing
- PSR-12
- SOLID
- Dependency Injection
- Reusable components
- Framework friendly
- Production ready

---

# Version

Architecture Version: 2.0