# TFL Social Public API

## Overview

The public API should remain stable regardless of the supported providers.

Applications should only communicate with the TflSocial Manager.

Applications must never communicate directly with provider drivers.

---

# Entry Point

```php
$social = service('tflSocial');
```

---

# Feed

Retrieve a normalized feed.

```php
$posts = $social->feed()
    ->account(15)
    ->latest();
```

Multiple accounts.

```php
$posts = $social->feed()
    ->accounts([2,5,8])
    ->latest();
```

All accounts.

```php
$posts = $social->feed()
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

->offset()

->orderBy()

->latest()

->oldest()

->get()
```

---

# Connections

Create a provider connection.

```php
$social->connect()
    ->provider('facebook');
```

Future.

```php
$social->connect()
    ->provider('instagram');
```

---

# Synchronization

Synchronize one account.

```php
$social->sync()
    ->account(15)
    ->run();
```

Synchronize all accounts.

```php
$social->sync()
    ->all();
```

---

# Providers

Retrieve registered providers.

```php
$social->providers();
```

Expected.

```php
facebook

instagram
```

Future.

```php
youtube

linkedin

tiktok
```

---

# Accounts

Retrieve connected accounts.

```php
$social->accounts();
```

---

# Normalized Post

Every provider returns the same entity.

```text
id

provider

external_id

type

message

caption

permalink

published_at

media

metrics
```

---

# Future Features

Planned additions.

- Webhooks
- Scheduled sync
- Queue support
- Widget Builder
- Theme Builder
- Analytics
- Caching
- Multiple provider support

---

# Design Goals

- Provider independent
- Framework friendly
- Consistent API
- Backwards compatible
- Extensible
- PSR-12 compliant