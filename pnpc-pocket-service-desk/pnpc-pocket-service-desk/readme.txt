=== PNPC Pocket Service Desk ===
Contributors: pnpc
Tags: helpdesk, service desk, support, tickets, customer support
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress-native service desk plugin for managing customer support tickets.

== Description ==

PNPC Pocket Service Desk provides a lightweight, WordPress-native ticketing workflow designed for small to mid-sized teams who need a focused service desk without a full-suite platform.

Key capabilities:
* Customers can create tickets, add attachments, and respond to ticket threads.
* Agents/Admins can manage tickets across status tabs (Open, In Progress, Waiting, Closed, Review, Trash).
* Optional WooCommerce integration via extension hooks (the [pnpc_services] shortcode is a placeholder by default).


== Third Party Libraries ==

This plugin bundles UI enhancement assets locally to avoid loading code from external CDNs.

* Select2 (https://select2.org)
  License: MIT

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the ZIP via **Plugins → Add New**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. (Recommended) Run the Setup Wizard (if enabled) to create optional front-end pages and place shortcodes.

== Shortcodes ==

You can place these shortcodes on pages or templates:

* `[pnpc_service_desk]` – Main customer dashboard experience (requires login).
* `[pnpc_create_ticket]` – Ticket submission form.
* `[pnpc_my_tickets]` – Customer ticket list.
* `[pnpc_ticket_detail]` – Ticket detail view (typically linked internally).
* `[pnpc_profile_settings]` – Customer profile/settings area.
* `[pnpc_services]` – Placeholder block for services/integrations (extended via hooks).

== Recommended customer flow (login → dashboard) ==

A common setup is:

1. Add a “Customer Login” link in your site header/menu that points to your dashboard page URL.
2. Place `[pnpc_service_desk]` on the dashboard page.

When a customer visits the dashboard:
* If not logged in, the plugin renders a login prompt on that page.
* After login, the customer sees only their own tickets and content.

If you use a separate “Logout” link/button, point it back to the dashboard (or your desired landing page) so customers can log in/out without visiting `/wp-admin/`.

== Testing Setup==
1. Create a test user  in either a customer or other external role.
2. Open a Incognito Browser session.
3. Navigate directly to the dashboard URL or use your websites external login link if it exists and links to the dashboard. 
4. Login to test making and tracking tickets. 


== Privacy ==

PNPC Pocket Service Desk stores support ticket data in your WordPress database. This includes ticket titles, descriptions, responses, status, priority, assigned agent, and timestamps. If attachments are enabled, uploaded files are stored in your site's WordPress uploads directory and linked to tickets.

The plugin does not send ticket content or attachments to third parties by default. Ticket visibility is enforced using WordPress roles and capabilities; for example, customers can only view their own tickets, while agents and administrators can view tickets according to their permissions.

Data retention on uninstall: by default, plugin data is preserved when the plugin is deleted so that you can reinstall without losing tickets or settings. To permanently remove plugin data on uninstall, enable the “Delete data on uninstall” setting in the plugin settings before uninstalling. When enabled, the uninstall process removes plugin options and deletes plugin-created data.


== Frequently Asked Questions ==

= Do customers see other customers’ tickets? =
No. The front-end queries are scoped so customers only see tickets they created (and replies on those tickets).

= Does the plugin work without WooCommerce? =
Yes. WooCommerce is not required for the core ticketing workflow.

The `[pnpc_services]` block is included as a neutral extension seam. By default, it does not output anything in this free plugin.

== Screenshots ==

1. Customer “My Support Tickets” dashboard (Open/Closed tabs).
2. Public “Create Ticket” form (guest-friendly submission if enabled).
3. Customer ticket detail view (thread + status).
4. Customer reply interface on an existing ticket.
5. User profile settings + dashboard stats view.
6. Admin dashboard stats overview.
7. Admin “All Tickets” list with filters/status tabs and badges.
8. Admin ticket detail view (full thread + management actions).
9. Settings: Core configuration.
10. Settings: Customize (styling/options).
11. Settings: Experience (UX/workflow toggles).

== Changelog ==

= 1.1.5 =
* WordPress.org submission fixes: Remove load_plugin_textdomain() calls (WordPress.org handles translations automatically since WP 4.6+)
* Update "Tested up to" version to WordPress 6.7
* Add .distignore file to exclude development files from distribution
* Standardize version number across all plugin files

= 1.1.4.3 =
* Setup Wizard: clarify Builder shortcode guidance (DIY/custom page reference + login menu note).
* Setup Wizard: remove heavy border styling from Elementor starter templates.



= 1.1.4.2 =
* Setup Wizard copy clarifies DIY shortcode usage.
* Wizard-created Elementor pages default to Canvas template to reduce theme chrome/borders.
= 1.1.4.1 =
* Fix Setup Wizard auto-redirect and admin prompt logic so it is based on actual page configuration (dashboard + ticket view) rather than the setup_completed_at timestamp, preventing false "completed" states.
* Activation now sets first-run Setup Wizard flags whenever required pages are not configured and there is no ticket history (clean installs).

= 1.1.3 =
* Add PHPCS ruleset (phpcs.xml.dist).
* Fix first-run Setup Wizard redirect and show Setup Wizard prompt on Plugins screen.
* Internal: tighten activation redirect flagging for clean installs.

= 1.1.1.4 =
* Version bump for WordPress.org submission.
* Documentation: add/normalize PHPDoc where applicable.

= 1.1.1.3.8 =
* Admin/Public: Improve pagination UX and make paging consistent under refresh.
* Admin: Normalize status display so In Progress renders consistently.
* Admin: Dashboard alerts now include Review queue items.
* Notifications: Add a setting to disable per-agent notification email overrides (default ON).

= 1.1.1.3.7 =
* WordPress.org submission build: bundle Select2 locally (no CDN), normalize readme metadata, remove dev artifacts from release ZIP.

= 1.1.1.3 =
* Distribution packaging cleanup for submission readiness (documentation/dev artifacts excluded from release ZIP).
