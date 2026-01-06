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
		cacheTimeout: 10000 // 10 seconds for preventing rapid requests
	};

	// State
	var autoRefreshEnabled = localStorage.getItem('pnpc_psd_auto_refresh') !== 'false';
	var autoRefreshTimer = null;
	var menuBadgeTimer = null;
	var lastRefreshTime = Date.now();
	var isRefreshing = false;

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
		localStorage.setItem('pnpc_psd_auto_refresh', autoRefreshEnabled);

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

		// Get current filter/tab from URL
		var urlParams = new URLSearchParams(window.location.search);
		var status = urlParams.get('status') || '';
		var view = urlParams.get('view') || '';

		$.ajax({
			url: pnpcPsdRealtime.ajaxUrl,
			type: 'POST',
			data: {
				action: 'pnpc_psd_refresh_ticket_list',
				nonce: pnpcPsdRealtime.nonce,
				status: status,
				view: view
			},
			beforeSend: function() {
				$('.pnpc-psd-refresh-indicator').addClass('active');
			},
			success: function(response) {
				if (response.success && response.data.html) {
					var $tbody = $('#pnpc-psd-tickets-table tbody');
					$tbody.fadeOut(200, function() {
						$tbody.html(response.data.html).fadeIn(200);
						updateLastRefreshTime();
						
						// Trigger custom event for other scripts
						$(document).trigger('pnpc_psd_tickets_refreshed');
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
	 * Update the "last refresh" timestamp display
	 */
	function updateLastRefreshTime() {
		var now = new Date();
		var timeString = now.toLocaleTimeString();
		$('#pnpc-psd-last-refresh-time').text(timeString);
	}

})(jQuery);
