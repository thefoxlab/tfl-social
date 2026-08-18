# TFL Social Architecture

## Overview

TFL Social is a provider-based PHP library that aggregates social media content from multiple platforms into a unified local database.

While built for CodeIgniter 4 integration via `Services::tflSocial()`, it remains framework-friendly and reusable across modern PHP 8.2+ environments.

Applications interact through the `TflSocial` entry point and fluent facades (`Connector`, `Synchronizer`, `FeedBuilder`, `ProviderManager`), maintaining strict separation of concerns across application layers.

---

# Design Principles

1. **Isolation**: Applications never call provider APIs or Database Models directly.
2. **Repository Pattern**: Repositories are the *only* component allowed to interact with CodeIgniter Models.
3. **Service Layer**: Business logic, token lifecycle rules, and sync orchestrations reside strictly inside Services.
4. **Normalized Storage**: All external platform items are mapped into standard `Post` and `Media` schema while preserving raw Graph JSON payloads in `raw_json`.
5. **Token Parentage**: Instagram Business accounts share access token authority with their parent Facebook Page connection (`parent_connection_id`).

---

# Component Architecture

```
                       Application
                            │
                            ▼
                        TflSocial
                            │
       ┌────────────────────┼────────────────────┬────────────────────┐
       ▼                    ▼                    ▼                    ▼
   Connector           Synchronizer         FeedBuilder       ProviderManager
       │                    │                    │                    │
       ├──────────────┐     │                    │                    ▼
       ▼              ▼     ▼                    ▼             ProviderRegistry
  Meta Graph     Services (Business Logic)   Local Database           │
  (Facebook /         │                           ▲                   ▼
   Instagram)         ▼                           │            FacebookProvider
                 Repositories (Persistence)       │
                      │                           │
                      ▼                           │
                   Models ────────────────────────┘
```

---

# Layer Responsibilities

### 1. Facades & Entry Points (`src/`)

- **`TflSocial`**: Main entry point; manages current account context and creates service facades.
- **`Manager`**: Wrapper class for DI / container management.
- **`Connector`**: Handles OAuth flow, Facebook Page & Instagram Business discovery, connection persistence, token refresh checks, and live Graph API edge requests.
- **`Synchronizer`**: Executes automated background data import, token validation, UPSERT mapping, and sync execution logging.
- **`FeedBuilder`**: Fluent query builder for retrieving normalized posts from the local database (currently stubbed).
- **`ProviderManager` & `ProviderRegistry`**: Provider registration and resolution hub.

### 2. Services (`src/Services/`)

- **`AccountService`**: Manages logical accounts (`social_account`).
- **`ConnectionService`**: Handles connection state (`active`, `expired`, `inactive`, `disconnected`), token persistence, parent-child token propagation, and token expiration buffer calculations.
- **`PostService`**: Manages post insertion, updates, and connection-scoped UPSERT logic.
- **`MediaService`**: Manages post media attachments and sort-order sync.
- **`SyncService`**: Records sync execution stats (`items_created`, `items_updated`, `items_failed`, `status`, `message`).

### 3. Repositories (`src/Repositories/`)

- **`AbstractRepository`**: Generic CRUD repository base wrapping CodeIgniter `Model`.
- **`AccountRepository`**, **`ConnectionRepository`**, **`PostRepository`**, **`MediaRepository`**, **`SyncRepository`**: Enforce type-safe entity operations and custom database query methods.

### 4. Models & Entities (`src/Models/`, `src/Entities/`)

- **Models**: CodeIgniter 4 Models (`AccountModel`, `ConnectionModel`, `PostModel`, `MediaModel`, `SyncModel`) defining table names, primary keys, and allowed fields.
- **Entities**: Domain objects (`Account`, `Connection`, `Post`, `Media`, `Sync`) encapsulating domain state and status constants.

### 5. Provider & Graph Layer (`src/Providers/`, `src/Http/`)

- **`FacebookProvider`**: Implements `ProviderInterface`.
- **`FacebookOAuth`**: Handles Meta OAuth 2.0 authorization code exchange and token lifespan expansion.
- **`PageService` & `BusinessAccountService`**: Page listing and Instagram Business account discovery wrappers.
- **`FacebookGraphService` & `InstagramGraphService`**: Low-level Meta Graph API endpoint wrappers returning `GraphResponse`, `GraphItem`, and `GraphCollection`.
- **`Client`**: HTTP client wrapper configured via `Config\TflSocial`.

---

# Data Flow Pipelines

### Live Graph Edge Request Flow

```
Application -> TflSocial -> Connector -> FacebookGraphService / InstagramGraphService
  -> Client -> Meta Graph API (v23.0) -> GraphCollection / GraphResponse -> Application
```

### Synchronization & UPSERT Pipeline

```
Synchronizer -> ConnectionService (Check/Refresh Tokens)
  -> FacebookGraphService / InstagramGraphService (Fetch Profile & Feed/Media)
  -> Normalize to Post & Media Data Arrays
  -> PostService->upsertPost() (Check social_connection_id + external_id)
  -> MediaService->syncMedia() (Update/Insert media by sort_order, detach removed items)
  -> SyncService (Record social_sync log and update last_synced_at)
```

---

# Database Schema Relationships

```
social_account (1) ───< social_connection (N) [parent_connection_id self-ref]
                              │
                              ├───< social_post (N) ───< social_media (N)
                              │
                              └───< social_sync (N)
```

---

# Version Information

- **Architecture Version**: 2.0
- **PHP Requirement**: ^8.2
- **Meta Graph API Version**: v23.0