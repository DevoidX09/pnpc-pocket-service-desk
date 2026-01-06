/**
 * Admin JavaScript for PNPC Pocket Service Desk
 */
(function( $ ) {
	'use strict';

	$(document).ready(function() {
		var $ticketDetail = $('#pnpc-psd-ticket-detail');
		var ticketId = $ticketDetail.data('ticket-id');
		var adminNonce = (typeof pnpcPsdAdmin !== 'undefined') ? pnpcPsdAdmin.nonce :  '';
		var MESSAGE_TARGETS = ['pnpc-psd-admin-action-message', 'response-message', 'pnpc-psd-bulk-message'];
		var autoSortApplied = false;

		if (! adminNonce) {
			return;
		}

		// Table sorting functionality
		var $ticketsTable = $('#pnpc-psd-tickets-table');
		var lastSortClick = 0;

		/**
		 * Check if we're on the "All" tab
		 */
		function isAllTab() {
			var urlParams = new URLSearchParams(window.location.search);
			var status = urlParams.get('status');
			var view = urlParams.get('view');
			
			// "All" tab = no status parameter and not trash view
			return !status && view !== 'trash';
		}

		/**
		 * Remove divider row from table
		 */
		function removeDivider() {
			$ticketsTable.find('tr[data-divider="true"]').remove();
			autoSortApplied = false;
		}

		/**
		 * Apply auto-sort for "All" tab: group active tickets above closed tickets with divider
		 */
		function applyAllTabAutoSort() {
			if (!isAllTab() || autoSortApplied) {
				return;
			}

			var $tbody = $ticketsTable.find('tbody');
			var $rows = $tbody.find('tr[data-status]');

			if ($rows.length === 0) {
				return;
			}

			// Separate rows into active and closed groups
			var activeRows = [];
			var closedRows = [];

			$rows.each(function() {
				var $row = $(this);
				var status = $row.attr('data-status');
				
				if (status === 'closed') {
					closedRows.push($row);
				} else {
					activeRows.push($row);
				}
			});

			// Don't show divider if one group is empty
			if (activeRows.length === 0 || closedRows.length === 0) {
				return;
			}

			// Sort each group by created date descending (newest first)
			function sortByCreatedDate(rows) {
				return rows.sort(function(a, b) {
					var $aCells = $(a).find('td');
					var $bCells = $(b).find('td');
					
					// Find the date column (adjust index if checkbox column exists)
					var dateColumnIndex = 6; // Default position
					if ($('#cb-select-all-1').length) {
						dateColumnIndex = 6; // Still 6 because we count from td, not including th
					}
					
					var aDate = parseInt($aCells.eq(dateColumnIndex).attr('data-sort-value')) || 0;
					var bDate = parseInt($bCells.eq(dateColumnIndex).attr('data-sort-value')) || 0;
					
					return bDate - aDate; // Descending order (newest first)
				});
			}

			activeRows = sortByCreatedDate(activeRows);
			closedRows = sortByCreatedDate(closedRows);

			// Create divider row
			var colCount = $ticketsTable.find('thead tr th').length;
			var $dividerRow = $('<tr>', {
				'class': 'pnpc-psd-divider-row',
				'data-divider': 'true'
			});
			
			// Add checkbox column if present (empty cell)
			if ($('#cb-select-all-1').length) {
				$dividerRow.append($('<td>', {
					'class': 'check-column'
				}));
				colCount--; // Reduce colspan count
			}
			
			$dividerRow.append($('<td>', {
				'colspan': colCount,
				'text': 'Closed Tickets'
			}));

			// Clear tbody and append sorted groups with divider
			$tbody.empty();
			
			$.each(activeRows, function(i, row) {
				$tbody.append(row);
			});
			
			$tbody.append($dividerRow);
			
			$.each(closedRows, function(i, row) {
				$tbody.append(row);
			});

			autoSortApplied = true;
		}

		if ($ticketsTable.length) {
			// Apply auto-sort for "All" tab on page load
			applyAllTabAutoSort();

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

				// Remove divider when manual sorting is applied
				removeDivider();

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
			// Get only ticket rows, not divider rows
			var rows = $tbody.find('tr:not([data-divider])').get();
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

			// Don't sort if only one or zero rows
			if (rows.length <= 1) {
				return;
			}

			// Sort rows
			rows.sort(function(a, b) {
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
			});

			// Reverse if descending
			if (order === 'desc') {
				rows.reverse();
			}

			// Re-append rows to tbody
			var fragment = document.createDocumentFragment();
			$.each(rows, function(index, row) {
				fragment.appendChild(row);
			});
			$tbody.empty().append(fragment);

			// Trigger custom event
			$ticketsTable.trigger('sortcomplete');

			// Announce to screen readers
			var sortTypeText = $header.text().trim();
			var orderText = (order === 'asc') ? 'ascending' : 'descending';
			announceToScreenReader('Table sorted by ' + sortTypeText + ' in ' + orderText + ' order');
		}

		function updateSelectAllState() {
			var $selectAll = $('#cb-select-all-1');
			var $checkboxes = getTicketCheckboxes();
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
		var $bulkActionSelector = $('#bulk-action-selector-top');
		var $applyButton = $('#doaction');
		var $selectAllCheckbox = $('#cb-select-all-1');

		// Get ticket checkboxes (excluding any in divider rows)
		function getTicketCheckboxes() {
			return $('input[name="ticket[]"]').filter(function() {
				return !$(this).closest('tr').attr('data-divider');
			});
		}

		// Select all functionality
		$selectAllCheckbox.on('change', function() {
			var isChecked = $(this).prop('checked');
			getTicketCheckboxes().prop('checked', isChecked);
		});

		// Update select all checkbox when individual checkboxes change
		$(document).on('change', 'input[name="ticket[]"]', function() {
			var $ticketCheckboxes = getTicketCheckboxes();
			var allChecked = $ticketCheckboxes.length === $ticketCheckboxes.filter(':checked').length;
			$selectAllCheckbox.prop('checked', allChecked);
		});

		// Handle bulk action apply button
		$applyButton.on('click', function(e) {
			e.preventDefault();

			var action = $bulkActionSelector.val();
			if (action === '-1') {
				return;
			}

			var selectedTickets = [];
			getTicketCheckboxes().filter(':checked').each(function() {
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
			} else if (action === 'restore') {
				confirmMessage = 'Are you sure you want to restore ' + selectedTickets.length + ' ticket(s)?';
				ajaxAction = 'pnpc_psd_bulk_restore_tickets';
			} else if (action === 'delete') {
				confirmMessage = 'Are you sure you want to permanently delete ' + selectedTickets.length + ' ticket(s)? This cannot be undone!';
				ajaxAction = 'pnpc_psd_bulk_delete_permanently_tickets';
			}

			if (!confirm(confirmMessage)) {
				return;
			}

			// Disable button during operation
			$applyButton.prop('disabled', true).val('Processing...');

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
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else if (result && result.data && result.data.message) {
						showMessage('error', result.data.message, 'pnpc-psd-bulk-message');
						$applyButton.prop('disabled', false).val('Apply');
					} else {
						showMessage('error', 'Failed to perform bulk action.', 'pnpc-psd-bulk-message');
						$applyButton.prop('disabled', false).val('Apply');
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.error('pnpc-psd-admin.js bulk action AJAX error', textStatus, errorThrown);
					showMessage('error', 'An error occurred. Please try again.', 'pnpc-psd-bulk-message');
					$applyButton.prop('disabled', false).val('Apply');
				}
			});
		});

		// Ticket detail page functionality
		if (ticketId) {
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
							showMessage('success', result.data.message, 'pnpc-psd-admin-action-message');
							setTimeout(function() {
								location.reload();
							}, 600);
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
				$('#pnpc-psd-status-button').trigger('click');
			});

			$('#pnpc-psd-response-form-admin').on('submit', function(e) {
				e.preventDefault();

				var $form = $(this);
				var formTicketId = $form.data('ticket-id');
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
				if (fileInput && fileInput.files) {
					for (var i = 0; i < fileInput.files.length; i++) {
						formData.append('attachments[]', fileInput.files[i]);
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
			$messageDiv.removeClass('success error').addClass(type).text(message).show();

			setTimeout(function() {
				$messageDiv.fadeOut();
			}, 5000);
		}
	});

})( jQuery );