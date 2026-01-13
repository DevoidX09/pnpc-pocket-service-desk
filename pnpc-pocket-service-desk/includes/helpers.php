<?php

/**
 * PNPC PSD helpers (extended)
 *
 * Utility functions used by templates and plugin logic.
 *
 * Place this file in includes/helpers.php and ensure it is required by your
 * main plugin bootstrap or public class so templates and other classes can call it.
 *
 * Example require:
 * require_once PNPC_PSD_PLUGIN_DIR . 'includes/helpers.php';
 *
 * @package PNPC_Pocket_Service_Desk
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Return the configured Ticket Detail page ID.
 *
 * @return int Page ID or 0.
 */
if (! function_exists('pnpc_psd_get_ticket_detail_page_id')) {
    function pnpc_psd_get_ticket_detail_page_id()
    {
        static $cached = null;
        if (null !== $cached) {
            return (int) $cached;
        }

        $config_id = absint(get_option('pnpc_psd_ticket_detail_page_id', 0));
        if ($config_id > 0 && get_post($config_id)) {
            $cached = $config_id;
            return $cached;
        }

        $pages = get_posts(array(
            'post_type'      => 'page',
            'posts_per_page' => -1,
            'post_status'    => array('publish', 'private', 'draft'),
            'suppress_filters' => true,
            'fields'         => 'ids',
        ));

        if (is_array($pages) && ! empty($pages)) {
            foreach ($pages as $pid) {
                $post = get_post($pid);
                if (! $post) {
                    continue;
                }
                if (has_shortcode($post->post_content, 'pnpc_ticket_detail')) {
                    $cached = (int) $pid;
                    return $cached;
                }
            }
        }

        $candidate_slugs = array('ticket-details', 'ticket-detail', 'ticket-view', 'dashboard-single');
        foreach ($candidate_slugs as $slug) {
            $page = get_page_by_path($slug);
            if ($page && ! is_wp_error($page)) {
                $cached = (int) $page->ID;
                return $cached;
            }
        }

        $cached = 0;
        return $cached;
    }
}

/**
 * Return the configured "All Tickets / My Tickets" page ID.
 *
 * @return int Page ID or 0.
 */
if (! function_exists('pnpc_psd_get_my_tickets_page_id')) {
    function pnpc_psd_get_my_tickets_page_id()
    {
        static $cached = null;
        if (null !== $cached) {
            return (int) $cached;
        }

        $config_id = absint(get_option('pnpc_psd_all_tickets_page_id', 0));
        if ($config_id > 0 && get_post($config_id)) {
            $cached = $config_id;
            return $cached;
        }

        $pages = get_posts(array(
            'post_type'      => 'page',
            'posts_per_page' => -1,
            'post_status'    => array('publish', 'private', 'draft'),
            'suppress_filters' => true,
            'fields'         => 'ids',
        ));

        if (is_array($pages) && ! empty($pages)) {
            foreach ($pages as $pid) {
                $post = get_post($pid);
                if (! $post) {
                    continue;
                }
                if (has_shortcode($post->post_content, 'pnpc_my_tickets')) {
                    $cached = (int) $pid;
                    return $cached;
                }
            }
        }

        $page = get_page_by_path('my-tickets');
        $cached = ($page && ! is_wp_error($page)) ? (int) $page->ID : 0;
        return $cached;
    }
}

/**
 * Build ticket detail URL for a ticket ID using the configured page.
 *
 * @param int $ticket_id
 * @return string URL or empty.
 */
if (! function_exists('pnpc_psd_get_ticket_detail_url')) {
    function pnpc_psd_get_ticket_detail_url($ticket_id)
    {
        $ticket_id = (int) $ticket_id;
        if ($ticket_id <= 0) {
            return '';
        }
        $page_id = pnpc_psd_get_ticket_detail_page_id();
        if (empty($page_id) || ! get_post($page_id)) {
            return '';
        }
        return add_query_arg('ticket_id', $ticket_id, get_permalink($page_id));
    }
}

/**
 * Return the URL to the configured "All Tickets / My Tickets" page.
 */
if (! function_exists('pnpc_psd_get_my_tickets_url')) {
    function pnpc_psd_get_my_tickets_url()
    {
        $page_id = pnpc_psd_get_my_tickets_page_id();
        if (empty($page_id)) {
            return '';
        }
        return get_permalink($page_id);
    }
}

