<?php
/**
 * Fired during plugin activation
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/includes
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
		add_option( 'pnpc_psd_db_version', '1.3.0' );
		add_option( 'pnpc_psd_ticket_counter', 1000 );

		// Run database migration for existing installations.
		self::maybe_upgrade_database();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Upgrade database schema for existing installations.
	 *
	 * @since 1.1.0
	 */
	public static function maybe_upgrade_database() {
		$current_db_version = get_option( 'pnpc_psd_db_version', '1.0.0' );

		// Upgrade to 1.1.0 if needed.
		if ( version_compare( $current_db_version, '1.1.0', '<' ) ) {
			self::upgrade_to_1_1_0();
		}

		// Upgrade to 1.2.0 if needed.
		if ( version_compare( $current_db_version, '1.2.0', '<' ) ) {
			self::upgrade_to_1_2_0();
		}

		// Upgrade to 1.3.0 if needed.
		if ( version_compare( $current_db_version, '1.3.0', '<' ) ) {
			self::upgrade_to_1_3_0();
		}

		// Upgrade to 1.4.0 if needed.
		if ( version_compare( $current_db_version, '1.4.0', '<' ) ) {
			self::upgrade_to_1_4_0();
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
	 * Upgrade database to version 1.4.0 (delete review queue).
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
	 * Upgrade database to version 1.1.0 (trash system).
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
	 * Upgrade database to version 1.2.0 (delete reason tracking).
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
	 * Upgrade database to version 1.3.0 (staff-created tickets tracking).
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

		// Add Service Desk Manager role (has all agent capabilities plus more).
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