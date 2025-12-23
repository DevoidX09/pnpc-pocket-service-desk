/**
 * Admin JavaScript for PNPC Pocket Service Desk (patched to submit admin response with attachments)
 */
(function( $ ) {
	'use strict';

	$(document).ready(function() {
		var ticketId = $('#pnpc-psd-ticket-detail').data('ticket-id');
		var adminNonce = (typeof pnpcPsdAdmin !== 'undefined') ? pnpcPsdAdmin.nonce : '';

		$('#pnpc-psd-assign-button').on('click', function(e) {
			e.preventDefault();
			if (!ticketId || !adminNonce) {
				return;
			}
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
					console.error('pnpc-psd-admin.js assign AJAX error', textStatus, errorThrown, jqXHR && jqXHR.responseText);
					showMessage('error', 'An error occurred. Please try again.', 'pnpc-psd-admin-action-message');
				}
			});
		});

		$('#pnpc-psd-status-button').on('click', function(e) {
			e.preventDefault();
			if (!ticketId || !adminNonce) {
				return;
			}
			var status = $('#pnpc-psd-status-select').val();
			$.ajax({
				url: pnpcPsdAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'pnpc_psd_update_ticket_status',
					nonce: adminNonce,
					ticket_id: ticketId,
					status: status
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
					console.error('pnpc-psd-admin.js status AJAX error', textStatus, errorThrown, jqXHR && jqXHR.responseText);
					showMessage('error', 'An error occurred. Please try again.', 'pnpc-psd-admin-action-message');
				}
			});
		});

		// Handle admin response form submission (supports attachments)
		$('#pnpc-psd-response-form-admin').on('submit', function(e) {
			e.preventDefault();

			var $form = $(this);
			var ticketId = $form.data('ticket-id');
			var response = $('#response-text').val();

			if (!response.trim()) {
				showMessage('error', 'Please enter a response.');
				return;
			}

			var formData = new FormData();
			formData.append('action', 'pnpc_psd_respond_to_ticket');
			// admin nonce field present in form
			var nonce = $form.find('input[name="nonce"]').val();
			if (nonce) {
				formData.append('nonce', nonce);
			}
			formData.append('ticket_id', ticketId);
			formData.append('response', response);

			// Attach files if present
			var files = document.getElementById('admin-response-attachments').files;
			for (var i = 0; i < files.length; i++) {
				formData.append('attachments[]', files[i]);
			}

			$.ajax({
				url: pnpcPsdAdmin.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(result) {
					if (result.success) {
						showMessage('success', result.data.message, 'response-message');
						$('#response-text').val('');
						setTimeout(function() {
							location.reload();
						}, 900);
					} else {
						showMessage('error', result.data.message, 'response-message');
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.error('pnpc-psd-admin.js AJAX error', textStatus, errorThrown, jqXHR && jqXHR.responseText);
					showMessage('error', 'An error occurred. Please try again.', 'response-message');
				}
			});
		});

		function showMessage(type, message, targetId) {
			var safeTarget = targetId ? String(targetId).replace(/[^A-Za-z0-9_-]/g, '') : '';
			var $messageDiv;
			if (safeTarget) {
				var targetEl = document.getElementById(safeTarget);
				$messageDiv = targetEl ? $(targetEl) : $();
			} else {
				$messageDiv = $('#response-message');
			}
			if (!$messageDiv.length) {
				return;
			}
			$messageDiv.removeClass('success error').addClass(type).text(message).show();

			setTimeout(function() {
				$messageDiv.fadeOut();
			}, 5000);
		}
	});

})( jQuery );