/**
 * Return the WP dashboard URL or fallback to my tickets.
 */
if (! function_exists('pnpc_psd_get_dashboard_url')) {
    function pnpc_psd_get_dashboard_url()
    {
        static $cached = null;
        if (null !== $cached) {
            return (string) $cached;
        }

        $config_id = absint(get_option('pnpc_psd_dashboard_page_id', 0));
        if ($config_id > 0 && get_post($config_id)) {
            $cached = get_permalink($config_id);
            return $cached;
        }

        // Prefer a stable, human-friendly dashboard slug.
        // Order: explicit setting -> /dashboard/ page -> legacy /dashboard-single/ page -> my tickets.
        $page = get_page_by_path('dashboard');
        if ($page && ! is_wp_error($page)) {
            $cached = get_permalink($page->ID);
            return $cached;
        }

        $legacy = get_page_by_path('dashboard-single');
        if ($legacy && ! is_wp_error($legacy)) {
            $cached = get_permalink($legacy->ID);
            return $cached;
        }

        $my = pnpc_psd_get_my_tickets_url();
        $cached = $my ? $my : '';
        return $cached;
    }
}

/**
 * Get WP timezone as DateTimeZone or null.
 */
if (! function_exists('pnpc_psd_get_wp_timezone')) {
    function pnpc_psd_get_wp_timezone()
    {
        if (function_exists('wp_timezone')) {
            try {
                $tz = wp_timezone();
                if ($tz instanceof DateTimeZone) {
                    return $tz;
                }
            } catch (Exception $e) {
                // ignore
            }
        }

        $tz_string = get_option('timezone_string', '');
        if (! empty($tz_string)) {
            try {
                return new DateTimeZone($tz_string);
            } catch (Exception $e) {
                // ignore
            }
        }

        $gmt_offset = get_option('gmt_offset', 0);
        if ($gmt_offset !== '') {
            $hours = floatval($gmt_offset);
            $offset_hours = -1 * $hours;
            $tz_name = sprintf('Etc/GMT%+d', intval($offset_hours));
            try {
                return new DateTimeZone($tz_name);
            } catch (Exception $e) {
                // ignore
            }
        }

        return null;
    }
}

/**
 * Convert DB MySQL datetime (assumed UTC) to WP-local unix timestamp.
 */
if (! function_exists('pnpc_psd_mysql_to_wp_local_ts')) {
    function pnpc_psd_mysql_to_wp_local_ts($mysql_datetime)
    {
        if ($mysql_datetime === null || $mysql_datetime === '' || $mysql_datetime === false) {
            return 0;
        }

        if (is_numeric($mysql_datetime) && (string)(int)$mysql_datetime === (string)$mysql_datetime) {
            return intval($mysql_datetime);
        }

        if ($mysql_datetime instanceof DateTimeInterface) {
            $dt = DateTimeImmutable::createFromInterface($mysql_datetime);
            $dt_tz = $dt->getTimezone();
            if ($dt_tz && $dt_tz->getName() !== 'UTC') {
                $wp_tz = pnpc_psd_get_wp_timezone();
                if ($wp_tz instanceof DateTimeZone) {
                    return (int) $dt->setTimezone($wp_tz)->format('U');
                }
                return (int) $dt->format('U');
            }
            try {
                $wp_tz = pnpc_psd_get_wp_timezone();
                if ($wp_tz instanceof DateTimeZone) {
                    return (int) $dt->setTimezone($wp_tz)->format('U');
                }
            } catch (Exception $e) {
                // ignore
            }
            return (int) $dt->format('U');
        }

        try {
            $dt_string = trim((string) $mysql_datetime);
            $dt = new DateTimeImmutable($dt_string, new DateTimeZone('UTC'));
            $wp_tz = pnpc_psd_get_wp_timezone();
            if ($wp_tz instanceof DateTimeZone) {
                return (int) $dt->setTimezone($wp_tz)->format('U');
            }
            $tz_string = get_option('timezone_string', '');
            if (! empty($tz_string)) {
                try {
                    $tz = new DateTimeZone($tz_string);
                    return (int) $dt->setTimezone($tz)->format('U');
                } catch (Exception $e) {
                    // ignore
                }
            }
            return (int) $dt->format('U');
        } catch (Exception $e) {
            // fall back
        }

        if (function_exists('mysql2date')) {
            $ts_gmt = intval(mysql2date('U', $mysql_datetime, true));
        } else {
            $ts_gmt = intval(strtotime($mysql_datetime));
        }
        $gmt_offset_hours = floatval(get_option('gmt_offset', 0));
        $offset_seconds = intval($gmt_offset_hours * 3600);
        return $ts_gmt + $offset_seconds;
    }
}

