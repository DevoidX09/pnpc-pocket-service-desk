(function( $ ) {
	'use strict';

	function updateBadges( data ) {
		if ( ! data ) {
			return;
		}

		var openCount      = parseInt( data.open_count || 0, 10 ) || 0;
		var unreadReplies  = parseInt( data.unread_replies || 0, 10 ) || 0;
		var attentionTotal = parseInt( data.attention_total || 0, 10 ) || 0;

		// Top-level menu: single combined attention badge.
		var $topLink = $( '#toplevel_page_pnpc-service-desk > a.menu-top' );
		if ( $topLink.length ) {
			$topLink.find( 'span.update-plugins' ).remove();
			if ( attentionTotal > 0 ) {
				var $menuName = $topLink.find( '.wp-menu-name' );
				var badgeHtml = '<span class="update-plugins count-' + attentionTotal + '"><span class="plugin-count">' + attentionTotal + '</span></span>';
				// Keep the badge on the same line by placing it inside .wp-menu-name.
				if ( $menuName.length ) {
					$menuName.append( ' ' + badgeHtml );
				} else {
					$topLink.append( badgeHtml );
				}
			}
		}

		// Submenu: All Tickets shows two badges (red=open/in-progress, green=unread replies).
		var $submenuLink = $( '#toplevel_page_pnpc-service-desk .wp-submenu a[href="admin.php?page=pnpc-service-desk"]' );
		if ( $submenuLink.length ) {
			$submenuLink.find( 'span.update-plugins' ).remove();

			if ( openCount > 0 ) {
				$submenuLink.append( '<span class="update-plugins pnpc-psd-open-badge count-' + openCount + '"><span class="plugin-count">' + openCount + '</span></span>' );
			}
			if ( unreadReplies > 0 ) {
				$submenuLink.append( ' <span class="update-plugins pnpc-psd-replies-badge count-' + unreadReplies + '"><span class="plugin-count">' + unreadReplies + '</span></span>' );
			}
		}
	}

	function fetchBadges() {
		if ( 'undefined' === typeof pnpcPsdMenuBadges || ! pnpcPsdMenuBadges.ajax_url ) {
			return;
		}

		$.post(
			pnpcPsdMenuBadges.ajax_url,
			{
				action: 'pnpc_psd_get_menu_badges',
				nonce: pnpcPsdMenuBadges.nonce
			}
		).done( function( resp ) {
			if ( resp && resp.success && resp.data ) {
				updateBadges( resp.data );
			}
		} );
	}

	$( function() {
		var pollMs = 45000;
		if ( 'undefined' !== typeof pnpcPsdMenuBadges && pnpcPsdMenuBadges.poll_ms ) {
			pollMs = parseInt( pnpcPsdMenuBadges.poll_ms, 10 ) || pollMs;
		}

		fetchBadges();
		window.setInterval( fetchBadges, pollMs );
	} );

})( jQuery );
