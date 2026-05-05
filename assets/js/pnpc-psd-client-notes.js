(function ($) {
	'use strict';

	function buildUI() {
		var cfg = window.PNPC_PSD_CLIENT_NOTES || {};
		var i18n = cfg.i18n || {};

		var ticketId = parseInt(cfg.ticketId, 10) || 0;
		if (!ticketId) {
			return;
		}

		var $toolbar = $('.pnpc-psd-reply-toolbar').first();
		// Toolbar is optional (some screens may not load Saved Replies).
		var hasToolbar = !!$toolbar.length;

		var $anchor = $('#pnpc-psd-client-notes-anchor');
		var $controlsAnchor = $('#pnpc-psd-client-notes-controls-anchor');
		var $fallbackMeta = $('.pnpc-psd-ticket-meta').first();

				// Avoid initializing twice.
		if ($('#pnpc-psd-client-notes-panel').length) {
			return;
		}

		// Notes toggle lives in the panel header (no separate button in the summary/toolbar).
		var badges = [];
var $panel = $('<div/>', {
			class: 'pnpc-psd-client-notes-panel',
			id: 'pnpc-psd-client-notes-panel'
		});

		var $header = $('<div/>', { class: 'pnpc-psd-client-notes-header' });
		$header.append($('<strong/>', { text: (i18n.heading || 'Client Notes') }));
		var $status = $('<span/>', { class: 'pnpc-psd-client-notes-status', text: '' });
		$header.append($status);

		// Header actions: indicator + expand/collapse icon.
		var $headerActions = $('<div/>', { class: 'pnpc-psd-client-notes-header-actions' });
		var $badge = $('<span/>', {
			class: 'pnpc-psd-client-notes-indicator',
			text: '!'
		});
		$badge.hide();
		var $toggle = $('<button/>', {
			type: 'button',
			class: 'pnpc-psd-client-notes-header-toggle',
			'aria-expanded': 'false'
		});
		var $icon = $('<span/>', { class: 'dashicons dashicons-plus', 'aria-hidden': 'true' });
		$toggle.append($icon);
		$headerActions.append($badge).append($toggle);
		$header.append($headerActions);
		badges.push($badge);

		$panel.append($header);

		var $body = $('<div/>', { class: 'pnpc-psd-client-notes-body' });

		var $list = $('<div/>', {
			class: 'pnpc-psd-client-notes-list',
			role: 'log',
			'aria-live': 'polite'
		});
		$list.append($('<div/>', {
			class: 'pnpc-psd-client-notes-empty',
			text: (i18n.loading || 'Loading...')
		}));
		$body.append($list);

		var $form = $('<div/>', { class: 'pnpc-psd-client-notes-form' });
		var $input = $('<textarea/>', {
			class: 'pnpc-psd-client-notes-input',
			rows: 3,
			placeholder: (i18n.placeholder || 'Write a private note for staff...')
		});
		var $add = $('<button/>', {
			type: 'button',
			class: 'button button-primary pnpc-psd-client-notes-add',
			text: (i18n.add || 'Add Note')
		});

		$form.append($input).append($add);
		$body.append($form);
		$panel.append($body);

		// Place the panel in the ticket summary area if possible (preferred).
		var pinned = false;
		if ($anchor.length) {
			$anchor.append($panel);
			pinned = true;
		} else if ($fallbackMeta.length) {
			// Fallback: inject just below the meta block so notes are visible without scrolling.
			$panel.insertAfter($fallbackMeta);
			pinned = true;
		} else {
			$panel.insertAfter($toolbar);
		}
		// Default: collapsed (header visible, body hidden), but persist per-browser.
		var collapseKey = 'pnpc_psd_client_notes_collapsed';
		var stored = null;
		try {
			stored = window.localStorage ? window.localStorage.getItem(collapseKey) : null;
		} catch (e) {
			stored = null;
		}
		var shouldOpen = (stored === '0');
		if (shouldOpen) {
			$body.show();
			$panel.addClass('is-open');
			$toggle.attr('aria-expanded', 'true');
			$icon.removeClass('dashicons-plus').addClass('dashicons-minus');
		} else {
			$body.hide();
			$panel.removeClass('is-open');
			$toggle.attr('aria-expanded', 'false');
			$icon.removeClass('dashicons-minus').addClass('dashicons-plus');
			// If unset, store the default collapsed state for consistency.
			try {
				if (window.localStorage && stored === null) {
					window.localStorage.setItem(collapseKey, '1');
				}
			} catch (e2) {}
		}

		var loaded = false;
		function ensureLoaded() {
			if (loaded) {
				return;
			}
			loaded = true;
			loadList(ticketId, $list, $status, i18n, badges);
		}

		// Load notes on first open.

		$toggle.on('click', function () {
			ensureLoaded();
			var isOpen = $body.is(':visible');
			if (isOpen) {
				$body.hide();
				$panel.removeClass('is-open');
				$toggle.attr('aria-expanded', 'false');
			$icon.removeClass('dashicons-minus').addClass('dashicons-plus');
				try { if (window.localStorage) { window.localStorage.setItem(collapseKey, '1'); } } catch (e1) {}
				return;
			}
			$body.show();
			$panel.addClass('is-open');
			$toggle.attr('aria-expanded', 'true');
			$icon.removeClass('dashicons-plus').addClass('dashicons-minus');
			try { if (window.localStorage) { window.localStorage.setItem(collapseKey, '0'); } } catch (e2) {}
		});

		$add.on('click', function () {
			if (!cfg.canWrite) {
				$status.text(i18n.forbidden || 'You do not have permission to add notes.');
				return;
			}
			var content = ($input.val() || '').replace(/\r\n/g, '\n').trim();
			if (!content) {
				return;
			}
				addNote(ticketId, content, $list, $status, $input, $add, i18n, badges);
		});

		// Delegated delete.
		$panel.on('click', '.pnpc-psd-client-notes-remove', function (e) {
			e.preventDefault();
			if (!cfg.canWrite) {
				return;
			}
			var $row = $(this).closest('.pnpc-psd-client-notes-item');
			var noteId = String($row.data('note-id') || '');
			if (!noteId) {
				return;
			}
			deleteNote(ticketId, noteId, $row, $list, $status, i18n, badges);
		});
	}

	function loadList(ticketId, $list, $status, i18n, badges) {
		var cfg = window.PNPC_PSD_CLIENT_NOTES || {};
		$status.text(i18n.loading || 'Loading...');
		$.ajax({
			url: cfg.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pnpc_psd_client_notes_list',
				nonce: cfg.nonce,
				ticketId: ticketId
			}
		}).done(function (resp) {
			var count = (resp && resp.success && resp.data && $.isArray(resp.data.items)) ? resp.data.items.length : 0;
			updateIndicator(badges, count);
			renderList(resp, $list, i18n);
			$status.text('');
		}).fail(function () {
			updateIndicator(badges, 0);
			$list.empty().append($('<div/>', {
				class: 'pnpc-psd-client-notes-empty',
				text: (i18n.error || 'Unable to load client notes.')
			}));
			$status.text('');
		});
	}

	function updateIndicator(badges, count) {
		if (!badges || !badges.length) {
			return;
		}
		badges.forEach(function ($b) {
			if (!$b || !$b.length) { return; }
			if (count && count > 0) { $b.show(); } else { $b.hide(); }
		});
	}
	function addNote(ticketId, content, $list, $status, $input, $add, i18n, badges) {
		var cfg = window.PNPC_PSD_CLIENT_NOTES || {};
		$add.prop('disabled', true);
		$status.text(i18n.loading || 'Loading...');
		$.ajax({
			url: cfg.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pnpc_psd_client_notes_add',
				nonce: cfg.nonce,
				ticketId: ticketId,
				content: content
			}
		}).done(function (resp) {
			if (resp && resp.success && resp.data && resp.data.item) {
				appendItem(resp.data.item, $list, i18n);
				updateIndicator(badges, $list.find('.pnpc-psd-client-notes-item').length);
				$input.val('');
				$status.text('');
			} else if (resp && resp.data && resp.data.message === 'forbidden_write') {
				$status.text(i18n.forbidden || 'You do not have permission to add notes.');
			} else {
				$status.text(i18n.error || 'Unable to load client notes.');
			}
		}).fail(function (xhr) {
			if (xhr && xhr.status === 403) {
				$status.text(i18n.forbidden || 'You do not have permission to add notes.');
			} else {
				$status.text(i18n.error || 'Unable to load client notes.');
			}
		}).always(function () {
			$add.prop('disabled', false);
		});
	}

	function deleteNote(ticketId, noteId, $row, $list, $status, i18n, badges) {
		var cfg = window.PNPC_PSD_CLIENT_NOTES || {};
		$status.text(i18n.loading || 'Loading...');
		$.ajax({
			url: cfg.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pnpc_psd_client_notes_delete',
				nonce: cfg.nonce,
				ticketId: ticketId,
				noteId: noteId
			}
		}).done(function (resp) {
			if (resp && resp.success && resp.data && resp.data.deleted) {
				$row.remove();
				updateIndicator(badges, $list.find('.pnpc-psd-client-notes-item').length);
				if (!$list.find('.pnpc-psd-client-notes-item').length) {
					$list.empty().append($('<div/>', {
						class: 'pnpc-psd-client-notes-empty',
						text: (i18n.empty || 'No client notes yet.')
					}));
					updateIndicator(badges, 0);
				}
			}
			$status.text('');
		}).fail(function () {
			$status.text(i18n.error || 'Unable to load client notes.');
		}).always(function () {
			setTimeout(function(){ $status.text(''); }, 800);
		});
	}

	function renderList(resp, $list, i18n) {
		var cfg = window.PNPC_PSD_CLIENT_NOTES || {};
		$list.empty();
		if (!(resp && resp.success && resp.data && $.isArray(resp.data.items))) {
			$list.append($('<div/>', {
				class: 'pnpc-psd-client-notes-empty',
				text: (i18n.error || 'Unable to load client notes.')
			}));
			return;
		}
		var items = resp.data.items;
		if (!items.length) {
			$list.append($('<div/>', {
				class: 'pnpc-psd-client-notes-empty',
				text: (i18n.empty || 'No client notes yet.')
			}));
			return;
		}
		items.forEach(function (item) {
			$list.append(renderItem(item, i18n, cfg.canWrite));
		});
	}

	function appendItem(item, $list, i18n) {
		var cfg = window.PNPC_PSD_CLIENT_NOTES || {};
		var $empty = $list.find('.pnpc-psd-client-notes-empty');
		if ($empty.length) {
			$list.empty();
		}
		$list.prepend(renderItem(item, i18n, cfg.canWrite));
	}

	function renderItem(item, i18n, canWrite) {
		var author = String(item.author || '');
		var created = String(item.createdDisplay || '');
		var content = String(item.content || '');
		var noteId = String(item.id || '');

		var $row = $('<div/>', { class: 'pnpc-psd-client-notes-item' });
		if (noteId) {
			$row.attr('data-note-id', noteId);
			$row.data('note-id', noteId);
		}

		var $meta = $('<div/>', { class: 'pnpc-psd-client-notes-meta' });
		$meta.append($('<span/>', { class: 'pnpc-psd-client-notes-author', text: author }));
		if (created) {
			$meta.append($('<span/>', { class: 'pnpc-psd-client-notes-created', text: created }));
		}
		if (canWrite && noteId) {
			$meta.append($('<button/>', {
				type: 'button',
				class: 'button-link-delete pnpc-psd-client-notes-remove',
				text: (i18n.remove || 'Remove')
			}));
		}
		$row.append($meta);
		$row.append($('<div/>', { class: 'pnpc-psd-client-notes-content', text: content }));
		return $row;
	}

	$(document).ready(buildUI);
})(jQuery);
