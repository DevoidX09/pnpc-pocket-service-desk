# Attachment Management System

## Overview

The PNPC Pocket Service Desk plugin now includes a comprehensive attachment management system with an inline viewer featuring:

- **Image Gallery/Lightbox** - Full-size preview with navigation
- **PDF Inline Viewer** - Browser-native PDF rendering
- **File Size Limits** - 5MB preview limit for free version
- **Download Controls** - Direct download for all file types
- **Mobile Support** - Touch-friendly with swipe gestures
- **Keyboard Navigation** - Full keyboard accessibility

## Features

### 1. Image Gallery/Lightbox

When viewing a ticket with image attachments, users can:

- Click on any image thumbnail to open full-size lightbox view
- Navigate between multiple images using arrow buttons or keyboard
- View image counter (e.g., "3 / 5")
- Close via X button, clicking backdrop, or pressing ESC
- Download images directly from lightbox

**Keyboard Controls:**
- `Left/Right Arrow` - Navigate between images
- `ESC` - Close lightbox
- `Home/End` - Jump to first/last image

**Mobile Support:**
- Swipe left/right to navigate
- Touch-friendly controls
- Responsive layout

### 2. PDF Inline Viewer

PDF files are rendered inline using the browser's native PDF viewer:

- Full-width display in modal
- Browser toolbar controls (zoom, download, print)
- Fallback for unsupported browsers
- Always-visible download button

**Browser Support:**
- Modern browsers with PDF support show inline viewer
- Unsupported browsers show download option with message

### 3. File Size Limits

**Free Version:**
- 5MB limit for inline preview
- Files ≤ 5MB: Show preview/lightbox
- Files > 5MB: Show warning with download-only option

**Implementation:**
```php
PNPC_PSD_FREE_PREVIEW_LIMIT = 5 * 1024 * 1024 (5MB in bytes)
```

**Large File Display:**
- Warning message: "⚠ Exceeds 5MB preview limit"
- Download button only (no preview)
- File size and type still displayed

### 4. Supported File Types

**Previewable Types:**
- **Images:** jpg, jpeg, png, gif, svg, webp, bmp
- **PDFs:** pdf

**Download-Only Types:**
- All other file types (doc, docx, xls, xlsx, zip, mp4, etc.)
- Displayed with appropriate emoji icons

### 5. Smart File Type Detection

The system automatically detects file types and displays appropriate UI:

```php
pnpc_psd_get_attachment_type($extension)
// Returns: 'image', 'pdf', or 'other'

pnpc_psd_get_file_icon($extension)
// Returns: Emoji icon (📄, 📝, 📊, 🗜, 🎬, 🎵, 🖼, 📎)
```

## User Interface

### Attachment List View

Each attachment is displayed with:

- **Thumbnail/Icon** - Image thumbnail or file type icon
- **File Information:**
  - File name
  - File size (formatted: 1.2 MB, 450 KB, etc.)
  - File type/extension
- **Action Buttons:**
  - "View" button (for images and PDFs ≤ 5MB)
  - "Download" button (for all files)
- **Warning Message** (for files > 5MB)

### Lightbox/Modal

The lightbox provides:

- **Close Button** - Top-right corner (X)
- **Download Button** - Top-left corner
- **Navigation Arrows** - Left/right (for multiple images)
- **Image Counter** - Bottom center
- **File Name Caption** - Bottom center
- **Semi-transparent backdrop** - Click to close

## Technical Implementation

### Files Added

1. **Helper Functions** (`includes/helpers.php`)
   - `pnpc_psd_get_attachment_type()` - Detect file type
   - `pnpc_psd_get_file_icon()` - Get emoji icon
   - `pnpc_psd_can_preview_attachment()` - Check size limit
   - `PNPC_PSD_FREE_PREVIEW_LIMIT` constant

2. **CSS** (`assets/css/pnpc-psd-attachments.css`)
   - Attachment list styles
   - Lightbox/modal styles
   - Responsive breakpoints
   - Mobile optimizations

