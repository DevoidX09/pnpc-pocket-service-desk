/**
 * Admin JavaScript for PNPC Pocket Service Desk (patched to submit admin response with attachments)
 */
(function( $ ) {
	'use strict';

	$(document).ready(function() {
		// Existing status/assign handlers unchanged (left out for brevity)...

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
						showMessage('success', result.data.message);
						$('#response-text').val('');
						setTimeout(function() {
							location.reload();
						}, 900);
					} else {
						showMessage('error', result.data.message);
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.error('pnpc-psd-admin.js AJAX error', textStatus, errorThrown, jqXHR && jqXHR.responseText);
					showMessage('error', 'An error occurred. Please try again.');
				}
			});
		});

		function showMessage(type, message) {
			var $messageDiv = $('#response-message');
			$messageDiv.removeClass('success error').addClass(type).text(message).show();

			setTimeout(function() {
				$messageDiv.fadeOut();
			}, 5000);
		}
	});

})( jQuery );