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
