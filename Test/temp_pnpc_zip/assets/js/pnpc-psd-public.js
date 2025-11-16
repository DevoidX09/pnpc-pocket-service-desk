/**
 * Public JavaScript for PNPC Pocket Service Desk
 *
 * @package PNPC_Pocket_Service_Desk
 */

(function( $ ) {
	'use strict';

	$(document).ready(function() {
		// Handle ticket creation form submission
		$('#pnpc-psd-create-ticket-form').on('submit', function(e) {
			e.preventDefault();

			var subject = $('#ticket-subject').val();
			var description = $('#ticket-description').val();
			var priority = $('#ticket-priority').val();

			if (!subject.trim() || !description.trim()) {
				showMessage('error', 'Please fill in all required fields.');
				return;
			}

			$.ajax({
				url: pnpcPsdPublic.ajax_url,
				type: 'POST',
				data: {
					action: 'pnpc_psd_create_ticket',
					nonce: pnpcPsdPublic.nonce,
					subject: subject,
					description: description,
					priority: priority
				},
				success: function(response) {
					if (response.success) {
						showMessage('success', response.data.message + ' (Ticket #' + response.data.ticket_number + ')');
						$('#pnpc-psd-create-ticket-form')[0].reset();
						
						// Optionally redirect to ticket detail page
						setTimeout(function() {
							window.location.href = '?ticket_id=' + response.data.ticket_id;
						}, 2000);
					} else {
						showMessage('error', response.data.message);
					}
				},
				error: function() {
					showMessage('error', 'An error occurred. Please try again.');
				}
			});
		});

		// Handle ticket response form submission
		$('#pnpc-psd-response-form').on('submit', function(e) {
			e.preventDefault();

			var ticketId = $(this).data('ticket-id');
			var response = $('#response-text').val();

			if (!response.trim()) {
				showMessage('error', 'Please enter your response.');
				return;
			}

			$.ajax({
				url: pnpcPsdPublic.ajax_url,
				type: 'POST',
				data: {
					action: 'pnpc_psd_respond_to_ticket',
					nonce: pnpcPsdPublic.nonce,
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

		// Handle profile image upload
		$('#profile-image-upload').on('change', function() {
			var file = this.files[0];
			
			if (!file) {
				return;
			}

			// Validate file type
			var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
			if (allowedTypes.indexOf(file.type) === -1) {
				showProfileMessage('error', 'Invalid file type. Only JPEG, PNG, and GIF are allowed.');
				return;
			}

			// Validate file size (max 2MB)
			if (file.size > 2097152) {
				showProfileMessage('error', 'File size must not exceed 2MB.');
				return;
			}

			var formData = new FormData();
			formData.append('action', 'pnpc_psd_upload_profile_image');
			formData.append('nonce', pnpcPsdPublic.nonce);
			formData.append('profile_image', file);

			$.ajax({
				url: pnpcPsdPublic.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					if (response.success) {
						showProfileMessage('success', response.data.message);
						$('#profile-image-preview').attr('src', response.data.url);
					} else {
						showProfileMessage('error', response.data.message);
					}
				},
				error: function() {
					showProfileMessage('error', 'An error occurred. Please try again.');
				}
			});
		});

		// Show message helper function
		function showMessage(type, message) {
			var $messageDiv = $('#ticket-create-message, #response-message').filter(function() {
				return $(this).is(':visible') || $(this).closest('form').length > 0;
			}).first();
			
			$messageDiv.removeClass('success error').addClass(type).text(message).show();

			setTimeout(function() {
				$messageDiv.fadeOut();
			}, 5000);
		}

		// Show profile message helper function
		function showProfileMessage(type, message) {
			var $messageDiv = $('#profile-image-message');
			$messageDiv.removeClass('success error').addClass(type).text(message).show();

			setTimeout(function() {
				$messageDiv.fadeOut();
			}, 5000);
		}
	});

})( jQuery );
