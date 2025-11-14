# Changelog

All notable changes to PNPC Pocket Service Desk will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Dashboard profile block with compact profile image display and secure upload form
- File attachment support for ticket creation (max 5MB per file)
- Admin settings for products mode (`pnpc_psd_products_mode`) and per-user product assignments (`pnpc_psd_enable_user_products`)
- User profile fields in admin for assigning products to specific users (`pnpc_psd_assigned_products`)
- `attach_file()` method in `PNPC_PSD_Ticket` class to associate attachments with tickets
- Profile block embedded in service desk dashboard view with greeting and quick upload
- Support for multiple file attachments in ticket creation form
- Admin hooks for user profile field rendering and saving

### Changed
- Service desk dashboard now displays embedded profile block instead of separate greeting
- Create ticket form accepts file attachments with proper nonce verification
- Public AJAX handler `ajax_create_ticket` now processes file uploads and attachments
- Admin settings page includes new fields for products mode and PRO feature toggle

### Technical
- Added file upload validation (size limits: profile images 2MB, ticket attachments 5MB per file)
- Enhanced security with nonce verification on all upload handlers
- Implemented `wp_handle_upload` and `wp_insert_attachment` for proper WordPress file handling
- User meta `pnpc_psd_profile_image` stores profile image URL
- Ticket attachments stored via attachment post type and linked in `wp_pnpc_psd_ticket_attachments` table

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
