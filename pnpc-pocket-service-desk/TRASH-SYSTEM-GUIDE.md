# Trash System Guide

## Overview

The PNPC Pocket Service Desk now includes a comprehensive trash system that allows administrators and managers to soft-delete tickets, restore them from trash, or permanently delete them. This feature includes bulk operations for efficient ticket management.

## Features

### 1. Soft Delete (Trash)
- Tickets can be moved to trash without permanent deletion
- When a ticket is trashed:
  - The ticket's `deleted_at` timestamp is set
  - All related responses are also soft-deleted
  - All related attachments are also soft-deleted
  - The ticket is hidden from all active views
  - The ticket is hidden from customer views

### 2. Restore from Trash
- Trashed tickets can be restored to their previous state
- When a ticket is restored:
  - The `deleted_at` timestamp is cleared
  - All related responses are restored
  - All related attachments are restored
  - The ticket reappears in active views with its original status

### 3. Permanent Delete
- Trashed tickets can be permanently deleted
- When permanently deleted:
  - The ticket is removed from the database
  - All responses are permanently deleted
  - All attachments are permanently deleted
  - All related user meta is cleaned up
  - **This action cannot be undone**

### 4. Bulk Operations
- Select multiple tickets using checkboxes
- Perform actions on multiple tickets simultaneously:
  - Move to Trash (bulk)
  - Restore (bulk)
  - Delete Permanently (bulk)
- Confirmation dialogs prevent accidental bulk operations

## User Interface

### Trash Tab
- New "Trash" tab in the admin tickets list
- Shows count of trashed tickets: "Trash (X)"
- Displays all trashed tickets sorted by deletion date

### Bulk Actions
- Checkbox column for ticket selection
- "Select All" checkbox in table header
- Bulk Actions dropdown with context-specific options:
  - When viewing active tickets: "Move to Trash"
  - When viewing trash: "Restore" and "Delete Permanently"
- "Apply" button to execute selected action

### Visual Feedback
- Success/error messages after operations
- Real-time ticket count updates
- Page reloads automatically after successful operations
- Disabled buttons during processing to prevent duplicate requests

## Permissions

Only users with the `pnpc_psd_delete_tickets` capability can:
- Move tickets to trash
- Restore tickets from trash
- Permanently delete tickets

This includes:
- Service Desk Managers
- Administrators

## Technical Details

### Database Schema
Three new columns added to existing tables:
- `wp_pnpc_psd_tickets.deleted_at` (datetime, default NULL)
- `wp_pnpc_psd_ticket_responses.deleted_at` (datetime, default NULL)
- `wp_pnpc_psd_ticket_attachments.deleted_at` (datetime, default NULL)

All columns are indexed for performance.

### Migration
- Automatic migration runs on plugin activation
- Safe for existing installations
- Checks current DB version before running
- Updates DB version to 1.1.0 after successful migration

### API Methods

#### PNPC_PSD_Ticket Class
```php
// Trash operations
PNPC_PSD_Ticket::trash($ticket_id);
PNPC_PSD_Ticket::bulk_trash($ticket_ids);

// Restore operations
PNPC_PSD_Ticket::restore($ticket_id);
PNPC_PSD_Ticket::bulk_restore($ticket_ids);

// Permanent delete operations
PNPC_PSD_Ticket::delete_permanently($ticket_id);
PNPC_PSD_Ticket::bulk_delete_permanently($ticket_ids);

// Query methods
PNPC_PSD_Ticket::get_trashed($args);
PNPC_PSD_Ticket::get_trashed_count();
PNPC_PSD_Ticket::get_all(['include_trashed' => true]); // Optional parameter
```

#### PNPC_PSD_Ticket_Response Class
```php
// Cascade operations
PNPC_PSD_Ticket_Response::trash_by_ticket($ticket_id);
PNPC_PSD_Ticket_Response::restore_by_ticket($ticket_id);
```

### AJAX Endpoints
- `wp_ajax_pnpc_psd_bulk_trash_tickets`
- `wp_ajax_pnpc_psd_bulk_restore_tickets`
- `wp_ajax_pnpc_psd_bulk_delete_permanently_tickets`

All endpoints:
- Verify nonces for security
- Check user capabilities
- Sanitize and validate input
- Return JSON responses
- Log errors appropriately

## Security

### Nonce Verification
All AJAX requests verify WordPress nonces to prevent CSRF attacks.

### Capability Checks
All operations check user capabilities before execution.

### Input Validation
- All ticket IDs are sanitized using `absint()`
- Arrays are validated and filtered
- SQL queries use prepared statements

### Data Integrity
- Cascade operations ensure related data is handled correctly
- Transactions prevent partial operations
- Indexes ensure query performance

## Customer Protection

### Automatic Filtering
- Public queries automatically exclude trashed tickets
- Shortcodes filter out trashed tickets
- Customers never see trashed tickets in:
  - `[pnpc_my_tickets]` shortcode
  - `[pnpc_service_desk]` dashboard
  - Ticket detail views (returns "not found" for trashed tickets)

### Access Control
- Customers cannot access trashed tickets by ID
- Only admins/managers can view trash
- Proper permission checks on all endpoints

## Best Practices

### When to Use Trash
- Customer requests ticket deletion
- Duplicate tickets need cleanup
- Spam or test tickets need removal
- Temporary cleanup before review

### When to Restore
- Ticket trashed by mistake
- Customer changes their mind
- Issue resurfaces and needs reopening

### When to Permanently Delete
- Complying with data deletion requests
- Final cleanup of old/resolved issues
- Removing sensitive information
- Regular maintenance after review period

### Recommendations
1. Review trash periodically (weekly/monthly)
2. Set internal policy for trash retention period
3. Always confirm before permanent deletion
4. Use bulk operations for efficiency
5. Document deletion reasons in your processes

## Troubleshooting

### Tickets Not Appearing in Trash
- Check user capabilities
- Verify DB migration completed
- Check `deleted_at` column exists

### Restore Not Working
- Verify user has `pnpc_psd_delete_tickets` capability
- Check for database errors in logs
- Ensure ticket exists in trash

### Bulk Operations Timing Out
- Reduce selection size for very large operations
- Increase PHP max_execution_time if needed
- Consider chunking operations for 100+ tickets

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and detailed changes.