/**
 * Return UTC MySQL datetime string suitable for DB insertion.
 */
if (! function_exists('pnpc_psd_get_utc_mysql_datetime')) {
    function pnpc_psd_get_utc_mysql_datetime()
    {
        if (function_exists('current_time')) {
            return (string) current_time('mysql', true);
        }
        return gmdate('Y-m-d H:i:s');
    }
}

/**
 * Format DB datetime for display according to WP settings.
 */
if (! function_exists('pnpc_psd_format_db_datetime_for_display')) {
    function pnpc_psd_format_db_datetime_for_display($mysql_datetime, $format = null)
    {
        if ($mysql_datetime === null || $mysql_datetime === '') {
            return '';
        }
        if (null === $format) {
            $format = get_option('date_format') . ' ' . get_option('time_format');
        }
        $ts = pnpc_psd_mysql_to_wp_local_ts($mysql_datetime);
        if ($ts <= 0) {
            return '';
        }
        if (function_exists('wp_date')) {
            $tz = pnpc_psd_get_wp_timezone();
            return wp_date($format, $ts, $tz instanceof DateTimeZone ? $tz : null);
        }
        if (function_exists('date_i18n')) {
            return date_i18n($format, $ts);
        }
        return gmdate($format, $ts);
    }
}

/**
 * Conditional debug logger (controlled by WP_DEBUG and option pnpc_psd_debug_timestamps).
 */
if (! function_exists('pnpc_psd_debug_log')) {
    function pnpc_psd_debug_log($label, $data = '')
    {
        if (! defined('WP_DEBUG') || ! WP_DEBUG) {
            return;
        }
        $enabled = get_option('pnpc_psd_debug_timestamps', 0);
        if (! $enabled) {
            return;
        }
        $payload = is_scalar($data) ? $data : print_r($data, true);
        error_log('pnpc-psd-debug [' . $label . ']: ' . $payload);
    }
}

/**
 * Normalize $_FILES multi-file structure to array of file arrays.
 */
if (! function_exists('pnpc_psd_rearrange_files')) {
    function pnpc_psd_rearrange_files($file_post)
    {
        $files = array();
        if (! is_array($file_post) || empty($file_post['name'])) {
            return $files;
        }
        if (! is_array($file_post['name'])) {
            return array($file_post);
        }
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);
        for ($i = 0; $i < $file_count; $i++) {
            $file = array();
            foreach ($file_keys as $key) {
                $file[$key] = isset($file_post[$key][$i]) ? $file_post[$key][$i] : null;
            }
            $files[] = $file;
        }
        return $files;
    }
}

/**
 * Human-friendly file size formatting (wraps size_format() if available).
 */
