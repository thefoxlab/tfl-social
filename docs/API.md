# TFL Social Public API

## Overview

The public API is provider independent.

Applications communicate only with the `TflSocial` manager.

Applications must never communicate directly with provider implementations.

The public API should remain stable as new providers are added.

---

# Entry Point

```php
$social = service('tflSocial');
```

---

# Account Context

Set the active account.

```php
$social
    ->account('thefoxlab');
```

All subsequent operations use this account.

```php
$social
    ->account('thefoxlab')
    ->facebook()
    ->feed();
```

---

# Feed

Retrieve a normalized feed from the local database.

```php
$posts = $social
    ->feed()
    ->account(15)
    ->latest();
```

Multiple accounts.

```php
$posts = $social
    ->feed()
    ->accounts([2,5,8])
    ->latest();
```

All accounts.

```php
$posts = $social
    ->feed()
    ->all()
    ->latest();
```

---

# Feed Filters

Supported methods.

```php
->account()

->accounts()

->platform()

->type()

->from()

->to()

->limit()

->latest()

->oldest()

->get()
```

---

# Connections

Create a provider connection.

Facebook.

```php
$social
    ->account('thefoxlab')
    ->connect()
    ->provider('facebook');
```

Future providers.

```php
$social
    ->connect()
    ->provider('instagram');

$social
    ->connect()
    ->provider('linkedin');

$social
    ->connect()
    ->provider('youtube');
```

---

# Synchronization

Synchronize one account.

```php
$social
    ->sync()
    ->account(15)
    ->run();
```

Synchronize one connection.

```php
$social
    ->sync()
    ->connection(5)
    ->run();
```

Synchronize all active connections.

```php
$social
    ->sync()
    ->all();
```

The synchronizer imports provider data into the local database.

Current synchronization scope.

Facebook

- Profile
- Feed

Instagram

- Profile
- Media

The synchronizer performs UPSERT operations and never deletes posts.

---

# Providers

Retrieve registered providers.

```php
$social->providers();
```

Current.

```text
facebook

instagram
```

Future.

```text
linkedin

youtube

threads

tiktok

x
```

---

# Accounts

Retrieve configured accounts.

```php
$social->accounts();
```

---

# Token Management

Access tokens are managed automatically.

Before every provider request the package verifies the current access token.

If required, the token is refreshed automatically.

Applications never need to manually refresh tokens.

---

# Normalized Post

Every provider is normalized into the same entity.

```text
id

provider

external_id

parent_external_id

type

message

permalink

published_at

metrics

media
```

The complete provider payload is always preserved inside `raw_json`.

---

# Future Features

Planned additions.

- Scheduler
- Queue support
- Webhooks
- Widget Builder
- Theme Builder
- Analytics
- Caching
- LinkedIn
- Threads
- TikTok
- X (Twitter)

---

# Design Goals

- Provider independent
- Database-first architecture
- Consistent public API
- Backwards compatible
- Extensible
- Multi-account support
- Multi-provider support
- PSR-12 compliant