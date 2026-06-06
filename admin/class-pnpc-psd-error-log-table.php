<?php
/**
 * Admin error log table.
 *
 * @package PNPC_Pocket_Service_Desk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * PNPC PSD Error Log Table.
 */
class PNPC_PSD_Error_Log_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'pnpc_psd_error_log',
				'plural'   => 'pnpc_psd_error_logs',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'created_at' => __( 'Date', 'pnpc-pocket-service-desk' ),
			'severity'   => __( 'Severity', 'pnpc-pocket-service-desk' ),
			'type'       => __( 'Type', 'pnpc-pocket-service-desk' ),
			'message'    => __( 'Message', 'pnpc-pocket-service-desk' ),
			'context'    => __( 'Context', 'pnpc-pocket-service-desk' ),
		);
	}

	/**
	 * Prepare items.
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		$table    = $wpdb->prefix . 'pnpc_psd_error_log';
		$per_page = (int) apply_filters( 'pnpc_psd_error_log_per_page', 25 );
		$paged    = $this->get_pagenum();
		$offset   = ( $paged - 1 ) * $per_page;
		$type     = isset( $_GET['error_type'] ) ? sanitize_key( wp_unslash( $_GET['error_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter.

		$where  = '';
		$params = array();
		if ( '' !== $type ) {
			$where    = ' WHERE type = %s';
			$params[] = $type;
		}

		if ( ! empty( $params ) ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM {$table}{$where}", $params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$sql   = $wpdb->prepare( "SELECT * FROM {$table}{$where} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d", array_merge( $params, array( $per_page, $offset ) ) ); // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
		} else {
			$total = (int) $wpdb->get_var( "SELECT COUNT(1) FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$sql   = $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d", $per_page, $offset ); // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
		}

		$this->items = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->_column_headers = array( $this->get_columns(), array(), array() );
		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Default column renderer.
	 *
	 * @param array  $item Item.
	 * @param string $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		$value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
		if ( 'context' === $column_name ) {
			$decoded = json_decode( (string) $value, true );
			if ( is_array( $decoded ) ) {
				$value = wp_json_encode( $decoded, JSON_PRETTY_PRINT );
			}
			return '<code style="white-space:pre-wrap;display:block;max-width:520px;">' . esc_html( (string) $value ) . '</code>';
		}

		if ( 'severity' === $column_name ) {
			return '<strong>' . esc_html( ucfirst( (string) $value ) ) . '</strong>';
		}

		return esc_html( (string) $value );
	}

	/**
	 * Extra table navigation.
	 *
	 * @param string $which Position.
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$current = isset( $_GET['error_type'] ) ? sanitize_key( wp_unslash( $_GET['error_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter.
		$types   = array( '', 'email', 'attachment', 'database', 'runtime', 'licensing' );
		echo '<div class="alignleft actions">';
		echo '<label class="screen-reader-text" for="error_type">' . esc_html__( 'Filter by error type', 'pnpc-pocket-service-desk' ) . '</label>';
		echo '<select id="error_type" name="error_type">';
		foreach ( $types as $type ) {
			$label = '' === $type ? __( 'All error types', 'pnpc-pocket-service-desk' ) : ucfirst( $type );
			echo '<option value="' . esc_attr( $type ) . '" ' . selected( $current, $type, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		submit_button( __( 'Filter', 'pnpc-pocket-service-desk' ), '', 'filter_action', false );
		echo '</div>';
	}
}