if (! function_exists('pnpc_psd_format_filesize')) {
    function pnpc_psd_format_filesize($bytes)
    {
        $bytes = intval($bytes);
        if (function_exists('size_format')) {
            return size_format($bytes);
        }
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}

/**
 * Define preview file size limit for free version (5MB in bytes).
 */
if (! defined('PNPC_PSD_FREE_PREVIEW_LIMIT')) {
    define('PNPC_PSD_FREE_PREVIEW_LIMIT', 5 * 1024 * 1024);
}

/**
 * Get attachment type based on file extension.
 *
 * @param string $extension File extension (without dot).
 * @return string 'image', 'pdf', or 'other'.
 */
if (! function_exists('pnpc_psd_get_attachment_type')) {
    function pnpc_psd_get_attachment_type($extension)
    {
        $extension = strtolower(trim($extension));
        $image_types = array('jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp');
        
        if (in_array($extension, $image_types, true)) {
            return 'image';
        }
        
        if ('pdf' === $extension) {
            return 'pdf';
        }
        
        return 'other';
    }
}

/**
 * Get file icon emoji based on file extension.
 *
 * @param string $extension File extension (without dot).
 * @return string Emoji icon.
 */
if (! function_exists('pnpc_psd_get_file_icon')) {
    function pnpc_psd_get_file_icon($extension)
    {
        $extension = strtolower(trim($extension));
        $icons = array(
            'pdf'  => '📄',
            'doc'  => '📝',
            'docx' => '📝',
            'xls'  => '📊',
            'xlsx' => '📊',
            'zip'  => '🗜',
            'rar'  => '🗜',
            'mp4'  => '🎬',
            'avi'  => '🎬',
            'mp3'  => '🎵',
            'jpg'  => '🖼',
            'jpeg' => '🖼',
            'png'  => '🖼',
            'gif'  => '🖼',
        );
        
        return isset($icons[$extension]) ? $icons[$extension] : '📎';
    }
}

/**
 * Check if attachment can be previewed based on file size.
 *
 * @param int $file_size File size in bytes.
 * @return bool True if can preview, false otherwise.
 */
if (! function_exists('pnpc_psd_can_preview_attachment')) {
    function pnpc_psd_can_preview_attachment($file_size)
    {
        return intval($file_size) <= PNPC_PSD_FREE_PREVIEW_LIMIT;
    }
}

/**
 * Format delete reason for display.
 *
 * @since 1.2.0
 * @param string $reason Reason code.
 * @param string $other_details Optional. Additional details if reason is 'other'.
 * @return string Formatted reason label.
 */
if (! function_exists('pnpc_psd_format_delete_reason')) {
    function pnpc_psd_format_delete_reason($reason, $other_details = '')
    {
        if (empty($reason)) {
            return esc_html__('No reason provided', 'pnpc-pocket-service-desk');
        }

        $reasons = array(
            'spam'               => __('Spam', 'pnpc-pocket-service-desk'),
            'duplicate'          => __('Duplicate ticket', 'pnpc-pocket-service-desk'),
            'resolved_elsewhere' => __('Resolved elsewhere', 'pnpc-pocket-service-desk'),
            'customer_request'   => __('Customer request', 'pnpc-pocket-service-desk'),
            'test'               => __('Test ticket', 'pnpc-pocket-service-desk'),
            'other'              => __('Other', 'pnpc-pocket-service-desk'),
        );

        $label = isset($reasons[$reason]) ? $reasons[$reason] : esc_html($reason);

        if ('other' === $reason && ! empty($other_details)) {
            $label .= ': ' . esc_html($other_details);
        }

        return $label;
    }
}

/**
 * Ensure custom roles and capabilities exist even after plugin updates.
 * Note: add_role() does not update an existing role, so we must add missing caps explicitly.
 */
if ( ! function_exists( 'pnpc_psd_sync_roles_caps' ) ) {
	function pnpc_psd_sync_roles_caps() {
		// Define canonical caps.
		$agent_caps = array(
			'read'                        => true,
			'pnpc_psd_view_tickets'       => true,
			'pnpc_psd_respond_to_tickets' => true,
			'pnpc_psd_assign_tickets'     => true,
		);

		// Manager is a Pro-unlocked role in the long-term plan.
		// In Free, the role exists for forward compatibility, but elevated caps stay disabled.
		$manager_caps = $agent_caps;
		if ( function_exists( 'pnpc_psd_is_pro' ) && pnpc_psd_is_pro() ) {
			$manager_caps = $manager_caps + array(
				'pnpc_psd_delete_tickets'  => true,
				'pnpc_psd_manage_settings' => true,
			);
		}

		// Roles: Agent.
		$agent_role = get_role( 'pnpc_psd_agent' );
		if ( ! $agent_role ) {
			add_role( 'pnpc_psd_agent', __( 'Service Desk Agent', 'pnpc-pocket-service-desk' ), $agent_caps );
			$agent_role = get_role( 'pnpc_psd_agent' );
		}
		if ( $agent_role ) {
			foreach ( $agent_caps as $cap => $grant ) {
				if ( $grant ) { $agent_role->add_cap( $cap ); }
			}
		}

		// Roles: Manager.
		$manager_role = get_role( 'pnpc_psd_manager' );
		if ( ! $manager_role ) {
			add_role( 'pnpc_psd_manager', __( 'Service Desk Manager', 'pnpc-pocket-service-desk' ), $manager_caps );
			$manager_role = get_role( 'pnpc_psd_manager' );
		}
		if ( $manager_role ) {
			// Always ensure baseline staff caps.
			foreach ( $agent_caps as $cap => $grant ) {
				if ( $grant ) { $manager_role->add_cap( $cap ); }
			}
			// Toggle elevated caps based on plan.
			$elevated = array( 'pnpc_psd_delete_tickets', 'pnpc_psd_manage_settings' );
			$enable_elevated = ( function_exists( 'pnpc_psd_is_pro' ) && pnpc_psd_is_pro() );
			foreach ( $elevated as $cap ) {
				if ( $enable_elevated ) {
					$manager_role->add_cap( $cap );
				} else {
					$manager_role->remove_cap( $cap );
				}
			}
		}

		// Ensure Administrators always have staff caps (menu visibility relies on these).
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_caps = array( 'pnpc_psd_view_tickets', 'pnpc_psd_respond_to_tickets', 'pnpc_psd_assign_tickets', 'pnpc_psd_delete_tickets', 'pnpc_psd_manage_settings' );
			foreach ( $admin_caps as $cap ) {
				$admin_role->add_cap( $cap );
			}
		}

		// Customer / Subscriber public caps (safe idempotent).
		$public_roles = array( 'customer', 'subscriber' );
		foreach ( $public_roles as $role_key ) {
			$r = get_role( $role_key );
			if ( $r ) {
				$r->add_cap( 'pnpc_psd_create_tickets' );
				$r->add_cap( 'pnpc_psd_view_own_tickets' );
			}
		}
	}
}

