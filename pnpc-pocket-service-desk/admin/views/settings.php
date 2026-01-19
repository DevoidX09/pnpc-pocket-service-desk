<?php

/**
 * Plugin settings page (admin)
 *
 * @package PNPC_Pocket_Service_Desk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Tabs: keep all fields in a single form to avoid partial-submission resets.
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'core'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selection.
$page_slug  = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'pnpc-service-desk-settings'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page slug.
$tabs       = array(
	'core'      => __( 'Core Setup', 'pnpc-pocket-service-desk' ),
	'experience'=> __( 'Experience', 'pnpc-pocket-service-desk' ),
	'customize' => __( 'Customize', 'pnpc-pocket-service-desk' ),
);
if ( ! isset( $tabs[ $active_tab ] ) ) {
	$active_tab = 'core';
}

?>
<div class="wrap pnpc-psd-settings">
	<h1><?php esc_html_e( 'PNPC Pocket Service Desk Settings', 'pnpc-pocket-service-desk' ); ?></h1>
	<?php if ( get_transient( 'pnpc_psd_agents_trimmed' ) ) : ?>
		<?php delete_transient( 'pnpc_psd_agents_trimmed' ); ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'Some enabled agents were automatically disabled to comply with your current plan limits. (Free supports up to 2 enabled agents.)', 'pnpc-pocket-service-desk' ); ?></p></div>
	<?php endif; ?>

	<h2 class="nav-tab-wrapper" style="margin-bottom: 16px;">
		<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
			<?php
			$href = add_query_arg(
				array(
					'page' => $page_slug,
					'tab'  => $tab_key,
				),
				admin_url( 'admin.php' )
			);
			?>
			<a href="<?php echo esc_url( $href ); ?>" class="nav-tab <?php echo ( $active_tab === $tab_key ) ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</h2>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'pnpc_psd_settings' );
		do_settings_sections( 'pnpc_psd_settings' );
		?>

		<?php
		// Helper: panel visibility.
		$panel_style = function( $tab ) use ( $active_tab ) {
			return ( $active_tab === $tab ) ? '' : 'display:none;';
		};
		?>

		<!-- =====================
		     TAB: CORE SETUP
		===================== -->
		<div class="pnpc-psd-settings-panel" id="pnpc-psd-tab-core" style="<?php echo esc_attr( $panel_style( 'core' ) ); ?>">

			<h2><?php esc_html_e( 'Notifications', 'pnpc-pocket-service-desk' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Control who receives emails and when. Per-agent overrides are configured in the Eligible Agents table below.', 'pnpc-pocket-service-desk' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="pnpc_psd_email_notifications"><?php esc_html_e( 'Global Staff Notification Email', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="email" name="pnpc_psd_email_notifications" id="pnpc_psd_email_notifications" value="<?php echo esc_attr( get_option( 'pnpc_psd_email_notifications', '' ) ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Optional. If set, staff notifications will also be sent here (in addition to the assigned agent).', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_notify_from_name"><?php esc_html_e( 'From Name', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="text" name="pnpc_psd_notify_from_name" id="pnpc_psd_notify_from_name" value="<?php echo esc_attr( get_option( 'pnpc_psd_notify_from_name', '' ) ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Optional. If empty, WordPress default mailer settings are used.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_notify_from_email"><?php esc_html_e( 'From Email', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="email" name="pnpc_psd_notify_from_email" id="pnpc_psd_notify_from_email" value="<?php echo esc_attr( get_option( 'pnpc_psd_notify_from_email', '' ) ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Optional. Use a domain email address that is authorized by your SMTP provider.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
			</table>

			<h3 style="margin-top:18px;"><?php esc_html_e( 'Notification Triggers', 'pnpc-pocket-service-desk' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Customer notifications', 'pnpc-pocket-service-desk' ); ?></th>
					<td>
						<label style="display:block;margin:2px 0;">
							<input type="hidden" name="pnpc_psd_notify_customer_on_create" value="0" />
							<input type="checkbox" name="pnpc_psd_notify_customer_on_create" value="1" <?php checked( 1, get_option( 'pnpc_psd_notify_customer_on_create', 1 ) ); ?> />
							<?php esc_html_e( 'On ticket created', 'pnpc-pocket-service-desk' ); ?>
						</label>
						<label style="display:block;margin:2px 0;">
							<input type="hidden" name="pnpc_psd_notify_customer_on_staff_reply" value="0" />
							<input type="checkbox" name="pnpc_psd_notify_customer_on_staff_reply" value="1" <?php checked( 1, get_option( 'pnpc_psd_notify_customer_on_staff_reply', 1 ) ); ?> />
							<?php esc_html_e( 'On staff reply', 'pnpc-pocket-service-desk' ); ?>
						</label>
						<label style="display:block;margin:2px 0;">
							<input type="hidden" name="pnpc_psd_notify_customer_on_close" value="0" />
							<input type="checkbox" name="pnpc_psd_notify_customer_on_close" value="1" <?php checked( 1, get_option( 'pnpc_psd_notify_customer_on_close', 1 ) ); ?> />
							<?php esc_html_e( 'On ticket closed', 'pnpc-pocket-service-desk' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Staff notifications', 'pnpc-pocket-service-desk' ); ?></th>
					<td>
						<label style="display:block;margin:2px 0;">
							<input type="hidden" name="pnpc_psd_notify_staff_on_create" value="0" />
							<input type="checkbox" name="pnpc_psd_notify_staff_on_create" value="1" <?php checked( 1, get_option( 'pnpc_psd_notify_staff_on_create', 1 ) ); ?> />
							<?php esc_html_e( 'On ticket created', 'pnpc-pocket-service-desk' ); ?>
						</label>
						<label style="display:block;margin:2px 0;">
							<input type="hidden" name="pnpc_psd_notify_staff_on_customer_reply" value="0" />
							<input type="checkbox" name="pnpc_psd_notify_staff_on_customer_reply" value="1" <?php checked( 1, get_option( 'pnpc_psd_notify_staff_on_customer_reply', 1 ) ); ?> />
							<?php esc_html_e( 'On customer reply', 'pnpc-pocket-service-desk' ); ?>
						</label>
					</td>
				</tr>
			</table>

			
			<h2><?php esc_html_e( 'Public Login', 'pnpc-pocket-service-desk' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Login prompt style', 'pnpc-pocket-service-desk' ); ?></th>
					<td>
						<?php $login_mode = (string) get_option( 'pnpc_psd_public_login_mode', 'inline' ); ?>
						<select name="pnpc_psd_public_login_mode">
							<option value="inline" <?php selected( $login_mode, 'inline' ); ?>><?php esc_html_e( 'Inline login form', 'pnpc-pocket-service-desk' ); ?></option>
							<option value="link" <?php selected( $login_mode, 'link' ); ?>><?php esc_html_e( 'Login button link', 'pnpc-pocket-service-desk' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Controls how the dashboard/create-ticket shortcodes prompt users when not logged in.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Custom login URL (optional)', 'pnpc-pocket-service-desk' ); ?></th>
					<td>
						<?php $login_url = (string) get_option( 'pnpc_psd_public_login_url', '' ); ?>
						<input type="url" name="pnpc_psd_public_login_url" value="<?php echo esc_attr( $login_url ); ?>" class="regular-text" placeholder="<?php echo esc_attr( wp_login_url() ); ?>" />
						<p class="description"><?php esc_html_e( 'If set, the “Login button link” mode will use this URL and append redirect_to back to the service desk page.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
			</table>

<h2><?php esc_html_e( 'Attachments', 'pnpc-pocket-service-desk' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="pnpc_psd_max_attachment_mb"><?php esc_html_e( 'Max attachment size (MB)', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
					<?php $effective_mb = function_exists( 'pnpc_psd_get_max_attachment_mb' ) ? (int) pnpc_psd_get_max_attachment_mb() : (int) get_option( 'pnpc_psd_max_attachment_mb', 5 ); ?>
					<input type="number" min="1" step="1" name="pnpc_psd_max_attachment_mb" id="pnpc_psd_max_attachment_mb" value="<?php echo esc_attr( $effective_mb ); ?>" class="small-text" />
						<p class="description">
							<?php
							$free_cap = 5;
							$pro_cap  = 20;
							printf(
								esc_html__( 'Recommended: %1$dMB for Free and up to %2$dMB for Pro. Your plan may clamp this value automatically.', 'pnpc-pocket-service-desk' ),
								(int) $free_cap,
								(int) $pro_cap
							);
							?>
						</p>
					</td>
				</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Allowed attachment formats', 'pnpc-pocket-service-desk' ); ?></th>
						<td>
							<?php
							$raw_allowed = get_option( 'pnpc_psd_allowed_file_types', '' );
							$raw_allowed = is_string( $raw_allowed ) ? trim( $raw_allowed ) : '';
							$defaults_ext = array( 'jpg','jpeg','png','gif','webp','pdf','txt','csv','doc','docx','xls','xlsx','zip' );
							$parts = $raw_allowed ? preg_split( '/[\s,;]+/', $raw_allowed ) : $defaults_ext;
							$parts = is_array( $parts ) ? array_filter( array_map( 'strtolower', array_map( 'trim', $parts ) ) ) : $defaults_ext;
							// Map common MIME defaults to extensions for display (backwards compatible with older installs).
							$mime_map = array(
								'image/jpeg' => array('jpg','jpeg','jpe'),
								'image/png'  => array('png'),
								'image/gif'  => array('gif'),
								'image/webp' => array('webp'),
								'application/pdf' => array('pdf'),
							);
							$selected = array();
							foreach ( (array) $parts as $it ) {
								$it = strtolower( trim( (string) $it ) );
								if ( '' === $it ) { continue; }
								if ( false !== strpos( $it, '/' ) && isset( $mime_map[ $it ] ) ) {
									$selected = array_merge( $selected, (array) $mime_map[ $it ] );
								} else {
									$selected[] = $it;
								}
							}
							$selected = array_values( array_unique( array_filter( $selected ) ) );
							$common = array(
								'jpg'  => __( 'JPG images', 'pnpc-pocket-service-desk' ),
								'jpeg' => __( 'JPEG images', 'pnpc-pocket-service-desk' ),
								'png'  => __( 'PNG images', 'pnpc-pocket-service-desk' ),
								'gif'  => __( 'GIF images', 'pnpc-pocket-service-desk' ),
								'webp' => __( 'WebP images', 'pnpc-pocket-service-desk' ),
								'pdf'  => __( 'PDF documents', 'pnpc-pocket-service-desk' ),
								'txt'  => __( 'Text (.txt)', 'pnpc-pocket-service-desk' ),
								'csv'  => __( 'CSV (.csv)', 'pnpc-pocket-service-desk' ),
								'doc'  => __( 'Word (.doc)', 'pnpc-pocket-service-desk' ),
								'docx' => __( 'Word (.docx)', 'pnpc-pocket-service-desk' ),
								'xls'  => __( 'Excel (.xls)', 'pnpc-pocket-service-desk' ),
								'xlsx' => __( 'Excel (.xlsx)', 'pnpc-pocket-service-desk' ),
								'zip'  => __( 'ZIP archives', 'pnpc-pocket-service-desk' ),
							);
							?>
							<fieldset>
								<?php foreach ( $common as $ext => $label ) : ?>
									<label style="display:inline-block;min-width:180px;margin:2px 10px 2px 0;">
										<input type="checkbox" name="pnpc_psd_allowed_file_types[]" value="<?php echo esc_attr( $ext ); ?>" <?php checked( in_array( $ext, $selected, true ) ); ?> />
										<?php echo esc_html( $label ); ?>
									</label>
								<?php endforeach; ?>
							</fieldset>
							<p class="description"><?php esc_html_e( 'These defaults cover common WordPress uploads. Your server and security plugins may further restrict uploads.', 'pnpc-pocket-service-desk' ); ?></p>
						</td>
					</tr>
			</table>

			<h2><?php esc_html_e( 'Ticket Assignment', 'pnpc-pocket-service-desk' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Configure default assignment behavior for newly created tickets.', 'pnpc-pocket-service-desk' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="pnpc_psd_default_agent_user_id"><?php esc_html_e( 'Default Agent', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<?php
						$default_agent_id = absint( get_option( 'pnpc_psd_default_agent_user_id', 0 ) );
						$staff_users      = get_users(
							array(
								'role__in' => ( ( function_exists( 'pnpc_psd_enable_manager_role' ) && pnpc_psd_enable_manager_role() ) ? array( 'administrator', 'pnpc_psd_manager', 'pnpc_psd_agent' ) : array( 'administrator', 'pnpc_psd_agent' ) ),
								'orderby'  => 'display_name',
								'order'    => 'ASC',
							)
						);
						$assignable_users = function_exists( 'pnpc_psd_get_assignable_agents' ) ? pnpc_psd_get_assignable_agents() : $staff_users;
						?>
						<select name="pnpc_psd_default_agent_user_id" id="pnpc_psd_default_agent_user_id">
							<option value="0" <?php selected( 0, $default_agent_id ); ?>><?php esc_html_e( '— No default (unassigned) —', 'pnpc-pocket-service-desk' ); ?></option>
							<?php foreach ( $assignable_users as $staff ) : ?>
								<option value="<?php echo esc_attr( (int) $staff->ID ); ?>" <?php selected( (int) $staff->ID, $default_agent_id ); ?>>
									<?php echo esc_html( $staff->display_name . ' (#' . (int) $staff->ID . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'If set, new tickets will be automatically assigned to this staff user unless an assignee is explicitly chosen elsewhere.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
			</table>

			<h3 style="margin-top:18px;"><?php esc_html_e( 'Eligible Agents', 'pnpc-pocket-service-desk' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Select which internal users can be assigned tickets. Optionally override the notification email per agent. If you do not configure agents here, all staff roles (Admin/Manager/Agent) remain assignable (backwards compatible).', 'pnpc-pocket-service-desk' ); ?></p>
			<?php if ( function_exists( 'pnpc_psd_get_max_agents_limit' ) ) : ?>
				<?php $agent_limit = (int) pnpc_psd_get_max_agents_limit(); ?>
				<p class="description" style="margin-top:4px;">
					<?php
					if ( $agent_limit > 0 ) {
						printf( esc_html__( 'Plan limit: up to %d enabled agents (Free). Extra enabled users will be automatically disabled on save.', 'pnpc-pocket-service-desk' ), (int) $agent_limit );
					} else {
						esc_html_e( 'Plan limit: unlimited enabled agents (Pro).', 'pnpc-pocket-service-desk' );
					}
					?>
				</p>
			<?php endif; ?>

			<?php
			$agents_cfg         = get_option( 'pnpc_psd_agents', array() );
			$agents_cfg         = is_array( $agents_cfg ) ? $agents_cfg : array();
			$has_any_cfg        = ! empty( $agents_cfg );
			?>

			<div style="margin: 10px 0 6px;">
				<button type="button" class="button" id="pnpc-psd-enable-all-agents"><?php esc_html_e( 'Enable all', 'pnpc-pocket-service-desk' ); ?></button>
				<button type="button" class="button" id="pnpc-psd-disable-all-agents"><?php esc_html_e( 'Disable all', 'pnpc-pocket-service-desk' ); ?></button>
				<span class="description" style="margin-left:8px;"><?php esc_html_e( 'These buttons only toggle the checkboxes on this screen—click Save Changes to persist.', 'pnpc-pocket-service-desk' ); ?></span>
			</div>

			<table class="widefat striped" style="max-width: 900px;">
				<thead>
					<tr>
						<th style="width:120px;"><?php esc_html_e( 'Enable', 'pnpc-pocket-service-desk' ); ?></th>
						<th><?php esc_html_e( 'User', 'pnpc-pocket-service-desk' ); ?></th>
						<th><?php esc_html_e( 'Role(s)', 'pnpc-pocket-service-desk' ); ?></th>
						<th><?php esc_html_e( 'Notification Email Override', 'pnpc-pocket-service-desk' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $staff_users as $staff ) : ?>
						<?php
							$uid      = (int) $staff->ID;
							$row      = isset( $agents_cfg[ $uid ] ) && is_array( $agents_cfg[ $uid ] ) ? $agents_cfg[ $uid ] : array();
							$enabled  = $has_any_cfg ? ( ! empty( $row['enabled'] ) ) : true;
							$notify_e = isset( $row['notify_email'] ) ? (string) $row['notify_email'] : '';
							$roles    = ! empty( $staff->roles ) ? implode( ', ', array_map( 'sanitize_text_field', (array) $staff->roles ) ) : '';
						?>
						<tr>
							<td>
								<input type="hidden" name="pnpc_psd_agents[<?php echo esc_attr( $uid ); ?>][enabled]" value="0" />
								<label>
									<input class="pnpc-psd-agent-enabled" type="checkbox" name="pnpc_psd_agents[<?php echo esc_attr( $uid ); ?>][enabled]" value="1" <?php checked( true, (bool) $enabled ); ?> />
									<?php esc_html_e( 'Enabled', 'pnpc-pocket-service-desk' ); ?>
								</label>
							</td>
							<td>
								<strong><?php echo esc_html( $staff->display_name ); ?></strong>
								<br><span class="description"><?php echo esc_html( $staff->user_email ); ?><?php echo esc_html( sprintf( /* translators: %d is the WordPress user ID. */ __( ' (#%d)', 'pnpc-pocket-service-desk' ), (int) $uid ) ); ?></span>
							</td>
							<td><?php echo esc_html( $roles ); ?></td>
							<td>
								<input type="email" name="pnpc_psd_agents[<?php echo esc_attr( $uid ); ?>][notify_email]" value="<?php echo esc_attr( $notify_e ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $staff->user_email ); ?>" />
								<p class="description" style="margin:4px 0 0;">
									<?php esc_html_e( 'Optional. Leave blank to use the user\'s account email.', 'pnpc-pocket-service-desk' ); ?>
								</p>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Logout Redirect', 'pnpc-pocket-service-desk' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Controls where users are sent after logging out via the Profile Settings shortcode.', 'pnpc-pocket-service-desk' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="pnpc_psd_logout_redirect_page_id"><?php esc_html_e( 'Logout Redirect', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<?php $logout_page_id = absint( get_option( 'pnpc_psd_logout_redirect_page_id', 0 ) ); ?>
						<select id="pnpc_psd_logout_redirect_page_id" name="pnpc_psd_logout_redirect_page_id">
							<option value="0" <?php selected( 0, $logout_page_id ); ?>><?php esc_html_e( 'Home Page', 'pnpc-pocket-service-desk' ); ?></option>
							<?php
							$pages = get_pages( array( 'post_status' => 'publish' ) );
							foreach ( $pages as $p ) {
								printf(
									'<option value="%d" %s>%s</option>',
									absint( $p->ID ),
									selected( absint( $p->ID ), $logout_page_id, false ),
									esc_html( $p->post_title )
								);
							}
							?>
						</select>
						<p class="description"><?php esc_html_e( 'Where users are sent after clicking Logout in the Profile Settings screen.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Data Retention', 'pnpc-pocket-service-desk' ); ?></h2>
			<p class="description"><?php esc_html_e( 'By default, settings and profile uploads are preserved across uninstall/reinstall. Enable this only if you want to permanently remove plugin data when uninstalling.', 'pnpc-pocket-service-desk' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Delete data on uninstall', 'pnpc-pocket-service-desk' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="pnpc_psd_delete_data_on_uninstall" value="0" />
							<input type="checkbox" name="pnpc_psd_delete_data_on_uninstall" value="1" <?php checked( 1, get_option( 'pnpc_psd_delete_data_on_uninstall', 0 ) ); ?> />
							<?php esc_html_e( 'Permanently delete plugin settings, ticket data, and profile uploads when uninstalling the plugin.', 'pnpc-pocket-service-desk' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Recommended OFF. Turn ON only for full cleanup when removing the plugin from a site.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
			</table>

		</div>

		<!-- =====================
		     TAB: EXPERIENCE
		===================== -->
		<div class="pnpc-psd-settings-panel" id="pnpc-psd-tab-experience" style="<?php echo esc_attr( $panel_style( 'experience' ) ); ?>">

			<h2><?php esc_html_e( 'Welcome Messages', 'pnpc-pocket-service-desk' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Control optional welcome headings shown in public shortcodes. Disable these if you are providing your own headings via a page builder.', 'pnpc-pocket-service-desk' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Profile Settings', 'pnpc-pocket-service-desk' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="pnpc_psd_show_welcome_profile" value="0" />
							<input type="checkbox" name="pnpc_psd_show_welcome_profile" value="1" <?php checked( 1, get_option( 'pnpc_psd_show_welcome_profile', 1 ) ); ?> />
							<?php esc_html_e( 'Show welcome message in [pnpc_profile_settings].', 'pnpc-pocket-service-desk' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Service Desk', 'pnpc-pocket-service-desk' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="pnpc_psd_show_welcome_service_desk" value="0" />
							<input type="checkbox" name="pnpc_psd_show_welcome_service_desk" value="1" <?php checked( 1, get_option( 'pnpc_psd_show_welcome_service_desk', 1 ) ); ?> />
							<?php esc_html_e( 'Show welcome message in [pnpc_service_desk].', 'pnpc-pocket-service-desk' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Real-Time Updates', 'pnpc-pocket-service-desk' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Configure automatic ticket list updates and menu badge notifications.', 'pnpc-pocket-service-desk' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Menu Badge', 'pnpc-pocket-service-desk' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="pnpc_psd_enable_menu_badge" value="0" />
							<input type="checkbox" name="pnpc_psd_enable_menu_badge" value="1" <?php checked( 1, get_option( 'pnpc_psd_enable_menu_badge', 1 ) ); ?> />
							<?php esc_html_e( 'Show ticket count badge in admin menu.', 'pnpc-pocket-service-desk' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Displays a counter of open and in-progress tickets in the admin sidebar.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_menu_badge_interval"><?php esc_html_e( 'Menu Badge Update Interval', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<select name="pnpc_psd_menu_badge_interval" id="pnpc_psd_menu_badge_interval">
							<option value="15" <?php selected( 15, get_option( 'pnpc_psd_menu_badge_interval', 30 ) ); ?>>15 <?php esc_html_e( 'seconds', 'pnpc-pocket-service-desk' ); ?></option>
							<option value="30" <?php selected( 30, get_option( 'pnpc_psd_menu_badge_interval', 30 ) ); ?>>30 <?php esc_html_e( 'seconds', 'pnpc-pocket-service-desk' ); ?></option>
							<option value="60" <?php selected( 60, get_option( 'pnpc_psd_menu_badge_interval', 30 ) ); ?>>60 <?php esc_html_e( 'seconds', 'pnpc-pocket-service-desk' ); ?></option>
							<option value="120" <?php selected( 120, get_option( 'pnpc_psd_menu_badge_interval', 30 ) ); ?>>2 <?php esc_html_e( 'minutes', 'pnpc-pocket-service-desk' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'How often to check for new tickets and update the menu badge.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Auto-Refresh', 'pnpc-pocket-service-desk' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="pnpc_psd_enable_auto_refresh" value="0" />
							<input type="checkbox" name="pnpc_psd_enable_auto_refresh" value="1" <?php checked( 1, get_option( 'pnpc_psd_enable_auto_refresh', 1 ) ); ?> />
							<?php esc_html_e( 'Automatically refresh ticket lists.', 'pnpc-pocket-service-desk' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Keeps ticket lists up to date without a full page reload.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_auto_refresh_interval"><?php esc_html_e( 'Auto-Refresh Interval', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<select name="pnpc_psd_auto_refresh_interval" id="pnpc_psd_auto_refresh_interval">
							<option value="15" <?php selected( 15, get_option( 'pnpc_psd_auto_refresh_interval', 30 ) ); ?>>15 <?php esc_html_e( 'seconds', 'pnpc-pocket-service-desk' ); ?></option>
							<option value="30" <?php selected( 30, get_option( 'pnpc_psd_auto_refresh_interval', 30 ) ); ?>>30 <?php esc_html_e( 'seconds', 'pnpc-pocket-service-desk' ); ?></option>
							<option value="60" <?php selected( 60, get_option( 'pnpc_psd_auto_refresh_interval', 30 ) ); ?>>60 <?php esc_html_e( 'seconds', 'pnpc-pocket-service-desk' ); ?></option>
							<option value="120" <?php selected( 120, get_option( 'pnpc_psd_auto_refresh_interval', 30 ) ); ?>>2 <?php esc_html_e( 'minutes', 'pnpc-pocket-service-desk' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'How often to refresh ticket lists.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_tickets_per_page"><?php esc_html_e( 'Tickets Per Page', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<select id="pnpc_psd_tickets_per_page" name="pnpc_psd_tickets_per_page">
							<option value="10" <?php selected( get_option( 'pnpc_psd_tickets_per_page', 20 ), '10' ); ?>>10</option>
							<option value="15" <?php selected( get_option( 'pnpc_psd_tickets_per_page', 20 ), '15' ); ?>>15</option>
							<option value="20" <?php selected( get_option( 'pnpc_psd_tickets_per_page', 20 ), '20' ); ?>>20</option>
							<option value="25" <?php selected( get_option( 'pnpc_psd_tickets_per_page', 20 ), '25' ); ?>>25</option>
							<option value="50" <?php selected( get_option( 'pnpc_psd_tickets_per_page', 20 ), '50' ); ?>>50</option>
						</select>
						<p class="description"><?php esc_html_e( 'Number of tickets to display per page in the ticket list.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Product / Services Display', 'pnpc-pocket-service-desk' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Controls which products are shown in the Services area (when WooCommerce is active).', 'pnpc-pocket-service-desk' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Display Public Products', 'pnpc-pocket-service-desk' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="pnpc_psd_show_products" value="0" />
							<input type="checkbox" name="pnpc_psd_show_products" value="1" <?php checked( 1, get_option( 'pnpc_psd_show_products', 1 ) ); ?> />
							<?php esc_html_e( 'Show a public product catalog in the Services block (free feature).', 'pnpc-pocket-service-desk' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'If enabled, the Services shortcode will show general published products to viewers (unless user-specific products are enabled).', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<?php if ( function_exists( 'pnpc_psd_is_pro_active' ) && pnpc_psd_is_pro_active() ) : ?>
<tr>
					<th scope="row"><?php esc_html_e( 'Enable User-specific Products (Pro)', 'pnpc-pocket-service-desk' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="pnpc_psd_user_specific_products" value="0" />
							<input type="checkbox" name="pnpc_psd_user_specific_products" value="1" <?php checked( 1, get_option( 'pnpc_psd_user_specific_products', 0 ) ); ?> />
							<?php esc_html_e( 'Restrict product listings to products allocated to an individual user (pro feature).', 'pnpc-pocket-service-desk' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'When enabled, the Services block will show only products explicitly allocated to the viewing user (user-specific takes precedence).', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
<?php endif; ?>
			</table>

		</div>

		<!-- =====================
		     TAB: CUSTOMIZE
		===================== -->
		<div class="pnpc-psd-settings-panel" id="pnpc-psd-tab-customize" style="<?php echo esc_attr( $panel_style( 'customize' ) ); ?>">
			<h2><?php esc_html_e( 'Colors & Buttons', 'pnpc-pocket-service-desk' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Customize colors used across shortcodes and cards. Values are stored as hex colors (e.g., #2b9f6a).', 'pnpc-pocket-service-desk' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="pnpc_psd_primary_button_color"><?php esc_html_e( 'Edit Profile Button color', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_primary_button_color" name="pnpc_psd_primary_button_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_primary_button_color', '#2b9f6a' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Used for the Edit Profile button and other primary actions.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_primary_button_hover_color"><?php esc_html_e( 'Edit Profile Button hover color', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_primary_button_hover_color" name="pnpc_psd_primary_button_hover_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_primary_button_hover_color', '#238a56' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Hover state for the Edit Profile button.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_logout_button_color"><?php esc_html_e( 'Logout Button color', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_logout_button_color" name="pnpc_psd_logout_button_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_logout_button_color', '#dc3545' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Used for the Logout button shown in Profile Settings.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_logout_button_hover_color"><?php esc_html_e( 'Logout Button hover color', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_logout_button_hover_color" name="pnpc_psd_logout_button_hover_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_logout_button_hover_color', '#b02a37' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Hover state for the Logout button.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_secondary_button_color"><?php esc_html_e( 'Create Ticket Button color', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_secondary_button_color" name="pnpc_psd_secondary_button_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_secondary_button_color', '#6c757d' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Used for the Create Ticket button in the Service Desk.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_secondary_button_hover_color"><?php esc_html_e( 'Create Ticket Button hover color', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_secondary_button_hover_color" name="pnpc_psd_secondary_button_hover_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_secondary_button_hover_color', '#5a6268' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Hover state for the Create Ticket button.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="pnpc_psd_card_bg_color"><?php esc_html_e( 'Service Card background', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_card_bg_color" name="pnpc_psd_card_bg_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_card_bg_color', '#ffffff' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Default background color for service/product cards.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_card_bg_hover_color"><?php esc_html_e( 'Service Card hover background', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_card_bg_hover_color" name="pnpc_psd_card_bg_hover_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_card_bg_hover_color', '#f7f9fb' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Hover background color for service/product cards.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_card_title_color"><?php esc_html_e( 'Service Card title color', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_card_title_color" name="pnpc_psd_card_title_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_card_title_color', '#2271b1' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Color used for service/product card titles.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_card_title_hover_color"><?php esc_html_e( 'Service Card title hover color', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_card_title_hover_color" name="pnpc_psd_card_title_hover_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_card_title_hover_color', '#135e96' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Hover color for service/product card titles.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="pnpc_psd_card_button_color"><?php esc_html_e( 'Service Card button color', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_card_button_color" name="pnpc_psd_card_button_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_card_button_color', '#2b9f6a' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Button color inside service/product cards.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_card_button_hover_color"><?php esc_html_e( 'Service Card button hover color', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_card_button_hover_color" name="pnpc_psd_card_button_hover_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_card_button_hover_color', '#238a56' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Hover color for buttons inside service/product cards.', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="pnpc_psd_my_tickets_card_bg_color"><?php esc_html_e( 'My Tickets card background', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_my_tickets_card_bg_color" name="pnpc_psd_my_tickets_card_bg_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_my_tickets_card_bg_color', '#ffffff' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Background for ticket cards in [pnpc_my_tickets].', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_my_tickets_card_bg_hover_color"><?php esc_html_e( 'My Tickets card hover background', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_my_tickets_card_bg_hover_color" name="pnpc_psd_my_tickets_card_bg_hover_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_my_tickets_card_bg_hover_color', '#f7f9fb' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Hover background for ticket cards in [pnpc_my_tickets].', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_my_tickets_view_button_color"><?php esc_html_e( 'My Tickets View Details button', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_my_tickets_view_button_color" name="pnpc_psd_my_tickets_view_button_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_my_tickets_view_button_color', '#2b9f6a' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Button color for the View Details action in [pnpc_my_tickets].', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pnpc_psd_my_tickets_view_button_hover_color"><?php esc_html_e( 'My Tickets View Details hover', 'pnpc-pocket-service-desk' ); ?></label></th>
					<td>
						<input type="color" id="pnpc_psd_my_tickets_view_button_hover_color" name="pnpc_psd_my_tickets_view_button_hover_color" value="<?php echo esc_attr( get_option( 'pnpc_psd_my_tickets_view_button_hover_color', '#238a56' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Hover color for the View Details button in [pnpc_my_tickets].', 'pnpc-pocket-service-desk' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button(); ?>
	</form>
</div>
