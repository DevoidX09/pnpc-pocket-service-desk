/**
 * Admin JavaScript for PNPC Pocket Service Desk
 */
(function( $ ) {
	'use strict';

	$(document).ready(function() {
		var $ticketDetail = $('#pnpc-psd-ticket-detail');
		var ticketId = $ticketDetail.data('ticket-id');
		var adminNonce = (typeof pnpcPsdAdmin !== 'undefined') ? pnpcPsdAdmin.nonce :  '';

		// Use localized ticket-specific values if available (for ticket detail page).
		if (typeof pnpcPsdTicketDetail !== 'undefined') {
			ticketId = pnpcPsdTicketDetail.ticketId || ticketId;
			adminNonce = pnpcPsdTicketDetail.adminNonce || adminNonce;
		}

		// Convert ticketId to integer for proper comparisons (0 = no ticket, skip ticket-specific features)
		ticketId = ticketId != null ? parseInt(ticketId, 10) : 0;

		var MESSAGE_TARGETS = ['pnpc-psd-admin-action-message', 'response-message', 'pnpc-psd-bulk-message'];
		// ================================
		// Attachments (Admin Response): preview + remove before send
		// ================================
		var adminResponseFiles = [];
		var MAX_ATTACHMENTS = 10;

		// ================================
		// Reply Signatures (Free): None / Personal / Group
		// ================================
		var SIGNATURE_MODE_KEY = 'pnpc_psd_signature_mode';

		function normalizeNewlines(str) {
			return (str || '').toString().replace(/\r\n/g, '\n');
		}

		function getSignatureText(mode) {
			if (typeof pnpcPsdAdmin === 'undefined') {
				return '';
			}
			if ('personal' === mode) {
				return normalizeNewlines(pnpcPsdAdmin.personal_signature || '');
			}
			if ('group' === mode) {
				return normalizeNewlines(pnpcPsdAdmin.group_signature || '');
			}
			return '';
		}

		function getSelectedSignatureMode() {
			var $checked = $('#pnpc-psd-signature-controls input[name="pnpc_psd_signature_mode"]:checked');
			return $checked.length ? $checked.val() : '';
		}

		function setSelectedSignatureMode(mode) {
			$('#pnpc-psd-signature-controls input[name="pnpc_psd_signature_mode"]').prop('checked', false);
			$('#pnpc-psd-signature-controls input[name="pnpc_psd_signature_mode"][value="' + mode + '"]').prop('checked', true);
		}

		function removeTrailingSignature(text) {
			var out = normalizeNewlines(text);
			['personal', 'group'].forEach(function(mode) {
				var sig = getSignatureText(mode);
				sig = sig.trim();
				if (!sig) {
					return;
				}
				var suffix = '\n\n' + sig;
				if (out.endsWith(suffix)) {
					out = out.slice(0, out.length - suffix.length);
					out = out.replace(/\s+$/, '');
				}
			});
			return out;
		}

		function applySignatureToTextarea($textarea) {
			if (!$textarea || !$textarea.length) {
				return;
			}
			var mode = getSelectedSignatureMode() || (typeof pnpcPsdAdmin !== 'undefined' ? pnpcPsdAdmin.default_signature_mode : 'none');
			var base = removeTrailingSignature($textarea.val() || '');
			if ('none' === mode) {
				$textarea.val(base);
				return;
			}
			var sigText = getSignatureText(mode).trim();
			if (!sigText) {
				$textarea.val(base);
				return;
			}
			var next = (base.trim().length ? base.replace(/\s+$/, '') + '\n\n' : '') + sigText;
			$textarea.val(next);
		}

		function initSignatureControls() {
			var $controls = $('#pnpc-psd-signature-controls');
			if (!$controls.length || typeof pnpcPsdAdmin === 'undefined') {
				return;
			}

			// Hide entirely if nothing is configured.
			if (!pnpcPsdAdmin.has_personal_signature && !pnpcPsdAdmin.has_group_signature) {
				$controls.hide();
				return;
			}

			var stored = '';
			try {
				stored = window.localStorage ? window.localStorage.getItem(SIGNATURE_MODE_KEY) : '';
			} catch (e) {
				stored = '';
			}

			var initial = stored || pnpcPsdAdmin.default_signature_mode || 'none';
			// If personal/group is selected but not available, fall back.
			if ('personal' === initial && !pnpcPsdAdmin.has_personal_signature) {
				initial = pnpcPsdAdmin.has_group_signature ? 'group' : 'none';
			}
			if ('group' === initial && !pnpcPsdAdmin.has_group_signature) {
				initial = pnpcPsdAdmin.has_personal_signature ? 'personal' : 'none';
			}

			setSelectedSignatureMode(initial);

			$controls.on('change', 'input[name="pnpc_psd_signature_mode"]', function() {
				var mode = getSelectedSignatureMode();
				try {
					if (window.localStorage) {
						window.localStorage.setItem(SIGNATURE_MODE_KEY, mode);
					}
				} catch (e) {
					// ignore
				}
				// If signature was already appended, re-apply based on new mode.
				applySignatureToTextarea($('#response-text'));
			});
		}

		// Initialize signature controls if present on the current screen.
		initSignatureControls();

		function renderAttachmentList(files, $list, $input) {
			if (!$list || !$list.length) {
				return;
			}
			$list.empty();
			if (!files || !files.length) {
				return;
			}
			files.forEach(function(file, idx) {
				var $item = $('<div/>').addClass('pnpc-psd-attachment-item').css({marginBottom:'6px'});
				$item.append($('<span/>').text(file.name + ' (' + Math.round(file.size / 1024) + ' KB)'));
				var $remove = $('<button/>').attr('type', 'button').addClass('button').css({marginLeft: '8px'}).text('Remove');
				$remove.on('click', function() {
					files.splice(idx, 1);
					if ($input && $input.length) {
						// If nothing remains, clear input so user can re-select.
						if (!files.length) {
							$input.val('');
						}
					}
					renderAttachmentList(files, $list, $input);
				});
				$item.append($remove);
				$list.append($item);
			});
		}

		$(document).on('change', '#admin-response-attachments', function(e) {
			adminResponseFiles = Array.prototype.slice.call(e.target.files || []);
			if (adminResponseFiles.length > MAX_ATTACHMENTS) {
				adminResponseFiles = adminResponseFiles.slice(0, MAX_ATTACHMENTS);
			}
			renderAttachmentList(adminResponseFiles, $('#pnpc-psd-admin-response-attachments-list'), $('#admin-response-attachments'));
		});



		// ==============================================
		// Auto-save tooltip (hover + click)
		// ==============================================
		var $tip = $('#pnpc-psd-autosave-tip');
		var $panel = $('#pnpc-psd-autosave-tip-panel');

		// Exit if elements don't exist
		if (!$tip.length || !$panel.length) {
			// Not the ticket detail screen; do not halt the rest of the admin JS.
		} else {
			var isPinnedOpen = false; // toggled via click
			var isHovering = false;

			var renderTip = function() {
				var shouldOpen = isPinnedOpen || isHovering;
				if (shouldOpen) {
					$panel.show();
					$tip.attr('aria-expanded', 'true');
				} else {
					$panel.hide();
					$tip.attr('aria-expanded', 'false');
				}
			};

			// Toggle pinned open/closed on click
			$tip.on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				isPinnedOpen = !isPinnedOpen;
				renderTip();
			});

			// Hover/focus behavior (non-pinned)
			$tip.on('mouseenter focus', function() {
				isHovering = true;
				renderTip();
			});

			$tip.on('mouseleave blur', function() {
				isHovering = false;
				renderTip();
			});

			// Keep open while hovering the panel itself
			$panel.on('mouseenter', function() {
				isHovering = true;
				renderTip();
			});

			$panel.on('mouseleave', function() {
				isHovering = false;
				renderTip();
			});

			// Close pinned tooltip on outside click
			$(document).on('click', function(e) {
				if (!isPinnedOpen) {
					return;
				}

				var $target = $(e.target);
				if ($target.closest('#pnpc-psd-autosave-tip').length || $target.closest('#pnpc-psd-autosave-tip-panel').length) {
					return;
				}

				isPinnedOpen = false;
				isHovering = false;
				renderTip();
			});

			// Close on ESC
			$(document).on('keydown', function(e) {
				if (e.key === 'Escape' && (isPinnedOpen || isHovering)) {
					isPinnedOpen = false;
					isHovering = false;
					renderTip();
				}
			});
		}

		if (! adminNonce) {
			return;
		}

		// Table sorting functionality
		var $ticketsTable = $('#pnpc-psd-tickets-table');
		var lastSortClick = 0;

		if ($ticketsTable.length) {
			// Apply default sort on page load (Created date, newest first)
			// Use requestAnimationFrame to ensure DOM is fully rendered
			var $createdHeader = $ticketsTable.find('th[data-sort-type="date"]');
			if ($createdHeader.length) {
				// Wait for next paint cycle to ensure table is rendered
				requestAnimationFrame(function() {
					sortTable($createdHeader, 'desc');
				});
			}

			// Click handler for sortable headers
			$ticketsTable.on('click', '.pnpc-psd-sortable', function() {
				// Debounce rapid clicks
				var now = Date.now();
				if (now - lastSortClick < 300) {
					return;
				}
				lastSortClick = now;

				var $header = $(this);
				var currentOrder = $header.attr('data-sort-order');
				var newOrder = '';

				if (currentOrder === '') {
					newOrder = 'asc';
				} else if (currentOrder === 'asc') {
					newOrder = 'desc';
				} else {
					// Reset to default sort (Created date, newest first)
					var $defaultHeader = $ticketsTable.find('th[data-sort-type="date"]');
					sortTable($defaultHeader, 'desc');
					return;
				}

				sortTable($header, newOrder);
			});

			// Keyboard support (Enter key)
			$ticketsTable.on('keydown', '.pnpc-psd-sortable', function(e) {
				if (e.key === 'Enter') {
					$(this).trigger('click');
				}
			});

			// Update select all checkbox after sorting
			$ticketsTable.on('sortcomplete', function() {
				updateSelectAllState();
			});
		}

		function sortTable($header, order) {
			var $tbody = $ticketsTable.find('tbody');
			var sortType = $header.attr('data-sort-type');
			var columnIndex = $header.index();

			// Adjust for checkbox column if present
			if ($('#cb-select-all-1').length) {
				columnIndex--;
			}

			// Clear all sort indicators
			$ticketsTable.find('.pnpc-psd-sortable').attr('data-sort-order', '');

			// Set current sort indicator
			$header.attr('data-sort-order', order);

			// Separate active, closed, and divider rows
			var $activeRows = $tbody.find('tr').not('.pnpc-psd-ticket-closed').not('.pnpc-psd-closed-divider').toArray();
			var $closedRows = $tbody.find('tr.pnpc-psd-ticket-closed').toArray();
			var $divider = $tbody.find('tr.pnpc-psd-closed-divider');

			// Don't sort if only one or zero rows (excluding divider)
			if ($activeRows.length + $closedRows.length <= 1) {
				return;
			}

			// Sort active rows
			if ($activeRows.length > 0) {
				$activeRows.sort(function(a, b) {
					return compareRows(a, b, sortType, columnIndex);
				});

				if (order === 'desc') {
					$activeRows.reverse();
				}
			}

			// Sort closed rows
			if ($closedRows.length > 0) {
				$closedRows.sort(function(a, b) {
					return compareRows(a, b, sortType, columnIndex);
				});

				if (order === 'desc') {
					$closedRows.reverse();
				}
			}

			// Re-append rows to tbody: active, divider, closed
			$tbody.empty();

			$.each($activeRows, function(index, row) {
				$tbody.append(row);
			});

			if ($divider.length && $closedRows.length > 0) {
				$tbody.append($divider);
			}

			$.each($closedRows, function(index, row) {
				$tbody.append(row);
			});

			// Trigger custom event
			$ticketsTable.trigger('sortcomplete');

			// Announce to screen readers
			var sortTypeText = $header.text().trim();
			var orderText = (order === 'asc') ? 'ascending' : 'descending';
			announceToScreenReader('Table sorted by ' + sortTypeText + ' in ' + orderText + ' order');
		}

		function compareRows(a, b, sortType, columnIndex) {
			var $aCell = $(a).find('td').eq(columnIndex);
			var $bCell = $(b).find('td').eq(columnIndex);
			var aVal = $aCell.attr('data-sort-value');
			var bVal = $bCell.attr('data-sort-value');

			// Handle empty or undefined values
			var aEmpty = (aVal === undefined || aVal === '' || aVal === null);
			var bEmpty = (bVal === undefined || bVal === '' || bVal === null);

			if (aEmpty && bEmpty) {
				return 0; // Both empty, equal
			}
			if (aEmpty) {
				return 1; // Empty values sort to bottom
			}
			if (bEmpty) {
				return -1; // Empty values sort to bottom
			}

			var result = 0;

			switch (sortType) {
				case 'ticket-number':
				case 'date':
				case 'status':
				case 'priority':
				case 'boolean':
					// Numeric comparison
					var aNum = parseFloat(aVal);
					var bNum = parseFloat(bVal);
					result = aNum - bNum;
					break;

				case 'text':
				default:
					// Text comparison (case-insensitive)
					var aText = String(aVal).toLowerCase();
					var bText = String(bVal).toLowerCase();
					if (aText < bText) {
						result = -1;
					} else if (aText > bText) {
						result = 1;
					} else {
						result = 0;
					}
					break;
			}

			return result;
		}

		function updateSelectAllState() {
			var $selectAll = $('#cb-select-all-1');
			var $checkboxes = $('input[name="ticket[]"]');
			if ($selectAll.length && $checkboxes.length) {
				var allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
				$selectAll.prop('checked', allChecked);
			}
		}

		function announceToScreenReader(message) {
			var $announcement = $('#pnpc-psd-sort-announcement');
			if (!$announcement.length) {
				$announcement = $('<div>', {
					id: 'pnpc-psd-sort-announcement',
					'aria-live': 'polite',
					'aria-atomic': 'true',
					css: {
						position: 'absolute',
						left: '-10000px',
						width: '1px',
						height: '1px',
						overflow: 'hidden'
					}
				}).appendTo('body');
			}
			$announcement.text(message);
		}

		// Bulk actions functionality

// Helpers: update tab counts + remove selected rows without full reload.
function pnpcPsdUpdateTabCounts(counts) {
	if (!counts) { return; }
	// Update counts in subsubsub tabs: text like "Open (3)"
	$('.subsubsub a').each(function(){
		var $a = $(this);
		var href = $a.attr('href') || '';
		var key = '';
		// Determine key based on href query params
		if (href.indexOf('view=trash') !== -1) { key = 'trash'; }
		else if (href.indexOf('view=review') !== -1) { key = 'review'; }
		else if (href.indexOf('view=archived') !== -1) { key = 'archived'; }
		else if (href.indexOf('status=open') !== -1) { key = 'open'; }
		else if (href.indexOf('status=in-progress') !== -1) { key = 'in-progress'; }
		else if (href.indexOf('status=waiting') !== -1) { key = 'waiting'; }
		else if (href.indexOf('status=closed') !== -1) { key = 'closed'; }
		else { key = 'all'; }
		if (typeof counts[key] === 'undefined') { return; }
		var label = $a.clone().children().remove().end().text();
		label = $.trim(label);
		// Strip existing (n)
		label = label.replace(/\s*\(\d+\)\s*$/, '');
		$a.find('.count').remove();
		$a.text(label + ' ').append($('<span class="count"/>').text('(' + counts[key] + ')'));
	});
}

function pnpcPsdRemoveSelectedTicketRows(selectedIds) {
	if (!selectedIds || !selectedIds.length) { return; }
	var $table = $('#pnpc-psd-tickets-table');
	selectedIds.forEach(function(id){
		$table.find('input[name="ticket[]"][value="' + id + '"]').closest('tr').remove();
	});
	// Clear select-all
	$('#cb-select-all-1').prop('checked', false);
	// Show empty state if no rows remain (excluding header)
	var remaining = $table.find('tbody tr').length;
	if (remaining === 0) {
		$table.find('tbody').append('<tr class="no-items"><td class="colspanchange" colspan="999">No tickets found.</td></tr>');
	}
}
		var $bulkActionSelector = $('#bulk-action-selector-top');
		var $applyButton = $('#doaction');

		// Function to bind/rebind checkbox handlers
		function rebindCheckboxHandlers() {
			var $selectAllCheckbox = $('#cb-select-all-1');
			var $ticketCheckboxes = $('input[name="ticket[]"]');

			// Unbind previous handlers to prevent duplicates
			$selectAllCheckbox.off('change.bulkactions');
			$ticketCheckboxes.off('change.bulkactions');

			// Select all functionality
			$selectAllCheckbox.on('change.bulkactions', function() {
				var isChecked = $(this).prop('checked');
				$ticketCheckboxes.prop('checked', isChecked);
			});

			// Update select all checkbox when individual checkboxes change
			$ticketCheckboxes.on('change.bulkactions', function() {
				var allChecked = $ticketCheckboxes.length === $ticketCheckboxes.filter(':checked').length;
				$selectAllCheckbox.prop('checked', allChecked);
			});
		}

		// Initial binding
		rebindCheckboxHandlers();

		// Re-bind after AJAX refresh
		$(document).on('pnpc_psd_tickets_refreshed', function() {
			rebindCheckboxHandlers();
		});

		// Handle bulk action apply button (delegated, survives AJAX refreshes)
		$(document).off('click.pnpcpsdBulks', '#doaction, #doaction2');
		$(document).on('click.pnpcpsdBulks', '#doaction, #doaction2', function(e) {
			e.preventDefault();

			var $btn = $(this);
			// Bulk action selector lives next to the clicked button
			var $actionsWrap = $btn.closest('.bulkactions');
			var $selector = $actionsWrap.length ? $actionsWrap.find('select[name="action"], select[name="action2"]') : $('#bulk-action-selector-top');

			var action = $selector.length ? $selector.val() : '-1';
			if (action === '-1') {
				return;
			}

			// Always pull a fresh checkbox set (tbody is replaced during auto-refresh)
			var selectedTickets = [];
			$('#pnpc-psd-tickets-table').find('input[name="ticket[]"]:checked').each(function() {
				selectedTickets.push($(this).val());
			});

			if (selectedTickets.length === 0) {
				showMessage('error', 'Please select at least one ticket.', 'pnpc-psd-bulk-message');
				return;
			}

			var confirmMessage = '';
			var ajaxAction = '';

			if (action === 'trash') {
				confirmMessage = 'Are you sure you want to move ' + selectedTickets.length + ' ticket(s) to trash?';
				ajaxAction = 'pnpc_psd_bulk_trash_tickets';
			} else if (action === 'archive') {
				confirmMessage = 'Move ' + selectedTickets.length + ' ticket(s) to archive?';
				ajaxAction = 'pnpc_psd_bulk_archive_tickets';
			} else if (action === 'restore_archive') {
				confirmMessage = 'Restore ' + selectedTickets.length + ' ticket(s) from archive?';
				ajaxAction = 'pnpc_psd_bulk_restore_archived_tickets';
			} else if (action === 'restore') {
				confirmMessage = 'Are you sure you want to restore ' + selectedTickets.length + ' ticket(s)?';
				ajaxAction = 'pnpc_psd_bulk_restore_tickets';
			} else if (action === 'delete') {
				confirmMessage = 'Are you sure you want to permanently delete ' + selectedTickets.length + ' ticket(s)? This cannot be undone!';
				ajaxAction = 'pnpc_psd_bulk_delete_permanently_tickets';
			} else if (action === 'approve_to_trash') {
				confirmMessage = 'Approve deletion for ' + selectedTickets.length + ' ticket(s) and move to trash?';
				ajaxAction = 'pnpc_psd_bulk_approve_review_tickets';
			} else if (action === 'cancel_review') {
				confirmMessage = 'Restore ' + selectedTickets.length + ' ticket(s) and cancel delete request?';
				ajaxAction = 'pnpc_psd_bulk_cancel_review_tickets';
			}

			if (!confirm(confirmMessage)) {
				return;
			}

			// Disable button during operation
			$btn.prop('disabled', true).val('Processing...');

			$.ajax({
				url: pnpcPsdAdmin.ajax_url,
				type: 'POST',
				data: {
					action: ajaxAction,
					nonce: adminNonce,
					ticket_ids: selectedTickets
				},
				success: function(result) {
					if (result && result.success) {
						showMessage('success', result.data.message, 'pnpc-psd-bulk-message');
						// Remove rows immediately for responsiveness.
						pnpcPsdRemoveSelectedTicketRows(selectedTickets);
						if (result.data && result.data.counts) { pnpcPsdUpdateTabCounts(result.data.counts); }

						// Reload back to the same tab so counts + pagination are consistent (matches prior behavior).
						try {
							var url = new URL(window.location.href);
							url.searchParams.set('page', 'pnpc-service-desk-tickets');
							window.location = url.toString();
						} catch (e) {
							window.location = (typeof pnpcPsdAdmin !== 'undefined' && pnpcPsdAdmin.tickets_url) ? pnpcPsdAdmin.tickets_url : window.location.href;
						}

						$btn.prop('disabled', false).val('Apply');
						return;
					}

					if (result && result.data && result.data.message) {
						showMessage('error', result.data.message, 'pnpc-psd-bulk-message');
					} else {
						showMessage('error', 'Failed to perform bulk action.', 'pnpc-psd-bulk-message');
					}

					$btn.prop('disabled', false).val('Apply');
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.error('pnpc-psd-admin.js bulk action AJAX error', textStatus, errorThrown);
					showMessage('error', 'An error occurred. Please try again.', 'pnpc-psd-bulk-message');
					$btn.prop('disabled', false).val('Apply');
				},
				complete: function() {
					// Safety: ensure the button is re-enabled if we did not reload.
					$btn.prop('disabled', false).val('Apply');
				}
			});
		});

		// Ticket detail page functionality
		if (ticketId) {

			function pnpcPsdNormalizeTicketControlValue(value) {
				return String(value || '').toLowerCase().replace(/_/g, '-').replace(/[^a-z0-9-]/g, '');
			}

			function pnpcPsdUpdateTicketControlClass($field, prefix, value) {
				var normalized = pnpcPsdNormalizeTicketControlValue(value);
				if (!$field || !$field.length || !prefix || !normalized) {
					return;
				}

				$field.removeClass(function(i, cls) {
					var matches = cls.match(new RegExp('(^|\\s)' + prefix + '-\\S+', 'g')) || [];
					return matches.join(' ');
				});
				$field.addClass(prefix + '-' + normalized);

				var $select = $field.find('select');
				if ($select.length) {
					if (prefix.indexOf('status') !== -1) {
						$select.removeClass(function(i, cls) {
							var matches = cls.match(/(^|\s)pnpc-psd-status-\S+/g) || [];
							return matches.join(' ');
						});
						$select.addClass('pnpc-psd-status-' + normalized);
					}

					if (prefix.indexOf('priority') !== -1) {
						$select.removeClass(function(i, cls) {
							var matches = cls.match(/(^|\s)pnpc-psd-priority-\S+/g) || [];
							return matches.join(' ');
						});
						$select.addClass('pnpc-psd-priority-' + normalized);
					}
				}
			}


			function pnpcPsdSyncTicketControlSelect($select, type) {
				if (!$select || !$select.length) {
					return;
				}
				var normalized = pnpcPsdNormalizeTicketControlValue($select.val());
				if (!normalized) {
					return;
				}
				if ('status' === type) {
					$select.removeClass(function(i, cls) {
						var matches = cls.match(/(^|\s)pnpc-psd-status-\S+/g) || [];
						return matches.join(' ');
					});
					$select.addClass('pnpc-psd-status-' + normalized);
					pnpcPsdUpdateTicketControlClass($select.closest('.pnpc-psd-status-pill-field'), 'pnpc-psd-current-status', normalized);
				}
				if ('priority' === type) {
					$select.removeClass(function(i, cls) {
						var matches = cls.match(/(^|\s)pnpc-psd-priority-\S+/g) || [];
						return matches.join(' ');
					});
					$select.addClass('pnpc-psd-priority-' + normalized);
					pnpcPsdUpdateTicketControlClass($select.closest('.pnpc-psd-priority-pill-field'), 'pnpc-psd-current-priority', normalized);
				}
			}

			pnpcPsdSyncTicketControlSelect($('#pnpc-psd-status-select'), 'status');
			pnpcPsdSyncTicketControlSelect($('#pnpc-psd-priority-select'), 'priority');

			$('#pnpc-psd-assign-button').on('click', function(e) {
				e.preventDefault();
				var assignedTo = $('#pnpc-psd-assign-agent').val() || 0;

				$.ajax({
					url: pnpcPsdAdmin.ajax_url,
					type: 'POST',
					data: {
						action: 'pnpc_psd_assign_ticket',
						nonce: adminNonce,
						ticket_id: ticketId,
						assigned_to: assignedTo
					},
					success: function(result) {
						if (result && result.success) {
							var statusKey = (result.data && result.data.status) ? result.data.status : status;
							var statusLabel = (result.data && result.data.label) ? result.data.label : $('#pnpc-psd-status-select option:selected').text();
							showMessage('success', result.data.message, 'pnpc-psd-admin-action-message');
							pnpcPsdUpdateTicketControlClass($('#pnpc-psd-status-select').closest('.pnpc-psd-status-pill-field'), 'pnpc-psd-current-status', statusKey);
							var $statusBadge = $('.pnpc-psd-status').not('#pnpc-psd-status-select').first();
							if ($statusBadge.length) {
								$statusBadge.text(statusLabel);
								$statusBadge.removeClass(function(i, cls) {
									var matches = cls.match(/(^|\s)pnpc-psd-status-\S+/g) || [];
									return matches.join(' ');
								});
								$statusBadge.addClass('pnpc-psd-status-' + pnpcPsdNormalizeTicketControlValue(statusKey));
							}
						} else if (result && result.data && result.data.message) {
							showMessage('error', result.data.message, 'pnpc-psd-admin-action-message');
						} else {
							showMessage('error', 'Failed to assign ticket.', 'pnpc-psd-admin-action-message');
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						console.error('pnpc-psd-admin.js assign AJAX error', textStatus, errorThrown);
						showMessage('error', 'An error occurred. Please try again.', 'pnpc-psd-admin-action-message');
					}
				});
			});

			$('#pnpc-psd-assign-agent').on('change', function() {
				$('#pnpc-psd-assign-button').trigger('click');
			});

			$('#pnpc-psd-status-button').on('click', function(e) {
				e.preventDefault();
				var status = $('#pnpc-psd-status-select').val();

				$.ajax({
					url: pnpcPsdAdmin.ajax_url,
					type: 'POST',
					data: {
						action: 'pnpc_psd_update_ticket_status',
						nonce: adminNonce,
						ticket_id: ticketId,
						status:  status
					},
					success: function(result) {
						if (result && result.success) {
							showMessage('success', result.data.message, 'pnpc-psd-admin-action-message');
							if (result.data && result.data.status) {
								$('#pnpc-psd-status-select').val(result.data.status);
							}
							pnpcPsdSyncTicketControlSelect($('#pnpc-psd-status-select'), 'status');
							setTimeout(function() {
								location.reload();
							}, 600);
						} else if (result && result.data && result.data.message) {
							showMessage('error', result.data.message, 'pnpc-psd-admin-action-message');
						} else {
							showMessage('error', 'Failed to update status.', 'pnpc-psd-admin-action-message');
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						console.error('pnpc-psd-admin.js status AJAX error', textStatus, errorThrown);
						showMessage('error', 'An error occurred. Please try again.', 'pnpc-psd-admin-action-message');
					}
				});
			});

			$('#pnpc-psd-status-select').on('change', function() {
				pnpcPsdSyncTicketControlSelect($(this), 'status');
				$('#pnpc-psd-status-button').trigger('click');
			});


			// Priority auto-save
			function pnpcPsdSavePriority(priorityVal) {
				var pr = priorityVal || $('#pnpc-psd-priority-select').val();
				if (!pr) {
					return;
				}
				showMessage('info', 'Saving priority…', 'pnpc-psd-admin-action-message');
				$.ajax({
					url: pnpcPsdAdmin.ajax_url,
					type: 'POST',
					data: {
						action: 'pnpc_psd_update_ticket_priority',
						nonce: adminNonce,
						ticket_id: ticketId,
						priority: pr
					},
					success: function(result) {
						if (result && result.success) {
							var priorityKey = (result.data && result.data.priority) ? result.data.priority : pr;
							var priorityLabel = (result.data && result.data.label) ? result.data.label : $('#pnpc-psd-priority-select option:selected').text();
							showMessage('success', result.data.message || 'Priority updated.', 'pnpc-psd-admin-action-message');
							$('#pnpc-psd-priority-select').val(priorityKey);
							pnpcPsdSyncTicketControlSelect($('#pnpc-psd-priority-select'), 'priority');

							// Update the on-screen priority indicator without forcing a full page reload.
							var $pri = $('.pnpc-psd-priority').not('#pnpc-psd-priority-select').first();
							if ($pri.length) {
								$pri.text(priorityLabel);
								$pri.removeClass(function(i, cls) {
									var matches = cls.match(/(^|\s)pnpc-psd-priority-\S+/g) || [];
									return matches.join(' ');
								});
								$pri.addClass('pnpc-psd-priority-' + pnpcPsdNormalizeTicketControlValue(priorityKey));
							}
						} else if (result && result.data && result.data.message) {
							showMessage('error', result.data.message, 'pnpc-psd-admin-action-message');
						} else {
							showMessage('error', 'Failed to update priority.', 'pnpc-psd-admin-action-message');
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						console.error('pnpc-psd-admin.js priority AJAX error', textStatus, errorThrown);
						showMessage('error', 'An error occurred. Please try again.', 'pnpc-psd-admin-action-message');
					}
				});
			}

			$(document).on('change', '#pnpc-psd-priority-select', function() {
				pnpcPsdSyncTicketControlSelect($(this), 'priority');
				pnpcPsdSavePriority($(this).val());
			});

			// Failsafe: if the manual Update Priority button is used, intercept and ajax instead of full post.
			$(document).on('submit', '#pnpc-psd-priority-form', function(e) {
				e.preventDefault();
				pnpcPsdSavePriority($('#pnpc-psd-priority-select').val());
			});

			$('#pnpc-psd-response-form-admin').on('submit', function(e) {
				e.preventDefault();

				var $form = $(this);
				var formTicketId = $form.data('ticket-id');

				// Apply selected signature (if any) before validation and send.
				applySignatureToTextarea($('#response-text'));
				var response = $('#response-text').val();

				if (!response.trim()) {
					showMessage('error', 'Please enter a response.', 'response-message');
					return;
				}

				var formData = new FormData();
				formData.append('action', 'pnpc_psd_admin_respond_to_ticket');
				var nonce = $form.find('input[name="nonce"]').val() || adminNonce;
				formData.append('nonce', nonce);
				formData.append('ticket_id', formTicketId);
				formData.append('response', response);

				var fileInput = document.getElementById('admin-response-attachments');
				if (adminResponseFiles && adminResponseFiles.length) {
					for (var i = 0; i < adminResponseFiles.length; i++) {
						formData.append('attachments[]', adminResponseFiles[i]);
					}
				}

				$.ajax({
					url: pnpcPsdAdmin.ajax_url,
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success:  function(result) {
						if (result.success) {
							showMessage('success', result.data.message, 'response-message');
							$('#response-text').val('');
							if (fileInput) {
								fileInput.value = '';
							}
							adminResponseFiles = [];
							$('#pnpc-psd-admin-response-attachments-list').empty();
							setTimeout(function() {
								location.reload();
							}, 900);
						} else {
							var msg = (result && result.data && result.data.message) ? result.data.message : 'Failed to add response.';
							showMessage('error', msg, 'response-message');
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						console.error('pnpc-psd-admin.js AJAX error', textStatus, errorThrown);
						showMessage('error', 'An error occurred. Please try again.', 'response-message');
					}
				});
			});
		}

		// Delete ticket from detail page (with reason)
		$(document).on('click', '.pnpc-psd-delete-ticket-btn', function() {
			var ticketId = $(this).data('ticket-id');
			deleteReasonModal.show([ticketId], false);
		});

		// Delete reason modal handling
		var deleteReasonModal = {
			ticketIds: [],
			isBulk: false,

			show: function(ticketIds, isBulk) {
				this.ticketIds = ticketIds;
				this.isBulk = isBulk;

				var message = 'Why are you deleting this ticket? This action cannot be undone.';

				$('#pnpc-psd-delete-modal-message').text(message);
				$('#pnpc-psd-delete-reason-select').val('');
				$('#pnpc-psd-delete-reason-other').val('');
				$('#pnpc-psd-delete-reason-other-wrapper').hide();
				$('#pnpc-psd-delete-error-message').hide();
				$('#pnpc-psd-delete-modal').fadeIn(300);
			},

			hide: function() {
				$('#pnpc-psd-delete-modal').fadeOut(300);
			},

			submit: function() {
				var reason = $('#pnpc-psd-delete-reason-select').val();
				var reasonOther = $('#pnpc-psd-delete-reason-other').val();

				// Validation
				if (!reason) {
					$('#pnpc-psd-delete-error-message')
						.text('Please select a reason before deleting.')
						.show();
					return;
				}

				if (reason === 'other' && reasonOther.length < 10) {
					$('#pnpc-psd-delete-error-message')
						.text('Please provide more details (at least 10 characters).')
						.show();
					return;
				}

				// Hide error message
				$('#pnpc-psd-delete-error-message').hide();

				// Disable submit button during operation
				$('.pnpc-psd-delete-submit').prop('disabled', true).text('Submitting...');

				// Send AJAX request
				$.ajax({
					url: pnpcPsdAdmin.ajax_url,
					type: 'POST',
					data: {
						action: 'pnpc_psd_request_delete_with_reason',
						nonce: adminNonce,
						ticket_ids: deleteReasonModal.ticketIds,
						reason: reason,
						reason_other: reasonOther
					},
					success: function(response) {
						if (response.success) {
							deleteReasonModal.hide();
							// Redirect to the All Tickets tab after a successful delete request.
							// All Tickets is the default view for the ticket list page.
							window.location.href = (pnpcPsdAdmin.tickets_url ? pnpcPsdAdmin.tickets_url : 'admin.php?page=pnpc-service-desk-tickets');
						} else {
							$('#pnpc-psd-delete-error-message')
								.text('Error: ' + response.data.message)
								.show();
							$('.pnpc-psd-delete-submit').prop('disabled', false).text('Request Delete');
						}
					},
					error: function() {
						$('#pnpc-psd-delete-error-message')
							.text('An error occurred. Please try again.')
							.show();
						$('.pnpc-psd-delete-submit').prop('disabled', false).text('Request Delete');
					}
				});
			}
		};

		// Show reason field if "Other" is selected
		$(document).on('change', '#pnpc-psd-delete-reason-select', function() {
			if ($(this).val() === 'other') {
				$('#pnpc-psd-delete-reason-other-wrapper').slideDown(200);
			} else {
				$('#pnpc-psd-delete-reason-other-wrapper').slideUp(200);
			}
		});

		// Modal close handlers
		$(document).on('click', '.pnpc-psd-modal-close, .pnpc-psd-delete-cancel', function() {
			deleteReasonModal.hide();
		});

		// Modal submit handler
		$(document).on('click', '.pnpc-psd-delete-submit', function() {
			deleteReasonModal.submit();
		});

		// Close modal when clicking backdrop
		$(document).on('click', '.pnpc-psd-modal-backdrop', function() {
			deleteReasonModal.hide();
		});

		function showMessage(type, message, targetId) {
			var safeTarget = (targetId && MESSAGE_TARGETS.indexOf(targetId) !== -1) ? targetId : '';
			var $messageDiv;
			if (safeTarget) {
				var targetEl = document.getElementById(safeTarget);
				$messageDiv = targetEl ?  $(targetEl) : $();
			} else {
				$messageDiv = $('#response-message');
			}
			if (! $messageDiv.length) {
				return;
			}

			// Remove existing notice classes (preserve pnpc-psd-message base class)
			$messageDiv.removeClass('notice notice-success notice-error notice-info notice-warning success error info');

			// Add WP notice classes based on type
			var noticeClass = 'notice';
			if (type === 'success') {
				noticeClass += ' notice-success';
			} else if (type === 'error') {
				noticeClass += ' notice-error';
			} else if (type === 'info') {
				noticeClass += ' notice-info';
			} else if (type === 'warning') {
				noticeClass += ' notice-warning';
			}

			// Ensure base message class is present
			if (!$messageDiv.hasClass('pnpc-psd-message')) {
				$messageDiv.addClass('pnpc-psd-message');
			}

			$messageDiv.addClass(noticeClass).text(message).show();

			setTimeout(function() {
				$messageDiv.fadeOut();
			}, 5000);
		}

		// File attachment preview for admin ticket creation
		$('#attachments').on('change', function() {
			var files = this.files;
			var $preview = $('#pnpc-psd-admin-attachments-preview');
			$preview.empty();

			if (files.length > 0) {
				var html = '<strong>Files to upload:</strong><ul>';
				for (var i = 0; i < files.length; i++) {
					var size = (files[i].size / 1024 / 1024).toFixed(2);
					html += '<li>' + files[i].name + ' (' + size + ' MB)</li>';
				}
				html += '</ul>';
				$preview.html(html);
			}
		});
	});

})( jQuery );
/* Ticket-detail lightweight activity indicators. */
(function($) {
	'use strict';
	$(function() {
		var $detail = $('#pnpc-psd-ticket-detail');
		if (!$detail.length || typeof pnpcPsdAdmin === 'undefined') {
			return;
		}
		var ticketId = parseInt($detail.attr('data-ticket-id') || '0', 10);
		if (!ticketId) {
			return;
		}
		var baseline = null;
		function getBadge($el, cls, label) {
			var $badge = $el.find('.' + cls);
			if (!$badge.length) {
				$badge = $('<span/>', { 'class': 'pnpc-psd-live-tab-badge ' + cls, text: label || 'New' }).hide();
				$el.append($badge);
			}
			return $badge;
		}
		function setBadge($el, cls, show, label) {
			if (!$el.length) { return; }
			getBadge($el, cls, label).toggle(!!show);
		}
		function refreshCustomerThread(markBaseline) {
			var $refreshButton = $('.pnpc-psd-conversation-refresh-button').first();
			$refreshButton.addClass('is-refreshing').attr('aria-busy', 'true');
			$.ajax({
				url: pnpcPsdAdmin.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: { action: 'pnpc_psd_admin_ticket_conversation_html', nonce: pnpcPsdAdmin.nonce, ticket_id: ticketId }
			}).done(function(resp) {
				if (resp && resp.success && resp.data && typeof resp.data.html === 'string') {
					$('.pnpc-psd-conversation-modern-card .pnpc-psd-message-thread').first().html(resp.data.html);
					$(document).trigger('pnpc_psd_attachment_gallery_refresh');
					if (markBaseline) { baseline = null; poll(); }
				}
			}).always(function() {
				$refreshButton.removeClass('is-refreshing').removeAttr('aria-busy');
			});
		}
		function customerTab() {
			return $('.pnpc-psd-reply-tab').filter(function() { return /client|customer/i.test($(this).text()); }).first();
		}
		function ensureConversationRefreshButton() {
			var $header = $('.pnpc-psd-conversation-modern-header').first();
			if (!$header.length || $header.find('.pnpc-psd-conversation-refresh-button').length) {
				return;
			}
			$header.append('<button type="button" class="button button-small pnpc-psd-conversation-refresh-button" title="Refresh conversation" aria-label="Refresh conversation"><span class="dashicons dashicons-update" aria-hidden="true"></span></button>');
		}
		ensureConversationRefreshButton();
		function poll() {
			$.ajax({
				url: pnpcPsdAdmin.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: { action: 'pnpc_psd_admin_ticket_activity_state', nonce: pnpcPsdAdmin.nonce, ticket_id: ticketId }
			}).done(function(resp) {
				if (!(resp && resp.success && resp.data)) { return; }
				var data = resp.data;
				if (!baseline) {
					baseline = data;
					return;
				}
				var customerNew = parseInt(data.customer_count, 10) > parseInt(baseline.customer_count || 0, 10);
				var attachmentNew = parseInt(data.attachment_count || 0, 10) > parseInt(baseline.attachment_count || 0, 10);
				var internalNew = parseInt(data.internal_count, 10) > parseInt(baseline.internal_count || 0, 10);
				setBadge(customerTab(), 'pnpc-psd-live-tab-badge-customer', customerNew, 'New');
				setBadge($('.pnpc-psd-pro-internal-tab, .pnpc-psd-pro-internal-discussion-tab').first(), 'pnpc-psd-live-tab-badge-internal', internalNew, 'New');
				if ((customerNew || attachmentNew) && customerTab().hasClass('is-active')) { refreshCustomerThread(true); }
				if (internalNew && $('.pnpc-psd-pro-internal-discussion-tab').first().hasClass('is-active')) { $('.pnpc-psd-pro-internal-discussion-tab').first().trigger('pnpcPsdRefreshInternalDiscussion'); }
			});
		}
		setInterval(poll, 10000);
		poll();
		$(document).on('click', '.pnpc-psd-conversation-refresh-button', function() {
			refreshCustomerThread(true);
		});
		$(document).on('click', '.pnpc-psd-reply-tab, .pnpc-psd-pro-internal-tab, .pnpc-psd-pro-internal-discussion-tab', function() {
			$(this).find('.pnpc-psd-live-tab-badge').hide();
			if ($(this).is(customerTab())) { refreshCustomerThread(true); }
			poll();
		});
	});
})(jQuery);

/* Hide empty WordPress notice containers on Service Desk screens. */
(function($) {
	'use strict';
	$(function() {
		$('.notice, .pnpc-psd-message').each(function() {
			var $notice = $(this);
			if (!$notice.text().trim() && !$notice.find('input, button, a, select, textarea, img, svg').length) {
				$notice.hide();
			}
		});
	});
})(jQuery);
