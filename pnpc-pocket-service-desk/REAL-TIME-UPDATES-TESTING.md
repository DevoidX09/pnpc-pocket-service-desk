# Real-Time Updates Testing Guide

This document provides detailed testing steps for the real-time updates feature.

## Features to Test

### 1. Menu Badge Counter
- **Location**: WordPress Admin Sidebar → "Service Desk" menu item
- **Purpose**: Shows count of open and in-progress tickets

### 2. Auto-Refresh Ticket List
- **Location**: Service Desk → All Tickets page
- **Purpose**: Automatically refreshes ticket list without page reload

### 3. Settings Configuration
- **Location**: Service Desk → Settings → Real-Time Updates section
- **Purpose**: Configure update intervals and enable/disable features

---

## Prerequisites

1. WordPress installation with PNPC Pocket Service Desk plugin activated
2. User account with `pnpc_psd_view_tickets` capability (Administrator or Agent role)
3. At least 2-3 test tickets in the system (with different statuses)
4. Browser with Developer Console access (Chrome, Firefox, Edge)

---

## Test Cases

### Test 1: Menu Badge Initial Display

**Steps:**
1. Log in to WordPress admin
2. Navigate to any admin page
3. Look at the left sidebar menu for "Service Desk"

**Expected Result:**
- If there are open or in-progress tickets, a badge should display the count
- Badge format: `Service Desk [5]` with WordPress standard badge styling
- If no open/in-progress tickets, no badge should appear

**Success Criteria:**
- ✅ Badge shows correct count
- ✅ Badge uses WordPress standard styling (blue background)
- ✅ Badge disappears when count is 0

---

### Test 2: Menu Badge Auto-Update

**Steps:**
1. Keep WordPress admin open
2. In another browser tab/window, create a new ticket or change a closed ticket to open
3. Wait 30 seconds (default interval)
4. Check the menu badge in the first tab

**Expected Result:**
- Badge count should update automatically without page refresh
- New count reflects the changes made

**Success Criteria:**
- ✅ Badge updates within the configured interval (30s default)
- ✅ No page reload required
- ✅ Console shows no JavaScript errors

**Debug:**
- Open browser Developer Console (F12)
- Check Network tab for XHR requests to `admin-ajax.php` with action `pnpc_psd_get_new_ticket_count`
- Should see requests every 30 seconds

---

### Test 3: Auto-Refresh Ticket List

**Steps:**
1. Navigate to Service Desk → All Tickets
2. Observe the refresh controls at the top of the ticket list:
   - "Pause Auto-Refresh" button
   - "Refresh Now" button
   - Loading indicator (spinner)
   - "Last updated" timestamp
3. Wait 30 seconds (default interval)

**Expected Result:**
- After 30 seconds, the ticket list should fade out and fade back in
- Loading indicator should briefly appear during refresh
- "Last updated" timestamp should update to current time
- Current filter/tab (All/Open/Closed/Trash) should be preserved

**Success Criteria:**
- ✅ Ticket list refreshes automatically every 30 seconds
- ✅ Visual fade animation is smooth
- ✅ Loading spinner appears briefly
- ✅ Timestamp updates correctly
- ✅ No jarring layout shifts

---

### Test 4: Pause/Resume Auto-Refresh

**Steps:**
1. On the All Tickets page, click "Pause Auto-Refresh" button
2. Wait 60 seconds
3. Verify ticket list does NOT refresh
4. Click "Resume Auto-Refresh" button
5. Wait 30 seconds

**Expected Result:**
- When paused:
  - Button text changes to "Resume Auto-Refresh"
  - Ticket list stops auto-refreshing
  - Pause state persists even after page reload
- When resumed:
  - Button text changes to "Pause Auto-Refresh"
  - Auto-refresh resumes immediately
  - First refresh happens right away, then every 30 seconds

