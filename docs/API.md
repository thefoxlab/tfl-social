# TFL Social Public API

## Overview

The TFL Social public API provides a provider-independent interface for managing social media connections, Graph API interaction, and content synchronization.

Applications interact primarily with the `TflSocial` manager instance and its fluent builders (`Connector`, `Synchronizer`, `FeedBuilder`, `ProviderManager`).

---

# Entry Point

Obtain the package service instance via CodeIgniter 4 services:

```php
$social = service('tflSocial');
```

---

# Account Context

Set the active logical account by name. If the account does not exist, it is automatically created in `social_account`.

```php
$social->account('thefoxlab');
```

All subsequent operations on the returned instance use this account context.

```php
$social
    ->account('thefoxlab')
    ->facebook()
    ->pages();
```

---

# Connection & Provider Setup

The `Connector` manages OAuth authentication, page discovery, and account binding.

```php
$connector = $social->account('thefoxlab')->connect();

// Or provider-specific shortcuts
$facebook  = $social->account('thefoxlab')->facebook();
$instagram = $social->account('thefoxlab')->instagram();
```

*Note: `provider('facebook')` is the primary Meta integration driver. Instagram Business accounts are discovered and accessed using the connected Facebook Page token.*

### OAuth Workflow

```php
// Generate CSRF state
$state = $connector->generateState();

// Get authorization URL
$url = $connector->authorizationUrl($state);

// Validate callback state
$connector->validateCallbackState($expectedState, $actualState);

// Exchange code for short-lived token response
$response = $connector->exchangeCodeForShortLivedToken($code);

// Exchange short-lived token for long-lived token
$longLived = $connector->exchangeShortLivedTokenForLongLivedToken($shortLivedToken);
```

### Facebook Page Operations

```php
// Set active token
$connector->accessToken($token);

// List pages accessible by token
$pages = $connector->pages();
$businessPages = $connector->businessPages();

// Connect a specific Facebook Page to the current account
$connection = $connector->connectPage($pageId);

// Get current active connection details or specific page info
$page = $connector->page();
$pageDetails = $connector->page($pageId);

// Disconnect active page
$connector->disconnectPage();
```

### Instagram Business Operations

```php
// List Instagram Business accounts connected to active Facebook Page
$igAccounts = $connector->instagramBusinesses();

// Connect Instagram Business account
$igConnection = $connector->connectInstagramBusiness($instagramAccountId);

// Get active Instagram connection
$currentIg = $connector->currentInstagramConnection();

// Disconnect Instagram connection
$connector->disconnectInstagramBusiness();
```

---

# Live Graph API Wrappers

The `Connector` provides direct live Graph API request wrappers for connected accounts:

### Facebook Edges

```php
$feed    = $connector->feed($fields, $limit, $after, $before);
$posts   = $connector->posts($fields, $limit, $after, $before);
$photos  = $connector->photos($fields, $limit, $after, $before);
$videos  = $connector->videos($fields, $limit, $after, $before);
$albums  = $connector->albums($fields, $limit, $after, $before);
$events  = $connector->events($fields, $limit, $after, $before);
$reviews = $connector->reviews($fields, $limit, $after, $before);
```

### Instagram Edges

```php
$profile  = $connector->profile($fields);
$media    = $connector->media($fields, $limit, $after, $before);
$item     = $connector->mediaById($mediaId, $fields);
$reels    = $connector->reels($fields, $limit, $after, $before);
$carousel = $connector->carousel($fields, $limit, $after, $before);

// Features subject to platform availability
$stories  = $connector->stories($fields, $limit, $after, $before);
$search   = $connector->hashtagSearch($hashtag);
$recent   = $connector->recentHashtagMedia($hashtagId, $fields);
$own      = $connector->ownMediaByHashtag($hashtag, $fields);
```

---

# Connection Status & Token Refresh

Token verification and refresh happen automatically prior to API calls and synchronization. Manual utility methods are also available:

```php
// Check status ('active', 'expired', 'disconnected', 'unknown')
$status = $connector->connectionStatus();

// Check token expiry (includes 5-minute safety buffer)
$isExpired = $connector->tokenExpired();

// Refresh long-lived token manually
$connection = $connector->refreshToken();

// Reconnect connection
$connection = $connector->reconnect();
```

---

# Data Synchronization

Import remote provider profiles and posts into the local database using the `Synchronizer`.

```php
// Synchronize specific account (by integer social_account_id)
$social->sync()->account(15)->run();

// Synchronize specific connection (by integer social_connection_id)
$social->sync()->connection(5)->run();

// Synchronize all active connections
$social->sync()->all();
```

---

# Local Feed Querying (FeedBuilder)

Query local database posts via the `FeedBuilder`:

```php
$posts = $social->feed()
    ->account(15)
    ->platform(['facebook', 'instagram'])
    ->type('image')
    ->from('2026-01-01')
    ->to('2026-12-31')
    ->limit(20)
    ->offset(0)
    ->latest();
```

*Note: In the current release, database querying inside `FeedBuilder` is a stub and returns empty result sets (`[]`). Use live Graph methods or direct repository access until FeedBuilder database queries are completed.*

---

# Provider Registry

Manage and inspect registered provider drivers:

```php
$providerManager = $social->providers();

// List registered provider names (e.g. ['facebook'])
$available = $providerManager->available();

// Get all registered provider instances
$providers = $providerManager->all();

// Resolve a provider instance
$facebookProvider = $providerManager->resolve('facebook');
```

---

# Normalized Entity Schema

Posts and media are normalized in the database regardless of the provider platform:

### Post Entity (`social_post`)

- `social_post_id` (PK)
- `social_connection_id` (FK)
- `provider` (`facebook`, `instagram`)
- `external_id` (Unique per connection)
- `parent_external_id`
- `type` (`post`, `profile`, `image`, `video`, `carousel_album`, etc.)
- `message`
- `caption`
- `permalink`
- `published_at`
- `sync_time`
- `metrics` (JSON encoded string)
- `raw_json` (Full original provider response)
- `status` (`active`)

### Media Entity (`social_media`)

- `social_media_id` (PK)
- `social_post_id` (FK)
- `type` (`image`, `video`)
- `url`
- `thumbnail_url`
- `alt_text`
- `sort_order`
- `metadata` (JSON encoded string)