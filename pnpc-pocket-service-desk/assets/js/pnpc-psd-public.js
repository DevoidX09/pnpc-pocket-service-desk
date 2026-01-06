/**
 * Public JavaScript for PNPC Pocket Service Desk
 * - Handles ticket creation (with nonce and attachments)
 * - Handles ticket responses (with attachments)
 */
(function($) {
	'use strict';

	$(document).ready(function() {

		/* -------------------------------------------------
		 * CREATE TICKET
		 * ------------------------------------------------- */
		$('#pnpc-psd-create-ticket-form').on('submit', function(e) {
			e.preventDefault();
			var $form = $(this);
			var formData = new FormData(this);

			// Ensure required fields
			var subj = $form.find('#ticket-subject').val();
			var desc = $form.find('#ticket-description').val();
			if (!subj || !desc) {
				var $msg = $('#ticket-create-message');
				$msg.removeClass('success').addClass('error').text('Please fill in all required fields.').show();
				return;
			}

			// Action and nonce
			formData.append('action', 'pnpc_psd_create_ticket');
			var nonceInput = $form.find('input[name="nonce"]').val();
			var nonce = nonceInput || (typeof pnpcPsdPublic !== 'undefined' ? pnpcPsdPublic.nonce : '');
			formData.set('nonce', nonce);

			$.ajax({
				url: pnpcPsdPublic.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(result) {
					var $msg = $('#ticket-create-message');
					if (result && result.success) {
						$msg.removeClass('error').addClass('success')
							.text(result.data && result.data.message ? result.data.message : 'Ticket created successfully.')
							.show();
						if (result.data && result.data.ticket_detail_url) {
							setTimeout(function() { window.location.href = result.data.ticket_detail_url; }, 800);
						} else {
							setTimeout(function() { location.reload(); }, 800);
						}
					} else {
						var err = (result && result.data && result.data.message) ? result.data.message : 'Failed to create ticket.';
						$msg.removeClass('success').addClass('error').text(err).show();
					}
				},
				error: function(xhr, status, err) {
					console.error('pnpc-psd-public.js create ticket AJAX error', status, err, xhr && xhr.responseText);
					var $msg = $('#ticket-create-message');
					$msg.removeClass('success').addClass('error').text('An error occurred. Please try again.').show();
				}
			});
		});

		/* -------------------------------------------------
		 * RESPONSE WITH ATTACHMENTS (public side)
		 * ------------------------------------------------- */
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
				var $item = $('<div/>').addClass('pnpc-psd-attachment-item').css({ marginBottom: '6px' });
				$item.append($('<span/>').text(file.name + ' (' + Math.round(file.size / 1024) + ' KB)'));
				var $remove = $('<button/>').attr('type', 'button').addClass('pnpc-psd-button').css({ marginLeft: '8px' }).text('Remove');
				$remove.on('click', function() {
					responseFiles.splice(idx, 1);
					$('#response-attachments').val('');
					renderResponseAttachmentsList();
				});
				$item.append($remove);
				$list.append($item);
			});
		}

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
						showResponseMessage('success', (result.data && result.data.message) ? result.data.message : 'Reply added');
						$('#response-text').val('');
						responseFiles = [];
						renderResponseAttachmentsList();
						setTimeout(function() { location.reload(); }, 900);
					} else {
						showResponseMessage('error', (result.data && result.data.message) ? result.data.message : 'Failed to add response.');
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
	});
})(jQuery);