<?php
/**
 * Setup Wizard admin view.
 *
 * @var string              $step
 * @var string              $path
 * @var array<string,mixed> $snapshot
 * @var int                 $dashboard_page_id
 * @var WP_Post|null        $dashboard_page
 * @var string              $editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$step = (string) $step;
$path = (string) $path;

if ( 'done' === $step ) {
	$step = 'complete';
}

$error = (string) get_option( 'pnpc_psd_setup_error', '' );
if ( ! empty( $error ) ) {
	delete_option( 'pnpc_psd_setup_error' );
}

$canonical_shortcodes = array(
	array(
		'code'        => '[pnpc_profile_settings]',
		'title'       => __( 'Profile Settings', 'pnpc-pocket-service-desk' ),
		'description' => __( 'Lets customers review and update their basic support profile details.', 'pnpc-pocket-service-desk' ),
	),
	array(
		'code'        => '[pnpc_service_desk]',
		'title'       => __( 'Service Desk Wrapper', 'pnpc-pocket-service-desk' ),
		'description' => __( 'Primary dashboard wrapper for the customer-facing service desk area.', 'pnpc-pocket-service-desk' ),
	),
	array(
		'code'        => '[pnpc_create_ticket]',
		'title'       => __( 'Create Ticket', 'pnpc-pocket-service-desk' ),
		'description' => __( 'Displays the customer ticket submission form.', 'pnpc-pocket-service-desk' ),
	),
	array(
		'code'        => '[pnpc_services]',
		'title'       => __( 'Services Area', 'pnpc-pocket-service-desk' ),
		'description' => __( 'Reserved services area for connected workflows and future extensions.', 'pnpc-pocket-service-desk' ),
	),
	array(
		'code'        => '[pnpc_my_tickets]',
		'title'       => __( 'My Tickets', 'pnpc-pocket-service-desk' ),
		'description' => __( 'Shows customers their submitted tickets and current statuses.', 'pnpc-pocket-service-desk' ),
	),
	array(
		'code'        => '[pnpc_ticket_detail]',
		'title'       => __( 'Ticket Detail', 'pnpc-pocket-service-desk' ),
		'description' => __( 'Displays the single-ticket conversation view.', 'pnpc-pocket-service-desk' ),
	),
);

$canonical = "";
foreach ( $canonical_shortcodes as $shortcode ) {
	$canonical .= $shortcode['code'] . "\n\n";
}

/**
 * Render setup progress.
 *
 * @param string $active_path  Active wizard path.
 * @param string $current_step Current wizard step.
 * @return void
 */
function pnpc_psd_render_setup_progress( $active_path, $current_step ) {
	$active_path  = (string) $active_path;
	$current_step = (string) $current_step;
	$steps        = array(
		'landing'    => __( 'Choose', 'pnpc-pocket-service-desk' ),
		'builder'    => __( 'Create Pages', 'pnpc-pocket-service-desk' ),
		'shortcodes' => __( 'Reference', 'pnpc-pocket-service-desk' ),
		'complete'   => __( 'Ready', 'pnpc-pocket-service-desk' ),
	);

	if ( 'existing' === $active_path ) {
		$steps = array(
			'landing'         => __( 'Choose', 'pnpc-pocket-service-desk' ),
			'choose_existing' => __( 'Select Page', 'pnpc-pocket-service-desk' ),
			'shortcodes'      => __( 'Reference', 'pnpc-pocket-service-desk' ),
			'complete'        => __( 'Ready', 'pnpc-pocket-service-desk' ),
		);
	}

	if ( 'custom' === $active_path ) {
		$steps = array(
			'landing'    => __( 'Choose', 'pnpc-pocket-service-desk' ),
			'builder'    => __( 'Custom', 'pnpc-pocket-service-desk' ),
			'shortcodes' => __( 'Reference', 'pnpc-pocket-service-desk' ),
			'complete'   => __( 'Ready', 'pnpc-pocket-service-desk' ),
		);
	}

	$keys    = array_keys( $steps );
	$current = array_search( $current_step, $keys, true );
	$current = ( false === $current ) ? 0 : (int) $current;
	?>
	<ol class="pnpc-psd-progress-bar">
		<?php foreach ( $steps as $key => $label ) : ?>
			<?php
			$index = (int) array_search( $key, $keys, true );
			$class = '';
			if ( $index < $current ) {
				$class = 'completed';
			} elseif ( $index === $current ) {
				$class = 'active';
			}
			?>
			<li class="pnpc-psd-progress-step <?php echo esc_attr( $class ); ?>">
				<span class="step-circle"><?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
				<span class="step-label"><?php echo esc_html( $label ); ?></span>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php
}

