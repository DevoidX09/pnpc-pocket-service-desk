# Changelog

All notable changes to PNPC Pocket Service Desk will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-01-06

### Added
- Comprehensive trash system for tickets with soft delete functionality
- Bulk actions for managing multiple tickets at once
- "Trash" tab in admin tickets list with trashed ticket count
- Checkbox selection for individual and bulk ticket operations
- Bulk trash operation - move multiple tickets to trash simultaneously
- Bulk restore operation - restore multiple tickets from trash simultaneously
- Bulk permanent delete operation - permanently delete multiple tickets
- Confirmation dialogs for all destructive actions
- Real-time success/error messages for bulk operations
- `deleted_at` column to tickets, responses, and attachments tables for soft delete tracking
- Database migration routine for existing installations to add new columns
- New methods in PNPC_PSD_Ticket class:
  - `trash()` - Move single ticket to trash
  - `bulk_trash()` - Move multiple tickets to trash
  - `restore()` - Restore single ticket from trash
  - `bulk_restore()` - Restore multiple tickets from trash
  - `delete_permanently()` - Hard delete ticket and all related data
  - `bulk_delete_permanently()` - Hard delete multiple tickets
  - `get_trashed()` - Get all trashed tickets
  - `get_trashed_count()` - Get count of trashed tickets
- Cascade operations for responses and attachments (trash/restore/delete with ticket)
- Protection in public interfaces to automatically exclude trashed tickets
- Updated CSS styling for bulk action controls and responsive design
- Three new AJAX handlers for bulk operations with proper security checks

### Changed
- Updated `get_all()` method to exclude trashed tickets by default
- Updated `get_by_user()` method to exclude trashed tickets by default
- Updated `get_count()` method to exclude trashed tickets from counts
- Database version bumped to 1.1.0
- Improved admin tickets list view with better responsive layout

### Security
- All bulk operations require `pnpc_psd_delete_tickets` capability
- Nonce verification on all new AJAX endpoints
- Proper sanitization and validation of ticket IDs in bulk operations
- Prepared statements for all database queries

### Database
- Added `deleted_at` column to `wp_pnpc_psd_tickets` table
- Added `deleted_at` column to `wp_pnpc_psd_ticket_responses` table
- Added `deleted_at` column to `wp_pnpc_psd_ticket_attachments` table
- Added indexes on `deleted_at` columns for performance
- Automatic migration on plugin activation for existing installations

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

[1.1.0]: https://github.com/DevoidX09/pnpc-pocket-service-desk/releases/tag/1.1.0
[1.0.0]: https://github.com/DevoidX09/pnpc-pocket-service-desk/releases/tag/1.0.0