**Success Criteria:**
- ✅ Pause button works correctly
- ✅ Resume button works correctly
- ✅ Pause state persists in localStorage
- ✅ Manual refresh button still works when paused

---

### Test 5: Manual Refresh

**Steps:**
1. Click "Pause Auto-Refresh" to stop automatic updates
2. Create/modify a ticket in another tab
3. Click "Refresh Now" button

**Expected Result:**
- Ticket list refreshes immediately
- New/updated ticket appears in the list
- Loading indicator shows briefly
- Timestamp updates

**Success Criteria:**
- ✅ Manual refresh works when auto-refresh is paused
- ✅ Manual refresh works when auto-refresh is enabled
- ✅ Changes appear immediately after refresh

---

### Test 6: Page Visibility API (Tab Switching)

**Steps:**
1. Open All Tickets page with auto-refresh enabled
2. Switch to a different browser tab
3. Wait 2 minutes
4. Switch back to the All Tickets tab

**Expected Result:**
- While tab is inactive (hidden): No AJAX requests sent, no refreshes
- When tab becomes active again: Immediate refresh occurs, then resumes normal interval

**Success Criteria:**
- ✅ No unnecessary requests when tab is hidden
- ✅ Immediate refresh when tab becomes visible
- ✅ Auto-refresh resumes correctly

**Debug:**
- Check Network tab in Developer Console
- Should see no `pnpc_psd_refresh_ticket_list` requests while tab is hidden

---

### Test 7: Filter/Tab Preservation

**Steps:**
1. Navigate to Service Desk → All Tickets
2. Click "Open" tab filter
3. Wait for auto-refresh (30 seconds)
4. Verify still on "Open" tab after refresh
5. Repeat for "Closed" and "Trash" tabs

**Expected Result:**
- Current filter/tab is preserved after each auto-refresh
- URL parameters remain unchanged
- Ticket list shows correct filtered results

**Success Criteria:**
- ✅ "All" tab filter preserved
- ✅ "Open" tab filter preserved
- ✅ "Closed" tab filter preserved
- ✅ "Trash" tab filter preserved

---

### Test 8: Sort Order Preservation

**Steps:**
1. On All Tickets page, click a column header to sort (e.g., "Subject")
2. Note the sort order (ascending/descending)
3. Wait for auto-refresh (30 seconds)
4. Verify tickets remain in the same sort order

**Expected Result:**
- Current sort order is preserved after auto-refresh
- Sorting is client-side, so it may reset to default server order
- This is expected behavior - sort is visual only

**Success Criteria:**
- ✅ Default sort (Created date, newest first) is maintained
- ⚠️  Manual column sorts may reset (expected - client-side only)

---

### Test 9: Settings Configuration

**Steps:**
1. Navigate to Service Desk → Settings
2. Scroll to "Real-Time Updates" section
3. Test each setting:

**Test 9.1: Disable Menu Badge**
- Uncheck "Enable Menu Badge"
- Save settings
- Verify badge disappears from menu
- Re-enable and verify badge reappears

**Test 9.2: Change Menu Badge Interval**
- Set "Menu Badge Update Interval" to 15 seconds
- Save settings
- Monitor Network tab for AJAX requests
- Verify requests occur every 15 seconds instead of 30

**Test 9.3: Disable Auto-Refresh**
- Uncheck "Enable Auto-Refresh"
- Save settings
- Navigate to All Tickets page
- Verify refresh controls don't appear
- Verify no auto-refresh occurs

**Test 9.4: Change Auto-Refresh Interval**
- Enable auto-refresh
- Set "Auto-Refresh Interval" to 60 seconds
- Save settings
- Navigate to All Tickets page
- Verify ticket list refreshes every 60 seconds

**Success Criteria:**
- ✅ All settings are saved correctly
- ✅ Menu badge can be disabled
- ✅ Auto-refresh can be disabled
- ✅ Intervals can be changed (15s, 30s, 60s, 2min)
- ✅ Changes take effect immediately

---