$dash_configured = ! empty( $snapshot['dashboard_configured'] );
$ticket_count    = isset( $snapshot['ticket_count'] ) ? absint( $snapshot['ticket_count'] ) : 0;
$ticket_view_ok  = ! empty( $snapshot['ticket_view_configured'] );

$dashboard_url = '';
if ( $dashboard_page_id > 0 && 'trash' !== get_post_status( $dashboard_page_id ) ) {
	$dashboard_url = get_permalink( $dashboard_page_id );
}
?>

<div class="wrap pnpc-psd-setup-wizard pnpc-psd-wizard-container">
	<div class="pnpc-psd-welcome-hero">
		<div class="pnpc-psd-hero-kicker"><?php echo esc_html__( 'PNPC Pocket Service Desk', 'pnpc-pocket-service-desk' ); ?></div>
		<h1><?php echo esc_html__( 'Set up customer support inside WordPress.', 'pnpc-pocket-service-desk' ); ?></h1>
		<p><?php echo esc_html__( 'Choose your page approach, create or connect the public dashboard, confirm the shortcodes, and start accepting tickets.', 'pnpc-pocket-service-desk' ); ?></p>
	</div>

	<?php pnpc_psd_render_setup_progress( $path, $step ); ?>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( 'landing' === $step ) : ?>
		<div class="pnpc-psd-layout-grid">
			<div class="pnpc-psd-setup-card pnpc-psd-main-card">
				<h2><?php echo esc_html__( 'How do you want to build your customer dashboard?', 'pnpc-pocket-service-desk' ); ?></h2>
				<p><?php echo esc_html__( 'Most sites can use the automatic page builder. Advanced teams can connect an existing page or build a custom layout manually.', 'pnpc-pocket-service-desk' ); ?></p>

				<div class="pnpc-psd-builder-grid">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=landing&path=builder' ) ); ?>" class="pnpc-psd-builder-card-form">
						<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>
						<input type="hidden" name="mode" value="begin_install" />
						<button type="submit" class="pnpc-psd-builder-card pnpc-psd-card-button">
							<span class="badge badge-recommended"><?php echo esc_html__( 'Recommended', 'pnpc-pocket-service-desk' ); ?></span>
							<span class="dashicons dashicons-admin-page builder-icon" aria-hidden="true"></span>
							<span class="pnpc-psd-card-title"><?php echo esc_html__( 'Create pages for me', 'pnpc-pocket-service-desk' ); ?></span>
							<span class="pnpc-psd-card-copy"><?php echo esc_html__( 'Create the starter dashboard and ticket view pages with the correct shortcodes.', 'pnpc-pocket-service-desk' ); ?></span>
						</button>
					</form>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=landing&path=existing' ) ); ?>" class="pnpc-psd-builder-card-form">
						<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>
						<input type="hidden" name="mode" value="use_existing" />
						<button type="submit" class="pnpc-psd-builder-card pnpc-psd-card-button">
							<span class="dashicons dashicons-admin-links builder-icon" aria-hidden="true"></span>
							<span class="pnpc-psd-card-title"><?php echo esc_html__( 'Use an existing page', 'pnpc-pocket-service-desk' ); ?></span>
							<span class="pnpc-psd-card-copy"><?php echo esc_html__( 'Connect a page you already created and review the required shortcodes.', 'pnpc-pocket-service-desk' ); ?></span>
						</button>
					</form>
				</div>
			</div>

			<aside class="pnpc-psd-setup-card pnpc-psd-status-card">
				<h2><?php echo esc_html__( 'Setup status', 'pnpc-pocket-service-desk' ); ?></h2>
				<ul class="pnpc-psd-check-list">
					<li class="<?php echo $dash_configured ? 'is-good' : 'is-warn'; ?>">
						<span class="dashicons <?php echo $dash_configured ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" aria-hidden="true"></span>
						<strong><?php echo esc_html__( 'Dashboard', 'pnpc-pocket-service-desk' ); ?></strong>
						<span><?php echo $dash_configured ? esc_html__( 'Connected', 'pnpc-pocket-service-desk' ) : esc_html__( 'Not configured yet', 'pnpc-pocket-service-desk' ); ?></span>
					</li>
					<li class="<?php echo $ticket_view_ok ? 'is-good' : 'is-neutral'; ?>">
						<span class="dashicons <?php echo $ticket_view_ok ? 'dashicons-yes-alt' : 'dashicons-admin-page'; ?>" aria-hidden="true"></span>
						<strong><?php echo esc_html__( 'Ticket view', 'pnpc-pocket-service-desk' ); ?></strong>
						<span><?php echo $ticket_view_ok ? esc_html__( 'Ready', 'pnpc-pocket-service-desk' ) : esc_html__( 'Created at finish', 'pnpc-pocket-service-desk' ); ?></span>
					</li>
					<li class="is-neutral">
						<span class="dashicons dashicons-tickets-alt" aria-hidden="true"></span>
						<strong><?php echo esc_html__( 'Tickets', 'pnpc-pocket-service-desk' ); ?></strong>
						<span><?php echo esc_html( sprintf( _n( '%d ticket detected', '%d tickets detected', $ticket_count, 'pnpc-pocket-service-desk' ), $ticket_count ) ); ?></span>
					</li>
				</ul>
				<div class="pnpc-psd-help-box">
					<strong><?php echo esc_html__( 'Built-in help', 'pnpc-pocket-service-desk' ); ?></strong>
					<p><?php echo esc_html__( 'After setup, add the customer dashboard page to your site menu so customers can create and review tickets.', 'pnpc-pocket-service-desk' ); ?></p>
				</div>
			</aside>
		</div>
	<?php endif; ?>

	<?php if ( 'choose_existing' === $step ) : ?>
		<div class="pnpc-psd-setup-card pnpc-psd-main-card">
			<h2><?php echo esc_html__( 'Choose your customer dashboard page', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php echo esc_html__( 'Select the page customers will use to create tickets and review existing requests.', 'pnpc-pocket-service-desk' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=choose_existing&path=existing' ) ); ?>">
				<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>
				<input type="hidden" name="mode" value="save_existing" />

				<label for="pnpc-psd-dashboard-page" class="pnpc-psd-label"><?php echo esc_html__( 'Dashboard page', 'pnpc-pocket-service-desk' ); ?></label>
				<?php
				wp_dropdown_pages(
					array(
						'name'              => 'dashboard_page_id',
						'id'                => 'pnpc-psd-dashboard-page',
						'show_option_none'  => __( 'Select a page', 'pnpc-pocket-service-desk' ),
						'option_none_value' => '0',
						'selected'          => $dashboard_page_id,
					)
				);
				?>

				<div class="pnpc-psd-actions-row">
					<button type="submit" class="button button-primary pnpc-psd-button-large"><?php echo esc_html__( 'Use This Page', 'pnpc-pocket-service-desk' ); ?></button>
					<a class="button button-secondary pnpc-psd-button-large" href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=landing&path=existing' ) ); ?>"><?php echo esc_html__( 'Back', 'pnpc-pocket-service-desk' ); ?></a>
				</div>
			</form>
		</div>
	<?php endif; ?>

	<?php if ( 'builder' === $step ) : ?>
		<div class="pnpc-psd-setup-card pnpc-psd-main-card">
			<h2><?php echo esc_html__( 'Choose a page style', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php echo esc_html__( 'Pick the layout approach that best matches your site. You can edit the pages after they are created.', 'pnpc-pocket-service-desk' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=builder&path=builder' ) ); ?>">
				<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>
				<input type="hidden" name="mode" value="create_dashboard" />

				<div class="pnpc-psd-builder-grid pnpc-psd-radio-grid">
					<label class="pnpc-psd-builder-card <?php echo 'block' === $editor ? 'selected' : ''; ?>">
						<input type="radio" name="editor" value="block" <?php checked( $editor, 'block' ); ?> />
						<span class="dashicons dashicons-block-default builder-icon" aria-hidden="true"></span>
						<span class="pnpc-psd-card-title"><?php echo esc_html__( 'Gutenberg / Block Editor', 'pnpc-pocket-service-desk' ); ?></span>
						<span class="pnpc-psd-card-copy"><?php echo esc_html__( 'Best default option for most WordPress sites.', 'pnpc-pocket-service-desk' ); ?></span>
					</label>

					<label class="pnpc-psd-builder-card <?php echo 'elementor' === $editor ? 'selected' : ''; ?> <?php echo defined( 'ELEMENTOR_VERSION' ) ? '' : 'is-disabled'; ?>">
						<input type="radio" name="editor" value="elementor" <?php checked( $editor, 'elementor' ); ?> <?php disabled( ! defined( 'ELEMENTOR_VERSION' ) ); ?> />
						<span class="dashicons dashicons-layout builder-icon" aria-hidden="true"></span>
						<span class="pnpc-psd-card-title"><?php echo esc_html__( 'Elementor', 'pnpc-pocket-service-desk' ); ?></span>
						<span class="pnpc-psd-card-copy"><?php echo defined( 'ELEMENTOR_VERSION' ) ? esc_html__( 'Create a starter layout for Elementor editing.', 'pnpc-pocket-service-desk' ) : esc_html__( 'Elementor is not active on this site.', 'pnpc-pocket-service-desk' ); ?></span>
					</label>

					<label class="pnpc-psd-builder-card <?php echo 'custom' === $editor ? 'selected' : ''; ?>">
						<input type="radio" name="editor" value="custom" <?php checked( $editor, 'custom' ); ?> />
						<span class="dashicons dashicons-admin-customizer builder-icon" aria-hidden="true"></span>
						<span class="pnpc-psd-card-title"><?php echo esc_html__( 'Custom', 'pnpc-pocket-service-desk' ); ?></span>
						<span class="pnpc-psd-card-copy"><?php echo esc_html__( 'Skip automatic page creation and use the shortcode reference manually.', 'pnpc-pocket-service-desk' ); ?></span>
					</label>
				</div>

				<div class="pnpc-psd-actions-row">
					<button type="submit" class="button button-primary pnpc-psd-button-large"><?php echo esc_html__( 'Continue', 'pnpc-pocket-service-desk' ); ?></button>
					<a class="button button-secondary pnpc-psd-button-large" href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=landing&path=builder' ) ); ?>"><?php echo esc_html__( 'Back', 'pnpc-pocket-service-desk' ); ?></a>
				</div>
			</form>
		</div>
	<?php endif; ?>

	<?php if ( 'shortcodes' === $step ) : ?>
		<div class="pnpc-psd-layout-grid">
			<div class="pnpc-psd-setup-card pnpc-psd-main-card">
				<h2><?php echo esc_html__( 'Shortcode reference', 'pnpc-pocket-service-desk' ); ?></h2>
				<p><?php echo esc_html__( 'These shortcodes power the customer-facing service desk. Use them together on the dashboard page or place them in custom sections.', 'pnpc-pocket-service-desk' ); ?></p>

				<div class="pnpc-psd-shortcodes-list">
					<?php foreach ( $canonical_shortcodes as $shortcode ) : ?>
						<div class="pnpc-psd-shortcode-item">
							<div class="shortcode-header">
								<h3 class="shortcode-title"><?php echo esc_html( $shortcode['title'] ); ?></h3>
								<code class="shortcode-code"><?php echo esc_html( $shortcode['code'] ); ?></code>
							</div>
							<p><?php echo esc_html( $shortcode['description'] ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<aside class="pnpc-psd-setup-card pnpc-psd-status-card">
				<h2><?php echo esc_html__( 'Copy all shortcodes', 'pnpc-pocket-service-desk' ); ?></h2>
				<textarea class="large-text code pnpc-psd-shortcode-textarea" rows="12" readonly="readonly"><?php echo esc_textarea( trim( $canonical ) ); ?></textarea>
				<?php if ( ! empty( $dashboard_url ) ) : ?>
					<p><strong><?php echo esc_html__( 'Dashboard:', 'pnpc-pocket-service-desk' ); ?></strong> <a href="<?php echo esc_url( $dashboard_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $dashboard_url ); ?></a></p>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=shortcodes&path=' . rawurlencode( $path ) ) ); ?>">
					<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>
					<input type="hidden" name="mode" value="confirm_shortcodes" />
					<div class="pnpc-psd-actions-row is-stacked">
						<button type="submit" class="button button-primary pnpc-psd-button-large"><?php echo esc_html__( 'Continue to Success', 'pnpc-pocket-service-desk' ); ?></button>
						<a class="button button-secondary pnpc-psd-button-large" href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=landing&path=' . rawurlencode( $path ) ) ); ?>"><?php echo esc_html__( 'Back to Start', 'pnpc-pocket-service-desk' ); ?></a>
					</div>
				</form>
			</aside>
		</div>
	<?php endif; ?>

	<?php if ( 'complete' === $step ) : ?>
		<div class="pnpc-psd-setup-card pnpc-psd-complete-card">
			<div class="pnpc-psd-success-icon"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span></div>
			<h2><?php echo esc_html__( 'Your Service Desk is ready.', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php echo esc_html__( 'Customers can submit tickets from your dashboard page once it is linked from your site navigation.', 'pnpc-pocket-service-desk' ); ?></p>

			<div class="pnpc-psd-next-steps">
				<div>
					<span class="dashicons dashicons-menu-alt" aria-hidden="true"></span>
					<h3><?php echo esc_html__( 'Add your menu link', 'pnpc-pocket-service-desk' ); ?></h3>
					<p><?php echo esc_html__( 'Add the Support Dashboard page to your site menu or customer account area.', 'pnpc-pocket-service-desk' ); ?></p>
				</div>
				<div>
					<span class="dashicons dashicons-email-alt2" aria-hidden="true"></span>
					<h3><?php echo esc_html__( 'Test notifications', 'pnpc-pocket-service-desk' ); ?></h3>
					<p><?php echo esc_html__( 'Submit a test ticket and confirm your team receives the expected email alerts.', 'pnpc-pocket-service-desk' ); ?></p>
				</div>
				<div>
					<span class="dashicons dashicons-sos" aria-hidden="true"></span>
					<h3><?php echo esc_html__( 'Review diagnostics', 'pnpc-pocket-service-desk' ); ?></h3>
					<p><?php echo esc_html__( 'Use Diagnostics to confirm operational health and review logged issues.', 'pnpc-pocket-service-desk' ); ?></p>
				</div>
			</div>

			<?php if ( ! empty( $dashboard_url ) ) : ?>
				<p class="pnpc-psd-dashboard-link"><strong><?php echo esc_html__( 'Dashboard URL:', 'pnpc-pocket-service-desk' ); ?></strong> <a href="<?php echo esc_url( $dashboard_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $dashboard_url ); ?></a></p>
			<?php endif; ?>

			<details class="pnpc-psd-details-box">
				<summary><?php echo esc_html__( 'Shortcode reference', 'pnpc-pocket-service-desk' ); ?></summary>
				<textarea class="large-text code pnpc-psd-shortcode-textarea" rows="10" readonly="readonly"><?php echo esc_textarea( trim( $canonical ) ); ?></textarea>
			</details>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pnpc_psd_setup_finish" />
				<?php wp_nonce_field( 'pnpc_psd_setup_finish', 'pnpc_psd_setup_finish_nonce' ); ?>
				<div class="pnpc-psd-actions-row is-centered">
					<button type="submit" class="button button-primary pnpc-psd-button-large"><?php echo esc_html__( 'Finish Setup', 'pnpc-pocket-service-desk' ); ?></button>
					<a class="button button-secondary pnpc-psd-button-large" href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-diagnostics' ) ); ?>"><?php echo esc_html__( 'Open Diagnostics', 'pnpc-pocket-service-desk' ); ?></a>
				</div>
			</form>
		</div>
	<?php endif; ?>
</div>
