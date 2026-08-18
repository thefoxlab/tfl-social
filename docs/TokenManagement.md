# TFL Social Token Management

## Overview

Access token lifecycle management in TFL Social is fully automated.

Before making live Graph API requests or executing synchronization loops, the package verifies token expiration dates and transparently performs token refreshes without requiring manual application intervention.

---

# Token Architecture & Parent-Child Inheritance

Meta Graph API requires a Facebook Page access token with appropriate permissions (`pages_read_engagement`, `pages_show_list`, `instagram_basic`, etc.) to interact with both Facebook Pages and linked Instagram Business accounts.

```
Facebook Page Connection (Parent)
    │ access_token
    │ token_expires_at
    ▼
Instagram Business Connection (Child - parent_connection_id)
    │ inherits access_token
    └ inherits token_expires_at
```

- **Facebook Page**: Primary token holder.
- **Instagram Business**: Child connection linked via `parent_connection_id`. It shares the parent Facebook Page's access token and expiry schedule.

---

# Automatic Token Lifecycle Pipeline

```
API Call or Sync Execution
            │
            ▼
   Load Active Connection
            │
            ▼
Evaluate Token Expiry (isTokenExpired)
[Check token_expires_at <= time() + 300s]
            │
      ┌─────┴─────┐
      ▼           ▼
  Valid        Expired / Nearing Expiry
  Token           │
    │             ▼
    │      Refresh Parent FB Token
    │      (FacebookOAuth->exchangeShortLivedTokenForLongLivedToken)
    │             │
    │             ▼
    │      Persist Refreshed FB Token in DB
    │             │
    │             ▼
    │      Propagate Token to Child Instagram Connections
    │             │
    └─────────────┼─────────────┐
                  ▼             ▼
              Success        Failure
                  │             │
                  ▼             ▼
              Execute       Mark Connection Status 'inactive'
              Request       Throw Exception
```

---

# Token Expiry Buffer & Statuses

- **Safety Buffer**: `TOKEN_EXPIRY_BUFFER_SECONDS = 300` (5 minutes). Tokens within 5 minutes of expiration are treated as expired to prevent mid-request failures.
- **Connection Statuses**:
  - `active`: Valid token and active connection.
  - `expired`: Expiry date has passed or buffer triggered.
  - `inactive`: Token refresh failed or permissions revoked.
  - `disconnected`: Manually disconnected by application.

---

# Token Storage Schema

Tokens are persisted inside `social_connection`:

- `access_token` (TEXT): Encoded access token string.
- `refresh_token` (TEXT): Optional refresh token.
- `token_expires_at` (DATETIME): Calculated expiration timestamp (`Y-m-d H:i:s`).
- `permissions` (JSON): List of granted OAuth permissions.

---

# Manual Token Utilities (`Connector`)

While token management is automatic during sync and graph calls, applications can use manual utility methods on `Connector`:

```php
// Check connection status
$status = $connector->connectionStatus();

// Check token expiry boolean
if ($connector->tokenExpired()) {
    // Refresh parent token and child connections
    $connector->refreshToken();
}

// Reconnect connection (refresh if expired & set status to 'active')
$connector->reconnect();
```

---

# Design Guarantees

1. **Transparent Execution**: Token refresh happens seamlessly behind public API wrappers.
2. **Propagated Persistence**: Updating a parent Facebook Page token immediately cascades updates to child Instagram Business connections.
3. **Fail-Safe Exception Handling**: Failed refreshes mark the database connection as `inactive` to avoid spamming invalid API requests.