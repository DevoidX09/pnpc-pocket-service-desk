/**
 * Public JavaScript (patched to submit attachments with response)
 */
(function( $ ) {
	'use strict';

	$(document).ready(function() {
		// Create ticket file handling and submission (unchanged)...

		// Response attachments preview support
		var responseFiles = [];

		$('#response-attachments').on('change', function(e) {
			responseFiles = Array.prototype.slice.call(e.target.files || []);
			renderResponseAttachmentsList();
		});

		function renderResponseAttachmentsList() {
			var $list = $('#pnpc-psd-response-attachments-list');
			$list.empty();
			if (!responseFiles.length) {
				return;
			}
			responseFiles.forEach(function(file, idx) {
				var $item = $('<div/>').addClass('pnpc-psd-attachment-item').css({marginBottom:'6px'});
				$item.append($('<span/>').text(file.name + ' (' + Math.round(file.size/1024) + ' KB)'));
				var $remove = $('<button/>').attr('type','button').addClass('pnpc-psd-button').css({marginLeft:'8px'}).text('Remove');
				$remove.on('click', function() {
					responseFiles.splice(idx, 1);
					$('#response-attachments').val('');
					renderResponseAttachmentsList();
				});
				$item.append($remove);
				$list.append($item);
			});
		}

		// Handle ticket response form submission (now supports attachments)
		$('#pnpc-psd-response-form').on('submit', function(e) {
			e.preventDefault();

			var $form = $(this);
			var ticketId = $form.data('ticket-id');
			var response = $('#response-text').val();

			if (!response.trim()) {
				showResponseMessage('error', 'Please enter your response.');
				return;
			}

			var formData = new FormData();
			formData.append('action', 'pnpc_psd_respond_to_ticket');
			formData.append('nonce', pnpcPsdPublic.nonce);
			formData.append('ticket_id', ticketId);
			formData.append('response', response);

			// Attach response files
			for (var i = 0; i < responseFiles.length; i++) {
				formData.append('attachments[]', responseFiles[i]);
			}

			$.ajax({
				url: pnpcPsdPublic.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(result) {
					if (result.success) {
						showResponseMessage('success', result.data.message || 'Reply added');
						$('#response-text').val('');
						responseFiles = [];
						renderResponseAttachmentsList();
						setTimeout(function() {
							location.reload();
						}, 900);
					} else {
						showResponseMessage('error', result.data && result.data.message ? result.data.message : 'Failed to add response.');
					}
				},
				error: function(xhr, status, err) {
					console.error('pnpc-psd-public.js AJAX error', status, err, xhr && xhr.responseText);
					showResponseMessage('error', 'An error occurred. Please try again.');
				}
			});
		});

		function showResponseMessage(type, message) {
			var $messageDiv = $('#response-message');
			$messageDiv.removeClass('success error').addClass(type).text(message).show();
			setTimeout(function() { $messageDiv.fadeOut(); }, 5000);
		}

		// (other handlers omitted for brevity)
	});
})( jQuery );