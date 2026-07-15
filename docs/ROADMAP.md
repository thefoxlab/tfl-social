# TFL Social Roadmap

## Vision

Build a provider-independent social media synchronization package that aggregates content from multiple social platforms into a normalized local database with a stable public API.

The package should be reusable in any PHP application while providing first-class CodeIgniter 4 integration.

---

# Current Status

## Foundation

- Composer package
- PSR-4 autoloading
- CodeIgniter 4 integration
- Configuration
- Service registration
- TflSocial Manager
- Contracts
- Provider architecture
- Exceptions

✔ Complete

---

## Database Layer

- Migrations
- Models
- Entities
- Repositories
- Normalized schema

✔ Complete

---

## Connection Management

- OAuth
- Facebook Login
- Facebook Pages
- Instagram Business discovery
- Multi-account support
- Multi-connection support
- Connection persistence

✔ Complete

---

## Provider Layer

Facebook

- Profile
- Feed
- Posts
- Photos
- Videos
- Albums

Instagram

- Profile
- Media
- Media By ID
- Reels
- Carousel
- Stories
- Hashtag Search
- Recent Hashtag Media
- Own Media By Hashtag

✔ Complete

---

# Current Development

## Automatic Token Management

- Transparent token refresh
- Automatic retry
- Connection status management

🚧 In Progress

---

## Synchronizer

- Facebook Profile
- Facebook Feed
- Instagram Profile
- Instagram Media
- UPSERT support
- Media synchronization
- Sync logging

🚧 In Progress

---

## Feed Builder

The Feed Builder should read only from the local database.

Features

- Latest
- Oldest
- Account filters
- Multiple accounts
- Platform filters
- Type filters
- Pagination

🚧 Planned

---

## Scheduler

- Manual synchronization
- Scheduled synchronization
- Cron support
- Incremental synchronization

🚧 Planned

---

## Widget API

- JSON endpoints
- Feed API
- Filtering
- Pagination

📋 Planned

---

## JavaScript Widgets

- Grid
- Masonry
- Carousel
- Responsive layouts
- Theme support

📋 Planned

---

# Future Providers

- LinkedIn
- Threads
- YouTube
- TikTok
- X (Twitter)

---

# Future Features

- Webhooks
- Queue support
- Analytics
- AI content generation
- Search
- Hashtag feeds
- Content moderation
- Multi-language support
- Widget Builder
- Theme Builder

---

# Version Roadmap

## Version 0.9

- Automatic token refresh
- Synchronizer
- Feed Builder

## Version 1.0

- Scheduler
- Widget API
- Documentation
- Testing
- Performance
- Security review
- Production release

---

# Development Principles

- Provider independent
- Database-first architecture
- Stable public API
- SOLID
- PSR-12
- Strict typing
- Dependency Injection
- Repository pattern
- Framework friendly
- Composer installable
- Backwards compatible
- Production ready