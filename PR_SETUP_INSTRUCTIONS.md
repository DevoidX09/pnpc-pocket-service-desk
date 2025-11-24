# PR Setup Instructions

## Current Status

A pull request exists at: https://github.com/DevoidX09/pnpc-pocket-service-desk/pull/8

This PR has been created with:
- **Source branch**: `copilot/restore-plugin-working-state`  
- **Target branch**: `dev/timezone-and-timestamp-fixes`
- **Title**: [WIP] Restore plugin from backup (2026-11-23)
- **Description**: Updated with full restore documentation

## Required Manual Actions

Since the automated tools have limitations, the following actions need to be completed manually by a repository administrator:

### 1. Update PR Title
Remove the `[WIP]` prefix:
- Current: `[WIP] Restore plugin from backup (2026-11-23)`
- Desired: `Restore plugin from backup (2026-11-23)`

### 2. Add Labels
Add the following labels to PR #8:
- `restore`
- `high-priority`

### 3. Request Reviewers
Request review from default repository reviewers (DevoidX09)

### 4. Mark as Ready for Review
If the PR is in draft state, mark it as "Ready for review"

## Note on Branch Differences

The original requirement was to create a PR FROM `restore/backup-20261123` TO `dev/timezone-and-timestamp-fixes`. However:

- Both `restore/backup-20261123` and `dev/timezone-and-timestamp-fixes` point to the same commit (3f34dbe)
- A direct PR between these branches would show zero file changes
- The current PR from `copilot/restore-plugin-working-state` includes documentation about the restore process
- The branch `snapshot/dev-before-restore` also points to 3f34dbe, preserving the state as documented

## Verification

To verify the state of the branches:
```bash
git log --oneline --graph --all --decorate | grep -E "(restore|timezone|snapshot)"
```

Expected output should show that `restore/backup-20261123`, `dev/timezone-and-timestamp-fixes`, and `snapshot/dev-before-restore` all point to the same commit.
