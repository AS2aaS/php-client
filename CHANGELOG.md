# Changelog

All notable changes to the AS2aaS PHP Client will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-09-28

### Added
- Complete mock client implementation for unit testing without API calls
- Comprehensive testing documentation and examples
- Laravel testing integration patterns
- Enhanced test coverage (60 tests, 226 assertions)
- Professional README with Laravel instructions prominently featured
- Mock data seeding with realistic pharmaceutical partners
- Master partner inheritance simulation in mock client
- Webhook signature verification testing
- Direct mock data access for test assertions

### Fixed
- All API endpoint paths corrected (removed leading slashes)
- Message sending API integration with proper idempotency keys
- Webhook creation API integration with UUID idempotency keys
- Data models updated to match actual API response structures
- Partner, Message, Account, and Tenant models field mapping
- Test suite compatibility with updated API responses
- PHPUnit test failures resolved

### Changed
- Updated README to be business-focused and professional
- Moved Laravel integration instructions to prominent position
- Enhanced error handling in HttpClient for missing API keys
- Improved mock client interface to match real client exactly

### Improved
- Authentication flow with better error messages
- Tenant context management with X-Tenant-ID headers
- Master partner inheritance with account ID in API paths
- Test coverage for all core models and modules
- Documentation clarity for enterprise and DSCSA use cases

## [1.0.0] - 2025-09-27

### Added
- Initial release of AS2aaS PHP Client
- Core Client class with multiple initialization patterns
- Single baked-in API endpoint (https://api.as2aas.com/v1) with automatic test/live detection
- Partners module with full CRUD operations and master partner support
- Messages module with send/receive functionality and batch operations
- Certificates module with upload, validation, and generation capabilities
- Accounts module for enterprise account management
- Tenants module for multi-tenant operations
- Webhooks module with signature verification and event handling
- Billing module for subscription and usage management
- Sandbox module for testing and development
- Partnerships module for advanced partner onboarding
- Utils module with helper functions for EDI validation and content type detection
- Comprehensive error handling with specific exception types
- Test environment support with test API keys
- Full PHPUnit test coverage
- Laravel service provider and facade integration
- Automatic content type detection for messages
- Support for environment variable configuration
- Global configuration management
- Retry logic with exponential backoff
- Certificate expiry monitoring and alerts
- Partner health monitoring
- Message delivery confirmation with timeout
- Batch message sending capabilities
- Real-time webhook event processing
- Master partner inheritance system
- Multi-tenant architecture support

### Features
- **Simple API**: Send AS2 messages in just 3 lines of code
- **Enterprise Ready**: Account-based architecture with master partners
- **Laravel Integration**: First-class Laravel support with service provider
- **Comprehensive Testing**: Test environment support and full test coverage
- **Error Handling**: Detailed exceptions with retry capabilities
- **Certificate Management**: Easy upload, validation, and generation
- **Webhook Support**: Real-time notifications with signature verification
- **Content Detection**: Automatic EDI, XML, JSON content type detection
- **Batch Operations**: Efficient bulk message and partner operations
- **Development Tools**: Sandbox environment and sample data generation

### Documentation
- Complete README with usage examples
- Laravel integration guide
- API reference documentation
- PHPUnit testing examples
- Error handling guide
- Webhook implementation examples

### Requirements
- PHP 8.0 or higher
- Guzzle HTTP client 7.0+
- OpenSSL extension for certificate operations
- JSON extension for API communication

### Supported Features
- AS2 message sending and receiving
- Partner management with inheritance
- Certificate upload and validation
- Real-time webhook notifications
- Multi-tenant account management
- Billing and usage tracking
- EDI content validation
- Sandbox testing environment
- Laravel framework integration
