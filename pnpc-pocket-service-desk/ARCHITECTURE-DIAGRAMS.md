# Attachment Management System - Architecture & User Flow

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    WordPress Environment                     │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │            Admin Ticket Detail View                    │ │
│  │                                                         │ │
│  │  ┌──────────────────────────────────────────────────┐  │ │
│  │  │  Ticket Attachments Section                      │  │ │
│  │  │                                                   │  │ │
│  │  │  ┌─────────────┐  ┌─────────────┐  ┌──────────┐ │  │ │
│  │  │  │   Image     │  │    PDF      │  │  Large   │ │  │ │
│  │  │  │  Card       │  │   Card      │  │   File   │ │  │ │
│  │  │  │  [View]     │  │  [View]     │  │ [Download]│ │  │ │
│  │  │  │ [Download]  │  │ [Download]  │  │   Only   │ │  │ │
│  │  │  └─────────────┘  └─────────────┘  └──────────┘ │  │ │
│  │  └──────────────────────────────────────────────────┘  │ │
│  │                                                         │ │
│  │  ┌──────────────────────────────────────────────────┐  │ │
│  │  │  Response Attachments (Compact)                  │  │ │
│  │  │  Same functionality, smaller thumbnails          │  │ │
│  │  └──────────────────────────────────────────────────┘  │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │            Lightbox Modal (Hidden by default)         │ │
│  │                                                         │ │
│  │  ┌──────────────────────────────────────────────────┐  │ │
│  │  │  [X]                                   [Download] │  │ │
│  │  │                                                   │  │ │
│  │  │          ┌─────────────────────┐                 │  │ │
│  │  │    [<]   │  Image or PDF View  │   [>]          │  │ │
│  │  │          └─────────────────────┘                 │  │ │
│  │  │                                                   │  │ │
│  │  │          [filename.jpg] [3 / 5]                  │  │ │
│  │  └──────────────────────────────────────────────────┘  │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Component Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      PHP Layer (Server)                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  includes/helpers.php                                        │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  • PNPC_PSD_FREE_PREVIEW_LIMIT (5MB)                  │ │
│  │  • pnpc_psd_get_attachment_type($ext)                 │ │
│  │  • pnpc_psd_get_file_icon($ext)                       │ │
│  │  • pnpc_psd_can_preview_attachment($size)             │ │
│  │  • pnpc_psd_format_filesize($bytes)                   │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  admin/class-pnpc-psd-admin.php                             │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  • enqueue_styles() → attachments.css                 │ │
│  │  • enqueue_scripts() → attachments.js                 │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  admin/views/ticket-detail.php                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  • Render attachment cards                            │ │
│  │  • Check file size & type                             │ │
│  │  • Show preview or download-only                      │ │
│  │  • Render lightbox HTML structure                     │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                   CSS Layer (Presentation)                   │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  assets/css/pnpc-psd-attachments.css                        │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Attachment List Styles                                │ │
│  │  • .pnpc-psd-attachments                              │ │
│  │  • .pnpc-psd-attachment (card)                        │ │
│  │  • .pnpc-psd-attachment-thumbnail                     │ │
│  │  • .pnpc-psd-attachment-icon                          │ │
│  │  • .pnpc-psd-attachment-info                          │ │
│  │  • .pnpc-psd-attachment-actions                       │ │
│  │  • .pnpc-psd-attachment-warning                       │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │  Lightbox Styles                                       │ │
│  │  • .pnpc-psd-lightbox                                 │ │
│  │  • .pnpc-psd-lightbox-backdrop                        │ │
│  │  • .pnpc-psd-lightbox-content                         │ │
│  │  • .pnpc-psd-lightbox-image                           │ │
│  │  • .pnpc-psd-lightbox-pdf                             │ │
│  │  • Navigation & controls                              │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │  Responsive Breakpoints                                │ │
│  │  • @media (max-width: 768px) - Tablet                 │ │
│  │  • @media (max-width: 480px) - Mobile                 │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                JavaScript Layer (Behavior)                   │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  assets/js/pnpc-psd-attachments.js                          │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Configuration                                         │ │
│  │  • SWIPE_THRESHOLD: 50px                              │ │
│  │  • PDF_LOAD_TIMEOUT: 1000ms                           │ │
│  │  • ANIMATION_DURATION: 300ms                          │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │  Initialization                                        │ │
│  │  • initLightbox() - Setup modal                       │ │
│  │  • bindEvents() - Attach listeners                    │ │
│  │  • buildAttachmentGallery() - Index attachments       │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │  Core Functions                                        │ │
│  │  • openLightbox(index)                                │ │
│  │  • closeLightbox()                                    │ │
│  │  • loadImage(attachment)                              │ │
│  │  • loadPDF(attachment)                                │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │  Navigation                                            │ │
│  │  • navigateNext()                                     │ │
│  │  • navigatePrev()                                     │ │
│  │  • navigateToIndex(index)                             │ │
│  │  • handleSwipe()                                      │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │  Event Handlers                                        │ │
│  │  • Click: .pnpc-psd-view-attachment                   │ │
│  │  • Click: .pnpc-psd-attachment-thumbnail              │ │
│  │  • Click: .pnpc-psd-lightbox-close                    │ │
│  │  • Click: .pnpc-psd-lightbox-backdrop                 │ │
│  │  • Keyboard: Arrow keys, ESC, Home/End                │ │
│  │  • Touch: touchstart, touchend                        │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## User Flow Diagrams

