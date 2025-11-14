<?php
/**
 * Public ticket detail view
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="pnpc-psd-ticket-detail">
	<div class="pnpc-psd-ticket-header">
		<h2><?php echo esc_html( $ticket->subject ); ?></h2>
		<div class="pnpc-psd-ticket-meta">
			<span class="pnpc-psd-ticket-number">
				<?php
				/* translators: %s: ticket number */
				printf( esc_html__( 'Ticket #%s', 'pnpc-pocket-service-desk' ), esc_html( $ticket->ticket_number ) );
				?>
			</span>
			<span class="pnpc-psd-status pnpc-psd-status-<?php echo esc_attr( $ticket->status ); ?>">
				<?php echo esc_html( ucfirst( $ticket->status ) ); ?>
			</span>
			<span class="pnpc-psd-priority pnpc-psd-priority-<?php echo esc_attr( $ticket->priority ); ?>">
				<?php echo esc_html( ucfirst( $ticket->priority ) ); ?>
			</span>
		</div>
		<div class="pnpc-psd-ticket-date">
			<?php
			/* translators: %s: date and time */
			printf( esc_html__( 'Created on %s', 'pnpc-pocket-service-desk' ), esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->created_at ) ) ) );
			?>
		</div>
	</div>

	<div class="pnpc-psd-ticket-description">
		<h3><?php esc_html_e( 'Your Request', 'pnpc-pocket-service-desk' ); ?></h3>
		<div class="pnpc-psd-ticket-content">
			<?php echo wp_kses_post( $ticket->description ); ?>
		</div>
	</div>

	<div class="pnpc-psd-ticket-responses">
		<h3><?php esc_html_e( 'Conversation', 'pnpc-pocket-service-desk' ); ?></h3>

		<?php if ( ! empty( $responses ) ) : ?>
			<?php foreach ( $responses as $response ) : ?>
				<?php
				$response_user = get_userdata( $response->user_id );
				$is_staff      = $response->is_staff_response;
				?>
				<div class="pnpc-psd-response <?php echo $is_staff ? 'pnpc-psd-response-staff' : 'pnpc-psd-response-customer'; ?>">
					<div class="pnpc-psd-response-header">
						<div class="pnpc-psd-response-author">
							<strong><?php echo $response_user ? esc_html( $response_user->display_name ) : esc_html__( 'Unknown', 'pnpc-pocket-service-desk' ); ?></strong>
							<?php if ( $is_staff ) : ?>
								<span class="pnpc-psd-staff-badge"><?php esc_html_e( 'Support Staff', 'pnpc-pocket-service-desk' ); ?></span>
							<?php endif; ?>
						</div>
						<span class="pnpc-psd-response-date">
							<?php echo esc_html( human_time_diff( strtotime( $response->created_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'pnpc-pocket-service-desk' ) ); ?>
						</span>
					</div>
					<div class="pnpc-psd-response-content">
						<?php echo wp_kses_post( $response->response ); ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<p class="pnpc-psd-no-responses">
				<?php esc_html_e( 'No responses yet. We will get back to you as soon as possible.', 'pnpc-pocket-service-desk' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<?php if ( 'closed' !== $ticket->status ) : ?>
		<div class="pnpc-psd-add-response">
			<h3><?php esc_html_e( 'Add a Reply', 'pnpc-pocket-service-desk' ); ?></h3>
			<form id="pnpc-psd-response-form" data-ticket-id="<?php echo esc_attr( $ticket->id ); ?>">
				<div class="pnpc-psd-form-group">
					<textarea id="response-text" name="response" rows="6" placeholder="<?php esc_attr_e( 'Type your message here...', 'pnpc-pocket-service-desk' ); ?>" required></textarea>
				</div>
				<div class="pnpc-psd-form-group">
					<button type="submit" class="pnpc-psd-button pnpc-psd-button-primary">
						<?php esc_html_e( 'Send Reply', 'pnpc-pocket-service-desk' ); ?>
					</button>
				</div>
				<div id="response-message" class="pnpc-psd-message"></div>
			</form>
		</div>
	<?php else : ?>
		<div class="pnpc-psd-ticket-closed-notice">
			<p><?php esc_html_e( 'This ticket has been closed. If you need further assistance, please create a new ticket.', 'pnpc-pocket-service-desk' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="pnpc-psd-ticket-actions">
		<a href="<?php echo esc_url( home_url( '/my-tickets/' ) ); ?>" class="pnpc-psd-button">
			&larr; <?php esc_html_e( 'Back to My Tickets', 'pnpc-pocket-service-desk' ); ?>
		</a>
	</div>
</div>
