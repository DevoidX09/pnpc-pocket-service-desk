/**
 * Settings page helper functions for PNPC Pocket Service Desk
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/assets/js
 */

(function() {
	'use strict';

	/**
	 * Initialize settings page functionality
	 */
	function initSettingsPage() {
		var enableBtn = document.getElementById('pnpc-psd-enable-all-agents');
		var disableBtn = document.getElementById('pnpc-psd-disable-all-agents');

		/**
		 * Set all agent checkboxes to a specific state
		 * @param {boolean} state - The checked state to set
		 */
		function setAll(state) {
			var boxes = document.querySelectorAll('.pnpc-psd-agent-enabled');
			for (var i = 0; i < boxes.length; i++) {
				boxes[i].checked = !!state;
			}
		}

		if (enableBtn) {
			enableBtn.addEventListener('click', function(e) {
				e.preventDefault();
				setAll(true);
			});
		}

		if (disableBtn) {
			disableBtn.addEventListener('click', function(e) {
				e.preventDefault();
				setAll(false);
			});
		}
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initSettingsPage);
	} else {
		initSettingsPage();
	}
})();
