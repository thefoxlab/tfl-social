# TFL Social Architecture

## Overview

TFL Social is a provider-based PHP library that aggregates social media content from multiple platforms into a unified, normalized API.

The package is designed primarily for CodeIgniter 4 but should remain framework-friendly and reusable in other PHP applications.

---

## Goals

- Simple public API
- Provider-based architecture
- Normalized data model
- Multiple accounts
- Multiple providers per account
- Extensible
- PSR-12 compliant
- SOLID principles
- Composer installable

---

## Initial Supported Providers

- Facebook Pages
- Instagram Business

---

## Planned Providers

- YouTube
- LinkedIn
- TikTok
- X (Twitter)

---

## Architecture

```
Application
        │
        ▼
 TflSocial Manager
        │
        ├─────────────┐
        ▼             ▼
 Facebook Driver   Instagram Driver
        │             │
        └──────┬──────┘
               ▼
      Meta Graph API
               │
               ▼
     Normalized Database
               │
               ▼
        Feed Builder API
```

---

## Design Principles

The application must never communicate directly with a provider.

All communication passes through the TflSocial Manager.

The application should not know whether a post originated from Facebook or Instagram.

All providers must return normalized entities.

---

## Public API

```php
$social = service('tflSocial');
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

## Feed Builder

Example:

```php
$posts = $social->feed()
    ->accounts([12, 25])
    ->platform(['facebook', 'instagram'])
    ->limit(20)
    ->latest();
```

Future filters may include:

- platform()
- accounts()
- type()
- from()
- to()
- limit()
- offset()
- orderBy()

---

## Normalized Entity

Every provider returns the same structure.

```
Post

id
provider
external_id

type

message
caption

permalink

published_at

media[]

metrics[]

raw_json
```

---

## Multiple Accounts

The package supports unlimited accounts.

Example:

```
Serengeti Lodge
    Facebook
    Instagram

Ngorongoro Camp
    Facebook
    Instagram

Masai Mara Camp
    Facebook
```

---

## Feed Aggregation

The library should be capable of returning:

- One account
- Multiple accounts
- All accounts

The application decides the scope.

Example:

```php
$social->feed()->account(15);

$social->feed()->accounts([4,7,12]);

$social->feed()->all();
```

---

## Storage Strategy

Provider data is normalized.

Never create provider-specific tables such as:

- facebook_posts
- instagram_posts

Instead use common tables.

Example:

- social_account
- social_connection
- social_post
- social_media
- social_sync

---

## Provider Responsibilities

Every provider is responsible for:

- OAuth
- Token refresh
- Fetching data
- Parsing provider response
- Mapping to normalized entities

---

## Future Extensibility

Adding a new provider should only require:

- New Driver
- New Mapper
- Provider Configuration

No changes should be required to application code.

---

## Coding Standards

- PHP 8.2+
- PSR-12
- Strict typing
- SOLID principles
- Dependency Injection
- No duplicated code
- Framework-friendly
- Production ready

---

## Version

Current Architecture Version: 1.0