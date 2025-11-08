# Changelog

All notable changes to `laravel-db-chat` will be documented in this file.

## [Unreleased]

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

## [1.0.0] - 2025-11-08

### Added
- First stable release

---

## Versioning

This package follows [Semantic Versioning](https://semver.org/).

## Release Process

1. Update this CHANGELOG
2. Update version in composer.json
3. Tag the release
4. Push to GitHub
5. Publish to Packagist
