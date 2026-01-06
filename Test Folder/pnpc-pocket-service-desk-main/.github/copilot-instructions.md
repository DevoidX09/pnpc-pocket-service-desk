# GitHub Copilot Instructions for PNPC Pocket Service Desk

## Project Overview

This is a WordPress plugin that provides a comprehensive service desk solution for managing customer support tickets with WooCommerce integration. The plugin follows WordPress coding standards and is built with PHP 7.4+.

## Technology Stack

- **Platform**: WordPress 5.8+
- **Language**: PHP 7.4+
- **Database**: MySQL 5.6+
- **Optional Integration**: WooCommerce
- **Frontend**: JavaScript (jQuery), CSS
- **Code Standards**: WordPress Coding Standards (WPCS)

## Architecture

### MVC-like Structure
- **Models**: `includes/class-pnpc-psd-ticket.php`, `includes/class-pnpc-psd-ticket-response.php`
- **Views**: Separate template files in `admin/views/` and `public/views/`
- **Controllers**: `admin/class-pnpc-psd-admin.php`, `public/class-pnpc-psd-public.php`

### Core Components
- **Main Orchestrator**: `includes/class-pnpc-psd.php`
- **Hook Management**: `includes/class-pnpc-psd-loader.php`
- **Activation/Deactivation**: `includes/class-pnpc-psd-activator.php`, `includes/class-pnpc-psd-deactivator.php`
- **Internationalization**: `includes/class-pnpc-psd-i18n.php`

## Coding Standards

### WordPress Coding Standards
- **MUST** follow WordPress Coding Standards (enforced via PHPCS)
- Use tabs for indentation, not spaces
- Follow WordPress naming conventions:
  - Functions: `snake_case`
  - Classes: `Capitalized_Snake_Case` with class prefix `PNPC_PSD_`
  - Constants: `UPPERCASE_SNAKE_CASE` with prefix `PNPC_PSD_`
  - File names: `class-pnpc-psd-*.php` for classes, lowercase with hyphens

### Security Requirements
- **ALWAYS** check user capabilities before performing privileged operations
- **ALWAYS** verify nonces on AJAX requests and form submissions
- **ALWAYS** sanitize input using WordPress functions:
  - `sanitize_text_field()` for simple text
  - `sanitize_email()` for emails
  - `wp_kses_post()` for HTML content
- **ALWAYS** escape output using:
  - `esc_html()` for HTML content
  - `esc_attr()` for HTML attributes
  - `esc_url()` for URLs
- **ALWAYS** use prepared statements with `$wpdb->prepare()` for database queries
- **NEVER** allow direct file access - include `if (!defined('ABSPATH')) { exit; }`

### Database Operations
- Use WordPress `$wpdb` class for all database operations
- **ALWAYS** use prepared statements for queries with user input
- Table names:
  - `wp_pnpc_psd_tickets` - Main tickets table
  - `wp_pnpc_psd_ticket_responses` - Ticket responses
  - `wp_pnpc_psd_ticket_attachments` - Attachments (reserved for future)
- Use `$wpdb->prefix` to ensure compatibility with custom table prefixes

## File Organization

### Plugin Root (`pnpc-pocket-service-desk/`)
```
├── admin/                      # Admin interface
│   ├── class-pnpc-psd-admin.php
│   └── views/                  # Admin templates
├── public/                     # Public/customer interface
│   ├── class-pnpc-psd-public.php
│   └── views/                  # Public templates
├── includes/                   # Core classes
├── assets/                     # CSS and JavaScript
│   ├── css/
│   └── js/
├── languages/                  # Internationalization
├── composer.json               # Dev dependencies
├── phpcs.xml                   # Code standards config
└── pnpc-pocket-service-desk.php  # Main plugin file
```

## Development Workflow

### Linting
```bash
# Check code standards
composer run phpcs

# Auto-fix code standards issues
composer run phpcbf
```

### Testing
- No automated tests are currently configured
- Manual testing required for new features
- Test both admin and customer interfaces
- Verify email notifications are sent correctly

### Adding New Features

1. **For Admin Features**: 
   - Add methods to `admin/class-pnpc-psd-admin.php`
   - Create view templates in `admin/views/`
   - Add AJAX handlers if needed
   - Register hooks via the loader

