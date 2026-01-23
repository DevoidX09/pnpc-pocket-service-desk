<?php
/**
 * Setup Wizard admin view.
 *
 * Variables provided by controller:
 * @var string               $step
 * @var string               $path
 * @var array<string,mixed>  $snapshot
 * @var int                  $dashboard_page_id
 * @var WP_Post|null         $dashboard_page
 * @var string               $editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$step = (string) $step;
$path = (string) $path;

$error = (string) get_option( 'pnpc_psd_setup_error', '' );
if ( ! empty( $error ) ) {
	delete_option( 'pnpc_psd_setup_error' );
}

$canonical = "[pnpc_profile_settings]\n\n[pnpc_service_desk]\n\n[pnpc_create_ticket]\n\n[pnpc_services]\n\n[pnpc_my_tickets]\n";

if ( 'done' === $step ) {
	$step = 'complete';
}

/**
 * Render a progress bar for the active path.
 *
 * @param string $active_path Active wizard path ('existing' or 'builder').
 * @param string $current_step Current step key.
 * @return void
 */
function pnpc_psd_render_setup_progress( $active_path, $current_step ) {
	$active_path  = (string) $active_path;
	$current_step = (string) $current_step;

	$steps = array();

	if ( 'existing' === $active_path ) {
		$steps = array(
			'landing'        => __( 'Welcome', 'pnpc-pocket-service-desk' ),
			'choose_existing'=> __( 'Choose Page', 'pnpc-pocket-service-desk' ),
			'shortcodes'     => __( 'Shortcodes', 'pnpc-pocket-service-desk' ),
			'complete'       => __( 'Complete', 'pnpc-pocket-service-desk' ),
		);
	} else {
		$steps = array(
			'landing'  => __( 'Welcome', 'pnpc-pocket-service-desk' ),
			'builder'  => __( 'Builder', 'pnpc-pocket-service-desk' ),
			'complete' => __( 'Complete', 'pnpc-pocket-service-desk' ),
		);
	}

	$keys    = array_keys( $steps );
	$current = array_search( $current_step, $keys, true );
	$current = ( false === $current ) ? 0 : (int) $current;

	?>
	<ul class="pnpc-psd-progress-bar" role="list">
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
				<div class="step-circle"><?php echo esc_html( (string) ( $index + 1 ) ); ?></div>
				<div class="step-label"><?php echo esc_html( $label ); ?></div>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

$dash_configured = ! empty( $snapshot['dashboard_configured'] );
$ticket_count    = isset( $snapshot['ticket_count'] ) ? absint( $snapshot['ticket_count'] ) : 0;

$dashboard_url = '';
if ( $dashboard_page_id > 0 && 'trash' !== get_post_status( $dashboard_page_id ) ) {
	$dashboard_url = get_permalink( $dashboard_page_id );
}

?>

<style>
.pnpc-psd-setup-wizard .pnpc-psd-setup-card{
	background:#fff;
	border:1px solid #dcdcde;
	border-radius:12px;
	padding:22px;
	max-width:900px;
	box-shadow:0 1px 2px rgba(0,0,0,.04);
}
.pnpc-psd-setup-wizard .pnpc-psd-setup-status{
	background:#f6f7f7;
	border:1px solid #dcdcde;
	border-radius:10px;
	padding:14px 16px;
	margin:14px 0 18px;
}
.pnpc-psd-setup-wizard .pnpc-psd-setup-actions{
	display:flex;
	gap:12px;
	flex-wrap:wrap;
	margin-top:14px;
}
.pnpc-psd-setup-wizard .pnpc-psd-button-large{
	padding:10px 18px;
	font-size:14px;
	line-height:1.4;
	border-radius:10px;
}
.pnpc-psd-setup-wizard .pnpc-psd-progress-bar{
	display:flex;
	gap:14px;
	flex-wrap:wrap;
	margin:18px 0 18px;
	padding:0;
}
.pnpc-psd-setup-wizard .pnpc-psd-progress-step{
	list-style:none;
	display:flex;
	align-items:center;
	gap:10px;
	opacity:.65;
}
.pnpc-psd-setup-wizard .pnpc-psd-progress-step.active,
.pnpc-psd-setup-wizard .pnpc-psd-progress-step.completed{opacity:1}
.pnpc-psd-setup-wizard .pnpc-psd-progress-step .step-circle{
	width:28px;height:28px;border-radius:50%;
	display:flex;align-items:center;justify-content:center;
	background:#dcdcde;color:#1d2327;font-weight:600;
}
.pnpc-psd-setup-wizard .pnpc-psd-progress-step.completed .step-circle{background:#00a32a;color:#fff}
.pnpc-psd-setup-wizard .pnpc-psd-progress-step.active .step-circle{background:#2271b1;color:#fff}
</style>

<div class="wrap pnpc-psd-setup-wizard">
	<h1><?php echo esc_html__( 'Service Desk Setup Wizard', 'pnpc-pocket-service-desk' ); ?></h1>

	<?php pnpc_psd_render_setup_progress( $path, $step ); ?>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif; ?>

	<?php
	// STEP: Landing (Welcome + Scan combined).
	if ( 'landing' === $step ) :
		?>
		<div class="pnpc-psd-setup-card">
			<h2><?php echo esc_html__( 'Welcome — let’s get you up and running in a few minutes', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php echo esc_html__( 'This wizard will help you connect (or create) your Support Dashboard and confirm the shortcodes you will use.', 'pnpc-pocket-service-desk' ); ?></p>

			<div class="pnpc-psd-setup-status">
				<h3><?php echo esc_html__( 'Current site status', 'pnpc-pocket-service-desk' ); ?></h3>
				<ul>
					<li>
						<strong><?php echo esc_html__( 'Dashboard:', 'pnpc-pocket-service-desk' ); ?></strong>
						<?php echo $dash_configured ? esc_html__( 'Found', 'pnpc-pocket-service-desk' ) : esc_html__( 'Not configured', 'pnpc-pocket-service-desk' ); ?>
					</li>
					<li>
						<strong><?php echo esc_html__( 'Tickets:', 'pnpc-pocket-service-desk' ); ?></strong>
						<?php
						if ( $ticket_count > 0 ) {
							echo esc_html( sprintf( __( '%d existing ticket(s) detected', 'pnpc-pocket-service-desk' ), $ticket_count ) );
						} else {
							echo esc_html__( 'No tickets detected (clean queue)', 'pnpc-pocket-service-desk' );
						}
						?>
					</li>
				</ul>
			</div>

			<div class="pnpc-psd-setup-actions">
<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=landing&path=' . rawurlencode( $path ) ) ); ?>">
				<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>
				<input type="hidden" name="mode" value="use_existing" />
				<p>
					<button type="submit" class="button button-secondary pnpc-psd-button-large">
						<?php echo esc_html__( 'Use an Existing Page as my Dashboard', 'pnpc-pocket-service-desk' ); ?>
					</button>
				</p>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=landing&path=builder' ) ); ?>">
				<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>
				<input type="hidden" name="mode" value="begin_install" />
				<p>
					<button type="submit" class="button button-primary pnpc-psd-button-large">
						<?php echo esc_html__( 'Begin Install (Create a Dashboard)', 'pnpc-pocket-service-desk' ); ?>
					</button>
				</p>
			</form>
</div>

			<p class="description">
				<?php echo esc_html__( 'Tip: If you already have a page you want to use, choose “Use an Existing Page.” Otherwise, choose “Begin Install.”', 'pnpc-pocket-service-desk' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php
	// STEP: Choose existing page.
	if ( 'choose_existing' === $step ) :
		$pages = get_pages(
			array(
				'sort_column' => 'post_title',
				'sort_order'  => 'ASC',
			)
		);
		?>
		<div class="pnpc-psd-setup-card">
			<h2><?php echo esc_html__( 'Choose an existing page for your Support Dashboard', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php echo esc_html__( 'Select the page you want to use as your Dashboard. You can add shortcodes to that page at any time.', 'pnpc-pocket-service-desk' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=choose_existing&path=existing' ) ); ?>">
				<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>
				<input type="hidden" name="mode" value="save_existing" />

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="pnpc_psd_dashboard_page_id"><?php echo esc_html__( 'Dashboard Page', 'pnpc-pocket-service-desk' ); ?></label>
						</th>
						<td>
							<select name="dashboard_page_id" id="pnpc_psd_dashboard_page_id" class="regular-text">
								<option value="0"><?php echo esc_html__( 'Select a page…', 'pnpc-pocket-service-desk' ); ?></option>
								<?php foreach ( $pages as $p ) : ?>
									<option value="<?php echo absint( $p->ID ); ?>" <?php selected( $dashboard_page_id, $p->ID ); ?>>
										<?php echo esc_html( $p->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php echo esc_html__( 'This page will be treated as your customer-facing Service Desk dashboard.', 'pnpc-pocket-service-desk' ); ?></p>
						</td>
					</tr>
				</table>

				<p>
					<button type="submit" class="button button-primary pnpc-psd-button-large">
						<?php echo esc_html__( 'Continue to Shortcodes', 'pnpc-pocket-service-desk' ); ?>
					</button>
					<a class="button button-secondary pnpc-psd-button-large" href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=landing&path=existing' ) ); ?>">
						<?php echo esc_html__( 'Back', 'pnpc-pocket-service-desk' ); ?>
					</a>
				</p>
			</form>
		</div>
	<?php endif; ?>

	<?php
	// STEP: Shortcodes.
	if ( 'shortcodes' === $step ) :
		?>
		<div class="pnpc-psd-setup-card">
			<h2><?php echo esc_html__( 'Shortcodes and Dashboard linking', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php echo esc_html__( 'Use the shortcodes below on your Dashboard page. If you use a builder, place these inside the appropriate sections or blocks.', 'pnpc-pocket-service-desk' ); ?></p>

			<?php if ( ! empty( $dashboard_url ) ) : ?>
				<p>
					<strong><?php echo esc_html__( 'Your Dashboard page:', 'pnpc-pocket-service-desk' ); ?></strong>
					<a href="<?php echo esc_url( $dashboard_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $dashboard_url ); ?></a>
				</p>
			<?php else : ?>
				<p class="description"><?php echo esc_html__( 'No Dashboard page is currently linked. Go back and select a page.', 'pnpc-pocket-service-desk' ); ?></p>
			<?php endif; ?>

			<textarea class="large-text code" rows="10" readonly="readonly"><?php echo esc_textarea( $canonical ); ?></textarea>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=shortcodes&path=existing' ) ); ?>">
				<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>
				<input type="hidden" name="mode" value="confirm_shortcodes" />

				<p>
					<button type="submit" class="button button-primary pnpc-psd-button-large">
						<?php echo esc_html__( 'Continue to Complete', 'pnpc-pocket-service-desk' ); ?>
					</button>
					<a class="button button-secondary pnpc-psd-button-large" href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=choose_existing&path=existing' ) ); ?>">
						<?php echo esc_html__( 'Back', 'pnpc-pocket-service-desk' ); ?>
					</a>
				</p>
			</form>
		</div>
	<?php endif; ?>

	<?php
	// STEP: Builder.
	if ( 'builder' === $step ) :
		?>
		<div class="pnpc-psd-setup-card">
			<h2><?php echo esc_html__( 'Create a Support Dashboard', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php echo esc_html__( 'We can create a starter Dashboard page for you. You can edit it later using your preferred editor.', 'pnpc-pocket-service-desk' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=builder&path=builder' ) ); ?>">
				<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>
				<input type="hidden" name="mode" value="create_dashboard" />

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Editor', 'pnpc-pocket-service-desk' ); ?></th>
						<td>
							<label>
								<input type="radio" name="editor" value="elementor" <?php checked( $editor, 'elementor' ); ?> <?php disabled( ! defined( 'ELEMENTOR_VERSION' ) ); ?> />
								<?php echo esc_html__( 'Elementor (recommended if installed)', 'pnpc-pocket-service-desk' ); ?>
							</label><br />
							<label>
								<input type="radio" name="editor" value="block" <?php checked( $editor, 'block' ); ?> />
								<?php echo esc_html__( 'WordPress Block Editor', 'pnpc-pocket-service-desk' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<p>
					<button type="submit" class="button button-primary">
						<?php echo esc_html__( 'Create Dashboard', 'pnpc-pocket-service-desk' ); ?>
					</button>
					<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=landing&path=builder' ) ); ?>">
						<?php echo esc_html__( 'Back', 'pnpc-pocket-service-desk' ); ?>
					</a>
				</p>
			</form>
		</div>
	<?php endif; ?>

	<?php
	// STEP: Complete.
	if ( 'complete' === $step ) :
		?>
		<div class="pnpc-psd-setup-card">
			<h2><?php echo esc_html__( 'Setup complete', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php echo esc_html__( 'Your Service Desk Dashboard is ready. Next, make sure your site navigation links to your Dashboard page.', 'pnpc-pocket-service-desk' ); ?></p>

			<?php if ( ! empty( $dashboard_url ) ) : ?>
				<p>
					<strong><?php echo esc_html__( 'Dashboard URL:', 'pnpc-pocket-service-desk' ); ?></strong>
					<a href="<?php echo esc_url( $dashboard_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $dashboard_url ); ?></a>
				</p>
			<?php endif; ?>

			<h3><?php echo esc_html__( 'Shortcodes reference', 'pnpc-pocket-service-desk' ); ?></h3>
			<p class="description"><?php echo esc_html__( 'Use these shortcodes on your Dashboard page. Link your site menu to the Dashboard URL above.', 'pnpc-pocket-service-desk' ); ?></p>
			<textarea class="large-text code" rows="10" readonly="readonly"><?php echo esc_textarea( $canonical ); ?></textarea>

			<p class="description"><?php echo esc_html__( 'Clicking Finished will create a Ticket View page for your customers and generate a sample customer and sample ticket to help you get started.', 'pnpc-pocket-service-desk' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pnpc_psd_setup_finish" />
				<?php wp_nonce_field( 'pnpc_psd_setup_finish', 'pnpc_psd_setup_finish_nonce' ); ?>
				<p>
					<button type="submit" class="button button-primary pnpc-psd-button-large">
						<?php echo esc_html__( 'Finished', 'pnpc-pocket-service-desk' ); ?>
					</button>
				</p>
			</form>
		</div>
	<?php endif; ?>

</div>
