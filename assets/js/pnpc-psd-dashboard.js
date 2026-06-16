/**
 * Dashboard ring animations for PNPC Pocket Service Desk
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/assets/js
 */

(function() {
	'use strict';

	/**
	 * Animate a progress ring element
	 * @param {HTMLElement} el - The ring element to animate
	 */
	function animateRing(el) {
		var target = parseInt(el.getAttribute('data-target') || '0', 10);
		target = Math.max(0, Math.min(100, target));
		var numEl = el.querySelector('.psd-ring__num');
		var start = 0;
		var dur = 650;
		var t0 = null;

		function step(ts) {
			if (!t0) {
				t0 = ts;
			}
			var p = Math.min(1, (ts - t0) / dur);
			var val = Math.round(start + (target - start) * p);
			el.style.setProperty('--p', val);
			if (numEl) {
				numEl.textContent = val;
			}
			if (p < 1) {
				requestAnimationFrame(step);
			}
		}
		requestAnimationFrame(step);
	}

	/**
	 * Initialize ring animations when DOM is ready
	 */
	function initRingAnimations() {
		var rings = document.querySelectorAll('.pnpc-psd-dashboard .psd-ring');
		rings.forEach(function(r) {
			animateRing(r);
		});
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initRingAnimations);
	} else {
		initRingAnimations();
	}
})();

/**
 * Dashboard Alert Inbox dismissal.
 */
(function() {
	'use strict';

	function cfg() {
		return window.pnpcPsdDashboard || {};
	}

	function clearTopbarInboxNotification() {
		var inbox = document.querySelector('.pnpc-psd-pro-topbar__signal--inbox');
		if (inbox) {
			inbox.querySelectorAll('.pnpc-psd-pro-topbar__dot, .pnpc-psd-pro-topbar__badge').forEach(function(node) {
				if (node && node.parentNode) {
					node.parentNode.removeChild(node);
				}
			});
		}
	}

	function removeAlert(key) {
		var alert = document.querySelector('[data-pnpc-psd-alert-key="' + window.CSS.escape(key) + '"]');
		if (alert && alert.parentNode) {
			alert.parentNode.removeChild(alert);
		}

		var remaining = document.querySelectorAll('.pnpc-psd-dashboard .psd-alert[data-pnpc-psd-alert-key]').length;
		var alertCard = document.querySelector('.pnpc-psd-dashboard .psd-card--alerts');
		if (remaining < 1) {
			clearTopbarInboxNotification();
			var headingBadge = document.querySelector('.pnpc-psd-dashboard .psd-card--alerts h2 small');
			if (headingBadge && headingBadge.parentNode) {
				headingBadge.parentNode.removeChild(headingBadge);
			}
		}

		if (alertCard && remaining < 1 && !alertCard.querySelector('.psd-alert-empty')) {
			var empty = document.createElement('p');
			empty.className = 'psd-muted psd-alert-empty';
			empty.textContent = 'No alerts right now.';
			var support = alertCard.querySelector('.psd-support-card');
			alertCard.insertBefore(empty, support || null);
		}
	}

	function postDismiss(key, removeNow) {
		var settings = cfg();
		if (!key || !settings.ajax_url || !settings.nonce) {
			if (removeNow) {
				removeAlert(key);
			}
			return;
		}

		var body = new window.URLSearchParams();
		body.append('action', 'pnpc_psd_dismiss_dashboard_alert');
		body.append('nonce', settings.nonce);
		body.append('key', key);

		if (navigator.sendBeacon && !removeNow) {
			navigator.sendBeacon(settings.ajax_url, body);
			return;
		}

		window.fetch(settings.ajax_url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: body.toString()
		}).then(function() {
			if (removeNow) {
				removeAlert(key);
			}
		}).catch(function() {
			if (removeNow) {
				removeAlert(key);
			}
		});
	}

	function initAlertInbox() {
		document.addEventListener('click', function(event) {
			var deleteButton = event.target.closest('[data-pnpc-psd-dismiss-alert]');
			if (deleteButton) {
				event.preventDefault();
				postDismiss(deleteButton.getAttribute('data-pnpc-psd-dismiss-alert'), true);
				return;
			}

			var openLink = event.target.closest('[data-pnpc-psd-open-alert]');
			if (openLink) {
				postDismiss(openLink.getAttribute('data-pnpc-psd-open-alert'), true);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAlertInbox);
	} else {
		initAlertInbox();
	}
})();