add_action( 'init', 'pnpc_psd_sync_roles_caps', 1 );

/**
 * Sanitize Agents option array.
 *
 * Stored format:
 *   [ user_id => [ 'enabled' => 1|0, 'notify_email' => '...' ] ]
 *
 * @param mixed $value Raw value.
 * @return array Sanitized option value.
 */
if ( ! function_exists( 'pnpc_psd_sanitize_agents_option' ) ) {
	function pnpc_psd_sanitize_agents_option( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$out = array();
		foreach ( $value as $user_id => $row ) {
			$uid = absint( $user_id );
			if ( ! $uid ) {
				continue;
			}
			$row = is_array( $row ) ? $row : array();
			$enabled = isset( $row['enabled'] ) ? absint( $row['enabled'] ) : 0;
			$email   = isset( $row['notify_email'] ) ? sanitize_email( (string) $row['notify_email'] ) : '';
			$out[ $uid ] = array(
				'enabled'     => $enabled ? 1 : 0,
				'notify_email'=> $email,
			);
		}

		// Enforce plan limits (Free vs Pro). Free is intentionally small.
		$limit = function_exists( 'pnpc_psd_get_max_agents_limit' ) ? (int) pnpc_psd_get_max_agents_limit() : 2;
		if ( $limit > 0 ) {
			$enabled_ids = array();
			foreach ( $out as $uid => $row ) {
				if ( ! empty( $row['enabled'] ) ) {
					$enabled_ids[] = (int) $uid;
				}
			}
			sort( $enabled_ids );
			if ( count( $enabled_ids ) > $limit ) {
				$keep = array_slice( $enabled_ids, 0, $limit );
				$keep = array_fill_keys( $keep, true );
				foreach ( $out as $uid => $row ) {
					if ( ! empty( $row['enabled'] ) && ! isset( $keep[ (int) $uid ] ) ) {
						$out[ (int) $uid ]['enabled'] = 0;
					}
				}
				// Optional: record that we trimmed the list (for admin notice).
				set_transient( 'pnpc_psd_agents_trimmed', 1, 60 );
			}
		}

		return $out;
	}
}

/**
 * Get assignable agent users.
 *
 * Backwards compatible: if no agents are configured, falls back to staff roles.
 *
 * @return WP_User[]
 */
