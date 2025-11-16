# Installation and Usage Guide

## Installation

### Method 1: Manual Installation

1. Download or clone this repository
2. Copy the `pnpc-pocket-service-desk` directory to your WordPress installation's `wp-content/plugins/` directory
3. Log in to your WordPress admin panel
4. Navigate to **Plugins > Installed Plugins**
5. Find "PNPC Pocket Service Desk" and click **Activate**

### Method 2: Upload via WordPress Admin

1. Download the plugin as a ZIP file
2. Log in to your WordPress admin panel
3. Navigate to **Plugins > Add New > Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. After installation, click **Activate Plugin**

## Initial Setup

### 1. Configure Plugin Settings

After activation:
1. Go to **Service Desk > Settings** in the WordPress admin menu
2. Configure the following options:
   - **Email Notifications**: Enable/disable email notifications (recommended: enabled)
   - **Auto-Assign Tickets**: Automatically assign new tickets to available agents
   - **Allowed File Types**: Set allowed file extensions for attachments

### 2. Assign User Roles

The plugin creates two custom roles:
- **Service Desk Agent**: Can view and respond to tickets
- **Service Desk Manager**: Can manage all aspects of tickets including deletion

To assign roles:
1. Go to **Users** in WordPress admin
2. Edit a user
3. Change their role to "Service Desk Agent" or "Service Desk Manager"

**Note**: Administrators automatically have all permissions.

### 3. Create Pages for Customer Interface

Create the following pages in WordPress and add the specified shortcodes:

#### Service Desk Dashboard (e.g., `/service-desk/`)
```
[pnpc_service_desk]
```

#### Create Ticket Page (e.g., `/create-ticket/`)
```
[pnpc_create_ticket]
```

#### My Tickets Page (e.g., `/my-tickets/`)
```
[pnpc_my_tickets]
```

#### Ticket Detail Page (e.g., `/ticket-detail/`)
```
[pnpc_ticket_detail]
```

#### Profile Settings Page (e.g., `/profile-settings/`)
```
[pnpc_profile_settings]
```

**Tip**: Add these pages to your site's navigation menu for easy customer access.

## Customer Usage

### Creating a Support Ticket

1. Customers must be logged in to create tickets
2. Navigate to the "Create Ticket" page
3. Fill in the following fields:
   - **Subject**: Brief description of the issue (required)
   - **Priority**: Low, Normal, High, or Urgent
   - **Description**: Detailed explanation of the issue (required)
4. Click "Create Ticket"
5. A ticket number will be assigned (format: PNPC-XXXX)
6. Confirmation email will be sent to the customer

### Viewing Tickets

1. Navigate to the "My Tickets" page
2. View all tickets with:
   - Ticket number and status badge
   - Priority indicator
   - Creation date
   - Number of responses
3. Click "View Details" to see the full conversation

### Responding to Tickets

1. Open a ticket from the "My Tickets" page
2. Read the staff responses
3. Type your reply in the "Add a Reply" section
4. Click "Send Reply"
5. The response will be added to the conversation
6. Staff will be notified via email

### Uploading Profile Image/Logo

1. Navigate to the "Profile Settings" page
2. Click "Choose Image" button
3. Select an image file (JPEG, PNG, or GIF, max 2MB)
4. The image will be uploaded automatically
5. Your profile image will appear on tickets and responses

### WooCommerce Integration

If WooCommerce is installed:
1. Access the shop from the Service Desk dashboard
2. View and purchase products
3. Access your WooCommerce account from Profile Settings

## Admin/Agent Usage

### Viewing Tickets

1. Log in to WordPress admin
2. Navigate to **Service Desk > All Tickets**
3. Use filters to view:
   - All tickets
   - Open tickets only
   - Closed tickets only
4. Click on any ticket to view details

### Responding to Tickets

1. Open a ticket from the tickets list
2. Review the ticket description and conversation
3. Type your response in the "Add Response" section
4. Click "Add Response"
5. The customer will be notified via email

### Managing Ticket Status