### Flow 1: Viewing an Image

```
┌─────────────┐
│   User      │
│  on Ticket  │
│  Detail     │
└──────┬──────┘
       │
       │ Sees image attachment
       │
       ▼
┌─────────────────┐
│  Click Image    │
│  Thumbnail or   │
│  [View] Button  │
└──────┬──────────┘
       │
       │ JavaScript: openLightbox()
       │
       ▼
┌─────────────────┐
│  Lightbox Opens │
│  • Fade in      │
│  • aria-hidden  │
│  • Body scroll  │
│    locked       │
└──────┬──────────┘
       │
       │ loadImage()
       │
       ▼
┌─────────────────┐
│  Image Displayed│
│  • Full size    │
│  • Caption      │
│  • Counter      │
│  • Nav arrows   │
└──────┬──────────┘
       │
       ├─────────────────┬─────────────────┬────────────────┐
       │                 │                 │                │
       ▼                 ▼                 ▼                ▼
  ┌─────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
  │  Press  │    │  Click   │    │  Press   │    │  Swipe   │
  │   ESC   │    │  Close   │    │  Arrow   │    │  Left/   │
  │         │    │  Button  │    │  Keys    │    │  Right   │
  └────┬────┘    └────┬─────┘    └────┬─────┘    └────┬─────┘
       │              │               │               │
       │              │               │               │
       └──────────────┴───────┬───────┴───────────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │  Close or        │
                    │  Navigate        │
                    └──────────────────┘
```

### Flow 2: Viewing a PDF

```
┌─────────────┐
│   User      │
│  on Ticket  │
│  Detail     │
└──────┬──────┘
       │
       │ Sees PDF attachment
       │
       ▼
┌─────────────────┐
│  Check File     │
│  Size           │
└──────┬──────────┘
       │
       ├─────────────────┬─────────────────┐
       │                 │                 │
       ▼                 ▼                 ▼
  ┌─────────┐    ┌──────────┐    ┌──────────┐
  │  ≤5MB   │    │  >5MB    │    │  Other   │
  │         │    │          │    │  Type    │
  └────┬────┘    └────┬─────┘    └────┬─────┘
       │              │               │
       │ Click        │ Only          │ Only
       │ [View]       │ Download      │ Download
       │              │ Available     │ Available
       ▼              │               │
┌─────────────┐       │               │
│  Lightbox   │       │               │
│  Opens      │       │               │
└──────┬──────┘       │               │
       │              │               │
       │ loadPDF()    │               │
       │              │               │
       ▼              │               │
┌─────────────┐       │               │
│  Try Load   │       │               │
│  in iframe  │       │               │
└──────┬──────┘       │               │
       │              │               │
       ├──────────┬───┴───────────────┴────┐
       │          │                        │
       ▼          ▼                        ▼
  ┌─────────┐ ┌─────────┐          ┌──────────┐
  │ Success │ │  Fail   │          │ Download │
  │ Show    │ │ Show    │          │ Direct   │
  │ iframe  │ │Fallback │          └──────────┘
  └─────────┘ └─────────┘
```

### Flow 3: Large File Handling