if ( ! function_exists( 'pnpc_psd_get_assignable_agents' ) ) {
	function pnpc_psd_get_assignable_agents() {
		$cfg = get_option( 'pnpc_psd_agents', array() );
		$ids = array();
		if ( is_array( $cfg ) ) {
			foreach ( $cfg as $uid => $row ) {
				$uid = absint( $uid );
				if ( ! $uid ) { continue; }
				$enabled = is_array( $row ) && ! empty( $row['enabled'] );
				if ( $enabled ) {
					$ids[] = $uid;
				}
			}
		}
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

		if ( ! empty( $ids ) ) {
			return get_users(
				array(
					'include' => $ids,
					'orderby' => 'display_name',
					'order'   => 'ASC',
				)
			);
		}

		// Fallback: original behavior based on staff roles.
		return get_users(
			array(
				'role__in' => array( 'administrator', 'pnpc_psd_manager', 'pnpc_psd_agent' ),
				'orderby'  => 'display_name',
				'order'    => 'ASC',
			)
		);
	}
}

/**
 * Resolve the notification email for an agent.
 * Falls back to the user's account email if no override is set.
 *
 * @param int $user_id Agent user ID.
 * @return string Email address.
 */
if ( ! function_exists( 'pnpc_psd_get_agent_notification_email' ) ) {
	function pnpc_psd_get_agent_notification_email( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return '';
		}
		$cfg = get_option( 'pnpc_psd_agents', array() );
		if ( is_array( $cfg ) && isset( $cfg[ $user_id ]['notify_email'] ) ) {
			$e = sanitize_email( (string) $cfg[ $user_id ]['notify_email'] );
			if ( ! empty( $e ) ) {
				return $e;
			}
		}
		$u = get_user_by( 'id', $user_id );
		return ( $u && ! empty( $u->user_email ) ) ? (string) $u->user_email : '';
	}
}

/**
 * Determine whether this installation is running the Pro build.
 *
 * Pro is enabled if any of the following are true:
 * - Constant PNPC_PSD_IS_PRO is defined and truthy.
 * - Option pnpc_psd_plan is set to 'pro'.
 * - Filter 'pnpc_psd_is_pro' returns true.
 *
 * @return bool
 */
if ( ! function_exists( 'pnpc_psd_is_pro' ) ) {
	function pnpc_psd_is_pro() {
		$is_pro = false;
		if ( defined( 'PNPC_PSD_IS_PRO' ) && PNPC_PSD_IS_PRO ) {
			$is_pro = true;
		}
		$plan = (string) get_option( 'pnpc_psd_plan', 'free' );
		if ( 'pro' === strtolower( $plan ) ) {
			$is_pro = true;
		}
		/**
		 * Allow themes/addons to declare Pro status.
		 */
		$is_pro = (bool) apply_filters( 'pnpc_psd_is_pro', $is_pro );
		return $is_pro;
	}
}

/**
 * Get the max number of eligible agents allowed by plan.
 *
 * Free: 2
 * Pro : unlimited (0)
 *
 * @return int 0 = unlimited.
 */
if ( ! function_exists( 'pnpc_psd_get_max_agents_limit' ) ) {
	function pnpc_psd_get_max_agents_limit() {
		return pnpc_psd_is_pro() ? 0 : 2;
	}
}

/**
 * Get max attachment size in megabytes for current plan.
 *
 * Free default: 5MB
 * Pro  default: 20MB
 *
 * Stored setting (pnpc_psd_max_attachment_mb) is clamped by plan defaults.
 *
 * @return int
 */
if ( ! function_exists( 'pnpc_psd_get_max_attachment_mb' ) ) {
	function pnpc_psd_get_max_attachment_mb() {
		$default = pnpc_psd_is_pro() ? 20 : 5;
		$min     = 1;
		$max     = pnpc_psd_is_pro() ? 20 : 5;
		$raw     = absint( get_option( 'pnpc_psd_max_attachment_mb', $default ) );
		$raw     = max( $min, $raw );
		$raw     = min( $max, $raw );
		return (int) $raw;
	}
}

/**
 * Sanitize max attachment size (MB) and clamp by plan.
 *
 * This ensures the stored option reflects the effective plan limit, avoiding UI confusion.
 *
 * @param mixed $value Raw value.
 * @return int
 */
