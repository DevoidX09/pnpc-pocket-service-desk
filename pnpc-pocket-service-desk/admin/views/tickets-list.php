<?php
/**
 * Admin tickets list view
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Service Desk Tickets', 'pnpc-pocket-service-desk' ); ?></h1>

	<ul class="subsubsub">
		<li>
			<a href="?page=pnpc-service-desk" <?php echo empty( $status ) ? 'class="current"' : ''; ?>>
				<?php esc_html_e( 'All', 'pnpc-pocket-service-desk' ); ?>
			</a> |
		</li>
		<li>
			<a href="?page=pnpc-service-desk&status=open" <?php echo 'open' === $status ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of open tickets */
				printf( esc_html__( 'Open (%d)', 'pnpc-pocket-service-desk' ), absint( $open_count ) );
				?>
			</a> |
		</li>
		<li>
			<a href="?page=pnpc-service-desk&status=closed" <?php echo 'closed' === $status ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of closed tickets */
				printf( esc_html__( 'Closed (%d)', 'pnpc-pocket-service-desk' ), absint( $closed_count ) );
				?>
			</a>
		</li>
	</ul>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Ticket #', 'pnpc-pocket-service-desk' ); ?></th>
				<th><?php esc_html_e( 'Subject', 'pnpc-pocket-service-desk' ); ?></th>
				<th><?php esc_html_e( 'Customer', 'pnpc-pocket-service-desk' ); ?></th>
				<th><?php esc_html_e( 'Status', 'pnpc-pocket-service-desk' ); ?></th>
				<th><?php esc_html_e( 'Priority', 'pnpc-pocket-service-desk' ); ?></th>
				<th><?php esc_html_e( 'Assigned To', 'pnpc-pocket-service-desk' ); ?></th>
				<th><?php esc_html_e( 'Created', 'pnpc-pocket-service-desk' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'pnpc-pocket-service-desk' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $tickets ) ) : ?>
				<?php foreach ( $tickets as $ticket ) : ?>
					<?php
					$user          = get_userdata( $ticket->user_id );
					$assigned_user = $ticket->assigned_to ? get_userdata( $ticket->assigned_to ) : null;
					?>
					<tr>
						<td><strong><?php echo esc_html( $ticket->ticket_number ); ?></strong></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id ) ); ?>">
								<?php echo esc_html( $ticket->subject ); ?>
							</a>
						</td>
						<td><?php echo $user ? esc_html( $user->display_name ) : esc_html__( 'Unknown', 'pnpc-pocket-service-desk' ); ?></td>
						<td>
							<span class="pnpc-psd-status pnpc-psd-status-<?php echo esc_attr( $ticket->status ); ?>">
								<?php echo esc_html( ucfirst( $ticket->status ) ); ?>
							</span>
						</td>
						<td>
							<span class="pnpc-psd-priority pnpc-psd-priority-<?php echo esc_attr( $ticket->priority ); ?>">
								<?php echo esc_html( ucfirst( $ticket->priority ) ); ?>
							</span>
						</td>
						<td><?php echo $assigned_user ? esc_html( $assigned_user->display_name ) : esc_html__( 'Unassigned', 'pnpc-pocket-service-desk' ); ?></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ticket->created_at ) ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id ) ); ?>" class="button button-small">
								<?php esc_html_e( 'View', 'pnpc-pocket-service-desk' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="8"><?php esc_html_e( 'No tickets found.', 'pnpc-pocket-service-desk' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