### Test 10: Performance & Load Testing

**Steps:**
1. Create 50+ test tickets
2. Navigate to All Tickets page
3. Monitor browser performance:
   - Open Performance tab in Developer Console
   - Record a session during auto-refresh
4. Check server load:
   - Monitor database queries
   - Check transient cache effectiveness

**Expected Result:**
- Page remains responsive during auto-refresh
- No memory leaks over extended periods (1+ hour)
- Database queries are cached with transients
- Network requests complete within 1-2 seconds

**Success Criteria:**
- ✅ No excessive memory usage
- ✅ No JavaScript errors in console
- ✅ Transient caching reduces database load
- ✅ AJAX responses under 2 seconds with 100+ tickets

---

### Test 11: Browser Compatibility

Test in multiple browsers:
- Google Chrome (latest)
- Mozilla Firefox (latest)
- Microsoft Edge (latest)
- Safari (latest, if available)

**Success Criteria:**
- ✅ All features work in Chrome
- ✅ All features work in Firefox
- ✅ All features work in Edge
- ✅ All features work in Safari
- ✅ No browser-specific console errors

---

### Test 12: Network Error Handling

**Steps:**
1. Open All Tickets page with auto-refresh enabled
2. Open Developer Console → Network tab
3. Enable "Offline" mode in Developer Tools
4. Wait for refresh attempt
5. Check console for errors
6. Disable "Offline" mode

**Expected Result:**
- When offline: AJAX request fails silently, no user-visible error
- Console may show network error (acceptable)
- When back online: Auto-refresh resumes normally

**Success Criteria:**
- ✅ Graceful handling of network errors
- ✅ No user-facing error messages
- ✅ Automatic recovery when connection restored
- ✅ No page breaking or crashes

---

## Known Issues & Expected Behavior

1. **Manual Sorts Reset**: Client-side column sorts may reset after auto-refresh. This is expected since refresh fetches fresh data from server with default sort.

2. **First Load Sort**: On initial page load, the default sort (Created date, newest first) may take a moment to apply. This is a visual race condition with page render.

3. **Transient Cache**: Response counts and ticket counts use transient caching (10-30 seconds). Very recent changes may take up to the cache timeout to appear.

4. **Permission-Based Counts**: Menu badge only shows tickets visible to the current user. Admins see all tickets, agents may see filtered counts based on assignments.

---

## Debugging Tips

### Check AJAX Requests

```javascript
// Open Developer Console and run:
jQuery(document).ajaxComplete(function(event, xhr, settings) {
    if (settings.url.includes('admin-ajax.php')) {
        console.log('AJAX:', settings.data, xhr.responseJSON);
    }
});
```

### Monitor Auto-Refresh State

```javascript
// Check localStorage:
localStorage.getItem('pnpc_psd_auto_refresh')
// Should return 'true' or 'false'
```

### Force Transient Clear

```php
// In WordPress, run in wp-admin/admin.php or a plugin:
delete_transient('pnpc_psd_new_count_' . get_current_user_id());
```

### Check Console for Errors

Common errors to look for:
- 401/403: Permission denied (check user capabilities)
- 500: Server error (check PHP error logs)
- Uncaught TypeError: Check for JavaScript syntax errors

---

## Success Summary

All tests pass when:
- ✅ Menu badge displays and updates correctly
- ✅ Auto-refresh works with all filters/tabs
- ✅ Pause/resume controls work
- ✅ Page Visibility API functions correctly
- ✅ Settings can be configured
- ✅ Performance is acceptable with 100+ tickets
- ✅ Works across all major browsers
- ✅ Handles network errors gracefully
- ✅ No security vulnerabilities (CodeQL passed)

---

## Reporting Issues

If you encounter issues, please report with:
1. WordPress version
2. Plugin version
3. Browser and version
4. Steps to reproduce
5. Expected vs actual behavior
6. Console errors (if any)
7. Network request details (if relevant)
