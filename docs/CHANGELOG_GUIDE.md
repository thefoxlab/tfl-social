# TFL Social Changelog & Documentation Standards

## Overview

This guide establishes the rules and conventions for maintaining `CHANGELOG.md` and repository documentation across all version releases of `thefoxlab/tfl-social`.

---

# Versioning Strategy

This project adheres to **Semantic Versioning (SemVer 2.0.0)**: `MAJOR.MINOR.PATCH`

- **MAJOR**: Incompatible API or breaking architectural changes.
- **MINOR**: New features, new provider drivers, or non-breaking API enhancements.
- **PATCH**: Backward-compatible bug fixes, security patches, or documentation corrections.

---

# Changelog Format

`CHANGELOG.md` follows the **Keep a Changelog 1.1.0** conventions.

### Structure Template

```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- New features or public SDK methods.

### Changed
- Changes in existing functionality or signatures.

### Deprecated
- Soon-to-be removed features.

### Removed
- Removed features or endpoints.

### Fixed
- Bug fixes.

### Security
- Vulnerability resolutions or security enhancements.

## [2.0.0] - YYYY-MM-DD
...
```

---

# Documentation Update Rules

When making changes to the codebase, engineers must verify and update the corresponding documentation files:

1. **`API.md`**: Update whenever public SDK methods (`TflSocial`, `Connector`, `Synchronizer`, `FeedBuilder`) or Graph wrappers change.
2. **`ARCHITECTURE.md`**: Update when modifying system design, layer responsibilities, data flow, or package structure.
3. **`DATABASE.md`**: Update when adding or altering migrations, table schemas, keys, or field definitions.
4. **`ROADMAP.md`**: Update feature implementation statuses (`✔ Complete`, `🚧 In Progress`, `📋 Planned`) following release milestones.
5. **`Synchronizer.md`**: Update when modifying synchronization logic, UPSERT mechanics, or payload mappings.
6. **`TokenManagement.md`**: Update when altering OAuth workflows, token storage, expiry buffers, or parent-child token propagation.

---

# Guidelines for Documentation Maintenance

- **Primary Source of Truth**: The actual PHP source code (`src/`) is the ultimate reference. Never document planned or theoretical features as already implemented.
- **Forbidden Filenames**: Do NOT create ad-hoc documentation files (e.g. `AI_*.md`). Keep all documentation restricted to the pre-established `docs/` files.
- **PSR-12 Compliance**: Code snippets inside markdown documents must adhere strictly to PSR-12 and strict PHP 8.2+ typing conventions.
