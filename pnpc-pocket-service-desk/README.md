# PNPC Pocket Service Desk

A comprehensive WordPress service desk plugin for managing customer support tickets with WooCommerce integration.

## Features

### Customer Features
- **User Authentication**: Customers must log in to access the service desk
- **Ticket Management**:
  - Create new support tickets with priority levels
  - View all past tickets
  - Read responses from support staff
  - Respond to staff replies
- **Profile Management**:
  - Upload profile image or company logo
  - View account information
- **WooCommerce Integration**:
  - View and purchase products directly from the service desk
  - Access WooCommerce account features

### Admin/Agent Features
- **Ticket Dashboard**:
  - View all tickets with filtering by status
  - See ticket statistics (open, closed counts)
- **Ticket Management**:
  - View detailed ticket information
  - Respond to customer tickets
  - Assign tickets to specific agents
  - Update ticket status (open, in-progress, waiting, closed)
  - View ticket priority levels
  - Delete tickets (managers/admins only)
- **User Roles**:
  - Service Desk Agent: Can view, respond to, and assign tickets
  - Service Desk Manager: All agent capabilities plus delete tickets and manage settings
  - Administrator: Full access to all features
- **Settings**:
  - Configure email notifications
  - Set up auto-assignment of tickets
  - Manage allowed file types for attachments

## Installation

1. Upload the `pnpc-pocket-service-desk` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access the Service Desk from the WordPress admin menu
4. Configure settings as needed

## Usage

### For Customers

Use the following shortcodes on your WordPress pages:

- `[pnpc_service_desk]` - Main service desk dashboard
- `[pnpc_create_ticket]` - Ticket creation form
- `[pnpc_my_tickets]` - List of user's tickets
- `[pnpc_ticket_detail]` - Single ticket view (requires ticket_id parameter)
- `[pnpc_profile_settings]` - Profile and image upload settings

### For Admins/Agents

Access the Service Desk from the WordPress admin menu:
- **All Tickets**: View and manage all support tickets
- **Settings**: Configure plugin options

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- WooCommerce (optional, for product integration)

## Database Tables

The plugin creates three custom database tables:

1. `wp_pnpc_psd_tickets` - Stores ticket information
2. `wp_pnpc_psd_ticket_responses` - Stores responses to tickets
3. `wp_pnpc_psd_ticket_attachments` - Stores file attachments (future feature)

## Custom User Roles

The plugin adds two custom user roles:

1. **Service Desk Agent**: Can view and respond to tickets
2. **Service Desk Manager**: Can manage all aspects of the service desk

## Email Notifications

The plugin sends email notifications for:
- New ticket creation (to customer and admins)
- Staff responses (to customers)
- Customer responses (to admins)

## Development

### Coding Standards

This plugin follows WordPress Coding Standards. To check your code:

```bash
composer install
composer run phpcs
```

### File Structure

```
pnpc-pocket-service-desk/
├── admin/
│   ├── class-pnpc-psd-admin.php
│   └── views/
├── public/
│   ├── class-pnpc-psd-public.php
│   └── views/
├── includes/
│   ├── class-pnpc-psd.php
│   ├── class-pnpc-psd-activator.php
│   ├── class-pnpc-psd-deactivator.php
│   ├── class-pnpc-psd-loader.php
│   ├── class-pnpc-psd-i18n.php
│   ├── class-pnpc-psd-ticket.php
│   └── class-pnpc-psd-ticket-response.php
├── assets/
│   ├── css/
│   └── js/
├── languages/
└── pnpc-pocket-service-desk.php
```

## Support

For support and feature requests, please visit the [GitHub repository](https://github.com/DevoidX09/pnpc-pocket-service-desk).

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- Core ticket management functionality
- Customer and admin interfaces
- Email notifications
- WooCommerce integration
- Profile image upload
- Custom user roles
