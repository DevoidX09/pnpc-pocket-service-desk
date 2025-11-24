<?php

/**
 * PNPC PSD helpers (extended)
 *
 * Utility functions used by templates.
 *
 * Place this file in includes/helpers.php (or update the existing file).
 * Ensure it is required from your main plugin bootstrap or public class so templates can call it.
 *
 * Example require:
 * require_once PNPC_PSD_PLUGIN_DIR . 'includes/helpers.php';
 */

if (! function_exists('pnpc_psd_get_ticket_detail_page_id')) {
    /**
     * Return the configured Ticket Detail page ID.
     * Fallback order:
     *  1. option 'pnpc_psd_ticket_detail_page_id'
     *  2. page containing the [pnpc_ticket_detail] shortcode
     *  3. pages with common slugs: 'ticket-details', 'ticket-detail', 'ticket-view', 'dashboard-single'
     *  4. 0 if not found
     *
     * @return int Page ID or 0.
     */
    function pnpc_psd_get_ticket_detail_page_id()
    {
        static $cached = null;
        if (null !== $cached) {
            return (int) $cached;
        }

        // 1) Explicit configured option
        $config_id = absint(get_option('pnpc_psd_ticket_detail_page_id', 0));
        if ($config_id > 0 && get_post($config_id)) {
            $cached = $config_id;
            return $cached;
        }

        // 2) Search pages for the shortcode [pnpc_ticket_detail]
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

        // 3) Try common slugs for backward compatibility / guesses
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

if (! function_exists('pnpc_psd_get_my_tickets_page_id')) {
    /**
     * Return the configured "All Tickets / My Tickets" page ID.
     * Falls back to page with slug 'my-tickets' or 0.
     *
     * @return int Page ID or 0.
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

        // Also check for a page that contains the [pnpc_my_tickets] shortcode
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

if (! function_exists('pnpc_psd_get_ticket_detail_url')) {
    /**
     * Build ticket detail URL for a ticket ID using the configured page.
     *
     * Tries to resolve the ticket detail page id via pnpc_psd_get_ticket_detail_page_id().
     * Returns an add_query_arg('ticket_id', ...) URL when a page is found, otherwise empty string.
     *
     * @param int $ticket_id Numeric ticket ID.
     * @return string URL (unescaped) or empty string if no page configured/found.
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

if (! function_exists('pnpc_psd_get_my_tickets_url')) {
    /**
     * Return the URL to the configured "All Tickets / My Tickets" page.
     *
     * @return string URL (unescaped) or empty string if not configured.
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

if (! function_exists('pnpc_psd_get_dashboard_url')) {
    /**
     * Return the URL to the per-user dashboard page.
     *
     * Order:
     * 1) configured option 'pnpc_psd_dashboard_page_id'
     * 2) page with slug 'dashboard-single'
     * 3) pnpc_psd_get_my_tickets_url() as final fallback
     *
     * @return string URL or empty string
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

        $page = get_page_by_path('dashboard-single');
        if ($page && ! is_wp_error($page)) {
            $cached = get_permalink($page->ID);
            return $cached;
        }

        // final fallback to My Tickets page
        $my = pnpc_psd_get_my_tickets_url();
        $cached = $my ? $my : '';
        return $cached;
    }
}

/**
 * Convert a MySQL DATETIME (assumed stored in UTC) to a WP-local unix timestamp.
 *
 * Implementation details:
 * - Accepts numeric timestamps (returns them).
 * - Tries to parse the input as UTC using DateTimeImmutable, then converts to the
 *   WordPress timezone via wp_timezone() (this honors timezone_string and DST).
 * - If wp_timezone() is not available, falls back to using timezone_string (if valid).
 * - Final fallback uses mysql2date('U', ..., true) and applies gmt_offset (legacy).
 *
 * Returns 0 on empty input or parse failure.
 */
if (! function_exists('pnpc_psd_mysql_to_wp_local_ts')) {
    function pnpc_psd_mysql_to_wp_local_ts($mysql_datetime)
    {
        if (empty($mysql_datetime) && '0' !== (string) $mysql_datetime) {
            return 0;
        }

        // If caller already provided an integer timestamp, just return it.
        if (is_numeric($mysql_datetime) && (string)(int)$mysql_datetime === (string)$mysql_datetime) {
            return intval($mysql_datetime);
        }

        // If the value is a DateTimeInterface, use it directly.
        if ($mysql_datetime instanceof DateTimeInterface) {
            $dt = DateTimeImmutable::createFromInterface($mysql_datetime);
            // If dt has timezone, convert properly; otherwise assume it's in UTC.
            $tz = $dt->getTimezone();
            if ($tz && 'UTC' !== $tz->getName()) {
                // Convert to WP timezone if possible
                if (function_exists('wp_timezone')) {
                    $wp_tz = wp_timezone();
                    if ($wp_tz instanceof DateTimeZone) {
                        return (int) $dt->setTimezone($wp_tz)->format('U');
                    }
                }
                $tz_string = get_option('timezone_string', '');
                if ($tz_string) {
                    try {
                        $wpzone = new DateTimeZone($tz_string);
                        return (int) $dt->setTimezone($wpzone)->format('U');
                    } catch (Exception $e) {
                        // ignore and fall through
                    }
                }
                // As last resort just return UTC timestamp
                return (int) $dt->format('U');
            }
            // dt is UTC (or we treat it as UTC) — now convert to WP local
            if (function_exists('wp_timezone')) {
                $wp_tz = wp_timezone();
                if ($wp_tz instanceof DateTimeZone) {
                    return (int) $dt->setTimezone($wp_tz)->format('U');
                }
            }
            $tz_string = get_option('timezone_string', '');
            if ($tz_string) {
                try {
                    $tzobj = new DateTimeZone($tz_string);
                    return (int) $dt->setTimezone($tzobj)->format('U');
                } catch (Exception $e) {
                    // ignore
                }
            }
            // fallback: return dt's U timestamp
            return (int) $dt->format('U');
        }

        // Try parsing as a string datetime assumed to be UTC.
        try {
            $dt_string = trim((string) $mysql_datetime);

            // If the string contains a timezone offset, DateTimeImmutable will parse it.
            // But we want to assume datetimes stored in DB without offset are UTC.
            $dt = new DateTimeImmutable($dt_string, new DateTimeZone('UTC'));

            // Convert to WP timezone if available (handles DST correctly).
            if (function_exists('wp_timezone')) {
                $wp_tz = wp_timezone();
                if ($wp_tz instanceof DateTimeZone) {
                    return (int) $dt->setTimezone($wp_tz)->format('U');
                }
            }

            // Fall back to timezone_string option if set and valid
            $tz_string = get_option('timezone_string', '');
            if (! empty($tz_string)) {
                try {
                    $tz = new DateTimeZone($tz_string);
                    return (int) $dt->setTimezone($tz)->format('U');
                } catch (Exception $e) {
                    // ignore and fallback
                }
            }

            // If we reach here, return the UTC timestamp (converted to int)
            return (int) $dt->format('U');
        } catch (Exception $e) {
            // parse failed — fall through to final fallback
        }

        // Final fallback: use mysql2date to get GMT unix timestamp then apply gmt_offset
        $ts_gmt = intval(mysql2date('U', $mysql_datetime, true));
        $gmt_offset_hours = floatval(get_option('gmt_offset', 0));
        $offset_seconds = intval($gmt_offset_hours * 3600);
        return $ts_gmt + $offset_seconds;
    }
}

/**
 * Return a UTC MySQL datetime string suitable for DB insertion.
 *
 * Uses WordPress helper to return the current time in GMT in 'Y-m-d H:i:s' format.
 * This makes storage explicit: use this when inserting created_at/updated_at so the DB stores UTC.
 *
 * @return string MySQL DATETIME in UTC, e.g. '2025-11-23 07:24:00'
 */
if (! function_exists('pnpc_psd_get_utc_mysql_datetime')) {
    function pnpc_psd_get_utc_mysql_datetime()
    {
        // current_time('mysql', true) returns GMT (UTC) formatted string
        return (string) current_time('mysql', true);
    }
}

/**
 * Format a DB MySQL datetime into a localized display string per WP settings.
 *
 * This uses pnpc_psd_mysql_to_wp_local_ts() internally and then date_i18n() to
 * produce a formatted string that respects the site's locale and timezone.
 *
 * @param string|int|null $mysql_datetime MySQL DATETIME string or numeric timestamp.
 * @param string|null $format Optional WP date format (defaults to date_format + time_format)
 * @return string Localized formatted date/time or empty string on failure.
 */
if (! function_exists('pnpc_psd_format_db_datetime_for_display')) {
    function pnpc_psd_format_db_datetime_for_display($mysql_datetime, $format = null)
    {
        if (empty($mysql_datetime) && '0' !== (string) $mysql_datetime) {
            return '';
        }

        if (null === $format) {
            $format = get_option('date_format') . ' ' . get_option('time_format');
        }

        $ts = pnpc_psd_mysql_to_wp_local_ts($mysql_datetime);
        if ($ts <= 0) {
            return '';
        }

        return date_i18n($format, $ts);
    }
}

/**
 * Simple conditional debug logger. Logs only if WP_DEBUG is true and the plugin option pnpc_psd_debug_timestamps is truthy.
 * Helpful during troubleshooting without permanently spamming logs in production.
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