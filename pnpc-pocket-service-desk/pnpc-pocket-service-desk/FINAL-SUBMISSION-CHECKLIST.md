# PNPC Pocket Service Desk — Final Submission Checklist (v1.1.3)

This checklist is intended for internal records and for WordPress.org plugin review readiness.

## Build identifiers
- Plugin folder slug: `pnpc-pocket-service-desk`
- Plugin header Version: `1.1.3`
- readme.txt Stable tag: `1.1.3`
- Text domain: `pnpc-pocket-service-desk`

## WPCS / PHPCS
- `phpcs.xml.dist` present at plugin root
- WPCS rulesets enabled: `WordPress`, `WordPress-Extra`, `WordPress-Docs`
- Excludes set for vendor/node_modules/build/dist/tests/tmp

## Reviewer red flags (passed-by-inspection)
- No references to “Pro”, “tiers”, paid gating, license checks, or trialware in code/readme
- No hidden features behind capability flags without UI disclosure
- No remote/CDN assets required for core functionality (Select2 bundled if used)

## Security & Privacy
- Nonces verified on state-changing admin actions (where applicable)
- Admin-post handlers use capability checks
- Attachment handling uses secure download endpoint and validates permissions
- `uninstall.php` present
- No unnecessary collection or transmission of personal data

## First-run UX
- Clean install sets activation redirect flag only when no ticket history exists
- Setup Wizard redirect works after activation
- Setup Wizard prompt/notice appears on:
  - Plugins screen
  - Dashboard
  - Service Desk admin screens

## Packaging
- No dev-only artifacts that violate WP.org guidelines
- No bundled PHARs or executables
- No dependency on Composer at runtime

## Quick sanity checks (recommended)
- Fresh install + activate → redirects to Setup Wizard
- Wizard completion creates/attaches required pages
- Front-end shortcodes render for logged-in users
- Create ticket + reply + attachment upload/download works
- Admin: Tickets list/status tabs load without warnings/notices

