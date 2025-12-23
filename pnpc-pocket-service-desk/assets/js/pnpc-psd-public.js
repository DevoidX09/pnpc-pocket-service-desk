/**
 * Public JavaScript (patched to submit attachments with response)
 */
(function( $ ) {
	'use strict';

	$(document).ready(function() {
		var createFiles = [];

		$('#ticket-attachments').on('change', function(e) {
			createFiles = Array.prototype.slice.call(e.target.files || []);
			renderCreateAttachmentsList();
		});

		function renderCreateAttachmentsList() {
			var $list = $('#pnpc-psd-attachments-list');
			if (!$list.length) {
				return;
			}
			$list.empty();
			if (!createFiles.length) {
				return;
			}
			createFiles.forEach(function(file, idx) {
				var $item = $('<div/>').addClass('pnpc-psd-attachment-item').css({marginBottom:'6px'});
				$item.append($('<span/>').text(file.name + ' (' + Math.round(file.size/1024) + ' KB)'));
				var $remove = $('<button/>').attr('type','button').addClass('pnpc-psd-button').css({marginLeft:'8px'}).text('Remove');
				$remove.on('click', function() {
					var toRemove = $(this).closest('.pnpc-psd-attachment-item').index();
					createFiles = createFiles.filter(function(_, fileIdx) {
						return fileIdx !== toRemove;
					});
					$('#ticket-attachments').val('');
					renderCreateAttachmentsList();
				});
				$item.append($remove);
				$list.append($item);
			});
		}

		$('#pnpc-psd-create-ticket-form').on('submit', function(e) {
			e.preventDefault();
			if (typeof pnpcPsdPublic === 'undefined') {
				return;
			}

			var subject = $('#ticket-subject').val();
			var description = $('#ticket-description').val();
			var priority = $('#ticket-priority').val() || 'normal';
			var $submitBtn = $(this).find('button[type="submit"]');

			if (!subject.trim() || !description.trim()) {
				showCreateMessage('error', 'Please fill in all required fields.');
				return;
			}

			var formData = new FormData();
			formData.append('action', 'pnpc_psd_create_ticket');
			formData.append('nonce', pnpcPsdPublic.nonce);
			formData.append('subject', subject);
			formData.append('description', description);
			formData.append('priority', priority);

			for (var i = 0; i < createFiles.length; i++) {
				formData.append('attachments[]', createFiles[i]);
			}

			$submitBtn.prop('disabled', true);

			$.ajax({
				url: pnpcPsdPublic.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(result) {
					if (result && result.success) {
						showCreateMessage('success', result.data.message || 'Ticket created successfully.');
						createFiles = [];
						renderCreateAttachmentsList();
						$('#pnpc-psd-create-ticket-form')[0].reset();

						if (result.data && result.data.ticket_detail_url) {
							try {
								var detailUrl = new URL(result.data.ticket_detail_url, window.location.origin);
								if (detailUrl.origin === window.location.origin && detailUrl.pathname.indexOf('..') === -1 && detailUrl.pathname.charAt(0) === '/') {
									setTimeout(function() {
										window.location.href = detailUrl.toString();
									}, 900);
								}
							} catch (err) {
								console.warn('pnpc-psd-public.js invalid redirect url', err);
							}
						}
					} else if (result && result.data && result.data.message) {
						showCreateMessage('error', result.data.message);
					} else {
						showCreateMessage('error', 'Failed to create ticket.');
					}
				},
				error: function(xhr, status, err) {
					console.error('pnpc-psd-public.js create ticket error', status, err, xhr && xhr.responseText);
					showCreateMessage('error', 'An error occurred. Please try again.');
				},
				complete: function() {
					$submitBtn.prop('disabled', false);
				}
			});
		});

		function showCreateMessage(type, message) {
			var $messageDiv = $('#ticket-create-message');
			if (!$messageDiv.length) {
				return;
			}
			$messageDiv.removeClass('success error').addClass(type).text(message).show();
			setTimeout(function() { $messageDiv.fadeOut(); }, 5000);
		}

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
					var toRemove = $(this).closest('.pnpc-psd-attachment-item').index();
					responseFiles = responseFiles.filter(function(_, fileIdx) {
						return fileIdx !== toRemove;
					});
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
