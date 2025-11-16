# Changelog

All notable changes to PNPC Pocket Service Desk will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-11-14

### Added
- Initial release of PNPC Pocket Service Desk
- Core ticket management system with custom database tables
- Customer-facing interface for creating and managing support tickets
- Admin/Agent dashboard for viewing and responding to tickets
- Ticket status management (open, in-progress, waiting, closed)
- Priority levels for tickets (low, normal, high, urgent)
- User role system with Service Desk Agent and Manager roles
- Email notifications for ticket creation and responses
- AJAX-powered ticket creation and response forms
- Profile image/logo upload functionality for customers
- WooCommerce integration for product viewing and purchasing
- Responsive CSS styling for all interfaces
- WordPress Coding Standards compliance
- Comprehensive shortcode system:
  - `[pnpc_service_desk]` - Dashboard
  - `[pnpc_create_ticket]` - Ticket creation form
  - `[pnpc_my_tickets]` - User's tickets list
  - `[pnpc_ticket_detail]` - Ticket detail view
  - `[pnpc_profile_settings]` - Profile settings
- Admin settings page for plugin configuration
- Ticket assignment system for agents
- Complete internationalization support (i18n ready)
- Security features with nonce verification on all AJAX requests
- Capability-based permission system
- Uninstall cleanup functionality

### Database
- Created `wp_pnpc_psd_tickets` table for ticket storage
- Created `wp_pnpc_psd_ticket_responses` table for response storage
- Created `wp_pnpc_psd_ticket_attachments` table for future attachment support

### Security
- Implemented WordPress nonce verification for all AJAX requests
- Capability-based access control for all features
- Input sanitization and output escaping throughout
- SQL injection prevention using prepared statements
- File upload validation for profile images

[1.0.0]: https://github.com/DevoidX09/pnpc-pocket-service-desk/releases/tag/1.0.0