2. **For Public Features**:
   - Add methods to `public/class-pnpc-psd-public.php`
   - Create view templates in `public/views/`
   - Register shortcodes if needed
   - Add AJAX handlers if needed

3. **For Database Changes**:
   - Modify `includes/class-pnpc-psd-activator.php`
   - Use `dbDelta()` for table schema changes
   - Test activation/deactivation carefully

## User Roles & Capabilities

### Custom Roles
- `service_desk_agent`: Can view and respond to tickets
- `service_desk_manager`: Agent capabilities + delete tickets + manage settings

### Capability Checks
- Use `current_user_can()` for permission checks
- Agent capabilities: `view_service_desk_tickets`, `respond_to_tickets`
- Manager capabilities: All agent caps + `delete_tickets`, `manage_service_desk_settings`

## Shortcode System

When adding or modifying shortcodes:
- Register in `public/class-pnpc-psd-public.php`
- Prefix all shortcodes with `pnpc_`
- Existing shortcodes:
  - `[pnpc_service_desk]` - Dashboard
  - `[pnpc_create_ticket]` - Create ticket form
  - `[pnpc_my_tickets]` - User's tickets list
  - `[pnpc_ticket_detail]` - Single ticket view
  - `[pnpc_profile_settings]` - Profile settings

## AJAX Handling

### Pattern for AJAX Endpoints
1. Register action in loader: `add_action('wp_ajax_action_name', ...)`
2. Verify nonce: `check_ajax_referer('nonce_action', 'nonce')`
3. Check capabilities: `if (!current_user_can('capability')) { wp_die(); }`
4. Sanitize inputs
5. Perform operation
6. Return JSON: `wp_send_json_success()` or `wp_send_json_error()`

## Email Notifications

Email templates use simple HTML formatting. When modifying:
- Use inline CSS for styling
- Keep HTML simple and compatible
- Include ticket number for reference
- Set appropriate `From` and `Reply-To` headers

## WooCommerce Integration

- Check if WooCommerce is active: `class_exists('WooCommerce')`
- Integration is optional - plugin works without it
- Links to shop and account pages when available

## Internationalization

- Text domain: `pnpc-pocket-service-desk`
- Use `__()` for translatable strings
- Use `_e()` for echoed strings
- Use `esc_html__()` or `esc_html_e()` when escaping is needed
- Always include text domain in translation functions

## Constants

Plugin defines these constants:
- `PNPC_PSD_VERSION` - Plugin version
- `PNPC_PSD_PLUGIN_DIR` - Plugin directory path
- `PNPC_PSD_PLUGIN_URL` - Plugin URL
- `PNPC_PSD_PLUGIN_BASENAME` - Plugin basename

## Common Patterns

### Loading Templates
```php
include PNPC_PSD_PLUGIN_DIR . 'path/to/template.php';
```

### Getting Current User
```php
$current_user = wp_get_current_user();
$user_id = get_current_user_id();
```

### Enqueueing Assets
```php
wp_enqueue_style('handle', PNPC_PSD_PLUGIN_URL . 'path/to/style.css', [], PNPC_PSD_VERSION);
wp_enqueue_script('handle', PNPC_PSD_PLUGIN_URL . 'path/to/script.js', ['jquery'], PNPC_PSD_VERSION, true);
```

## Key Business Logic

### Ticket Lifecycle
1. **Open** - New ticket created by customer
2. **In Progress** - Agent is working on it
3. **Waiting** - Awaiting customer response
4. **Closed** - Resolved

### Priority Levels
- Low
- Normal (default)
- High
- Urgent

### Ticket Numbering
- Format: `PNPC-{timestamp}{random}`
- Generated in `includes/class-pnpc-psd-ticket.php`

## Important Notes

- **DO NOT** remove or modify security checks
- **DO NOT** bypass capability checks
- **DO NOT** use raw SQL queries without preparation
- **ALWAYS** maintain backward compatibility with WordPress 5.8+
- **ALWAYS** test with both admin and customer user roles
- **ALWAYS** verify changes don't break existing functionality
- **ALWAYS** maintain the plugin's class-based architecture

## When Making Changes

1. Understand the existing pattern before modifying
2. Follow the established naming conventions
3. Add security checks (nonces, capabilities, sanitization)
4. Run PHPCS before committing
5. Test both admin and customer flows
6. Update documentation if adding new features
7. Maintain consistency with existing code style
