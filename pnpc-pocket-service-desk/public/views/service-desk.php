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

$current_user = wp_get_current_user();
$tickets      = PNPC_PSD_Ticket::get_by_user( $current_user->ID, array( 'limit' => 5 ) );
$open_count   = count( array_filter( $tickets, function( $ticket ) {
	return 'open' === $ticket->status || 'in-progress' === $ticket->status;
} ) );
?>

<div class="pnpc-psd-dashboard">
	<h2>
		<?php
		/* translators: %s: user display name */
		printf( esc_html__( 'Welcome, %s!', 'pnpc-pocket-service-desk' ), esc_html( $current_user->display_name ) );
		?>
	</h2>

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
