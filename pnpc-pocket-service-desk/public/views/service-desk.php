<?php
/**
 * Public service desk dashboard view
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pnpc_current_user = wp_get_current_user();
$tickets           = PNPC_PSD_Ticket::get_by_user( $pnpc_current_user->ID, array( 'limit' => 5 ) );
$open_count        = count(
	array_filter(
		$tickets,
		function ( $ticket ) {
			return 'open' === $ticket->status || 'in-progress' === $ticket->status;
		}
	)
);
?>

<div class="pnpc-psd-dashboard">
	<?php
	// Embed profile block.
	$profile_image  = get_user_meta( $pnpc_current_user->ID, 'pnpc_psd_profile_image', true );
	$default_avatar = get_avatar_url( $pnpc_current_user->ID );
	?>
	<div class="pnpc-psd-profile-block">
		<div class="pnpc-psd-profile-image">
			<img id="dashboard-profile-image" src="<?php echo esc_url( $profile_image ? $profile_image : $default_avatar ); ?>" alt="<?php esc_attr_e( 'Profile Image', 'pnpc-pocket-service-desk' ); ?>" />
		</div>
		<div class="pnpc-psd-profile-info">
			<h2>
				<?php
				/* translators: %s: user display name */
				printf( esc_html__( 'Welcome, %s!', 'pnpc-pocket-service-desk' ), esc_html( $pnpc_current_user->display_name ) );
				?>
			</h2>
			<form id="pnpc-psd-profile-upload-form" class="pnpc-psd-compact-upload" enctype="multipart/form-data">
				<label for="dashboard-profile-upload" class="pnpc-psd-button pnpc-psd-button-small">
					<?php esc_html_e( 'Update Image', 'pnpc-pocket-service-desk' ); ?>
				</label>
				<input type="file" id="dashboard-profile-upload" name="profile_image" accept="image/*" style="display: none;" />
				<p class="pnpc-psd-help-text"><?php esc_html_e( 'Max 2MB', 'pnpc-pocket-service-desk' ); ?></p>
				<div id="dashboard-profile-message" class="pnpc-psd-message"></div>
			</form>
		</div>
	</div>

	<div class="pnpc-psd-dashboard-stats">
		<div class="pnpc-psd-stat-box">
			<h3><?php esc_html_e( 'Open Tickets', 'pnpc-pocket-service-desk' ); ?></h3>
			<div class="pnpc-psd-stat-number"><?php echo absint( $open_count ); ?></div>
		</div>
		<div class="pnpc-psd-stat-box">
			<h3><?php esc_html_e( 'Total Tickets', 'pnpc-pocket-service-desk' ); ?></h3>
			<div class="pnpc-psd-stat-number"><?php echo count( $tickets ); ?></div>
		</div>
	</div>

	<div class="pnpc-psd-quick-actions">
		<h3><?php esc_html_e( 'Quick Actions', 'pnpc-pocket-service-desk' ); ?></h3>
		<div class="pnpc-psd-action-buttons">
			<a href="<?php echo esc_url( home_url( '/create-ticket/' ) ); ?>" class="pnpc-psd-button pnpc-psd-button-primary">
				<?php esc_html_e( 'Create New Ticket', 'pnpc-pocket-service-desk' ); ?>
			</a>
			<a href="<?php echo esc_url( home_url( '/my-tickets/' ) ); ?>" class="pnpc-psd-button">
				<?php esc_html_e( 'View All Tickets', 'pnpc-pocket-service-desk' ); ?>
			</a>
			<a href="<?php echo esc_url( home_url( '/profile-settings/' ) ); ?>" class="pnpc-psd-button">
				<?php esc_html_e( 'Profile Settings', 'pnpc-pocket-service-desk' ); ?>
			</a>
		</div>
	</div>

	<?php if ( ! empty( $tickets ) ) : ?>
		<div class="pnpc-psd-recent-tickets">
			<h3><?php esc_html_e( 'Recent Tickets', 'pnpc-pocket-service-desk' ); ?></h3>
			<div class="pnpc-psd-tickets-list">
				<?php foreach ( $tickets as $ticket ) : ?>
					<?php
					$ticket_url = add_query_arg( 'ticket_id', $ticket->id, home_url( '/ticket-detail/' ) );
					?>
					<div class="pnpc-psd-ticket-item">
						<div class="pnpc-psd-ticket-header">
							<h4>
								<a href="<?php echo esc_url( $ticket_url ); ?>">
									<?php echo esc_html( $ticket->subject ); ?>
								</a>
							</h4>
							<span class="pnpc-psd-status pnpc-psd-status-<?php echo esc_attr( $ticket->status ); ?>">
								<?php echo esc_html( ucfirst( $ticket->status ) ); ?>
							</span>
						</div>
						<div class="pnpc-psd-ticket-meta">
							<span><?php echo esc_html( '#' . $ticket->ticket_number ); ?></span>
							<span>
								<?php
								/* translators: %s: time ago */
								printf( esc_html__( 'Updated %s', 'pnpc-pocket-service-desk' ), esc_html( human_time_diff( strtotime( $ticket->updated_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'pnpc-pocket-service-desk' ) ) );
								?>
							</span>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( class_exists( 'WooCommerce' ) ) : ?>
		<div class="pnpc-psd-woocommerce-section">
			<h3><?php esc_html_e( 'Shop Our Products', 'pnpc-pocket-service-desk' ); ?></h3>
			<p><?php esc_html_e( 'Browse our product catalog and make purchases.', 'pnpc-pocket-service-desk' ); ?></p>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pnpc-psd-button pnpc-psd-button-primary">
				<?php esc_html_e( 'View Products', 'pnpc-pocket-service-desk' ); ?>
			</a>
		</div>
	<?php endif; ?>
</div>
