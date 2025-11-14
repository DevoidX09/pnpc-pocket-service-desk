/**
 * Admin JavaScript for PNPC Pocket Service Desk
 *
 * @package PNPC_Pocket_Service_Desk
 */

(function( $ ) {
	'use strict';

	$(document).ready(function() {
		// Handle ticket status change
		$('#ticket-status').on('change', function() {
			var ticketId = $(this).data('ticket-id');
			var status = $(this).val();

			$.ajax({
				url: pnpcPsdAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'pnpc_psd_update_ticket_status',
					nonce: pnpcPsdAdmin.nonce,
					ticket_id: ticketId,
					status: status
				},
				success: function(response) {
					if (response.success) {
						showMessage('success', response.data.message);
					} else {
						showMessage('error', response.data.message);
					}
				},
				error: function() {
					showMessage('error', 'An error occurred. Please try again.');
				}
			});
		});

		// Handle ticket assignment
		$('#ticket-assign').on('change', function() {
			var ticketId = $(this).data('ticket-id');
			var assignedTo = $(this).val();

			$.ajax({
				url: pnpcPsdAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'pnpc_psd_assign_ticket',
					nonce: pnpcPsdAdmin.nonce,
					ticket_id: ticketId,
					assigned_to: assignedTo
				},
				success: function(response) {
					if (response.success) {
						showMessage('success', response.data.message);
					} else {
						showMessage('error', response.data.message);
					}
				},
				error: function() {
					showMessage('error', 'An error occurred. Please try again.');
				}
			});
		});

		// Handle response form submission
		$('#pnpc-psd-response-form').on('submit', function(e) {
			e.preventDefault();

			var ticketId = $(this).data('ticket-id');
			var response = $('#response-text').val();

			if (!response.trim()) {
				showMessage('error', 'Please enter a response.');
				return;
			}

			$.ajax({
				url: pnpcPsdAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'pnpc_psd_respond_to_ticket',
					nonce: pnpcPsdAdmin.nonce,
					ticket_id: ticketId,
					response: response
				},
				success: function(result) {
					if (result.success) {
						showMessage('success', result.data.message);
						$('#response-text').val('');
						// Reload page to show new response
						setTimeout(function() {
							location.reload();
						}, 1500);
					} else {
						showMessage('error', result.data.message);
					}
				},
				error: function() {
					showMessage('error', 'An error occurred. Please try again.');
				}
			});
		});

		// Handle ticket deletion
		$('#delete-ticket').on('click', function() {
			if (!confirm('Are you sure you want to delete this ticket? This action cannot be undone.')) {
				return;
			}

			var ticketId = $(this).data('ticket-id');

			$.ajax({
				url: pnpcPsdAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'pnpc_psd_delete_ticket',
					nonce: pnpcPsdAdmin.nonce,
					ticket_id: ticketId
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						window.location.href = 'admin.php?page=pnpc-service-desk';
					} else {
						alert(response.data.message);
					}
				},
				error: function() {
					alert('An error occurred. Please try again.');
				}
			});
		});

		// Show message helper function
		function showMessage(type, message) {
			var $messageDiv = $('#response-message');
			$messageDiv.removeClass('success error').addClass(type).text(message).show();

			setTimeout(function() {
				$messageDiv.fadeOut();
			}, 5000);
		}
	});

})( jQuery );
