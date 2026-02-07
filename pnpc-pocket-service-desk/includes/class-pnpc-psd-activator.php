<?php
/**
 * Fired during plugin activation
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/includes
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema operations during activation
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names properly prefixed
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/includes
 */
/**
 * PNPC PSD Activator.
 *
 * @since 1.1.1.4
 */
class PNPC_PSD_Activator {

	/**
	 * Plugin activation handler.
	 *
	 * Creates database tables, adds custom roles and capabilities,
	 * and sets default options.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Create tickets table.
		$tickets_table = $wpdb->prefix . 'pnpc_psd_tickets';
		$sql_tickets   = "CREATE TABLE $tickets_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			ticket_number varchar(20) NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			subject varchar(255) NOT NULL,
			description longtext NOT NULL,
			status varchar(20) DEFAULT 'open' NOT NULL,
			priority varchar(20) DEFAULT 'normal' NOT NULL,
			assigned_to bigint(20) UNSIGNED DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
			deleted_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY ticket_number (ticket_number),
			KEY user_id (user_id),
			KEY assigned_to (assigned_to),
			KEY status (status),
			KEY deleted_at (deleted_at)
		) $charset_collate;";

		// Create ticket responses table.
		$responses_table = $wpdb->prefix . 'pnpc_psd_ticket_responses';
		$sql_responses   = "CREATE TABLE $responses_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			response longtext NOT NULL,
			is_staff_response tinyint(1) DEFAULT 0 NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			deleted_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY ticket_id (ticket_id),
			KEY user_id (user_id),
			KEY deleted_at (deleted_at)
		) $charset_collate;";

		// Create ticket attachments table.
		$attachments_table = $wpdb->prefix . 'pnpc_psd_ticket_attachments';
		$sql_attachments   = "CREATE TABLE $attachments_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) UNSIGNED NOT NULL,
			response_id bigint(20) UNSIGNED DEFAULT NULL,
			file_name varchar(255) NOT NULL,
			file_path varchar(500) NOT NULL,
			file_type varchar(100) NOT NULL,
			file_size bigint(20) UNSIGNED NOT NULL,
			uploaded_by bigint(20) UNSIGNED NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			deleted_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY ticket_id (ticket_id),
			KEY response_id (response_id),
			KEY deleted_at (deleted_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_tickets );
		dbDelta( $sql_responses );
		dbDelta( $sql_attachments );

		// Add custom roles and capabilities.
		self::add_custom_roles();

		// Set default options.
		add_option( 'pnpc_psd_version', PNPC_PSD_VERSION );
		// Fresh installs start at 1.0.0 so migrations run consistently.
		add_option( 'pnpc_psd_db_version', '1.0.0' );
		add_option( 'pnpc_psd_ticket_counter', 1000 );

		// Run database migration for existing installations.
		self::maybe_upgrade_database();

		// Flush rewrite rules.
		flush_rewrite_rules();
		// First-run setup wizard prompt (non-destructive).
		// Auto-redirect ONLY when a dashboard page has not been configured yet
		// AND there is no existing ticket history (clean install).
		$dash_id = (int) get_option( 'pnpc_psd_dashboard_page_id', 0 );

		$has_dashboard = false;
		if ( $dash_id > 0 ) {
			$status = get_post_status( $dash_id );
			if ( ! empty( $status ) && 'trash' !== $status && 'auto-draft' !== $status ) {
				$created_by_builder = (bool) get_post_meta( $dash_id, '_pnpc_psd_created_by_builder', true );
				if ( $created_by_builder ) {
					$has_dashboard = true;
				} else {
					$post = get_post( $dash_id );
					$haystack = ( $post instanceof WP_Post ) ? (string) $post->post_content : '';
					$elementor_data = (string) get_post_meta( $dash_id, '_elementor_data', true );
					if ( ! empty( $elementor_data ) ) {
						$haystack .= "\n" . $elementor_data;
					}
					$tags = array( 'pnpc_profile_settings', 'pnpc_service_desk', 'pnpc_create_ticket', 'pnpc_services', 'pnpc_my_tickets' );
					foreach ( $tags as $tag ) {
						if ( false !== strpos( $haystack, '[' . $tag ) ) {
							$has_dashboard = true;
							break;
						}
					}
				}
			}
		}

		// Determine whether the Ticket View page is configured (optional but recommended).
		$ticket_view_id  = (int) get_option( 'pnpc_psd_ticket_view_page_id', 0 );
		$has_ticket_view = false;
		if ( $ticket_view_id > 0 ) {
			$status = get_post_status( $ticket_view_id );
			if ( ! empty( $status ) && 'trash' !== $status && 'auto-draft' !== $status ) {
				$created_by_builder = (bool) get_post_meta( $ticket_view_id, '_pnpc_psd_created_by_builder', true );
				if ( $created_by_builder ) {
					$has_ticket_view = true;
				} else {
					$post = get_post( $ticket_view_id );
					$haystack = ( $post instanceof WP_Post ) ? (string) $post->post_content : '';
					$elementor_data = (string) get_post_meta( $ticket_view_id, '_elementor_data', true );
					if ( ! empty( $elementor_data ) ) {
						$haystack .= "\n" . $elementor_data;
					}
					if ( false !== strpos( $haystack, '[pnpc_ticket_detail' ) ) {
						$has_ticket_view = true;
					}
				}
			}
		}
		$has_tickets   = false;

		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';

		// Determine whether the tickets table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name );

		if ( $table_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$ticket_count = (int) $wpdb->get_var( "SELECT COUNT(1) FROM {$table_name}" );
			$has_tickets  = ( $ticket_count > 0 );
		}

		// First-run Setup Wizard flags.
		//
		// On a clean install (no ticket history) we want to nudge admins into the Setup Wizard,
		// even if a dashboard page ID was pre-populated by a host/site-template.
		// The wizard will detect/repair existing pages as needed.
		$needs_setup = ( ! $has_dashboard || ! $has_ticket_view );

		if ( ! $has_tickets && $needs_setup ) {
			update_option( 'pnpc_psd_needs_setup_wizard', 1 );
			update_option( 'pnpc_psd_setup_notice_dismissed', 0 );
			// Redirect into the wizard once after activation (clean installs only).
			// Use an option (not a transient) so this survives object cache variance.
			update_option( 'pnpc_psd_activation_redirect', 1, false );
			// Back-compat: older builds checked this key.
			update_option( 'pnpc_psd_do_setup_redirect', 1, false );
		}
	}

	/**
	 * Update database schema for existing installations.
	 *
	 * @since 1.1.0
	 */
	public static function maybe_upgrade_database() {
		$current_db_version = get_option( 'pnpc_psd_db_version', '1.0.0' );

		// Update to 1.1.0 if needed.
		if ( version_compare( $current_db_version, '1.1.0', '<' ) ) {
			self::upgrade_to_1_1_0();
		}

		// Update to 1.2.0 if needed.
		if ( version_compare( $current_db_version, '1.2.0', '<' ) ) {
			self::upgrade_to_1_2_0();
		}

		// Update to 1.3.0 if needed.
		if ( version_compare( $current_db_version, '1.3.0', '<' ) ) {
			self::upgrade_to_1_3_0();
		}

		// Update to 1.4.0 if needed.
		if ( version_compare( $current_db_version, '1.4.0', '<' ) ) {
			self::upgrade_to_1_4_0();
		}

		// Update to 1.5.0 if needed (unread/activity tracking + attachment settings baseline).
		if ( version_compare( $current_db_version, '1.5.0', '<' ) ) {
			self::upgrade_to_1_5_0();
		}

		// Update to 1.6.0 if needed (audit log, archiving, csv export scaffolding).
		if ( version_compare( $current_db_version, '1.6.0', '<' ) ) {
			self::upgrade_to_1_6_0();
		}
	}


