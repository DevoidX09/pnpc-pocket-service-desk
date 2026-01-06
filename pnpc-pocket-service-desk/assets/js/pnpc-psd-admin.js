/**
 * Admin JavaScript for PNPC Pocket Service Desk
 */
(function( $ ) {
	'use strict';

	$(document).ready(function() {
		var $ticketDetail = $('#pnpc-psd-ticket-detail');
		var ticketId = $ticketDetail.data('ticket-id');
		var adminNonce = (typeof pnpcPsdAdmin !== 'undefined') ? pnpcPsdAdmin.nonce :  '';
		var MESSAGE_TARGETS = ['pnpc-psd-admin-action-message', 'response-message'];

		if (! adminNonce) {
			return;
		}

		if (! ticketId) {
			return;
		}

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
					showMessage('error', 'An error occurred.Please try again.', 'pnpc-psd-admin-action-message');
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
					showMessage('error', 'An error occurred.Please try again.', 'pnpc-psd-admin-action-message');
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
					showMessage('error', 'An error occurred.Please try again.', 'response-message');
				}
			});
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
	});

})( jQuery );