```
┌─────────────┐
│   User      │
│  on Ticket  │
│  Detail     │
└──────┬──────┘
       │
       │ Sees large file (>5MB)
       │
       ▼
┌─────────────────────────────┐
│  Server: Check File Size    │
│  pnpc_psd_can_preview()     │
│  Returns: false             │
└──────┬──────────────────────┘
       │
       ▼
┌─────────────────────────────┐
│  UI Displays:               │
│  • File icon (emoji)        │
│  • File name & size         │
│  • ⚠ Warning message        │
│  • [Download] button only   │
│  • NO [View] button         │
└──────┬──────────────────────┘
       │
       │ User clicks Download
       │
       ▼
┌─────────────────────────────┐
│  Browser downloads file     │
│  with original filename     │
└─────────────────────────────┘
```

### Flow 4: Gallery Navigation

```
┌──────────────┐
│  Lightbox    │
│  Open with   │
│  Image 1/5   │
└──────┬───────┘
       │
       ├─────────────┬─────────────┬─────────────┬──────────────┐
       │             │             │             │              │
       ▼             ▼             ▼             ▼              ▼
  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌──────────┐
  │  Click  │  │  Press  │  │  Press  │  │  Swipe  │  │  Press   │
  │   [>]   │  │  Right  │  │  Home   │  │  Left   │  │   End    │
  │  Button │  │  Arrow  │  │   Key   │  │         │  │   Key    │
  └────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘  └────┬─────┘
       │            │            │            │            │
       │            │            │            │            │
       ▼            ▼            ▼            ▼            ▼
  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐
  │ Next    │  │ Next    │  │ Jump to │  │Previous │  │ Jump to │
  │ Image   │  │ Image   │  │ First   │  │ Image   │  │  Last   │
  │ (2/5)   │  │ (2/5)   │  │ (1/5)   │  │ (5/5)   │  │ (5/5)   │
  └─────────┘  └─────────┘  └─────────┘  └─────────┘  └─────────┘
```

## Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    Database (WordPress)                      │
│                                                              │
│  wp_pnpc_psd_ticket_attachments                             │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  id, ticket_id, response_id, file_name, file_path,    │ │
│  │  file_type, file_size, uploaded_by, created_at        │ │
│  └────────────────────────────────────────────────────────┘ │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            │ SQL Query
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   PHP Processing                             │
│                                                              │
│  ticket-detail.php                                          │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  foreach ($ticket_attachments as $att) {               │ │
│  │    $file_ext = pathinfo($att->file_name, ...);        │ │
│  │    $file_type = pnpc_psd_get_attachment_type($ext);   │ │
│  │    $can_preview = pnpc_psd_can_preview($att->size);   │ │
│  │    // Render card with metadata                       │ │
│  │  }                                                      │ │
│  └────────────────────────────────────────────────────────┘ │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            │ HTML Rendering
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      Browser DOM                             │
│                                                              │
│  <div class="pnpc-psd-attachment">                          │
│    <img class="pnpc-psd-attachment-thumbnail">              │
│    <button class="pnpc-psd-view-attachment"                 │
│            data-type="image"                                │
│            data-url="...">                                  │
│  </div>                                                     │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            │ User Interaction
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   JavaScript (jQuery)                        │
│                                                              │
│  pnpc-psd-attachments.js                                    │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  $('.pnpc-psd-view-attachment').on('click', ...)      │ │
│  │    ↓                                                    │ │
│  │  openLightbox(index)                                   │ │
│  │    ↓                                                    │ │
│  │  loadImage() or loadPDF()                              │ │
│  │    ↓                                                    │ │
│  │  Update lightbox content & show                        │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Integration Points

```
┌─────────────────────────────────────────────────────────────┐
│              WordPress Core Integration                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  • wp_enqueue_style() - Load CSS                            │
│  • wp_enqueue_script() - Load JS with jQuery dependency     │
│  • esc_html(), esc_url(), esc_attr() - Security             │
│  • __(), esc_html__() - i18n/Translations                   │
│  • size_format() - File size formatting                     │
│  • pathinfo() - File extension extraction                   │
│  • wp_kses_post() - Content sanitization                    │
│                                                              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│           Plugin Internal Integration                        │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  • PNPC_PSD_PLUGIN_URL - Asset URL generation               │
│  • PNPC_PSD_VERSION - Cache busting                         │
│  • Existing attachment storage system                       │
│  • Existing ticket response system                          │
│  • Existing admin menu system                               │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

**Document Version:** 1.0  
**Last Updated:** 2026-01-06  
**Status:** Complete