3. **JavaScript** (`assets/js/pnpc-psd-attachments.js`)
   - Lightbox functionality
   - Image gallery navigation
   - Keyboard event handlers
   - Touch/swipe support
   - PDF viewer handling

### Files Modified

1. **Admin Class** (`admin/class-pnpc-psd-admin.php`)
   - Enqueue attachments CSS
   - Enqueue attachments JS

2. **Ticket Detail View** (`admin/views/ticket-detail.php`)
   - Enhanced attachment display
   - Lightbox HTML structure
   - Response attachments upgrade

## Usage

### For Users

1. **Viewing Images:**
   - Click on any image thumbnail
   - Use arrow buttons or keyboard to navigate
   - Click X or press ESC to close

2. **Viewing PDFs:**
   - Click "View PDF" button
   - Use browser's PDF controls
   - Click X to close

3. **Downloading Files:**
   - Click "Download" button
   - Files download with original name

### For Developers

**Checking if File Can Be Previewed:**
```php
$can_preview = pnpc_psd_can_preview_attachment($file_size);
if ($can_preview) {
    // Show preview button
}
```

**Getting File Type:**
```php
$file_ext = pathinfo($filename, PATHINFO_EXTENSION);
$file_type = pnpc_psd_get_attachment_type($file_ext);
// Returns: 'image', 'pdf', or 'other'
```

**Getting File Icon:**
```php
$icon = pnpc_psd_get_file_icon($file_ext);
// Returns: '📄' for PDF, '🖼' for images, etc.
```

## Customization

### Changing Preview Size Limit

Currently set to 5MB. To change:

```php
// In includes/helpers.php
define('PNPC_PSD_FREE_PREVIEW_LIMIT', 10 * 1024 * 1024); // 10MB
```

### Adding Custom File Icons

Add to `pnpc_psd_get_file_icon()` function:

```php
$icons = array(
    'pdf' => '📄',
    'custom' => '🎯', // Add custom icon
);
```

### Styling Customization

Override CSS classes in your theme:

```css
.pnpc-psd-attachment {
    /* Custom attachment card styles */
}

.pnpc-psd-lightbox-backdrop {
    /* Custom backdrop color/opacity */
}
```

## Browser Compatibility

- **Modern Browsers:** Full support (Chrome, Firefox, Safari, Edge)
- **Mobile Browsers:** Full support with touch gestures
- **PDF Support:** Depends on browser (fallback provided)
- **Legacy Browsers:** Graceful degradation (download-only)

## Accessibility

- **Keyboard Navigation:** Full keyboard support
- **ARIA Labels:** Proper labeling for screen readers
- **Focus Management:** Focus returns to trigger element on close
- **Color Contrast:** WCAG AA compliant
- **Screen Reader Text:** Hidden labels where needed

## Performance

- **Lazy Loading:** Images loaded only when viewed
- **CSS Transitions:** Hardware-accelerated animations
- **Event Delegation:** Efficient event handling
- **No Dependencies:** Pure JavaScript (jQuery for WordPress compat)

## Future Enhancements (PRO Version)

Planned features:
- Higher preview limits (25MB or 50MB)
- Admin setting to configure limit
- Image zoom/pan in lightbox
- Video inline player
- Drag-and-drop reordering
- Bulk download

## Troubleshooting

**Issue: Images not opening in lightbox**
- Check browser console for JavaScript errors
- Verify jQuery is loaded
- Ensure scripts are enqueued correctly

**Issue: PDFs not displaying inline**
- Browser may not support inline PDFs
- Fallback download option should appear
- Check browser PDF viewer settings

**Issue: Styles not applying**
- Clear browser cache
- Check CSS is enqueued
- Verify no CSS conflicts

## Support

For issues or feature requests, please visit:
- GitHub: https://github.com/DevoidX09/pnpc-pocket-service-desk
- Documentation: See plugin README.md

---

**Version:** 1.0.0  
**Last Updated:** 2026-01-06
