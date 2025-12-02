# Changelog

All notable changes to `laravel-db-chat` will be documented in this file.

## [Unreleased]

### Added
-

### Fixed
-

## [v0.1.2] - 2025-11-23

### Added
- Dedicated Form Request classes with reusable validation logic and sensible defaults for conversations, participants, messages, and polling endpoints.
- JSON Resource classes for conversations, participants, messages, and read receipts to provide consistent API payloads.

### Fixed
- Validation now enforces that referenced user IDs actually exist and normalizes rule definitions to array syntax.
- Default pagination and polling parameters are prepared before validation to avoid missing values and null-to-null comparisons in downstream services.
- Message and conversation service responses eagerly load the relationships expected by the new resources, preventing missing data in API responses.

## [v0.1.1] - 2025-11-08

### Changed
- Broadened `illuminate/*` dependency constraints to include Laravel 12 so the package can be installed into Laravel 12 projects.
- Bumped package development version to `0.1.1` in `composer.json`.

### Notes
- This is a metadata/compatibility release. Run tests against Laravel 12 to confirm runtime compatibility.

## [v0.1.0] - 2025-11-07

### Added
- Initial release
- Database-driven chat system for Laravel
- Support for direct (1:1) and group conversations
- Long polling for realtime-ish updates
- Read receipts functionality
- Cursor-based message polling using monotonic IDs
- Rate limiting on all endpoints
- Configurable table names
- Configurable polling timeout and check intervals
- Message pagination support
- Participant management for group chats
- Complete REST API
- Laravel 10 and 11 support
- Comprehensive documentation
- JavaScript client example
- Vue 3 component example
- Service provider with auto-discovery
- Database migrations
- Configuration file

### Security
- Authentication via Laravel Sanctum
- Authorization middleware to ensure users can only access their conversations
- Rate limiting to prevent abuse
- Input validation on all endpoints

---

## Versioning

This package follows [Semantic Versioning](https://semver.org/).

## Release Process

1. Update this CHANGELOG
2. Update version in composer.json
3. Tag the release
4. Push to GitHub
5. Publish to Packagist
