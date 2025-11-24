<?php

/**
 * Ticket management functionality
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/includes
 */

/**
 * Ticket management class.
 */
class PNPC_PSD_Ticket
{
    // (file content as prepared earlier in conversation; ensure updated created_at/updated_at storage)

    public static function create($data = array())
    {
        global $wpdb;

        $table = $wpdb->prefix . 'pnpc_psd_tickets';
        $table = esc_sql($table);

        $subject     = isset($data['subject']) ? sanitize_text_field($data['subject']) : '';
        $description = isset($data['description']) ? wp_kses_post($data['description']) : '';
        $priority    = isset($data['priority']) ? sanitize_text_field($data['priority']) : 'normal';
        $user_id     = isset($data['user_id']) ? intval($data['user_id']) : get_current_user_id();
        $assigned_to = isset($data['assigned_to']) ? (! empty($data['assigned_to']) ? intval($data['assigned_to']) : null) : null;

        if (empty($subject) || empty($description)) {
            return false;
        }

        $temp_ticket_number = 'PNPC-T' . substr(md5(uniqid('', true)), 0, 8);

        $insert_data = array(
            'ticket_number' => $temp_ticket_number,
            'user_id'       => $user_id,
            'subject'       => $subject,
            'description'   => $description,
            'status'        => 'open',
            'priority'      => $priority,
            'assigned_to'   => $assigned_to,
        );

        if (function_exists('pnpc_psd_get_utc_mysql_datetime')) {
            $utc_now = pnpc_psd_get_utc_mysql_datetime();
        } else {
            $utc_now = current_time('mysql', true);
        }
        if (! isset($insert_data['created_at'])) {
            $insert_data['created_at'] = $utc_now;
        }
        if (! isset($insert_data['updated_at'])) {
            $insert_data['updated_at'] = $utc_now;
        }

        $format = array('%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s');

        $ok = $wpdb->insert($table, $insert_data, $format);

        if (! $ok) {
            $last_error = isset($wpdb->last_error) ? $wpdb->last_error : '';
            error_log('pnpc-psd: ticket create initial insert failed: ' . $last_error);
            return false;
        }

        $insert_id = intval($wpdb->insert_id);

        $base = 1000;
        $opt_counter = get_option('pnpc_psd_ticket_counter', false);
        if (false !== $opt_counter && is_numeric($opt_counter)) {
            $base = max(intval($opt_counter), $base);
        }
        $final_ticket_number = 'PNPC-' . ($base + $insert_id);

        $updated = $wpdb->update(
            $table,
            array('ticket_number' => $final_ticket_number),
            array('id' => $insert_id),
            array('%s'),
            array('%d')
        );

        if (false === $updated) {
            $last_error = isset($wpdb->last_error) ? $wpdb->last_error : '';
            error_log('pnpc-psd: ticket create failed to set final ticket_number: ' . $last_error);
            return false;
        }

        try {
            $new_counter = $base + $insert_id + 1;
            $current_counter = get_option('pnpc_psd_ticket_counter', 0);
            if (! is_numeric($current_counter) || intval($current_counter) < $new_counter) {
                update_option('pnpc_psd_ticket_counter', intval($new_counter));
            }
        } catch (Exception $e) {
            error_log('pnpc-psd: failed to update option counter after create: ' . $e->getMessage());
        }

        try {
            self::send_ticket_created_notification($insert_id);
        } catch (Exception $e) {
            error_log('pnpc-psd: send_ticket_created_notification failed: ' . $e->getMessage());
        }

        return $insert_id;
    }

    // Other methods (get, get_by_user, update, delete, get_count) remain the same as earlier patched versions.
}