From the ticket detail page:
1. Use the **Status** dropdown to change ticket status:
   - **Open**: New ticket waiting for response
   - **In Progress**: Currently being worked on
   - **Waiting on Customer**: Awaiting customer reply
   - **Closed**: Issue resolved
2. Status changes are saved automatically

### Assigning Tickets

From the ticket detail page:
1. Use the **Assign To** dropdown
2. Select an agent from the list
3. The assignment is saved automatically
4. Assigned agent can see the ticket in their view

### Deleting Tickets

**Note**: Only Service Desk Managers and Administrators can delete tickets.

1. Open the ticket
2. Scroll to the "Danger Zone" section
3. Click "Delete Ticket"
4. Confirm the deletion
5. **Warning**: This action cannot be undone

## Email Notifications

The plugin sends automatic emails for:

### Customer Notifications
- **New ticket created**: Confirmation with ticket number
- **Staff response**: Alert when staff replies to ticket

### Admin Notifications
- **New ticket**: Alert when customer creates ticket
- **Customer response**: Alert when customer replies

### Customizing Email Content

To customize email content, you can use WordPress filters in your theme's `functions.php`:

```php
// Example: Customize new ticket email subject
add_filter('pnpc_psd_new_ticket_email_subject', function($subject, $ticket_number) {
    return "Your Support Request #{$ticket_number} - We're Here to Help!";
}, 10, 2);
```

## Troubleshooting

### Common Issues

#### Customers Can't Create Tickets
- Ensure customers are logged in
- Check that the user has the "customer" or "subscriber" role
- Verify the shortcode `[pnpc_create_ticket]` is on the page

#### Emails Not Being Sent
- Check WordPress email configuration
- Verify "Email Notifications" is enabled in Settings
- Consider using an SMTP plugin like WP Mail SMTP

#### Profile Images Not Uploading
- Check file size is under 2MB
- Verify file type is JPEG, PNG, or GIF
- Ensure WordPress uploads directory is writable

#### 404 Error on Ticket Pages
- Go to **Settings > Permalinks** in WordPress admin
- Click "Save Changes" to flush rewrite rules

### Getting Support

For additional support:
1. Check the plugin's README.md file
2. Review the CHANGELOG.md for known issues
3. Visit the GitHub repository for updates

## Security Best Practices

1. **Keep WordPress Updated**: Always use the latest version
2. **Strong Passwords**: Require strong passwords for all users
3. **Limit Admin Access**: Only give necessary permissions
4. **Regular Backups**: Backup your database regularly
5. **SSL Certificate**: Use HTTPS for all pages

## Uninstalling

To completely remove the plugin:

1. Deactivate the plugin from **Plugins** page
2. Click **Delete** to remove plugin files
3. **Note**: Plugin options will be removed, but ticket data is preserved
4. To remove all data including tickets, uncomment the table drop commands in `uninstall.php` before deletion

## Advanced Configuration

### Database Tables

The plugin creates three tables:
- `wp_pnpc_psd_tickets`: Stores ticket information
- `wp_pnpc_psd_ticket_responses`: Stores responses
- `wp_pnpc_psd_ticket_attachments`: Reserved for future use

### Custom Capabilities

The plugin uses these custom capabilities:
- `pnpc_psd_view_tickets`: View all tickets
- `pnpc_psd_respond_to_tickets`: Respond to tickets
- `pnpc_psd_assign_tickets`: Assign tickets to agents
- `pnpc_psd_delete_tickets`: Delete tickets
- `pnpc_psd_manage_settings`: Access settings
- `pnpc_psd_create_tickets`: Create new tickets
- `pnpc_psd_view_own_tickets`: View own tickets only

## Development

### WordPress Coding Standards

To check code quality:

```bash
cd pnpc-pocket-service-desk
composer install
composer run phpcs
```

To automatically fix some issues:

```bash
composer run phpcbf
```

### Contributing

Contributions are welcome! Please follow WordPress Coding Standards and submit pull requests to the GitHub repository.
