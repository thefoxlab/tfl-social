# TFL Social Token Management

## Overview

Access token management is completely automatic.

Applications never refresh tokens manually.

The package is responsible for ensuring valid tokens before every provider request.

---

# Supported Providers

Current:

- Facebook Pages
- Instagram Business

Instagram Business uses the parent Facebook Page token.

---

# Token Lifecycle

```
Provider Request

↓

Load Active Connection

↓

Check Token Expiry

↓

Expired?

↓

Yes
    ↓
Refresh Token
    ↓
Save Token
    ↓
Retry Request

No
    ↓
Execute Request
```

---

# Refresh Rules

Before every Graph request:

- Verify token expiry.
- If expired or nearing expiry, refresh automatically.
- Persist the refreshed token.
- Retry the original request.

The caller must never know a refresh occurred.

---

# Failure Handling

If refresh fails:

- Mark connection inactive.
- Throw a meaningful exception.

Never return invalid provider responses.

---

# Storage

Tokens are stored in:

```
social_connection
```

Fields:

- access_token
- refresh_token
- token_expires_at

---

# Connection Resolution

All provider requests resolve the active connection.

Current Account

↓

Active Facebook Page

↓

Active Instagram Business (if required)

Applications never manually resolve connections.

---

# Provider Responsibilities

Providers are responsible for:

- Token validation
- Token refresh
- Graph authentication

Providers must never access Models.

Repositories remain responsible for persistence.

---

# Design Goals

- Transparent
- Automatic
- Reliable
- No manual intervention
- No breaking changes
- Production ready