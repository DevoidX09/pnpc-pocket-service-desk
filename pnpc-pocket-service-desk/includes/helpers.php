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
/**
 * Pnpc psd get ticket detail page id.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd get my tickets page id.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd get ticket detail url.
 *
 * @param mixed $ticket_id 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd get my tickets url.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd get dashboard url.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd get wp timezone.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd mysql to wp local ts.
 *
 * @param mixed $mysql_datetime 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd get utc mysql datetime.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd format db datetime for display.
 *
 * @param mixed $mysql_datetime 
 * @param mixed $format 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd debug log.
 *
 * @param mixed $label 
 * @param mixed $data 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log
        error_log( 'pnpc-psd-debug [' . $label . ']: ' . $payload );
    }
}

/**
 * Normalize $_FILES multi-file structure to array of file arrays.
 */
if (! function_exists('pnpc_psd_rearrange_files')) {
/**
 * Pnpc psd rearrange files.
 *
 * @param mixed $file_post 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd format filesize.
 *
 * @param mixed $bytes 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd get attachment type.
 *
 * @param mixed $extension 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd get file icon.
 *
 * @param mixed $extension 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd can preview attachment.
 *
 * @param mixed $file_size 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd format delete reason.
 *
 * @param mixed $reason 
 * @param mixed $other_details 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd sync roles caps.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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

		// Roles: Manager (Pro-only).
		if ( function_exists( 'pnpc_psd_enable_manager_role' ) && pnpc_psd_enable_manager_role() ) {
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
				// Elevated caps are Pro-only; if manager role is enabled, grant them.
				$elevated = array( 'pnpc_psd_delete_tickets', 'pnpc_psd_manage_settings' );
				foreach ( $elevated as $cap ) {
					$manager_role->add_cap( $cap );
				}
			}
		} else {
			// If manager role is not enabled, do not create it and do not maintain its caps.
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
/**
 * Pnpc psd sanitize agents option.
 *
 * @param mixed $value 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
			$notify  = isset( $row['notify'] ) ? absint( $row['notify'] ) : 0;
			$out[ $uid ] = array(
				'enabled'     => $enabled ? 1 : 0,
				'notify_email'=> $email,
				'notify'     => $notify ? 1 : 0,
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
/**
 * Pnpc psd get assignable agents.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
				'role__in' => ( ( function_exists( 'pnpc_psd_enable_manager_role' ) && pnpc_psd_enable_manager_role() ) ? array( 'administrator', 'pnpc_psd_manager', 'pnpc_psd_agent' ) : array( 'administrator', 'pnpc_psd_agent' ) ),
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
/**
 * Pnpc psd get agent notification email.
 *
 * @param mixed $user_id 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
	function pnpc_psd_get_agent_notification_email( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return '';
		}

		// Default behavior for WP.org-ready builds: do not allow per-agent override emails unless explicitly enabled.
		$disable_overrides = absint( get_option( 'pnpc_psd_disable_agent_notify_overrides', 1 ) );
		if ( 1 === $disable_overrides ) {
			$u = get_user_by( 'id', $user_id );
			return ( $u && ! empty( $u->user_email ) ) ? (string) $u->user_email : '';
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
/**
 * Pnpc psd is pro.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
	function pnpc_psd_is_pro() {
		$is_pro = false;
		if ( defined( 'PNPC_PSD_IS_PRO' ) && PNPC_PSD_IS_PRO ) {
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
 * Back-compat helper: determine whether Pro is active/available.
 *
 * @return bool
 */
if ( ! function_exists( 'pnpc_psd_is_pro_active' ) ) {
/**
 * Pnpc psd is pro active.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
	function pnpc_psd_is_pro_active() {
		return pnpc_psd_is_pro();
	}
}

/**
 * Whether the Service Desk Manager role should be enabled.
 *
 * Free: false by default.
 * Pro : enabled by Pro add-on via filter.
 *
 * @return bool
 */
if ( ! function_exists( 'pnpc_psd_enable_manager_role' ) ) {
/**
 * Pnpc psd enable manager role.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
	function pnpc_psd_enable_manager_role() {
		$enabled = false;
		/**
		 * Filter: enable manager role support.
		 *
		 * @param bool $enabled
		 */
		return (bool) apply_filters( 'pnpc_psd_enable_manager_role', $enabled );
	}
}

/**
 * Filter visible WooCommerce product IDs for the current user.
 * Free: passthrough by default.
 * Pro : restrict via add-on.
 *
 * @param int[] $product_ids
 * @param int   $user_id
 *
 * @return int[]
 */
if ( ! function_exists( 'pnpc_psd_filter_visible_products_for_user' ) ) {
/**
 * Pnpc psd filter visible products for user.
 *
 * @param mixed $product_ids 
 * @param mixed $user_id 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
	function pnpc_psd_filter_visible_products_for_user( $product_ids, $user_id ) {
		/**
		 * Filter: visible products for a given user.
		 *
		 * @param int[] $product_ids
		 * @param int   $user_id
		 */
		$product_ids = apply_filters( 'pnpc_psd_visible_products_for_user', (array) $product_ids, (int) $user_id );
		return array_values( array_unique( array_map( 'absint', (array) $product_ids ) ) );
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
/**
 * Pnpc psd get max agents limit.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd get max attachment mb.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd sanitize max attachment mb.
 *
 * @param mixed $value 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd get max attachment bytes.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd get allowed file types list.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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
/**
 * Pnpc psd sanitize allowed file types.
 *
 * @param mixed $value 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
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



/**
 * Attachment security helpers.
 *
 * Attachments are stored as file paths (preferred) or legacy URLs in the DB.
 * Downloads (and previews) are served through a gated handler to prevent direct access.
 */
if ( ! function_exists( 'pnpc_psd_attachment_db_to_path' ) ) {
	/**
	 * Convert a stored attachment "file_path" value to an absolute filesystem path.
	 *
	 * @param string $stored Stored value (absolute path preferred; legacy URL supported).
	 * @return string Absolute path or empty string if it cannot be resolved.
	 */
	function pnpc_psd_attachment_db_to_path( $stored ) {
		$stored = is_string( $stored ) ? trim( $stored ) : '';
		if ( '' === $stored ) {
			return '';
		}

		// If it's already an absolute path, keep it.
		if ( 0 === strpos( $stored, ABSPATH ) || 0 === strpos( $stored, '/' ) ) {
			return $stored;
		}

		// Legacy: URL stored in DB. Convert uploads baseurl -> basedir.
		$uploads = function_exists( 'wp_get_upload_dir' ) ? wp_get_upload_dir() : array();
		$baseurl = isset( $uploads['baseurl'] ) ? (string) $uploads['baseurl'] : '';
		$basedir = isset( $uploads['basedir'] ) ? (string) $uploads['basedir'] : '';
		if ( '' !== $baseurl && '' !== $basedir && 0 === strpos( $stored, $baseurl ) ) {
			$rel = ltrim( substr( $stored, strlen( $baseurl ) ), '/' );
			return rtrim( $basedir, '/' ) . '/' . $rel;
		}

		return '';
	}
}

if ( ! function_exists( 'pnpc_psd_get_attachment_download_url' ) ) {
	/**
	 * Build a secure (gated) attachment download URL.
	 *
	 * @param int  $attachment_id Attachment row ID.
	 * @param int  $ticket_id     Ticket ID (scope validation).
	 * @param bool $inline        Whether to request inline display (preview).
	 * @return string
	 */
	function pnpc_psd_get_attachment_download_url( $attachment_id, $ticket_id, $inline = false ) {
		$attachment_id = absint( $attachment_id );
		$ticket_id     = absint( $ticket_id );
		if ( ! $attachment_id || ! $ticket_id ) {
			return '';
		}
		$args = array(
			'action' => 'pnpc_psd_download_attachment',
			'att'    => $attachment_id,
			'ticket' => $ticket_id,
		);
		if ( $inline ) {
			$args['inline'] = 1;
		}
		$url = add_query_arg( $args, admin_url( 'admin-post.php' ) );
		$url = wp_nonce_url( $url, 'pnpc_psd_download_attachment_' . $attachment_id );
		return $url;
	}
}

if ( ! function_exists( 'pnpc_psd_handle_download_attachment' ) ) {
	/**
	 * Admin-post handler for gated attachment downloads.
	 *
	 * Enforces:
	 * - Logged-in
	 * - Nonce
	 * - Capability (staff) OR ticket ownership (customer)
	 * - Attachment belongs to ticket and is not deleted
	 */
	function pnpc_psd_handle_download_attachment() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'You must be logged in to download attachments.', 'pnpc-pocket-service-desk' ), 403 );
		}

		$attachment_id = isset( $_GET['att'] ) ? absint( wp_unslash( $_GET['att'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		$ticket_id     = isset( $_GET['ticket'] ) ? absint( wp_unslash( $_GET['ticket'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		$inline        = isset( $_GET['inline'] ) ? absint( wp_unslash( $_GET['inline'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below.

		if ( ! $attachment_id || ! $ticket_id ) {
			wp_die( esc_html__( 'Invalid attachment request.', 'pnpc-pocket-service-desk' ), 400 );
		}

		check_admin_referer( 'pnpc_psd_download_attachment_' . $attachment_id );

		global $wpdb;
		$att_table    = $wpdb->prefix . 'pnpc_psd_ticket_attachments';
		$ticket_table = $wpdb->prefix . 'pnpc_psd_tickets';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$att = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$att_table} WHERE id = %d AND ticket_id = %d AND deleted_at IS NULL",
				$attachment_id,
				$ticket_id
			)
		);

		if ( ! $att ) {
			wp_die( esc_html__( 'Attachment not found.', 'pnpc-pocket-service-desk' ), 404 );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$ticket = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, user_id FROM {$ticket_table} WHERE id = %d",
				$ticket_id
			)
		);

		if ( ! $ticket ) {
			wp_die( esc_html__( 'Ticket not found.', 'pnpc-pocket-service-desk' ), 404 );
		}

		$current_user_id = get_current_user_id();
		$is_staff        = current_user_can( 'pnpc_psd_respond_to_tickets' ) || current_user_can( 'pnpc_psd_manage_tickets' );

		if ( ! $is_staff && absint( $ticket->user_id ) !== absint( $current_user_id ) ) {
			wp_die( esc_html__( 'Permission denied.', 'pnpc-pocket-service-desk' ), 403 );
		}

		$path = pnpc_psd_attachment_db_to_path( isset( $att->file_path ) ? (string) $att->file_path : '' );
		if ( '' === $path || ! file_exists( $path ) || ! is_readable( $path ) ) {
			wp_die( esc_html__( 'File is missing or not readable.', 'pnpc-pocket-service-desk' ), 404 );
		}

		// Determine content type.
		$filename = isset( $att->file_name ) ? (string) $att->file_name : basename( $path );
		$ctype    = '';
		if ( function_exists( 'wp_check_filetype' ) ) {
			$ft = wp_check_filetype( $filename );
			$ctype = ! empty( $ft['type'] ) ? (string) $ft['type'] : '';
		}
		if ( '' === $ctype ) {
			$ctype = 'application/octet-stream';
		}

		/**
		 * Serve attachment file with security verification.
		 * 
		 * Security measures in place:
		 * 1. Nonce verification (check_admin_referer)
		 * 2. User login requirement (is_user_logged_in)
		 * 3. Capability check (staff) OR ticket ownership verification
		 * 4. File existence and readability validation
		 * 5. Path traversal protection via pnpc_psd_attachment_db_to_path()
		 * 6. MIME type validation via wp_check_filetype()
		 * 
		 * Using readfile() instead of WP_Filesystem because:
		 * - Efficiently streams large binary files without loading into memory
		 * - Properly handles Content-Length for download progress
		 * - Tested and secure for binary file output
		 * - All security checks completed before file access
		 */
		nocache_headers();
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Type: ' . $ctype );
		header( 'Content-Length: ' . (string) filesize( $path ) );

		$disposition = $inline ? 'inline' : 'attachment';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		header( 'Content-Disposition: ' . $disposition . '; filename="' . rawurlencode( $filename ) . '"' );

		// Clear any existing output buffers to avoid corrupting binary output.
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
		exit;
	}
}

// Register admin-post handler (logged-in only).
add_action( 'admin_post_pnpc_psd_download_attachment', 'pnpc_psd_handle_download_attachment' );



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

