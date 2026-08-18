# GEMINI.md — Project Knowledge Base & AI Developer Context

## Overview & Purpose

`thefoxlab/tfl-social` is a provider-based social media aggregation SDK for PHP 8.2+ and CodeIgniter 4 (`Config/Services.php`).

The library aggregates social media content (profiles, posts, media assets, metrics) from Meta platforms (Facebook Pages and Instagram Business) into a unified, normalized local relational database.

Applications interact with the library through a provider-independent public entry point (`TflSocial` manager instance and fluent facade builders: `Connector`, `Synchronizer`, `FeedBuilder`, `ProviderManager`).

---

## Current Implementation State

* **Facebook Integration**:
  * OAuth 2.0 authorization code exchange & long-lived user token exchange ([`FacebookOAuth`](file:///Applications/MAMP/HTdocs/tfl-social/src/Providers/Facebook/OAuth.php)).
  * Facebook Page discovery ([`PageService`](file:///Applications/MAMP/HTdocs/tfl-social/src/Providers/Facebook/PageService.php)) and account connection ([`Connector::connectPage()`](file:///Applications/MAMP/HTdocs/tfl-social/src/Connector.php#L178-L198)).
  * Graph API Edge wrappers: Feed, Posts, Photos, Videos, Albums, Events, Reviews ([`FacebookGraphService`](file:///Applications/MAMP/HTdocs/tfl-social/src/Providers/Facebook/GraphService.php)).
* **Instagram Business Integration**:
  * Instagram Business account discovery ([`BusinessAccountService`](file:///Applications/MAMP/HTdocs/tfl-social/src/Providers/Instagram/BusinessAccountService.php)) via connected Facebook Page access tokens.
  * Parent-child connection mapping ([`parent_connection_id`](file:///Applications/MAMP/HTdocs/tfl-social/src/Database/Migrations/2026-07-10-000001_CreateSocialTables.php#L88-L93) in `social_connection`).
  * Graph API Edge wrappers: Profile, Media, MediaById, Reels, Carousel, Stories, HashtagSearch, RecentHashtagMedia, OwnMediaByHashtag ([`InstagramGraphService`](file:///Applications/MAMP/HTdocs/tfl-social/src/Providers/Instagram/GraphService.php)).
* **Token Management & Refresh**:
  * Automatic pre-request token expiration validation with 5-minute safety buffer ([`TOKEN_EXPIRY_BUFFER_SECONDS = 300`](file:///Applications/MAMP/HTdocs/tfl-social/src/Services/ConnectionService.php#L24)).
  * Parent-to-child token propagation from Facebook Page connections to child Instagram Business connections ([`Connector::refreshToken()`](file:///Applications/MAMP/HTdocs/tfl-social/src/Connector.php#L460-L484), [`Synchronizer::refreshToken()`](file:///Applications/MAMP/HTdocs/tfl-social/src/Synchronizer.php#L165-L215)).
* **Synchronization Pipeline**:
  * Scoped execution per account (`account`), connection (`connection`), or all (`all`).
  * Normalization of platform items into `Post` and `Media` domain objects.
  * Connection-scoped UPSERT logic by composite key `(social_connection_id, external_id)` in [`PostService::upsertPost()`](file:///Applications/MAMP/HTdocs/tfl-social/src/Services/PostService.php#L52-L78).
  * Media attachment synchronization by `sort_order` and detachment of removed media in [`MediaService::syncMedia()`](file:///Applications/MAMP/HTdocs/tfl-social/src/Services/MediaService.php#L48-L78).
  * Synchronization history tracking in `social_sync` ([`SyncService`](file:///Applications/MAMP/HTdocs/tfl-social/src/Services/SyncService.php)).
* **Database Layer**:
  * Migration [`2026-07-10-000001_CreateSocialTables.php`](file:///Applications/MAMP/HTdocs/tfl-social/src/Database/Migrations/2026-07-10-000001_CreateSocialTables.php).
  * Relational tables: `social_account`, `social_connection`, `social_post`, `social_media`, `social_sync`.
  * Repositories ([`AccountRepository`](file:///Applications/MAMP/HTdocs/tfl-social/src/Repositories/AccountRepository.php), [`ConnectionRepository`](file:///Applications/MAMP/HTdocs/tfl-social/src/Repositories/ConnectionRepository.php), [`PostRepository`](file:///Applications/MAMP/HTdocs/tfl-social/src/Repositories/PostRepository.php), [`MediaRepository`](file:///Applications/MAMP/HTdocs/tfl-social/src/Repositories/MediaRepository.php), [`SyncRepository`](file:///Applications/MAMP/HTdocs/tfl-social/src/Repositories/SyncRepository.php)).
  * Services ([`AccountService`](file:///Applications/MAMP/HTdocs/tfl-social/src/Services/AccountService.php), [`ConnectionService`](file:///Applications/MAMP/HTdocs/tfl-social/src/Services/ConnectionService.php), [`PostService`](file:///Applications/MAMP/HTdocs/tfl-social/src/Services/PostService.php), [`MediaService`](file:///Applications/MAMP/HTdocs/tfl-social/src/Services/MediaService.php), [`SyncService`](file:///Applications/MAMP/HTdocs/tfl-social/src/Services/SyncService.php)).
* **FeedBuilder Status**:
  * Stubbed in current release. `FeedBuilder::get()`, `latest()`, and `oldest()` return empty result sets (`[]`). Local database feed querying is planned for a future release.

---

## Critical Token Rules & Behavior

1. **Page Access Tokens vs. User Access Tokens**:
   * Facebook Page Access Tokens derived from long-lived User Access Tokens do not expire on Meta's platform.
   * Page Access Tokens **must not** be sent through Meta's `/oauth/access_token?grant_type=fb_exchange_token` endpoint. Passing a Page token to `fb_exchange_token` triggers HTTP 400 OAuth errors.
2. **Page Token Storage**:
   * Page connections use `token_expires_at = null`.
3. **Instagram Token Inheritance**:
   * Instagram Business connections inherit the parent Facebook Page's `access_token` and `token_expires_at = null` via `parent_connection_id`.
4. **Safety Buffer**:
   * The 300-second safety buffer (`TOKEN_EXPIRY_BUFFER_SECONDS = 300`) applies strictly to tokens that possess a non-null `token_expires_at` timestamp.

---

## Verified Pre-Beta Status

* **Status**: Ready for beta with known limitations.
* **Automated Test Suites**: 6 Suites ([`TokenLifecycleTest`](file:///Applications/MAMP/HTdocs/tfl-social/tests/TokenLifecycleTest.php), [`ConnectionServiceTest`](file:///Applications/MAMP/HTdocs/tfl-social/tests/ConnectionServiceTest.php), [`PostServiceTest`](file:///Applications/MAMP/HTdocs/tfl-social/tests/PostServiceTest.php), [`FacebookOAuthTest`](file:///Applications/MAMP/HTdocs/tfl-social/tests/FacebookOAuthTest.php), [`ConnectorTest`](file:///Applications/MAMP/HTdocs/tfl-social/tests/ConnectorTest.php), [`SynchronizerTest`](file:///Applications/MAMP/HTdocs/tfl-social/tests/SynchronizerTest.php)).
* **Assertions**: 90 assertions.
* **Pass Rate**: 100% (90 Passed, 0 Failed).
* **Test Suite Execution Command**:
  ```bash
  php tests/RunAllTests.php
  ```

---

## Known Limitations

1. **`FeedBuilder` Database Querying**: The local database query implementation in `FeedBuilder::get()` is incomplete and returns `[]`. Applications querying local posts should use `PostRepository` / `PostService` directly.
2. **High-Concurrency Load Validation**: Database performance under high-concurrency multi-threaded background sync execution has not yet been load-tested against production MySQL instances.

---

## Documentation Map

Consult the specific `docs/` files for detailed reference:

* [`docs/API.md`](file:///Applications/MAMP/HTdocs/tfl-social/docs/API.md) — Public SDK entry points, method signatures, parameters, and code examples.
* [`docs/ARCHITECTURE.md`](file:///Applications/MAMP/HTdocs/tfl-social/docs/ARCHITECTURE.md) — System design, layer boundaries, component responsibilities, and data pipelines.
* [`docs/DATABASE.md`](file:///Applications/MAMP/HTdocs/tfl-social/docs/DATABASE.md) — Relational schema, tables, fields, indexes, primary/foreign keys, and cascade rules.
* [`docs/Synchronizer.md`](file:///Applications/MAMP/HTdocs/tfl-social/docs/Synchronizer.md) — Synchronization flow, profile/feed data mapping, UPSERT mechanics, and sync history logging.
* [`docs/TokenManagement.md`](file:///Applications/MAMP/HTdocs/tfl-social/docs/TokenManagement.md) — OAuth token lifecycle, parent-child token inheritance, 300s buffer, and status transitions.
* [`docs/ROADMAP.md`](file:///Applications/MAMP/HTdocs/tfl-social/docs/ROADMAP.md) — Authoritative milestone roadmap and completed feature tracking.
* [`docs/CHANGELOG_GUIDE.md`](file:///Applications/MAMP/HTdocs/tfl-social/docs/CHANGELOG_GUIDE.md) — Versioning standards (SemVer 2.0.0), changelog format (Keep a Changelog 1.1.0), and documentation rules.

---

## AI Development Rules for Future Sessions

Future AI agents working on this repository must adhere to the following rules:

1. **Source Code is Truth**: Inspect the actual source code inside `src/` before forming conclusions.
2. **Run Tests After Changes**: Always execute `php tests/RunAllTests.php` after modifying code to verify zero regressions.
3. **Preserve Architecture**: Maintain the strict separation of concerns (Facade ➔ Service ➔ Repository ➔ Model ➔ Database). Repositories are the *only* layer permitted to interact with CodeIgniter Models.
4. **No Ad-Hoc Drivers**: Instagram Business connections are accessed using the parent Facebook Page access token; do not assume or invent a standalone `InstagramProvider` driver class.
5. **Respect Token Parentage**: Always check parent-child token inheritance before modifying Facebook or Instagram connection logic.
6. **No Unnecessary Refactoring**: Make minimal, safe, production-ready changes to address requirements.
7. **Keep Documentation In Sync**: Update corresponding `docs/*.md` files and this `GEMINI.md` when architectural or behavioral changes occur.

---

## Historical Fixes

* **Page Access Token Exchange Bug**:
  * *Root Cause*: Page Access Tokens were incorrectly passed to Meta's `/oauth/access_token` `fb_exchange_token` endpoint, triggering HTTP 400 OAuth errors and marking connections `inactive`.
  * *Resolution*: Updated `Connector::refreshToken()` and `Synchronizer::refreshToken()` to verify token type/expiry and skip `fb_exchange_token` for Page tokens.
* **Artificial 60-Day Page Token Expiry**:
  * *Root Cause*: `connectPage()` assigned the User Access Token's 60-day expiry date to Page Access Tokens, causing `isTokenExpired()` to flag valid Page tokens as expired after 60 days.
  * *Resolution*: Set `token_expires_at = null` for Page connections.
* **CLI `site_url()` Failure**:
  * *Root Cause*: `OAuth::redirectUri()` called CodeIgniter's `site_url()` helper without checking if the helper was loaded, causing fatal errors in CLI / standalone unit test execution.
  * *Resolution*: Added `function_exists('site_url')` guard in [`OAuth.php`](file:///Applications/MAMP/HTdocs/tfl-social/src/Providers/Facebook/OAuth.php#L239).

---

## Future Roadmap & Work

Refer to [`docs/ROADMAP.md`](file:///Applications/MAMP/HTdocs/tfl-social/docs/ROADMAP.md) as the authoritative roadmap for planned milestones (local DB `FeedBuilder` query engine, automated scheduler, widget APIs, and additional provider drivers).
