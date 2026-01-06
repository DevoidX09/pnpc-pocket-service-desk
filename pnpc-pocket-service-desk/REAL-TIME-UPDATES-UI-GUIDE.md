# Real-Time Updates - UI Screenshots & Mockups

## Visual Guide to Real-Time Updates Feature

### 1. Menu Badge Counter

**Location**: WordPress Admin Sidebar → "Service Desk" menu item

**Before** (No tickets or badge disabled):
```
┌─────────────────────────┐
│ ☰ Dashboard             │
├─────────────────────────┤
│ 📝 Posts                │
│ 📄 Pages                │
│ 🎫 Service Desk         │  ← No badge
│ ⚙️  Settings             │
└─────────────────────────┘
```

**After** (5 open/in-progress tickets):
```
┌─────────────────────────┐
│ ☰ Dashboard             │
├─────────────────────────┤
│ 📝 Posts                │
│ 📄 Pages                │
│ 🎫 Service Desk    [5]  │  ← Blue badge with count
│ ⚙️  Settings             │
└─────────────────────────┘
```

**Badge Styling**:
- Background: WordPress blue (#2271b1)
- Color: White text
- Border-radius: Circular pill shape
- Same style as WordPress plugin update badges
- Updates automatically every 30 seconds (configurable)

---

### 2. Auto-Refresh Controls (Ticket List Page)

**Location**: Above the ticket table on "All Tickets" page

```
┌─────────────────────────────────────────────────────────────────────┐
│  Service Desk Tickets                                               │
├─────────────────────────────────────────────────────────────────────┤
│  All | Open (12) | Closed (45) | Trash (2)                          │
├─────────────────────────────────────────────────────────────────────┤
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │ [Pause Auto-Refresh] [Refresh Now] ⟳  Last updated: 2:45 PM  │  │
│  └───────────────────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────────────┤
│  Bulk Actions ▼ [Apply]                                             │
├─────────────────────────────────────────────────────────────────────┤
│  □ | Ticket #  | Subject      | Customer | Status | Priority | ... │
├─────────────────────────────────────────────────────────────────────┤
│  □ | PNPC-1234 | Login Issue  | John Doe | Open   | High     | ... │
│  □ | PNPC-1235 | Bug Report   | Jane S.  | Open   | Normal   | ... │
│  ...                                                                 │
└─────────────────────────────────────────────────────────────────────┘
```

**Refresh Controls Breakdown**:

```
┌──────────────────────────────────────────────────────────────────────┐
│  [Pause Auto-Refresh]  [Refresh Now]  ⟳  Last updated: 2:45:33 PM  │
│   ▲                     ▲              ▲   ▲                         │
│   │                     │              │   └─ Timestamp              │
│   │                     │              └───── Loading indicator      │
│   │                     └──────────────────── Manual refresh button  │
│   └────────────────────────────────────────── Pause/Resume toggle   │
└──────────────────────────────────────────────────────────────────────┘
```

**States**:

1. **Auto-Refresh Enabled** (default):
   - Button text: "Pause Auto-Refresh"
   - Spinner: Hidden (only shows during refresh)
   - Timestamp: Updates every 30 seconds
   - Background: Light gray (#f9f9f9)

2. **Auto-Refresh Paused**:
   - Button text: "Resume Auto-Refresh"
   - Spinner: Hidden
   - Timestamp: Frozen at last refresh time
   - Background: Light gray (#f9f9f9)

3. **During Refresh**:
   - Button: Disabled temporarily
   - Spinner: Animated rotating (⟳)
   - Timestamp: About to update
   - Table: Fades out then fades in

---

### 3. Settings Page

**Location**: Service Desk → Settings → Real-Time Updates section

```
┌─────────────────────────────────────────────────────────────────────┐
│  PNPC Pocket Service Desk Settings                                  │
├─────────────────────────────────────────────────────────────────────┤
│  Notifications                                                       │
│  ├─ Notification Email: [admin@example.com          ]               │
│  └─ Description: Enter email address for notifications              │
├─────────────────────────────────────────────────────────────────────┤
│  Product / Services Display                                         │
│  └─ ... (existing settings) ...                                     │
├─────────────────────────────────────────────────────────────────────┤
│  Real-Time Updates  ← NEW SECTION                                   │
│  ├─────────────────────────────────────────────────────────────┐    │
│  │  ☑ Enable Menu Badge                                         │    │
│  │     Show ticket count badge in admin menu                    │    │
│  │     Display a real-time counter of open and in-progress      │    │
│  │     tickets in the admin sidebar menu.                       │    │
│  │                                                               │    │
│  │  Menu Badge Update Interval: [30 seconds ▼]                  │    │
│  │     Options: 15 seconds, 30 seconds, 60 seconds, 2 minutes   │    │
│  │     How often to check for new tickets and update the menu   │    │
│  │     badge.                                                    │    │
│  │                                                               │    │
│  │  ☑ Enable Auto-Refresh                                       │    │
│  │     Automatically refresh ticket list                        │    │
│  │     Automatically update the ticket list without requiring a │    │
│  │     page reload. Users can pause/resume this feature.        │    │
│  │                                                               │    │
│  │  Auto-Refresh Interval: [30 seconds ▼]                       │    │
│  │     Options: 15 seconds, 30 seconds, 60 seconds, 2 minutes   │    │
│  │     How often to automatically refresh the ticket list on    │    │
│  │     the admin page.                                          │    │
│  └─────────────────────────────────────────────────────────────┘    │
├─────────────────────────────────────────────────────────────────────┤
│  Colors & Buttons                                                    │
│  └─ ... (existing settings) ...                                     │
├─────────────────────────────────────────────────────────────────────┤
│  [Save Changes]                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

### 4. Loading Animation

**During Auto-Refresh**:

```
Step 1: Normal state (0.0s)
┌──────────────────────────────┐
│  Ticket #  | Subject  | ...  │
├──────────────────────────────┤
│  PNPC-1234 | Issue 1  | ...  │  ← Fully visible
│  PNPC-1235 | Issue 2  | ...  │  ← Fully visible
└──────────────────────────────┘

Step 2: Fade out (0.2s)
┌──────────────────────────────┐
│  Ticket #  | Subject  | ...  │
├──────────────────────────────┤
│  PNPC-1234 | Issue 1  | ...  │  ← Fading out (opacity: 0.5)
│  PNPC-1235 | Issue 2  | ...  │  ← Fading out (opacity: 0.5)
└──────────────────────────────┘

Step 3: Hidden & AJAX request (0.2s)
┌──────────────────────────────┐
│  Ticket #  | Subject  | ...  │
├──────────────────────────────┤
│                              │  ← Hidden (opacity: 0)
│        [⟳ Loading...]       │  ← Hidden (opacity: 0)
└──────────────────────────────┘

Step 4: New content fade in (0.2s)
┌──────────────────────────────┐
│  Ticket #  | Subject  | ...  │
├──────────────────────────────┤
│  PNPC-1234 | Issue 1  | ...  │  ← Fading in (opacity: 0.5)
│  PNPC-1235 | Issue 2  | ...  │  ← Fading in (opacity: 0.5)
│  PNPC-1236 | NEW!     | ...  │  ← Fading in (opacity: 0.5)
└──────────────────────────────┘

Step 5: Complete (0.4s total)
┌──────────────────────────────┐
│  Ticket #  | Subject  | ...  │
├──────────────────────────────┤
│  PNPC-1234 | Issue 1  | ...  │  ← Fully visible
│  PNPC-1235 | Issue 2  | ...  │  ← Fully visible
│  PNPC-1236 | NEW!     | ...  │  ← Fully visible (NEW TICKET!)
└──────────────────────────────┘
```

**Spinner Animation** (when active):
```
Frame 1: ⟲
Frame 2: ⟳
Frame 3: ⟲
Frame 4: ⟳
... (continuous rotation)
```

---

### 5. Browser Console Output (Debug Mode)

**Normal Operation**:
```
[pnpc-psd-realtime.js] Menu badge update: count = 5
[pnpc-psd-realtime.js] Auto-refresh triggered
[pnpc-psd-realtime.js] AJAX request: pnpc_psd_refresh_ticket_list
[pnpc-psd-realtime.js] Ticket list refreshed successfully
[pnpc-psd-realtime.js] Last refresh time updated: 2:45:33 PM
```

**When Tab Becomes Hidden**:
```
[pnpc-psd-realtime.js] Page visibility: hidden
[pnpc-psd-realtime.js] Auto-refresh paused (tab inactive)
```

**When Tab Becomes Visible Again**:
```
[pnpc-psd-realtime.js] Page visibility: visible
[pnpc-psd-realtime.js] Auto-refresh resumed
[pnpc-psd-realtime.js] Immediate refresh triggered
```

**Error Handling**:
```
[pnpc-psd-realtime.js] Menu badge update failed: timeout
[pnpc-psd-realtime.js] Ticket list refresh failed: network error
// (Errors logged to console but don't disrupt user experience)
```

---

### 6. Network Tab (Developer Tools)

**AJAX Requests**:
```
Request #1:
POST /wp-admin/admin-ajax.php
Action: pnpc_psd_get_new_ticket_count
Response: {"success":true,"data":{"count":5}}
Time: 45ms

Request #2 (30 seconds later):
POST /wp-admin/admin-ajax.php
Action: pnpc_psd_get_new_ticket_count
Response: {"success":true,"data":{"count":5}}
Time: 42ms

Request #3:
POST /wp-admin/admin-ajax.php
Action: pnpc_psd_refresh_ticket_list
Response: {"success":true,"data":{"html":"<tr>...</tr>"}}
Time: 156ms
```

---

### 7. Mobile Responsive View

**On Mobile/Tablet** (< 782px):

```
┌──────────────────────────────────┐
│  Service Desk Tickets            │
├──────────────────────────────────┤
│  All | Open (12) | Closed (45)   │
├──────────────────────────────────┤
│  [Pause Auto-Refresh]            │  ← Full width
│  [Refresh Now]         ⟳         │  ← Full width
│  Last updated: 2:45 PM           │  ← Below buttons
├──────────────────────────────────┤
│  Bulk Actions ▼ [Apply]          │
├──────────────────────────────────┤
│  ... ticket list ...             │
└──────────────────────────────────┘
```

**Responsive Adjustments**:
- Controls stack vertically on small screens
- Buttons go full width
- Timestamp moves below buttons
- Maintains functionality on all devices

---

### 8. Accessibility Features

**Screen Reader Announcements**:
```
"Menu badge updated: 5 new tickets"
"Ticket list refreshed"
"Auto-refresh paused"
"Auto-refresh resumed"
"Last updated at 2:45 PM"
```

**Keyboard Navigation**:
- Tab to "Pause Auto-Refresh" button
- Press Enter to toggle
- Tab to "Refresh Now" button
- Press Enter to manually refresh
- All controls accessible via keyboard

**ARIA Labels**:
```html
<button aria-label="Pause automatic refresh">Pause Auto-Refresh</button>
<button aria-label="Manually refresh ticket list">Refresh Now</button>
<span aria-live="polite" aria-atomic="true">Last updated: 2:45 PM</span>
```

---

## Color Scheme

### Refresh Controls
- Background: `#f9f9f9` (Light gray)
- Border: `#ddd` (Medium gray)
- Text: `#646970` (Dark gray)

### Loading Spinner
- Border: `#f3f3f3` (Very light gray)
- Active segment: `#2271b1` (WordPress blue)
- Animation: 1-second continuous rotation

### Buttons
- Primary: WordPress default button style
- Hover: Slight darkening
- Disabled: Reduced opacity (0.6)

### Menu Badge
- Background: `#2271b1` (WordPress blue)
- Text: `#ffffff` (White)
- Border-radius: `50%` (Circular)
- Padding: `2px 6px`

---

## User Flows

### Flow 1: First-Time User
1. User logs into WordPress admin
2. Sees "Service Desk" with badge showing ticket count
3. Clicks to view tickets
4. Notices refresh controls at top
5. Sees auto-refresh in action after 30 seconds
6. Clicks "Pause" to stop automatic updates
7. Uses "Refresh Now" for manual updates

### Flow 2: Configuration
1. User navigates to Settings
2. Finds "Real-Time Updates" section
3. Adjusts intervals to preferred timing
4. Disables features if not needed
5. Saves changes
6. Returns to ticket list to see changes in effect

### Flow 3: Multi-Tab Usage
1. User opens ticket list in Tab A
2. Auto-refresh is working
3. User switches to Tab B (different site)
4. Tab A stops auto-refresh (invisible)
5. User switches back to Tab A
6. Tab A immediately refreshes and resumes polling
7. User continues working with fresh data

---

## Screenshots Checklist

For documentation purposes, capture:
- ✅ Menu badge with count
- ✅ Menu badge without count
- ✅ Refresh controls (all states)
- ✅ Settings page section
- ✅ Loading animation sequence
- ✅ Mobile responsive view
- ✅ Browser console output
- ✅ Network tab requests

---

## Visual Design Principles

1. **Non-Intrusive**: Updates happen in background, minimal visual disruption
2. **Clear Feedback**: Loading indicator and timestamp show what's happening
3. **User Control**: Pause/resume gives users choice
4. **Consistent**: Follows WordPress admin UI patterns
5. **Accessible**: Works for all users, including keyboard and screen reader users
6. **Responsive**: Adapts to all screen sizes
7. **Performant**: Smooth animations, no janky updates

---

## Notes for Designers/Developers

- All animations use CSS transitions (0.2s fade)
- Loading spinner is pure CSS (no images)
- Badge uses WordPress core styles (no custom CSS needed)
- Colors match WordPress admin theme
- Responsive breakpoint: 782px (WordPress standard)
- Supports both light and dark admin themes
- RTL (Right-to-Left) compatible
