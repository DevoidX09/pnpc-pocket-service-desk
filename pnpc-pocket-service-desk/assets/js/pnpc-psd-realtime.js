/**
 * Real-time updates for PNPC Pocket Service Desk
 * Handles menu badge counter and auto-refresh ticket list
 */
(function($) {
	'use strict';

	// Configuration
	var config = {
		menuBadgeInterval: 30000, // 30 seconds
		autoRefreshInterval: 30000, // 30 seconds
		cacheTimeout: 10000, // 10 seconds for preventing rapid requests
		sortRestoreDelay: 50, // Delay for sort restoration check
		scrollRestoreDelay: 100, // Delay for scroll position restoration
		newTicketAnimationDuration: 3000 // Duration of new ticket animation (must match CSS)
	};

	// State
	var autoRefreshEnabled = localStorage.getItem('pnpc_psd_auto_refresh') === 'false' ? false : true;
	var autoRefreshTimer = null;
	var menuBadgeTimer = null;
	var lastRefreshTime = Date.now();
	var isRefreshing = false;
	var previousTicketIds = [];
	var currentSortColumn = '';
	var currentSortOrder = '';
	var selectedTicketIds = [];
	var currentScrollPosition = 0;

	$(document).ready(function() {
		// Only proceed if we have the required data
		if (typeof pnpcPsdRealtime === 'undefined') {
			return;
		}

		// Override intervals from settings if provided
		if (pnpcPsdRealtime.menuBadgeInterval) {
			config.menuBadgeInterval = parseInt(pnpcPsdRealtime.menuBadgeInterval, 10) * 1000;
		}
		if (pnpcPsdRealtime.autoRefreshInterval) {
			config.autoRefreshInterval = parseInt(pnpcPsdRealtime.autoRefreshInterval, 10) * 1000;
		}

		// Initialize menu badge updates (on all admin pages)
		if (pnpcPsdRealtime.enableMenuBadge) {
			initMenuBadgeUpdates();
		}

		// Initialize auto-refresh (only on ticket list page)
		if (pnpcPsdRealtime.enableAutoRefresh && isTicketListPage()) {
			initAutoRefresh();
		}
	});

	/**
	 * Check if current page is the ticket list page
	 */
	function isTicketListPage() {
		return $('#pnpc-psd-tickets-table').length > 0;
	}

	/**
	 * Initialize menu badge counter updates
	 */
	function initMenuBadgeUpdates() {
		// Initial update
		updateMenuBadge();

		// Set up periodic updates
		menuBadgeTimer = setInterval(updateMenuBadge, config.menuBadgeInterval);

		// Clean up on page unload
		$(window).on('beforeunload', function() {
			if (menuBadgeTimer) {
				clearInterval(menuBadgeTimer);
			}
		});
	}

	/**
	 * Update menu badge counter via AJAX
	 */
	function updateMenuBadge() {
		$.ajax({
			url: pnpcPsdRealtime.ajaxUrl,
			type: 'POST',
			data: {
				action: 'pnpc_psd_get_new_ticket_count',
				nonce: pnpcPsdRealtime.nonce
			},
			success: function(response) {
				if (response.success && typeof response.data.count !== 'undefined') {
					var count = parseInt(response.data.count, 10);
					updateMenuBadgeDisplay(count);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				// Silently fail - don't disrupt user experience
				console.log('Menu badge update failed:', textStatus);
			}
		});
	}

	/**
	 * Update the menu badge display
	 */
	function updateMenuBadgeDisplay(count) {
		var $menuItem = $('#toplevel_page_pnpc-service-desk .wp-menu-name');
		
		if (!$menuItem.length) {
			return;
		}

		// Remove existing badge
		$menuItem.find('.update-plugins').remove();

		// Add badge if count > 0
		if (count > 0) {
			var badgeHtml = ' <span class="update-plugins count-' + count + '"><span class="plugin-count">' + count + '</span></span>';
			$menuItem.append(badgeHtml);
		}
	}

	/**
	 * Initialize auto-refresh for ticket list
	 */
	function initAutoRefresh() {
		// Add refresh controls to the page
		addRefreshControls();

		// Initial refresh if enabled
		if (autoRefreshEnabled) {
			startAutoRefresh();
		}

		// Set up visibility change handler
		document.addEventListener('visibilitychange', handleVisibilityChange);

		// Clean up on page unload
		$(window).on('beforeunload', function() {
			stopAutoRefresh();
		});
	}

	/**
	 * Add refresh controls UI to the ticket list page
	 */
	function addRefreshControls() {
		var $table = $('#pnpc-psd-tickets-table');
		if (!$table.length) {
			return;
		}

		var controlsHtml = '<div class="pnpc-psd-refresh-controls">' +
			'<button type="button" id="pnpc-psd-toggle-auto-refresh" class="button">' +
			(autoRefreshEnabled ? 'Pause Auto-Refresh' : 'Resume Auto-Refresh') +
			'</button>' +
			'<button type="button" id="pnpc-psd-manual-refresh" class="button">Refresh Now</button>' +
			'<span class="pnpc-psd-refresh-indicator"></span>' +
			'<span class="pnpc-psd-last-refresh">' +
			'Last updated: <span id="pnpc-psd-last-refresh-time">Just now</span>' +
			'</span>' +
			'</div>';

		$table.before(controlsHtml);

		// Bind event handlers
		$('#pnpc-psd-toggle-auto-refresh').on('click', toggleAutoRefresh);
		$('#pnpc-psd-manual-refresh').on('click', manualRefresh);
	}

	/**
	 * Toggle auto-refresh on/off
	 */
	function toggleAutoRefresh() {
		autoRefreshEnabled = !autoRefreshEnabled;
		localStorage.setItem('pnpc_psd_auto_refresh', autoRefreshEnabled ? 'true' : 'false');

		var $button = $('#pnpc-psd-toggle-auto-refresh');
		
		if (autoRefreshEnabled) {
			$button.text('Pause Auto-Refresh');
			startAutoRefresh();
		} else {
			$button.text('Resume Auto-Refresh');
			stopAutoRefresh();
		}
	}

	/**
	 * Manual refresh button handler
	 */
	function manualRefresh() {
		if (!isRefreshing) {
			refreshTicketList();
		}
	}

	/**
	 * Start auto-refresh timer
	 */
	function startAutoRefresh() {
		if (autoRefreshTimer) {
			clearInterval(autoRefreshTimer);
		}
		autoRefreshTimer = setInterval(refreshTicketList, config.autoRefreshInterval);
	}

	/**
	 * Stop auto-refresh timer
	 */
	function stopAutoRefresh() {
		if (autoRefreshTimer) {
			clearInterval(autoRefreshTimer);
			autoRefreshTimer = null;
		}
	}

	/**
	 * Handle page visibility change
	 */
	function handleVisibilityChange() {
		if (document.hidden) {
			// Page is hidden, pause auto-refresh
			stopAutoRefresh();
		} else if (autoRefreshEnabled) {
			// Page is visible again, resume and do immediate refresh
			startAutoRefresh();
			refreshTicketList();
		}
	}

	/**
	 * Save current state before refresh
	 */
	function saveCurrentState() {
		// Store current ticket IDs
		previousTicketIds = [];
		$('input[name="ticket[]"]').each(function() {
			previousTicketIds.push(parseInt($(this).val(), 10));
		});

		// Store sort state - find column with non-empty data-sort-order
		var $sortedColumn = $('.pnpc-psd-sortable').filter(function() {
			return $(this).attr('data-sort-order') !== '';
		}).first();
		
		if ($sortedColumn.length) {
			currentSortColumn = $sortedColumn.attr('data-sort-type');
			currentSortOrder = $sortedColumn.attr('data-sort-order');
		}

		// Store selected checkboxes
		selectedTicketIds = [];
		$('input[name="ticket[]"]:checked').each(function() {
			selectedTicketIds.push(parseInt($(this).val(), 10));
		});

		// Store scroll position
		currentScrollPosition = $(window).scrollTop();
	}

	/**
	 * Restore state after refresh
	 */
	function restoreCurrentState() {
		// Re-bind checkbox event handlers FIRST (critical for bulk actions)
		rebindCheckboxHandlers();
		
		// Restore checkbox selections
		if (selectedTicketIds.length > 0) {
			selectedTicketIds.forEach(function(ticketId) {
				$('input[name="ticket[]"][value="' + ticketId + '"]').prop('checked', true);
			});

			// Update "select all" checkbox state
			updateSelectAllCheckbox();
		}

		// Restore sort order AFTER DOM is ready
		if (currentSortColumn && currentSortOrder) {
			setTimeout(function() {
				// Find the column header
				var $columnHeader = $('.pnpc-psd-sortable[data-sort-type="' + currentSortColumn + '"]');
				
				if ($columnHeader.length) {
					// Remove all existing sort indicators
					$('.pnpc-psd-sortable').attr('data-sort-order', '');
					
					// Set the sort order data attribute
					$columnHeader.attr('data-sort-order', currentSortOrder);
					
					// Actually sort the table
					sortTable(currentSortColumn, currentSortOrder);
				}
			}, 150); // Small delay ensures DOM is ready
		}

		// Restore scroll position (keep existing)
		setTimeout(function() {
			$(window).scrollTop(currentScrollPosition);
		}, 200);
	}
	
	/**
	 * Re-bind checkbox event handlers after AJAX refresh
	 * Critical for bulk operations to work after refresh
	 */
	function rebindCheckboxHandlers() {
		console.log('Re-binding checkbox handlers after refresh');
		
		// Remove old handlers to prevent duplicates (using namespaced events)
		$('#cb-select-all-1').off('change.pnpc-refresh');
		$('input[name="ticket[]"]').off('change.pnpc-refresh');
		
		// Re-bind "select all" checkbox
		$('#cb-select-all-1').on('change.pnpc-refresh', function() {
			var isChecked = $(this).is(':checked');
			$('input[name="ticket[]"]').prop('checked', isChecked);
			console.log('Select all toggled:', isChecked);
		});
		
		// Re-bind individual checkboxes
		$('input[name="ticket[]"]').on('change.pnpc-refresh', function() {
			updateSelectAllCheckbox();
			console.log('Checkbox changed:', $(this).val());
		});
	}

	/**
	 * Helper function to sort table without triggering click event
	 */
	function sortTable(sortType, sortOrder) {
		var $tbody = $('#pnpc-psd-tickets-table tbody');
		var $rows = $tbody.find('tr').toArray();
		
		if ($rows.length <= 1) {
			return; // Nothing to sort
		}
		
		$rows.sort(function(a, b) {
			var $aCells = $(a).find('td');
			var $bCells = $(b).find('td');
			
			// Find cells with data-sort-value matching our column
			var aValue = null;
			var bValue = null;
			
			$aCells.each(function() {
				var $cell = $(this);
				if ($cell.attr('data-sort-value') !== undefined) {
					// Check if this is the right column by comparing with header position
					var cellIndex = $cell.index();
					var $header = $('.pnpc-psd-sortable').eq(cellIndex - ($(a).find('th').length > 0 ? 1 : 0));
					if ($header.attr('data-sort-type') === sortType) {
						aValue = $cell.attr('data-sort-value');
						return false; // break
					}
				}
			});
			
			$bCells.each(function() {
				var $cell = $(this);
				if ($cell.attr('data-sort-value') !== undefined) {
					var cellIndex = $cell.index();
					var $header = $('.pnpc-psd-sortable').eq(cellIndex - ($(b).find('th').length > 0 ? 1 : 0));
					if ($header.attr('data-sort-type') === sortType) {
						bValue = $cell.attr('data-sort-value');
						return false; // break
					}
				}
			});
			
			// Handle different data types
			if (!isNaN(aValue) && !isNaN(bValue)) {
				// Numeric comparison
				aValue = parseFloat(aValue);
				bValue = parseFloat(bValue);
			} else {
				// String comparison
				aValue = String(aValue).toLowerCase();
				bValue = String(bValue).toLowerCase();
			}
			
			if (sortOrder === 'asc') {
				return aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
			} else {
				return aValue < bValue ? 1 : aValue > bValue ? -1 : 0;
			}
		});
		
		// Re-append sorted rows
		$.each($rows, function(index, row) {
			$tbody.append(row);
		});
	}

	/**
	 * Flash screen border when new tickets arrive
	 */
	function flashScreenBorder(count) {
		// Prevent multiple simultaneous flashes
		if ($('body').hasClass('pnpc-psd-flash-active')) {
			return;
		}
		
		// Add body class to prevent scroll shift
		$('body').addClass('pnpc-psd-flash-active');
		
		// Create full-screen border overlay
		var $overlay = $('<div class="pnpc-psd-screen-flash-overlay"></div>');
		
		// Add notification text
		var ticketText = count === 1 
			? '1 new ticket arrived'
			: count + ' new tickets arrived';
		
		var $notification = $('<div class="pnpc-psd-screen-flash-notification">' + 
			'<span class="dashicons dashicons-yes-alt" style="margin-right: 8px; font-size: 18px; vertical-align: middle;"></span>' + 
			ticketText + 
			'</div>');
		
		// Append to body (not inside any scrollable container)
		$('body').append($overlay).append($notification);
		
		// Remove after animation completes
		setTimeout(function() {
			$overlay.fadeOut(400, function() {
				$(this).remove();
			});
			$notification.fadeOut(400, function() {
				$(this).remove();
			});
			
			// Remove body class
			$('body').removeClass('pnpc-psd-flash-active');
		}, 3000);
	}

	/**
	 * Detect and highlight new tickets
	 */
	function detectAndHighlightNewTickets() {
		var newTicketIds = [];

		// Find tickets that weren't in previous list
		$('input[name="ticket[]"]').each(function() {
			var ticketId = parseInt($(this).val(), 10);
			if (previousTicketIds.indexOf(ticketId) === -1) {
				newTicketIds.push(ticketId);
			}
		});

		// Apply highlight animation to new tickets
		if (newTicketIds.length > 0) {
			// Trigger screen border flash
			flashScreenBorder(newTicketIds.length);

			newTicketIds.forEach(function(ticketId) {
				var $row = $('input[name="ticket[]"][value="' + ticketId + '"]').closest('tr');

				// Add animation class
				$row.addClass('pnpc-psd-ticket-row-new');

				// Remove class after animation completes
				setTimeout(function() {
					$row.removeClass('pnpc-psd-ticket-row-new');
				}, config.newTicketAnimationDuration);
			});
			
			// ALSO flash entire screen border
			flashScreenBorder(newTicketIds.length);
		}
	}
	
	/**
	 * Flash full-screen border to alert user of new tickets
	 */
	function flashScreenBorder(count) {
		// Prevent multiple simultaneous flashes
		if ($('body').hasClass('pnpc-psd-flash-active')) {
			return;
		}
		
		// Add body class
		$('body').addClass('pnpc-psd-flash-active');
		
		// Create notification text at top
		var ticketText = count === 1 
			? (typeof pnpcPsdAdmin !== 'undefined' && pnpcPsdAdmin.i18n && pnpcPsdAdmin.i18n.new_ticket_singular 
				? pnpcPsdAdmin.i18n.new_ticket_singular 
				: '1 new ticket arrived')
			: count + ' ' + (typeof pnpcPsdAdmin !== 'undefined' && pnpcPsdAdmin.i18n && pnpcPsdAdmin.i18n.new_tickets_plural 
				? pnpcPsdAdmin.i18n.new_tickets_plural 
				: 'new tickets arrived');
		
		var $notification = $('<div class="pnpc-psd-screen-flash-notification">' + 
			'<span class="dashicons dashicons-yes-alt"></span>' + 
			ticketText + 
			'</div>');
		
		// Create border flash around the CONTENT AREA (not full screen)
		var $contentBorder = $('<div class="pnpc-psd-content-border-flash"></div>');
		
		// Find the ticket list wrapper
		var $wrapElement = $('.wrap');
		
		if ($wrapElement.length) {
			// Wrap the content with border flash
			$wrapElement.prepend($contentBorder);
		}
		
		// Append notification to body
		$('body').append($notification);
		
		// Remove after animation completes
		setTimeout(function() {
			$contentBorder.fadeOut(400, function() {
				$(this).remove();
			});
			$notification.fadeOut(400, function() {
				$(this).remove();
			});
			
			// Remove body class
			$('body').removeClass('pnpc-psd-flash-active');
		}, 3000);
	}

	/**
	 * Update "select all" checkbox state
	 */
	function updateSelectAllCheckbox() {
		var $checkboxes = $('input[name="ticket[]"]');
		var $checkedBoxes = $('input[name="ticket[]"]:checked');
		var $selectAll = $('#cb-select-all-1');

		if ($checkboxes.length === $checkedBoxes.length && $checkboxes.length > 0) {
			$selectAll.prop('checked', true);
		} else {
			$selectAll.prop('checked', false);
		}
	}

	/**
	 * Refresh the ticket list via AJAX
	 */
	function refreshTicketList() {
		// Don't refresh if tab is hidden or already refreshing
		if (document.hidden || isRefreshing) {
			return;
		}

		// Prevent rapid successive refreshes
		var now = Date.now();
		if (now - lastRefreshTime < config.cacheTimeout) {
			return;
		}

		isRefreshing = true;

		// Save current state before refresh
		saveCurrentState();

		// Get current filter/tab from URL
		var urlParams = new URLSearchParams(window.location.search);
		var status = urlParams.get('status') || '';
		var view = urlParams.get('view') || '';
		var currentPage = urlParams.get('paged') || 1;

		$.ajax({
			url: pnpcPsdRealtime.ajaxUrl,
			type: 'POST',
			data: {
				action: 'pnpc_psd_refresh_ticket_list',
				nonce: pnpcPsdRealtime.nonce,
				status: status,
				view: view,
				paged: currentPage
			},
			beforeSend: function() {
				$('.pnpc-psd-refresh-indicator').addClass('active');
			},
			success: function(response) {
				if (response.success && response.data.html) {
					var $tbody = $('#pnpc-psd-tickets-table tbody');
					$tbody.fadeOut(200, function() {
						$tbody.html(response.data.html).fadeIn(200, function() {
							// Detect and highlight new tickets
							detectAndHighlightNewTickets();

							// Restore previous state
							restoreCurrentState();

							// Update tab counts if provided
							if (response.data.counts) {
								updateTabCounts(response.data.counts);
							}

							updateLastRefreshTime();

							// Trigger custom event for other scripts
							$(document).trigger('pnpc_psd_tickets_refreshed');
						});
					});
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.log('Ticket list refresh failed:', textStatus);
			},
			complete: function() {
				$('.pnpc-psd-refresh-indicator').removeClass('active');
				isRefreshing = false;
				lastRefreshTime = Date.now();
			}
		});
	}

	/**
	 * Update tab counts in navigation
	 */
	function updateTabCounts(counts) {
		// Sanitize counts to ensure they are safe integers within valid range
		if (counts.open !== undefined) {
			var openCount = parseInt(counts.open, 10);
			// Validate as safe integer, non-negative, and within safe range
			if (!isNaN(openCount) && isFinite(openCount) && openCount >= 0 && openCount <= Number.MAX_SAFE_INTEGER) {
				$('.subsubsub a[href*="status=open"]').text('Open (' + openCount + ')');
			}
		}
		if (counts.closed !== undefined) {
			var closedCount = parseInt(counts.closed, 10);
			// Validate as safe integer, non-negative, and within safe range
			if (!isNaN(closedCount) && isFinite(closedCount) && closedCount >= 0 && closedCount <= Number.MAX_SAFE_INTEGER) {
				$('.subsubsub a[href*="status=closed"]').text('Closed (' + closedCount + ')');
			}
		}
		if (counts.trash !== undefined) {
			var trashCount = parseInt(counts.trash, 10);
			// Validate as safe integer, non-negative, and within safe range
			if (!isNaN(trashCount) && isFinite(trashCount) && trashCount >= 0 && trashCount <= Number.MAX_SAFE_INTEGER) {
				$('.subsubsub a[href*="view=trash"]').text('Trash (' + trashCount + ')');
			}
		}
	}

	/**
	 * Update the "last refresh" timestamp display
	 */
	function updateLastRefreshTime() {
		var now = new Date();
		var timeString = now.toLocaleTimeString();
		$('#pnpc-psd-last-refresh-time').text(timeString);
	}

})(jQuery);
