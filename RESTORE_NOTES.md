# Plugin Restore from Backup (2026-11-23)

## Purpose

This PR restores the previously working version of the plugin from the user's local backup (branch `restore/backup-20261123`) to replace the temporary changes on `dev/timezone-and-timestamp-fixes` that caused regressions.

## Issues with Previous State

Admin features were broken and non-functional:
- Cannot respond to tickets
- Cannot assign tickets to agents  
- Cannot change ticket status

The user restored a local working copy and uploaded it to the dev branch.

## Testing Checklist

Before merging, complete the following verification steps:

1. **Plugin Deployment**
   - On dev site, deactivate the plugin
   - Replace plugin files from this branch
   - Reactivate the plugin

2. **Admin Feature Verification**
   - Open Service Desk → Tickets
   - Open a ticket detail page
   - Add a response to the ticket
   - Change the ticket status
   - Assign an agent to the ticket

3. **Public Feature Verification**
   - As a normal user, view My Tickets page
   - View individual Ticket Detail pages
   - Create a new ticket

4. **Database Compatibility**
   - Ensure no SQL errors appear in logs
   - If schema mismatch occurs, restore DB backup matching this plugin version

5. **Timestamp Functionality**
   - Confirm last-view timestamps behave as before restore
   - Confirm response timestamps display correctly

## Branch Preservation

The previous state of `dev/timezone-and-timestamp-fixes` is preserved in branch `snapshot/dev-before-restore` for later inspection and selective re-application of safe fixes.

## Review Notes

- **Labels**: restore, high-priority
- **Status**: Ready for review - DO NOT MERGE without approval
- **Reviewers**: Default repository reviewers requested
