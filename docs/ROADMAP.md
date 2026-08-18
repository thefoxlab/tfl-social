# TFL Social Roadmap

## Vision

Build a provider-independent social media synchronization and aggregation library for PHP 8.2+ and CodeIgniter 4, enabling application developers to connect social channels, auto-sync content into normalized local storage, and serve unified feeds.

---

# Development Milestones & Status

## 1. Foundation & Configuration

- Composer package structure (`thefoxlab/tfl-social`)
- PSR-4 namespace autoloading (`TheFoxLab\TflSocial\`)
- CodeIgniter 4 Service Provider (`Services::tflSocial()`)
- Configuration class (`Config\TflSocial`)
- Entry point classes (`TflSocial`, `Manager`)
- Interface definitions (`ConnectorInterface`, `SynchronizerInterface`, `FeedBuilderInterface`, `ProviderManagerInterface`)
- Provider registry & manager (`ProviderManager`, `ProviderRegistry`, `FacebookProvider`)
- Exception hierarchy (`RepositoryException`, `HttpException`, `OAuthException`)

✔ Complete

---

## 2. Database Layer & Data Model

- Database Migration (`CreateSocialTables`)
- Normalized tables (`social_account`, `social_connection`, `social_post`, `social_media`, `social_sync`)
- Foreign key constraints & cascade rules
- CodeIgniter Models (`AccountModel`, `ConnectionModel`, `PostModel`, `MediaModel`, `SyncModel`)
- Domain Entities (`Account`, `Connection`, `Post`, `Media`, `Sync`)
- Repositories (`AccountRepository`, `ConnectionRepository`, `PostRepository`, `MediaRepository`, `SyncRepository`)
- Service layer (`AccountService`, `ConnectionService`, `PostService`, `MediaService`, `SyncService`)

✔ Complete

---

## 3. Connection & OAuth Management

- Meta OAuth 2.0 flow (`FacebookOAuth`)
- CSRF state generation and validation
- Short-lived to long-lived access token exchange
- Facebook Page discovery (`PageService`)
- Instagram Business account discovery (`BusinessAccountService`)
- Multi-account logical scoping (`AccountService`)
- Connection persistence & parent connection linking (`parent_connection_id`)

✔ Complete

---

## 4. Live Graph API Wrappers

- Meta Graph API HTTP Client (`Client`)
- Facebook Graph Edges: Profile, Feed, Posts, Photos, Videos, Albums, Events, Reviews
- Instagram Graph Edges: Profile, Media, MediaById, Reels, Carousel, Stories, OwnMediaByHashtag, HashtagSearch, RecentHashtagMedia
- Response object wrappers (`GraphResponse`, `GraphItem`, `GraphCollection`, `Pagination`, `FeatureUnavailableResponse`)

✔ Complete

---

## 5. Automatic Token Management

- Pre-request token expiry check with 5-minute buffer (`TOKEN_EXPIRY_BUFFER_SECONDS = 300`)
- Pre-request automatic token refresh in `Connector`
- Auto-refresh within `Synchronizer` pipeline
- Parent-to-child token propagation (Facebook Page token updated ➔ Child Instagram Business token updated)
- Connection status updates (`active`, `expired`, `inactive`, `disconnected`)

✔ Complete

---

## 6. Synchronizer Engine

- Connection target resolution (`account`, `connection`, `all`)
- Profile and feed fetchers for Facebook & Instagram
- Post normalization mapper & engagement metric extractor
- Connection-scoped UPSERT logic (`social_connection_id` + `external_id`)
- Media attachment synchronization & sort order management
- Sync execution log recording (`social_sync` stats: `items_created`, `items_updated`, `items_failed`)
- Update connection `last_synced_at` timestamp

✔ Complete

---

## 7. Feed Builder (Local DB Feed Querying)

- Fluent builder interface (`account`, `accounts`, `all`, `platform`, `type`, `from`, `to`, `limit`, `offset`, `orderBy`, `latest`, `oldest`, `get`)
- Local database feed query implementation in `FeedBuilder::get()`

🚧 Incomplete (Class interface exists, but database querying method is currently stubbed and returns `[]`)

---

## 8. Scheduler & Background Execution

- Automated background cron runner for synchronization
- Incremental sync options
- Event triggers / queue integration

📋 Planned

---

## 9. Widget API & Frontend Components

- Feed REST API controllers / JSON endpoints
- Frontend JavaScript widgets (Grid, Masonry, Carousel)
- Customizable CSS themes

📋 Planned

---

## 10. Additional Provider Drivers

- LinkedIn Provider
- Threads Provider
- YouTube Provider
- TikTok Provider
- X (Twitter) Provider

📋 Planned

---

# Versioning Roadmap

- **v2.0.0-beta**: Core architecture, Meta integration, OAuth, Synchronizer, Repositories, Models, Migrations (Current codebase).
- **v2.1.0**: Complete `FeedBuilder` database query implementation and local feed test suite.
- **v2.2.0**: Scheduler & CLI command integrations.
- **v3.0.0**: Additional provider drivers (LinkedIn, YouTube, X).