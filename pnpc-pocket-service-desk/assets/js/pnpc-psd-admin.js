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
			var $selector = $actionsWrap.length ? $actionsWrap.find('select[name="action"]') : $('#bulk-action-selector-top');

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
						// Ask realtime refresh script to pull fresh rows immediately (if loaded)
						$(document).trigger('pnpc_psd_force_refresh');
						$btn.prop('disabled', false).val('Apply');
					} else if (result && result.data && result.data.message) {
						showMessage('error', result.data.message, 'pnpc-psd-bulk-message');
						$btn.prop('disabled', false).val('Apply');
					} else {
						showMessage('error', 'Failed to perform bulk action.', 'pnpc-psd-bulk-message');
						$btn.prop('disabled', false).val('Apply');
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.error('pnpc-psd-admin.js bulk action AJAX error', textStatus, errorThrown);
					showMessage('error', 'An error occurred. Please try again.', 'pnpc-psd-bulk-message');
					$btn.prop('disabled', false).val('Apply');
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
							window.location.href = (pnpcPsdAdmin.tickets_url ? pnpcPsdAdmin.tickets_url : 'admin.php?page=pnpc-service-desk');
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
			$messageDiv.removeClass('success error').addClass(type).text(message).show();

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
					var sizeClass = size > 5 ? 'style="color: red;"' : '';
					html += '<li>' + files[i].name + ' (' + size + ' MB)';
					if (size > 5) {
						html += ' <span style="color: red;">- Exceeds 5MB limit!</span>';
					}
					html += '</li>';
				}
				html += '</ul>';
				$preview.html(html);
			}
		});
	});

})( jQuery );