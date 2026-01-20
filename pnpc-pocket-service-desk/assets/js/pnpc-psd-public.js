/**
 * Public JavaScript (patched to submit attachments with response)
 */
(function( $ ) {
	'use strict';

	$(document).ready(function() {
		var createFiles = [];
		var responseFiles = [];
		var MAX_ATTACHMENTS = 10;

		// ================================
		// Profile Settings: Upload Profile Image / Logo
		// ================================
		function showProfileImageMessage(type, message) {
			var $msg = $('#profile-image-message');
			if (!$msg.length) {
				return;
			}
			$msg.removeClass('success error').addClass(type).text(message).show();
			setTimeout(function() { $msg.fadeOut(); }, 6000);
		}

		$(document).on('change', '#profile-image-upload', function(e) {
			if (typeof pnpcPsdPublic === 'undefined') {
				return;
			}
			var file = e.target && e.target.files ? e.target.files[0] : null;
			if (!file) {
				return;
			}

			var formData = new FormData();
			formData.append('action', 'pnpc_psd_upload_profile_image');
			formData.append('nonce', pnpcPsdPublic.nonce);
			formData.append('profile_image', file);

			showProfileImageMessage('success', 'Uploading...');

			$.ajax({
				url: pnpcPsdPublic.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(result) {
					if (result && result.success && result.data && result.data.url) {
						$('#profile-image-preview').attr('src', result.data.url);
						showProfileImageMessage('success', result.data.message || 'Profile image updated.');
					} else {
						var msg = (result && result.data && result.data.message) ? result.data.message : 'Upload failed.';
						showProfileImageMessage('error', msg);
					}
				},
				error: function(xhr, status, err) {
					console.error('pnpc-psd-public.js profile upload error', status, err, xhr && xhr.responseText);
					showProfileImageMessage('error', 'Upload failed. Please try again.');
				}
			});
		});

		// ================================
		// My Tickets: Lightweight refresh/polling
		// ================================
		var myTicketsPolling = null;
		function setMyTicketsStatus(text) {
			var $s = $('#pnpc-psd-my-tickets-status');
			if ($s.length) {
				$s.text(text || '');
			}
		}
		function refreshMyTickets(options) {
			options = options || {};
			if (typeof pnpcPsdPublic === 'undefined') {
				return;
			}
			if (!$('#pnpc-psd-my-tickets-list').length) {
				return;
			}

			if (!options.silent) {
				setMyTicketsStatus('Refreshing...');
			}

			$.ajax({
				url: pnpcPsdPublic.ajax_url,
				type: 'POST',
				data: {
					action: 'pnpc_psd_refresh_my_tickets',
					nonce: pnpcPsdPublic.nonce,
					tab: ($('#pnpc-psd-my-tickets').length ? ($('#pnpc-psd-my-tickets').attr('data-tab') || 'open') : 'open'),
					sort: ($('#pnpc-psd-my-tickets').length ? ($('#pnpc-psd-my-tickets').attr('data-sort') || 'latest') : 'latest'),
					page: ($('#pnpc-psd-my-tickets').length ? parseInt($('#pnpc-psd-my-tickets').attr('data-page') || '1', 10) : 1)
				},
				success: function(result) {
					if (result && result.success && result.data && typeof result.data.html === 'string') {
						// Replace entire list block; partial includes wrapper.
						var $existing = $('#pnpc-psd-my-tickets-list');
						$existing.replaceWith(result.data.html);
						setMyTicketsStatus('Updated ' + (new Date()).toLocaleTimeString());
					} else {
						setMyTicketsStatus('');
					}
				},
				error: function(xhr, status, err) {
					console.error('pnpc-psd-public.js my tickets refresh error', status, err, xhr && xhr.responseText);
					setMyTicketsStatus('');
				}
			});
		}

		$(document).on('click', '#pnpc-psd-my-tickets-refresh', function() {
			refreshMyTickets({ silent: false });
		});

		// Start polling when the My Tickets view is present.
		if ($('#pnpc-psd-my-tickets-refresh').length && $('#pnpc-psd-my-tickets-list').length) {
			// Poll every 15 seconds (lightweight). Adjust as needed.
			myTicketsPolling = setInterval(function() {
				refreshMyTickets({ silent: true });
			}, 15000);
		}

		function renderAttachmentList(files, $list, inputSelector) {
			if (!$list.length) {
				return;
			}
			$list.empty();
			if (!files.length) {
				return;
			}
			files.forEach(function(file) {
				var $item = $('<div/>').addClass('pnpc-psd-attachment-item').css({marginBottom:'6px'});
				$item.append($('<span/>').text(file.name + ' (' + Math.round(file.size / 1024) + ' KB)'));
				var $remove = $('<button/>').attr('type', 'button').addClass('pnpc-psd-button').css({marginLeft: '8px'}).text('Remove');
				$remove.on('click', function() {
					var toRemove = $(this).closest('.pnpc-psd-attachment-item').index();
					if (toRemove > -1) {
						files.splice(toRemove, 1);
						if (inputSelector) {
							$(inputSelector).val('');
						}
						renderAttachmentList(files, $list, inputSelector);
					}
				});
				$item.append($remove);
				$list.append($item);
			});
		}

		// Use delegated bindings so the create form can be re-rendered without losing handlers.
		$(document).on('change', '#ticket-attachments', function(e) {
			var $form = $(this).closest('form');
			var createFiles = Array.prototype.slice.call(e.target.files || []);
			if (createFiles.length > MAX_ATTACHMENTS) {
				createFiles = createFiles.slice(0, MAX_ATTACHMENTS);
			}
			// Persist per-form so the submit handler can read it and send files.
			$form.data('pnpcCreateFiles', createFiles);
			renderAttachmentList(createFiles, $form.find('.pnpc-psd-attachments-list'), '#ticket-attachments');
		});

		$(document).on('submit', '#pnpc-psd-create-ticket-form', function(e) {
			e.preventDefault();
			if (typeof pnpcPsdPublic === 'undefined') {
				return;
			}

			var $form = $(this);
			if ($form.data('pnpcSubmitting')) {
				return;
			}
			$form.data('pnpcSubmitting', 1);

			// Use per-form createFiles to avoid conflicts when multiple shortcodes render on one page.
			var createFiles = $form.data('pnpcCreateFiles') || [];



			var subject = $form.find('[name="subject"]').val();
			var description = $form.find('[name="description"]').val();
			var priority = $form.find('[name="priority"]').val() || 'normal';
			var $submitBtn = $(this).find('button[type="submit"]');

			if (!subject.trim() || !description.trim()) {
				showCreateMessage('error', 'Please fill in all required fields.', $form);
				$form.data('pnpcSubmitting', 0);
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
						showCreateMessage('success', result.data.message || 'Ticket created successfully.', $form);
						createFiles = [];
						$form.data('pnpcCreateFiles', createFiles);
						renderAttachmentList(createFiles, $form.find('.pnpc-psd-attachments-list'), '#ticket-attachments');
						$form[0].reset();
						// Allow creating another ticket without leaving the page.
						$submitBtn.prop('disabled', false);

					} else if (result && result.data && result.data.message) {
						showCreateMessage('error', result.data.message, $form);
						$submitBtn.prop('disabled', false);
					} else {
						var msg = (result && result.data && result.data.message) ? result.data.message : 'Failed to create ticket.';
						showCreateMessage('error', msg, $form);
						$submitBtn.prop('disabled', false);
					}
				},
				error: function(xhr, status, err) {
					console.error('pnpc-psd-public.js create ticket error', status, err, xhr && xhr.responseText);
					showCreateMessage('error', 'Request failed. Please reload and try again.', $form);
					$submitBtn.prop('disabled', false);
				},
				complete: function() {
					$submitBtn.prop('disabled', false);
					$form.data('pnpcSubmitting', 0);
				}
			});
		});

		function showCreateMessage(type, message, $scope) {
			var $root = ($scope && $scope.length) ? $scope : $(document);
			var $messageDiv = $root.find('.pnpc-psd-create-message').first();
			if (!$messageDiv.length) {
				return;
			}
			$messageDiv.removeClass('success error').addClass(type).text(message).show();
			setTimeout(function() { $messageDiv.fadeOut(); }, 5000);
		}

		$('#response-attachments').on('change', function(e) {
			responseFiles = Array.prototype.slice.call(e.target.files || []);
			if (responseFiles.length > MAX_ATTACHMENTS) {
				responseFiles = responseFiles.slice(0, MAX_ATTACHMENTS);
			}
			renderAttachmentList(responseFiles, $('#pnpc-psd-response-attachments-list'), '#response-attachments');
		});

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
						renderAttachmentList(responseFiles, $('#pnpc-psd-response-attachments-list'), '#response-attachments');
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