# Auto-Refresh Enhancements

## Overview
Enhanced the auto-refresh functionality to provide visual feedback for new tickets and maintain user state during background updates.

## Features Implemented

### 1. Visual Feedback for New Tickets (Green Border Flash)
- **File Modified:** `assets/css/pnpc-psd-admin.css`
- **Description:** New tickets that arrive during auto-refresh are highlighted with an animated green border flash that lasts 3 seconds
- **CSS Classes Added:**
  - `.pnpc-psd-ticket-row-new` - Applied to new ticket rows
  - Animations: `pnpc-psd-new-ticket-flash` and `pnpc-psd-border-fade`

### 2. Sort Order Persistence
- **File Modified:** `assets/js/pnpc-psd-realtime.js`
- **Description:** Sort order is maintained across auto-refresh cycles
- **How it works:**
  - Saves current sort column and order before refresh
  - Restores sort state after new content is loaded
  - Works with all sortable columns (Ticket #, Subject, Status, Priority, etc.)

### 3. Checkbox Selection Persistence
- **File Modified:** `assets/js/pnpc-psd-realtime.js`
- **Description:** Selected ticket checkboxes remain checked after auto-refresh
- **How it works:**
  - Stores IDs of checked tickets before refresh
  - Re-checks matching tickets after refresh
  - Updates "Select All" checkbox state appropriately

### 4. Scroll Position Persistence
- **File Modified:** `assets/js/pnpc-psd-realtime.js`
- **Description:** Page scroll position is maintained during refresh
- **How it works:**
  - Saves scroll position before refresh
  - Restores scroll position 100ms after content loads (allows for DOM rendering)

### 5. Tab Count Updates
- **Files Modified:** 
  - `admin/class-pnpc-psd-admin.php` - Updated AJAX handler
  - `assets/js/pnpc-psd-realtime.js` - Added count update logic
- **Description:** Tab counts (Open, Closed, Trash) update dynamically during refresh
- **How it works:**
  - AJAX handler returns counts along with HTML
  - JavaScript updates the navigation tab text with new counts

## Technical Details

### JavaScript Functions Added/Modified (pnpc-psd-realtime.js)

#### State Variables
```javascript
var previousTicketIds = [];      // Track tickets before refresh
var currentSortColumn = '';       // Current sort column
var currentSortOrder = '';        // Current sort order (asc/desc)
var selectedTicketIds = [];       // Selected ticket IDs
var currentScrollPosition = 0;    // Current scroll position
```

#### New Functions
1. `saveCurrentState()` - Saves all state before refresh
2. `restoreCurrentState()` - Restores all state after refresh
3. `detectAndHighlightNewTickets()` - Identifies and highlights new tickets
4. `updateSelectAllCheckbox()` - Updates "select all" checkbox state
5. `updateTabCounts(counts)` - Updates tab navigation counts

### CSS Animations Added (pnpc-psd-admin.css)

#### Green Border Flash Effect
- **Animation Duration:** 3 seconds
- **Color:** Green (#48bb78 / rgba(72, 187, 120))
- **Effect:** Box shadow pulse with background color fade
- **Visual Indicator:** 4px green border on left side of row

### PHP Changes (class-pnpc-psd-admin.php)

#### ajax_refresh_ticket_list() Enhancement
- **Added:** Count calculation for Open, Closed, and Trash tickets
- **Returns:** JSON response with both `html` and `counts` properties
- **Counts:** 
  - `open` - Number of open tickets
  - `closed` - Number of closed tickets  
  - `trash` - Number of trashed tickets

## User Experience Improvements

### Before
- New tickets appeared silently without notification
- Sort order reset to default after refresh
- Checkbox selections cleared after refresh
- Page jumped to top after refresh
- Had to manually check tab counts

### After
- New tickets flash with green border for immediate visibility
- Sort order maintained across refreshes
- Checkbox selections preserved for bulk actions
- Scroll position stays in place
- Tab counts update automatically

## Configuration
Auto-refresh settings are configurable in WordPress admin:
- **Settings Location:** Service Desk > Settings
- **Enable/Disable:** Auto-refresh can be toggled on/off
- **Interval:** Default is 30 seconds (configurable)

## Browser Compatibility
- Works with all modern browsers (Chrome, Firefox, Safari, Edge)
- Uses standard CSS animations and JavaScript
- jQuery-based for compatibility with WordPress

## Performance Considerations
- Minimal overhead: Only stores IDs and simple state variables
- Animation uses CSS for hardware acceleration
- AJAX requests are debounced to prevent rapid calls
- Count queries use existing ticket count methods

## Testing Recommendations

1. **New Ticket Detection:**
   - Have another user/tab create a ticket
   - Wait for auto-refresh (30 seconds)
   - Verify new ticket flashes green

2. **Sort Persistence:**
   - Sort by Priority (descending)
   - Wait for auto-refresh
   - Verify sort order is maintained

3. **Checkbox Persistence:**
   - Check 3 tickets
   - Wait for auto-refresh
   - Verify checkboxes still checked

4. **Scroll Persistence:**
   - Scroll to bottom of ticket list
   - Wait for auto-refresh
   - Verify page doesn't jump to top

5. **Tab Count Updates:**
   - Note current counts in tabs
   - Create/close/trash tickets in another tab
   - Wait for auto-refresh
   - Verify counts update correctly

## Future Enhancements (Not Implemented)
- Audio notification for new tickets
- Browser notification API integration
- Real-time updates using WebSockets
- Diff highlighting for changed ticket properties