	/**
	 * Ensure delete reason tracking columns exist (defensive schema guard).
	 *
	 * Some environments can miss the 1.2.0 migration if the plugin was upgraded without reactivation.
	 * This check is safe to run on init; it only ALTERs the table when columns are missing.
	 *
	 * @since 1.4.1
	 * @return void
	 */
	public static function ensure_delete_reason_columns() {
		global $wpdb;
		$tickets_table = $wpdb->prefix . 'pnpc_psd_tickets';

		// Verify tickets table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$tickets_table
			)
		);
		if ( ! $table_exists ) {
			return;
		}

		// Check columns individually; some sites can end up with partial migrations.
		$needs = array(
			'delete_reason'       => "ALTER TABLE {$tickets_table} ADD COLUMN delete_reason VARCHAR(50) DEFAULT NULL AFTER deleted_at",
			'delete_reason_other' => "ALTER TABLE {$tickets_table} ADD COLUMN delete_reason_other TEXT DEFAULT NULL AFTER delete_reason",
			'deleted_by'          => "ALTER TABLE {$tickets_table} ADD COLUMN deleted_by BIGINT(20) UNSIGNED DEFAULT NULL AFTER delete_reason_other",
		);

		foreach ( $needs as $col => $sql ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$column_exists = $wpdb->get_results(
				$wpdb->prepare(
					"SHOW COLUMNS FROM {$tickets_table} LIKE %s",
					$col
				)
			);
			if ( empty( $column_exists ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query( $sql );
			}
		}

		// Add indexes if missing.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$idx_reason = $wpdb->get_results("SHOW INDEX FROM {$tickets_table} WHERE Key_name = 'delete_reason'");
		if ( empty( $idx_reason ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query("ALTER TABLE {$tickets_table} ADD KEY delete_reason (delete_reason)");
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$idx_deleted_by = $wpdb->get_results("SHOW INDEX FROM {$tickets_table} WHERE Key_name = 'deleted_by'");
		if ( empty( $idx_deleted_by ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query("ALTER TABLE {$tickets_table} ADD KEY deleted_by (deleted_by)");
		}
	}

	/**
	 * Update database to version 1.4.0 (delete review queue).
	 *
	 * Adds pending delete fields so agent/staff deletion requests can be reviewed
	 * before being moved to trash.
	 *
	 * @since 1.4.0
	 */
	private static function upgrade_to_1_4_0() {
		global $wpdb;

		$tickets_table = $wpdb->prefix . 'pnpc_psd_tickets';

		// Verify table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$tickets_table
			)
		);

		if ( empty( $table_exists ) ) {
			return;
		}

		$columns_to_add = array(
			'pending_delete_at'          => "ALTER TABLE {$tickets_table} ADD COLUMN pending_delete_at datetime DEFAULT NULL AFTER deleted_at",
			'pending_delete_by'          => "ALTER TABLE {$tickets_table} ADD COLUMN pending_delete_by BIGINT(20) UNSIGNED DEFAULT NULL AFTER pending_delete_at",
			'pending_delete_reason'      => "ALTER TABLE {$tickets_table} ADD COLUMN pending_delete_reason varchar(100) DEFAULT NULL AFTER pending_delete_by",
			'pending_delete_reason_other'=> "ALTER TABLE {$tickets_table} ADD COLUMN pending_delete_reason_other text DEFAULT NULL AFTER pending_delete_reason",
		);

		foreach ( $columns_to_add as $col => $sql ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$column_exists = $wpdb->get_results(
				$wpdb->prepare(
					"SHOW COLUMNS FROM {$tickets_table} LIKE %s",
					$col
				)
			);
			if ( empty( $column_exists ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query( $sql );
			}
		}

		// Add indexes to keep review queries fast.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$pending_idx = $wpdb->get_results("SHOW INDEX FROM {$tickets_table} WHERE Key_name = 'pending_delete_at'");
		if ( empty( $pending_idx ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query("ALTER TABLE {$tickets_table} ADD KEY pending_delete_at (pending_delete_at)");
		}

		update_option( 'pnpc_psd_db_version', '1.4.0' );
	}

	/**
	 * Add activity/unread tracking columns and backfill sane defaults.
	 *
	 * @since 1.5.0
	 */
	private static function upgrade_to_1_5_0() {
		global $wpdb;
		$tickets_table = $wpdb->prefix . 'pnpc_psd_tickets';

		$columns = array(
			'last_customer_activity_at' => "ALTER TABLE {$tickets_table} ADD COLUMN last_customer_activity_at datetime DEFAULT NULL",
			'last_staff_activity_at'    => "ALTER TABLE {$tickets_table} ADD COLUMN last_staff_activity_at datetime DEFAULT NULL",
			'last_customer_viewed_at'   => "ALTER TABLE {$tickets_table} ADD COLUMN last_customer_viewed_at datetime DEFAULT NULL",
			'last_staff_viewed_at'      => "ALTER TABLE {$tickets_table} ADD COLUMN last_staff_viewed_at datetime DEFAULT NULL",
		);

		foreach ( $columns as $col => $sql ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is safely constructed from $wpdb->prefix and hardcoded string
			$exists = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$tickets_table} LIKE %s", $col ) );
			if ( ! $exists ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Required for plugin activation and updates
				$wpdb->query( $sql );
			}
		}

		// Backfill: treat ticket creation as customer activity + customer viewed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is safely constructed from $wpdb->prefix and hardcoded string
		$wpdb->query( "UPDATE {$tickets_table} SET last_customer_activity_at = COALESCE(last_customer_activity_at, created_at)" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is safely constructed from $wpdb->prefix and hardcoded string
		$wpdb->query( "UPDATE {$tickets_table} SET last_customer_viewed_at = COALESCE(last_customer_viewed_at, created_at)" );

		// Default settings introduced in v1.1.0+.
		if ( false === get_option( 'pnpc_psd_max_attachment_mb', false ) ) {
			add_option( 'pnpc_psd_max_attachment_mb', 5 );
		}
		$bool_defaults = array(
			'pnpc_psd_notify_customer_on_create'      => 1,
			'pnpc_psd_notify_staff_on_create'         => 1,
			'pnpc_psd_notify_customer_on_staff_reply' => 1,
			'pnpc_psd_notify_staff_on_customer_reply' => 1,
			'pnpc_psd_notify_customer_on_close'       => 1,
		);
		foreach ( $bool_defaults as $key => $val ) {
			if ( false === get_option( $key, false ) ) {
				add_option( $key, $val );
			}
		}
		if ( false === get_option( 'pnpc_psd_notification_from_name', false ) ) {
			add_option( 'pnpc_psd_notification_from_name', '' );
		}
		if ( false === get_option( 'pnpc_psd_notification_from_email', false ) ) {
			add_option( 'pnpc_psd_notification_from_email', '' );
		}

		update_option( 'pnpc_psd_db_version', '1.5.0' );
	}

	/**
	 * Add audit log table + archiving column.
	 *
	 * @since 1.6.0
	 */
	private static function upgrade_to_1_6_0() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$tickets_table = $wpdb->prefix . 'pnpc_psd_tickets';
		$audit_table   = $wpdb->prefix . 'pnpc_psd_audit_log';

		// Add archived_at column if missing.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is safely constructed from $wpdb->prefix and hardcoded string
		$archived_exists = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$tickets_table} LIKE %s", 'archived_at' ) );
		if ( ! $archived_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Required for plugin activation and updates
			$wpdb->query( "ALTER TABLE {$tickets_table} ADD COLUMN archived_at datetime DEFAULT NULL AFTER deleted_at" );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Required for plugin activation and updates
			$wpdb->query( "ALTER TABLE {$tickets_table} ADD KEY archived_at (archived_at)" );
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$sql_audit = "CREATE TABLE {$audit_table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) UNSIGNED DEFAULT NULL,
			actor_id bigint(20) UNSIGNED DEFAULT NULL,
			action varchar(80) NOT NULL,
			context longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY ticket_id (ticket_id),
			KEY actor_id (actor_id),
			KEY action (action),
			KEY created_at (created_at)
		) {$charset_collate};";
		dbDelta( $sql_audit );

		update_option( 'pnpc_psd_db_version', '1.6.0' );
	}

	/**
	 * Update database to version 1.1.0 (trash system).
	 *
	 * @since 1.1.0
	 */
	private static function upgrade_to_1_1_0() {

		global $wpdb;

		// Add deleted_at column to tickets table if not exists.
		$tickets_table = $wpdb->prefix . 'pnpc_psd_tickets';
		
		// Verify table exists before attempting to alter it.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$tickets_table
			)
		);

		if ($table_exists) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is safely constructed from $wpdb->prefix and hardcoded string
			$column_exists = $wpdb->get_results(
				$wpdb->prepare(
					"SHOW COLUMNS FROM {$tickets_table} LIKE %s",
					'deleted_at'
				)
			);

			if ( empty( $column_exists ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query(
					"ALTER TABLE {$tickets_table} ADD COLUMN deleted_at datetime DEFAULT NULL AFTER updated_at, ADD KEY deleted_at (deleted_at)"
				);
			}
		}

		// Add deleted_at column to responses table if not exists.
		$responses_table = $wpdb->prefix . 'pnpc_psd_ticket_responses';
		
		// Verify table exists before attempting to alter it.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$responses_table
			)
		);

		if ($table_exists) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is safely constructed from $wpdb->prefix and hardcoded string
			$column_exists = $wpdb->get_results(
				$wpdb->prepare(
					"SHOW COLUMNS FROM {$responses_table} LIKE %s",
					'deleted_at'
				)
			);

			if ( empty( $column_exists ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query(
					"ALTER TABLE {$responses_table} ADD COLUMN deleted_at datetime DEFAULT NULL AFTER created_at, ADD KEY deleted_at (deleted_at)"
				);
			}
		}

		// Add deleted_at column to attachments table if not exists.
		$attachments_table = $wpdb->prefix . 'pnpc_psd_ticket_attachments';
		
		// Verify table exists before attempting to alter it.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$attachments_table
			)
		);

		if ($table_exists) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is safely constructed from $wpdb->prefix and hardcoded string
			$column_exists = $wpdb->get_results(
				$wpdb->prepare(
					"SHOW COLUMNS FROM {$attachments_table} LIKE %s",
					'deleted_at'
				)
			);

			if ( empty( $column_exists ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query(
					"ALTER TABLE {$attachments_table} ADD COLUMN deleted_at datetime DEFAULT NULL AFTER created_at, ADD KEY deleted_at (deleted_at)"
				);
			}
		}

		// Update DB version.
		update_option( 'pnpc_psd_db_version', '1.1.0' );
	}

	/**
	 * Update database to version 1.2.0 (delete reason tracking).
	 *
	 * @since 1.2.0
	 */
	private static function upgrade_to_1_2_0() {
		global $wpdb;

		// Add delete reason columns to tickets table.
		$tickets_table = $wpdb->prefix . 'pnpc_psd_tickets';

		// Verify table exists before attempting to alter it.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$tickets_table
			)
		);

		if ( $table_exists ) {
			// Check if delete_reason column exists.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is safely constructed from $wpdb->prefix and hardcoded string
			$column_exists = $wpdb->get_results(
				$wpdb->prepare(
					"SHOW COLUMNS FROM {$tickets_table} LIKE %s",
					'delete_reason'
				)
			);

			if ( empty( $column_exists ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query(
					"ALTER TABLE {$tickets_table} 
					ADD COLUMN delete_reason VARCHAR(50) DEFAULT NULL AFTER deleted_at,
					ADD COLUMN delete_reason_other TEXT DEFAULT NULL AFTER delete_reason,
					ADD COLUMN deleted_by BIGINT(20) UNSIGNED DEFAULT NULL AFTER delete_reason_other,
					ADD KEY delete_reason (delete_reason),
					ADD KEY deleted_by (deleted_by)"
				);
			}
		}

		// Create ticket meta table for deletion history.
		$meta_table       = $wpdb->prefix . 'pnpc_psd_ticket_meta';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$meta_table} (
			meta_id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			ticket_id BIGINT(20) UNSIGNED NOT NULL,
			meta_key VARCHAR(255) NOT NULL,
			meta_value LONGTEXT,
			KEY ticket_id (ticket_id),
			KEY meta_key (meta_key)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Update DB version.
		update_option( 'pnpc_psd_db_version', '1.2.0' );
	}

	/**
	 * Update database to version 1.3.0 (staff-created tickets tracking).
	 *
	 * @since 1.3.0
	 */
	private static function upgrade_to_1_3_0() {
		global $wpdb;

		$tickets_table = $wpdb->prefix . 'pnpc_psd_tickets';

		// Verify table exists before attempting to alter it.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$tickets_table
			)
		);

		if ( $table_exists ) {
			// Check if created_by_staff column exists.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is safely constructed from $wpdb->prefix and hardcoded string
			$column_exists = $wpdb->get_results(
				$wpdb->prepare(
					"SHOW COLUMNS FROM {$tickets_table} LIKE %s",
					'created_by_staff'
				)
			);

			if ( empty( $column_exists ) ) {
				// Note: Table name is safe here as it's constructed from $wpdb->prefix
				// which is a controlled WordPress constant.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query(
					"ALTER TABLE {$tickets_table} 
					ADD COLUMN created_by_staff BIGINT(20) UNSIGNED DEFAULT NULL AFTER assigned_to,
					ADD KEY created_by_staff (created_by_staff)"
				);
			}
		}

		// Update DB version.
		update_option( 'pnpc_psd_db_version', '1.3.0' );
	}

	/**
	 * Add custom roles and capabilities.
	 *
	 * @since 1.0.0
	 */
	private static function add_custom_roles() {
		// Add Service Desk Agent role.
		add_role(
			'pnpc_psd_agent',
			__( 'Service Desk Agent', 'pnpc-pocket-service-desk' ),
			array(
				'read'                        => true,
				'pnpc_psd_view_tickets'       => true,
				'pnpc_psd_respond_to_tickets' => true,
				'pnpc_psd_assign_tickets'     => true,
			)
		);

		// Add Service Desk Manager role (extension-controlled via filter).
		$enable_manager = (bool) apply_filters( 'pnpc_psd_enable_manager_role', false );
		if ( $enable_manager ) {
			add_role(
				'pnpc_psd_manager',
				__( 'Service Desk Manager', 'pnpc-pocket-service-desk' ),
				array(
					'read'                        => true,
					'pnpc_psd_view_tickets'       => true,
					'pnpc_psd_respond_to_tickets' => true,
					'pnpc_psd_assign_tickets'     => true,
					'pnpc_psd_delete_tickets'     => true,
					'pnpc_psd_manage_settings'    => true,
				)
			);
		}

		// Add capabilities to administrator.
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'pnpc_psd_view_tickets' );
			$admin_role->add_cap( 'pnpc_psd_respond_to_tickets' );
			$admin_role->add_cap( 'pnpc_psd_assign_tickets' );
			$admin_role->add_cap( 'pnpc_psd_delete_tickets' );
			$admin_role->add_cap( 'pnpc_psd_manage_settings' );
		}

		// Add ticket viewing capability to customers.
		$customer_role = get_role( 'customer' );
		if ( $customer_role ) {
			$customer_role->add_cap( 'pnpc_psd_create_tickets' );
			$customer_role->add_cap( 'pnpc_psd_view_own_tickets' );
		}

		// Add ticket viewing capability to subscribers.
		$subscriber_role = get_role( 'subscriber' );
		if ( $subscriber_role ) {
			$subscriber_role->add_cap( 'pnpc_psd_create_tickets' );
			$subscriber_role->add_cap( 'pnpc_psd_view_own_tickets' );
		}
	}


