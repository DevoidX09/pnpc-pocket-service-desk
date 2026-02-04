<?php
/**
 * Admin Audit Log table (WP_List_Table).
 *
 * UI-only surface over the existing pnpc_psd_audit_log DB table.
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * PNPC PSD Audit Log Table.
 *
 * @since 1.1.1.4
 */
class PNPC_PSD_Audit_Log_Table extends WP_List_Table {

	/**
	 * Retention cap for visible rows.
	 *
	 * @var int
	 */
	protected $cap = 250;

	/**

	/**

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'pnpc_psd_audit_log',
				'plural'   => 'pnpc_psd_audit_logs',
				'ajax'     => false,
			)
		);

		$this->cap = function_exists( 'pnpc_psd_get_audit_log_cap' ) ? (int) pnpc_psd_get_audit_log_cap() : 250;
		$this->cap = max( 0, $this->cap );
	}

	/**
	 * Columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'created_at' => esc_html__( 'Date', 'pnpc-pocket-service-desk' ),
			'actor'      => esc_html__( 'Actor', 'pnpc-pocket-service-desk' ),
			'action'     => esc_html__( 'Action', 'pnpc-pocket-service-desk' ),
			'ticket_id'  => esc_html__( 'Ticket ID', 'pnpc-pocket-service-desk' ),
			'context'    => esc_html__( 'Details', 'pnpc-pocket-service-desk' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'created_at' => array( 'created_at', true ),
			'action'    => array( 'action', false ),
			'ticket_id' => array( 'ticket_id', false ),
			'actor'     => array( 'actor_id', false ),
		);
	}

	/**
	 * Render default column.
	 *
	 * @param array  $item Row.
	 * @param string $column_name Column key.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'created_at':
				return esc_html( (string) $item['created_at_display'] );
			case 'actor':
				return $this->render_actor( $item );
			case 'action':
				return esc_html( (string) $item['action'] );
			case 'ticket_id':
				return '' !== (string) $item['ticket_id'] ? esc_html( (string) $item['ticket_id'] ) : '—';
			case 'context':
				return $this->render_context( $item );
			default:
				return '';
		}
	}

	/**
	 * Actor renderer.
	 *
	 * @param array $item Row.
	 * @return string
	 */
	protected function render_actor( $item ) {
		$actor_id = isset( $item['actor_id'] ) ? absint( $item['actor_id'] ) : 0;
		if ( $actor_id < 1 ) {
			return '—';
		}
		$user = get_user_by( 'id', $actor_id );
		if ( ! $user ) {
			return sprintf( '#%d', $actor_id );
		}

		$label = $user->display_name ? $user->display_name : $user->user_login;
		$role  = '';
		if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
			$role = reset( $user->roles );
		}
		if ( $role ) {
			$label .= sprintf( ' (%s)', $role );
		}
		return esc_html( $label );
	}

	/**
	 * Context renderer (lightweight; UI-only).
	 *
	 * @param array $item Row.
	 * @return string
	 */
	protected function render_context( $item ) {
		$raw = isset( $item['context'] ) ? (string) $item['context'] : '';
		if ( '' === $raw ) {
			return '—';
		}

		$preview = $raw;
		$decoded = null;

		// If JSON, show a compact key summary when possible.
		if ( $this->looks_like_json( $raw ) ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				$preview = $this->format_context_array( $decoded );
			}
		}

		$preview = wp_strip_all_tags( (string) $preview );
		$preview = preg_replace( '/\s+/', ' ', $preview );
		$preview = trim( (string) $preview );

		if ( strlen( $preview ) > 160 ) {
			$preview = substr( $preview, 0, 157 ) . '...';
		}

		return esc_html( $preview );
	}

	/**
	 * Determine if string might be JSON.
	 *
	 * @param string $s Raw.
	 * @return bool
	 */
	protected function looks_like_json( $s ) {
		$s = ltrim( $s );
		return '' !== $s && ( '{' === $s[0] || '[' === $s[0] );
	}

	/**
	 * Format a decoded context array into a compact string.
	 *
	 * @param array $ctx Context.
	 * @return string
	 */
	protected function format_context_array( $ctx ) {
		$keys_prefer = array( 'message', 'note', 'status', 'from', 'to', 'field', 'value', 'reason' );
		$parts       = array();

		foreach ( $keys_prefer as $k ) {
			if ( isset( $ctx[ $k ] ) && '' !== (string) $ctx[ $k ] ) {
				$parts[] = $k . ': ' . ( is_scalar( $ctx[ $k ] ) ? (string) $ctx[ $k ] : wp_json_encode( $ctx[ $k ] ) );
			}
		}

		// If none of the preferred keys exist, fall back to a small subset of keys.
		if ( empty( $parts ) ) {
			$count = 0;
			foreach ( $ctx as $k => $v ) {
				$parts[] = (string) $k . ': ' . ( is_scalar( $v ) ? (string) $v : wp_json_encode( $v ) );
				$count++;
				if ( $count >= 4 ) {
					break;
				}
			}
		}

		return implode( ' | ', $parts );
	}

	/**
	 * Bulk actions (none).
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array();
	}

	/**
	 * Prepare items.
	 */
	public function prepare_items() {
		global $wpdb;

		$table = $wpdb->prefix . 'pnpc_psd_audit_log';

		$per_page = (int) apply_filters( 'pnpc_psd_audit_log_per_page', 25 );
		$per_page = max( 5, min( 200, $per_page ) );

		$paged = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;

		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'desc';

		$allowed_orderby = array( 'created_at', 'action', 'ticket_id', 'actor_id' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'created_at';
		}
		$order = ( 'asc' === strtolower( $order ) ) ? 'asc' : 'desc';

		$filters = $this->get_filters();

		$where  = 'WHERE 1=1';
		$params = array();

		if ( '' !== $filters['action'] ) {
			$where    .= ' AND action = %s';
			$params[] = $filters['action'];
		}
		if ( $filters['ticket_id'] ) {
			$where    .= ' AND ticket_id = %d';
			$params[] = $filters['ticket_id'];
		}
		if ( $filters['actor_id'] ) {
			$where    .= ' AND actor_id = %d';
			$params[] = $filters['actor_id'];
		}

		
// Count.
		$sql_count = "SELECT COUNT(*) FROM {$table} {$where}";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safely constructed from $wpdb->prefix and hardcoded string
		$total_items = (int) $wpdb->get_var( $wpdb->prepare( $sql_count, $params ) );

		/*
		 * Free uses a capped dataset, so enforce newest-first ordering to keep paging and cap
		 * semantics consistent even if a user tries to sort ascending.
		 */
		$order = 'desc';

		$offset = ( $paged - 1 ) * $per_page;

		$limit_clause = $wpdb->prepare( 'LIMIT %d OFFSET %d', $per_page, $offset );

		// Main select.
		$sql = "SELECT id, ticket_id, actor_id, action, context, created_at FROM {$table} {$where} ORDER BY {$orderby} {$order} {$limit_clause}";

// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safely constructed from $wpdb->prefix and hardcoded string
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		$items = array();
		foreach ( (array) $rows as $row ) {
			$created_at = isset( $row['created_at'] ) ? (string) $row['created_at'] : '';
			$row['created_at_display'] = $created_at ? get_date_from_gmt( $created_at, 'Y-m-d H:i:s' ) : '';
			$items[] = $row;
		}

		$this->items = $items;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total_items / $per_page ),
			)
		);

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'created_at' );
	}

	/**
	 * Get filters from request.
	 *
	 * @return array{action:string,ticket_id:int,actor_id:int}
	 */
	protected function get_filters() {
		$action   = isset( $_GET['audit_action'] ) ? sanitize_text_field( wp_unslash( $_GET['audit_action'] ) ) : '';
		$ticket_id = isset( $_GET['ticket_id'] ) ? absint( wp_unslash( $_GET['ticket_id'] ) ) : 0;
		$actor_id  = isset( $_GET['actor_id'] ) ? absint( wp_unslash( $_GET['actor_id'] ) ) : 0;

		return array(
			'action'    => $action,
			'ticket_id' => $ticket_id,
			'actor_id'  => $actor_id,
		);
	}

	/**
	 * Extra controls (filters).
	 *
	 * @param string $which Top/bottom.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$filters = $this->get_filters();

		echo '<div class="alignleft actions">';

		// Action filter.
		echo '<label class="screen-reader-text" for="audit_action">' . esc_html__( 'Filter by action', 'pnpc-pocket-service-desk' ) . '</label>';
		echo '<input type="text" name="audit_action" id="audit_action" value="' . esc_attr( $filters['action'] ) . '" placeholder="' . esc_attr__( 'Action (e.g., ticket_created)', 'pnpc-pocket-service-desk' ) . '" />';

		// Ticket ID filter.
		echo '<label class="screen-reader-text" for="ticket_id">' . esc_html__( 'Filter by ticket ID', 'pnpc-pocket-service-desk' ) . '</label>';
		echo '<input type="number" min="0" name="ticket_id" id="ticket_id" value="' . esc_attr( $filters['ticket_id'] ) . '" placeholder="' . esc_attr__( 'Ticket ID', 'pnpc-pocket-service-desk' ) . '" />';

		// Actor ID filter.
		echo '<label class="screen-reader-text" for="actor_id">' . esc_html__( 'Filter by actor', 'pnpc-pocket-service-desk' ) . '</label>';
		echo '<input type="number" min="0" name="actor_id" id="actor_id" value="' . esc_attr( $filters['actor_id'] ) . '" placeholder="' . esc_attr__( 'Actor ID', 'pnpc-pocket-service-desk' ) . '" />';

		submit_button( esc_html__( 'Filter', 'pnpc-pocket-service-desk' ), 'secondary', 'filter_action', false );

		echo '</div>';
	}
}