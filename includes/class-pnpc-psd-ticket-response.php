<?php

/**
 * Ticket response management functionality
 */
class PNPC_PSD_Ticket_Response
{
    public static function create($data)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pnpc_psd_ticket_responses';

        $defaults = array(
            'ticket_id'         => 0,
            'user_id'           => get_current_user_id(),
            'response'          => '',
            'is_staff_response' => 0,
        );

        $data = wp_parse_args($data, $defaults);

        if (empty($data['ticket_id']) || empty($data['response'])) {
            return false;
        }

        $is_staff = current_user_can('pnpc_psd_respond_to_tickets');

        $insert_data = array(
            'ticket_id'         => absint($data['ticket_id']),
            'user_id'           => absint($data['user_id']),
            'response'          => wp_kses_post($data['response']),
            'is_staff_response' => $is_staff ? 1 : 0,
        );

        if (function_exists('pnpc_psd_get_utc_mysql_datetime')) {
            $insert_data['created_at'] = pnpc_psd_get_utc_mysql_datetime();
        } else {
            $insert_data['created_at'] = current_time('mysql', true);
        }

        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            array('%d', '%d', '%s', '%d', '%s')
        );

        if ($result) {
            $response_id = $wpdb->insert_id;
            self::send_response_notification($response_id);
            return $response_id;
        }

        return false;
    }

    // Other methods unchanged (get, get_by_ticket, delete_by_ticket, get_count)
}