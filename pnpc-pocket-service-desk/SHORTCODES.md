# Shortcode Reference

Quick reference for all available shortcodes in PNPC Pocket Service Desk.

## Available Shortcodes

### 1. Service Desk Dashboard
```
[pnpc_service_desk]
```
**Description**: Displays the main service desk dashboard with statistics, quick actions, and recent tickets.

**Recommended Page**: `/service-desk/` or `/support/`

**Features**:
- Welcome message with user name
- Open and total ticket counts
- Quick action buttons
- Recent tickets list
- WooCommerce products link (if installed)

**User Access**: Logged-in users only

---

### 2. Create Ticket Form
```
[pnpc_create_ticket]
```
**Description**: Displays a form for customers to create new support tickets.

**Recommended Page**: `/create-ticket/` or `/new-ticket/`

**Form Fields**:
- Subject (required)
- Priority (Low, Normal, High, Urgent)
- Description (required)

**User Access**: Logged-in users only

---

### 3. My Tickets List
```
[pnpc_my_tickets]
```
**Description**: Displays a list of all tickets created by the current user.

**Recommended Page**: `/my-tickets/`

**Information Shown**:
- Ticket number
- Subject
- Status badge
- Priority level
- Creation date
- Response count
- View details link

**User Access**: Logged-in users only

---

### 4. Ticket Detail View
```
[pnpc_ticket_detail]
```
**Description**: Displays detailed information about a single ticket, including all responses and reply form.

**Recommended Page**: `/ticket-detail/` or `/view-ticket/`

**URL Parameter**: Requires `?ticket_id=X` in the URL

**Features**:
- Full ticket information
- Conversation history
- Staff and customer responses
- Reply form (if ticket is open)
- Back to tickets link

**User Access**: Ticket owner or staff only

**Example URL**: `https://yoursite.com/ticket-detail/?ticket_id=123`

---

### 5. Profile Settings
```
[pnpc_profile_settings]
```
**Description**: Displays profile settings including image/logo upload and account information.

**Recommended Page**: `/profile-settings/` or `/account-settings/`

**Features**:
- Profile image upload (max 2MB, JPEG/PNG/GIF)
- Account information display
- Link to WordPress profile
- WooCommerce integration links (if installed)

**User Access**: Logged-in users only

### 5. Services

**Description**: 

[pnpc_services]
 Renders the Services/Products list. Respects plugin settings:
- "Show Services/Products" (pnpc_psd_show_products)
- "Use User-Specific Services" (pnpc_psd_user_specific_products)
- Allocated products use user meta key pnpc_psd_allocated_products (comma-separated product IDs).
**Recommended Page**: wherever you want the services/products list displayed.
Deployment & test checklist (what I did and what you should run)



---

## Implementation Examples

### Basic Setup

Create these pages in WordPress admin:

1. **Service Desk** (`/service-desk/`)
   ```
   [pnpc_service_desk]
   ```

2. **Create Ticket** (`/create-ticket/`)
   ```
   [pnpc_create_ticket]
   ```

3. **My Tickets** (`/my-tickets/`)
   ```
   [pnpc_my_tickets]
   ```

4. **Ticket Detail** (`/ticket-detail/`)
   ```
   [pnpc_ticket_detail]
   ```

5. **Profile Settings** (`/profile-settings/`)
   ```
   [pnpc_profile_settings]
   ```

### With Additional Content

You can combine shortcodes with other content:

```html
<h1>Welcome to Our Support Center</h1>
<p>We're here to help! Create a ticket and our team will respond within 24 hours.</p>

[pnpc_create_ticket]
```

### In Sidebar Widget

Shortcodes can also be used in text widgets:

```
[pnpc_my_tickets]
```

## Menu Setup

Add these pages to your WordPress menu for easy customer access:

**Suggested Menu Structure**:
```
Support
├── Dashboard (/service-desk/)
├── Create Ticket (/create-ticket/)
├── My Tickets (/my-tickets/)
└── Profile Settings (/profile-settings/)
```

## URL Structure

### Ticket Detail Page

The ticket detail shortcode requires a ticket ID parameter:

- **Format**: `https://yoursite.com/ticket-detail/?ticket_id=123`
- **Automatic**: Links from "My Tickets" page automatically include this parameter

### Custom Permalinks

If you want cleaner URLs, you can use custom rewrite rules in your theme's `functions.php`:

```php
add_action('init', function() {
    add_rewrite_rule(
        '^ticket/([0-9]+)/?$',
        'index.php?pagename=ticket-detail&ticket_id=$matches[1]',
        'top'
    );
});

// Don't forget to flush rewrite rules after adding this
```

Then tickets can be accessed as: `https://yoursite.com/ticket/123/`

## Access Control

All shortcodes check for user authentication:
- Non-logged-in users see a message prompting them to log in
- Ticket detail page checks ownership or staff permissions
- Profile settings are user-specific

## Styling

All shortcodes output HTML with CSS classes prefixed with `pnpc-psd-`:

```css
.pnpc-psd-dashboard { /* Dashboard styles */ }
.pnpc-psd-create-ticket { /* Create form styles */ }
.pnpc-psd-my-tickets { /* Tickets list styles */ }
.pnpc-psd-ticket-detail { /* Ticket detail styles */ }
.pnpc-psd-profile-settings { /* Profile styles */ }
```

You can override these styles in your theme's CSS.

## Troubleshooting

### Shortcode Displays as Text

**Problem**: `[pnpc_service_desk]` appears as text instead of rendering

**Solution**: 
1. Check that the plugin is activated
2. Verify the shortcode is spelled correctly
3. Make sure you're not in a code block or pre-formatted text area

### "Please log in" Message

**Problem**: Users see "Please log in" message when already logged in

**Solution**:
1. Clear browser cache and cookies
2. Check that user session is active
3. Verify WordPress authentication is working

### Ticket Detail Shows "Invalid Ticket ID"

**Problem**: Ticket detail page doesn't show ticket

**Solution**:
1. Verify the URL has `?ticket_id=X` parameter
2. Check that the ticket ID exists
3. Confirm the user has permission to view the ticket

## WordPress Page Builder Compatibility

The shortcodes work with most page builders:

- **Gutenberg**: Use the "Shortcode" block
- **Elementor**: Use the "Shortcode" widget
- **Beaver Builder**: Use the "Text" module
- **Divi**: Use the "Code" module
- **WPBakery**: Use the "Raw HTML" element

## Best Practices

1. **Create Dedicated Pages**: Don't mix multiple service desk shortcodes on one page
2. **Use Descriptive Slugs**: Use clear page slugs like `/create-ticket/` not `/page-1/`
3. **Add to Menu**: Make pages easily accessible from your main navigation
4. **Mobile Responsive**: Test all pages on mobile devices
5. **Custom Styling**: Match the plugin's styling to your theme

## Need More Help?

- See `INSTALLATION.md` for setup instructions
- Check `README.md` for feature documentation
- Review the code in `public/views/` for customization options
