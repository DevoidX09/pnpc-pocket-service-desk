<?php
/**
 * Admin ticket detail view
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user = get_userdata( $ticket->user_id );
?>

<div class="wrap">
	<h1>
		<?php
		/* translators: %s: ticket number */
		printf( esc_html__( 'Ticket: %s', 'pnpc-pocket-service-desk' ), esc_html( $ticket->ticket_number ) );
		?>
	</h1>

	<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk' ) ); ?>" class="button">
		&larr; <?php esc_html_e( 'Back to All Tickets', 'pnpc-pocket-service-desk' ); ?>
	</a>

	<div class="pnpc-psd-ticket-detail">
		<div class="pnpc-psd-ticket-header">
			<div class="pnpc-psd-ticket-meta">
				<h2><?php echo esc_html( $ticket->subject ); ?></h2>
				<p>
					<strong><?php esc_html_e( 'Customer:', 'pnpc-pocket-service-desk' ); ?></strong>
					<?php echo $user ? esc_html( $user->display_name . ' (' . $user->user_email . ')' ) : esc_html__( 'Unknown', 'pnpc-pocket-service-desk' ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Created:', 'pnpc-pocket-service-desk' ); ?></strong>
					<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->created_at ) ) ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Last Updated:', 'pnpc-pocket-service-desk' ); ?></strong>
					<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->updated_at ) ) ); ?>
				</p>
			</div>

			<div class="pnpc-psd-ticket-actions">
				<div class="pnpc-psd-field">
					<label for="ticket-status"><?php esc_html_e( 'Status:', 'pnpc-pocket-service-desk' ); ?></label>
					<select id="ticket-status" data-ticket-id="<?php echo esc_attr( $ticket->id ); ?>">
						<option value="open" <?php selected( $ticket->status, 'open' ); ?>><?php esc_html_e( 'Open', 'pnpc-pocket-service-desk' ); ?></option>
						<option value="in-progress" <?php selected( $ticket->status, 'in-progress' ); ?>><?php esc_html_e( 'In Progress', 'pnpc-pocket-service-desk' ); ?></option>
						<option value="waiting" <?php selected( $ticket->status, 'waiting' ); ?>><?php esc_html_e( 'Waiting on Customer', 'pnpc-pocket-service-desk' ); ?></option>
						<option value="closed" <?php selected( $ticket->status, 'closed' ); ?>><?php esc_html_e( 'Closed', 'pnpc-pocket-service-desk' ); ?></option>
					</select>
				</div>

				<div class="pnpc-psd-field">
					<label for="ticket-priority"><?php esc_html_e( 'Priority:', 'pnpc-pocket-service-desk' ); ?></label>
					<span class="pnpc-psd-priority pnpc-psd-priority-<?php echo esc_attr( $ticket->priority ); ?>">
						<?php echo esc_html( ucfirst( $ticket->priority ) ); ?>
					</span>
				</div>

				<div class="pnpc-psd-field">
					<label for="ticket-assign"><?php esc_html_e( 'Assign To:', 'pnpc-pocket-service-desk' ); ?></label>
					<select id="ticket-assign" data-ticket-id="<?php echo esc_attr( $ticket->id ); ?>">
						<option value="0"><?php esc_html_e( 'Unassigned', 'pnpc-pocket-service-desk' ); ?></option>
						<?php foreach ( $agents as $agent ) : ?>
							<option value="<?php echo esc_attr( $agent->ID ); ?>" <?php selected( $ticket->assigned_to, $agent->ID ); ?>>
								<?php echo esc_html( $agent->display_name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>

		<div class="pnpc-psd-ticket-description">
			<h3><?php esc_html_e( 'Description', 'pnpc-pocket-service-desk' ); ?></h3>
			<div class="pnpc-psd-ticket-content">
				<?php echo wp_kses_post( $ticket->description ); ?>
			</div>
		</div>

		<div class="pnpc-psd-ticket-responses">
			<h3><?php esc_html_e( 'Responses', 'pnpc-pocket-service-desk' ); ?></h3>

			<?php if ( ! empty( $responses ) ) : ?>
				<?php foreach ( $responses as $response ) : ?>
					<?php $response_user = get_userdata( $response->user_id ); ?>
					<div class="pnpc-psd-response <?php echo $response->is_staff_response ? 'pnpc-psd-response-staff' : 'pnpc-psd-response-customer'; ?>">
						<div class="pnpc-psd-response-header">
							<strong><?php echo $response_user ? esc_html( $response_user->display_name ) : esc_html__( 'Unknown', 'pnpc-pocket-service-desk' ); ?></strong>
							<span class="pnpc-psd-response-type">
								<?php echo $response->is_staff_response ? esc_html__( '(Staff)', 'pnpc-pocket-service-desk' ) : esc_html__( '(Customer)', 'pnpc-pocket-service-desk' ); ?>
							</span>
							<span class="pnpc-psd-response-date">
								<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $response->created_at ) ) ); ?>
							</span>
						</div>
						<div class="pnpc-psd-response-content">
							<?php echo wp_kses_post( $response->response ); ?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<p><?php esc_html_e( 'No responses yet.', 'pnpc-pocket-service-desk' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="pnpc-psd-add-response">
			<h3><?php esc_html_e( 'Add Response', 'pnpc-pocket-service-desk' ); ?></h3>
			<form id="pnpc-psd-response-form" data-ticket-id="<?php echo esc_attr( $ticket->id ); ?>">
				<textarea id="response-text" name="response" rows="6" placeholder="<?php esc_attr_e( 'Enter your response...', 'pnpc-pocket-service-desk' ); ?>" required></textarea>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Response', 'pnpc-pocket-service-desk' ); ?></button>
			</form>
			<div id="response-message"></div>
		</div>

		<?php if ( current_user_can( 'pnpc_psd_delete_tickets' ) ) : ?>
			<div class="pnpc-psd-danger-zone">
				<h3><?php esc_html_e( 'Danger Zone', 'pnpc-pocket-service-desk' ); ?></h3>
				<button id="delete-ticket" class="button button-danger" data-ticket-id="<?php echo esc_attr( $ticket->id ); ?>">
					<?php esc_html_e( 'Delete Ticket', 'pnpc-pocket-service-desk' ); ?>
				</button>
			</div>
		<?php endif; ?>
	</div>
</div>