if ( ! function_exists( 'pnpc_psd_sanitize_max_attachment_mb' ) ) {
	function pnpc_psd_sanitize_max_attachment_mb( $value ) {
		$val = absint( $value );
		$val = max( 1, $val );
		$cap = pnpc_psd_is_pro() ? 20 : 5;
		$val = min( $cap, $val );
		return (int) $val;
	}
}

/**
 * Get max attachment size in bytes.
 *
 * @return int
 */
if ( ! function_exists( 'pnpc_psd_get_max_attachment_bytes' ) ) {
	function pnpc_psd_get_max_attachment_bytes() {
		$mb = pnpc_psd_get_max_attachment_mb();
		return (int) ( $mb * 1024 * 1024 );
	}
}

/**
 * Get the allowed attachment types list.
 *
 * Historically this option has been saved in a few formats (comma-separated,
 * whitespace-separated, semicolon-separated). If the setting is blank or
 * contains only delimiters, fall back to safe defaults so attachments do not
 * silently stop working.
 *
 * @return string[] Lowercased allowed types/extensions.
 */
if ( ! function_exists( 'pnpc_psd_get_allowed_file_types_list' ) ) {
	function pnpc_psd_get_allowed_file_types_list() {
		$default_raw = 'jpg,jpeg,png,gif,webp,pdf,txt,csv,doc,docx,xls,xlsx,zip';
		$raw         = (string) get_option( 'pnpc_psd_allowed_file_types', $default_raw );
		$raw         = trim( (string) $raw );
		if ( '' === $raw ) {
			$raw = $default_raw;
		}

		// Split on commas, semicolons, and whitespace (including newlines/tabs).
		$parts = preg_split( '/[\s,;]+/', $raw );
		if ( ! is_array( $parts ) ) {
			$parts = array();
		}
		$parts = array_map( 'trim', $parts );
		$parts = array_filter(
			$parts,
			function ( $v ) {
				return '' !== trim( (string) $v );
			}
		);
		$parts = array_map(
			function ( $v ) {
				return strtolower( (string) $v );
			},
			$parts
		);
		$parts = array_values( array_unique( $parts ) );

		// If the list is still effectively empty, force defaults.
		if ( empty( $parts ) ) {
			$parts = array( 'jpg','jpeg','png','gif','webp','pdf','txt','csv','doc','docx','xls','xlsx','zip' );
		}

		return $parts;
	}
}

/**
 * Sanitize allowed attachment types setting.
 *
 * Stores a normalized comma-separated list, and never stores an empty value
 * (to prevent attachments from being silently blocked later).
 *
 * @param mixed $value Raw setting.
 * @return string
 */
if ( ! function_exists( 'pnpc_psd_sanitize_allowed_file_types' ) ) {
	function pnpc_psd_sanitize_allowed_file_types( $value ) {
		if ( is_array( $value ) ) {
			$raw = implode( ',', array_map( 'strval', $value ) );
		} else {
			$raw = is_string( $value ) ? $value : '';
		}
		$items = preg_split( '/[\s,;]+/', (string) $raw );
		if ( ! is_array( $items ) ) {
			$items = array();
		}
		$items = array_map( 'trim', $items );
		$items = array_filter(
			$items,
			function ( $v ) {
				return '' !== trim( (string) $v );
			}
		);
		$items = array_map(
			function ( $v ) {
				return strtolower( (string) $v );
			},
			$items
		);
		$items = array_values( array_unique( $items ) );
		if ( empty( $items ) ) {
			$items = array( 'jpg','jpeg','png','gif','webp','pdf','txt','csv','doc','docx','xls','xlsx','zip' );
		}
		return implode( ',', $items );
	}
}


if ( ! function_exists( 'pnpc_psd_sanitize_public_login_mode' ) ) {
	/**
	 * Sanitize public login mode.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	function pnpc_psd_sanitize_public_login_mode( $value ) {
		$value = is_string( $value ) ? strtolower( trim( $value ) ) : 'inline';
		return in_array( $value, array( 'inline', 'link' ), true ) ? $value : 'inline';
	}
}

if ( ! function_exists( 'pnpc_psd_sanitize_public_login_url' ) ) {
	/**
	 * Sanitize public login URL (optional).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	function pnpc_psd_sanitize_public_login_url( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		return $value ? esc_url_raw( $value ) : '';
	}
}