/**
 * Ensure custom roles and required caps are present (updates existing roles too).
 *
 * NOTE: add_role() does not update an existing role, so we must add_cap() when the role already exists.
 *
 * @return void
 */
public static function sync_custom_roles_caps() {
    // Define the canonical capabilities for our custom roles.
    $agent_caps = array(
        'read'                        => true,
        // Some sites restrict wp-admin access for non-admins by checking legacy caps like
        // 'edit_posts' or 'level_0' (instead of just 'read'). Grant these minimal legacy caps
        // to ensure agents can access the back end to service tickets without granting admin privileges.
        'edit_posts'                  => true,
        'level_0'                     => true,
        'pnpc_psd_view_tickets'       => true,
        'pnpc_psd_respond_to_tickets' => true,
        'pnpc_psd_assign_tickets'     => true,
    );

    $manager_caps = array(
        'read'                        => true,
        // Same rationale as agent role (see above).
        'edit_posts'                  => true,
        'level_0'                     => true,
        'pnpc_psd_view_tickets'       => true,
        'pnpc_psd_respond_to_tickets' => true,
        'pnpc_psd_assign_tickets'     => true,
        'pnpc_psd_delete_tickets'     => true,
        'pnpc_psd_manage_settings'    => true,
    );

    // Agent role: create if missing, otherwise ensure caps exist.
    $agent_role = get_role( 'pnpc_psd_agent' );
    if ( ! $agent_role ) {
        add_role(
            'pnpc_psd_agent',
            __( 'Service Desk Agent', 'pnpc-pocket-service-desk' ),
            $agent_caps
        );
    } else {
        foreach ( $agent_caps as $cap => $grant ) {
            if ( $grant ) {
                $agent_role->add_cap( $cap );
            }
        }
    }

    // Manager role: create if missing, otherwise ensure caps exist.
    $manager_role = get_role( 'pnpc_psd_manager' );
    if ( ! $manager_role ) {
        add_role(
            'pnpc_psd_manager',
            __( 'Service Desk Manager', 'pnpc-pocket-service-desk' ),
            $manager_caps
        );
    } else {
        foreach ( $manager_caps as $cap => $grant ) {
            if ( $grant ) {
                $manager_role->add_cap( $cap );
            }
        }
    }
}

}
