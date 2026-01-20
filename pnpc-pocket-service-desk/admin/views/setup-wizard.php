<?php
/**
 * Setup Wizard admin view.
 *
 * @var string $step
 * @var int    $dashboard_page_id
 * @var WP_Post|null $dashboard_page
 * @var WP_Post|null $dashboard_slug_candidate
 * @var string $editor
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$error = get_option( 'pnpc_psd_setup_error', '' );
if ( $error ) {
	delete_option( 'pnpc_psd_setup_error' );
}
$canonical = "[pnpc_profile_settings]\n\n[pnpc_service_desk]\n\n[pnpc_create_ticket]\n\n[pnpc_services]\n\n[pnpc_my_tickets]\n";
?>

<div class="wrap">
	<h1><?php echo esc_html__( 'Service Desk Setup Wizard', 'pnpc-pocket-service-desk' ); ?></h1>

	<?php if ( $error ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
	<?php endif; ?>

	<?php if ( 'done' === $step ) : ?>
		<div class="notice notice-success"><p><?php echo esc_html__( 'Setup saved.', 'pnpc-pocket-service-desk' ); ?></p></div>

		<div class="card" style="max-width: 980px; padding:16px;">
			<h2 style="margin-top:0;"><?php echo esc_html__( 'Dashboard Entry Link', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php echo esc_html__( 'Add a menu/header link labeled “Customer Login” (or “Support Portal”) that points to your dashboard page. Logged-out users will be prompted to log in; logged-in users will see only their own tickets.', 'pnpc-pocket-service-desk' ); ?></p>

			<?php if ( $dashboard_page ) : ?>
				<p><strong><?php echo esc_html__( 'Selected Dashboard Page:', 'pnpc-pocket-service-desk' ); ?></strong>
					<a href="<?php echo esc_url( get_edit_post_link( $dashboard_page->ID ) ); ?>"><?php echo esc_html( get_the_title( $dashboard_page ) ); ?></a>
					<br />
					<strong><?php echo esc_html__( 'Permalink:', 'pnpc-pocket-service-desk' ); ?></strong>
					<a href="<?php echo esc_url( get_permalink( $dashboard_page ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( get_permalink( $dashboard_page ) ); ?></a>
				</p>

				<p>
					<a class="button button-primary" href="<?php echo esc_url( get_permalink( $dashboard_page ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'View Dashboard', 'pnpc-pocket-service-desk' ); ?></a>
					<a class="button" href="<?php echo esc_url( get_edit_post_link( $dashboard_page->ID ) ); ?>"><?php echo esc_html__( 'Edit Page', 'pnpc-pocket-service-desk' ); ?></a>
					<?php if ( 'elementor' === $editor && defined( 'ELEMENTOR_VERSION' ) ) : ?>
						<a class="button" href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $dashboard_page->ID ) . '&action=elementor' ) ); ?>"><?php echo esc_html__( 'Edit with Elementor', 'pnpc-pocket-service-desk' ); ?></a>
					<?php endif; ?>
				</p>
			<?php else : ?>
				<p><?php echo esc_html__( 'No dashboard page is currently linked. Re-run the wizard to select or create one.', 'pnpc-pocket-service-desk' ); ?></p>
			<?php endif; ?>

			<hr />

			<h2><?php echo esc_html__( 'Canonical Shortcode Layout', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php echo esc_html__( 'If you chose DIY or want to recreate the default single-column layout, use this shortcode stack (top to bottom):', 'pnpc-pocket-service-desk' ); ?></p>
			<textarea class="large-text code" rows="10" readonly><?php echo esc_textarea( $canonical ); ?></textarea>

			<p style="margin-top:12px;">
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=start' ) ); ?>"><?php echo esc_html__( 'Run Wizard Again', 'pnpc-pocket-service-desk' ); ?></a>
			</p>
		</div>

	<?php elseif ( 'welcome' === $step ) : ?>
		<div class="card" style="max-width: 980px; padding:16px;">
			<h2 style="margin-top:0;">
				<?php echo esc_html__( 'Welcome — Install Your Customer Dashboard', 'pnpc-pocket-service-desk' ); ?>
			</h2>
			<p><?php echo esc_html__( 'This installer will create (or help you link) a single Dashboard page that customers use as their Support Portal.', 'pnpc-pocket-service-desk' ); ?></p>
			<ol>
				<li><?php echo esc_html__( 'Create the Dashboard page (Elementor template or WordPress blocks).', 'pnpc-pocket-service-desk' ); ?></li>
				<li><?php echo esc_html__( 'Confirm the Dashboard link in your menu/header (Customer Login / Support Portal).', 'pnpc-pocket-service-desk' ); ?></li>
			</ol>

			<div class="notice notice-info inline"><p><?php echo esc_html__( 'Reminder: add a “Customer Login” or “Support Portal” link in your menu/header that points to your Dashboard page.', 'pnpc-pocket-service-desk' ); ?></p></div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=start' ) ); ?>" style="margin-top:16px;">
				<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>
				<input type="hidden" name="mode" value="create" />
				<input type="hidden" name="editor" value="<?php echo esc_attr( defined( 'ELEMENTOR_VERSION' ) ? 'elementor' : 'block' ); ?>" />
				<input type="hidden" name="page_title" value="<?php echo esc_attr__( 'Support Dashboard', 'pnpc-pocket-service-desk' ); ?>" />
				<input type="hidden" name="page_slug" value="dashboard" />
				<p>
					<button type="submit" class="button button-primary">
						<?php echo esc_html__( 'Begin Install (Recommended)', 'pnpc-pocket-service-desk' ); ?>
					</button>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=start' ) ); ?>">
						<?php echo esc_html__( 'Choose Options…', 'pnpc-pocket-service-desk' ); ?>
					</a>
				</p>
			</form>
		</div>

	<?php else : ?>

		<p><?php echo esc_html__( 'This wizard helps you create (or link) a single Dashboard page that customers use as their “Support Portal”. The dashboard uses shortcodes and will only display tickets for the currently logged-in customer.', 'pnpc-pocket-service-desk' ); ?></p>

		<div class="card" style="max-width: 980px; padding:16px;">
			<h2 style="margin-top:0;"><?php echo esc_html__( 'Step 1 — Choose Setup Type', 'pnpc-pocket-service-desk' ); ?></h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=start' ) ); ?>">
				<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="pnpc_psd_editor"><?php echo esc_html__( 'Page Builder', 'pnpc-pocket-service-desk' ); ?></label></th>
						<td>
							<select name="editor" id="pnpc_psd_editor">
								<option value="elementor" <?php selected( $editor, 'elementor' ); ?>><?php echo esc_html__( 'Elementor', 'pnpc-pocket-service-desk' ); ?></option>
								<option value="block" <?php selected( $editor, 'block' ); ?>><?php echo esc_html__( 'WordPress Block Editor', 'pnpc-pocket-service-desk' ); ?></option>
								<option value="diy" <?php selected( $editor, 'diy' ); ?>><?php echo esc_html__( 'DIY (no pages created)', 'pnpc-pocket-service-desk' ); ?></option>
							</select>
							<p class="description"><?php echo esc_html__( 'Elementor/Block will create a dashboard page with the canonical shortcode layout. DIY will create nothing and instead shows you the shortcodes to paste into your own page.', 'pnpc-pocket-service-desk' ); ?></p>
						</td>
					</tr>
				</table>

				<hr />

				<h2><?php echo esc_html__( 'Step 2 — Select or Create Dashboard Page', 'pnpc-pocket-service-desk' ); ?></h2>

				<?php if ( $dashboard_page ) : ?>
					<p><strong><?php echo esc_html__( 'Current linked dashboard:', 'pnpc-pocket-service-desk' ); ?></strong> <?php echo esc_html( get_the_title( $dashboard_page ) ); ?></p>
				<?php endif; ?>

				<?php if ( $dashboard_slug_candidate && ( ! $dashboard_page || (int) $dashboard_slug_candidate->ID !== (int) $dashboard_page->ID ) ) : ?>
					<div class="notice notice-info inline"><p><?php echo esc_html__( 'A page with the slug “dashboard” already exists. You can select it below if that is your intended Support Portal page.', 'pnpc-pocket-service-desk' ); ?></p></div>
				<?php endif; ?>

				<h3><?php echo esc_html__( 'Use an Existing Page', 'pnpc-pocket-service-desk' ); ?></h3>
				<input type="hidden" name="mode" value="use_existing" />
				<p>
					<?php
					wp_dropdown_pages( array(
						'name'              => 'existing_page_id',
						'id'                => 'existing_page_id',
						'show_option_none'  => esc_html__( '— Select —', 'pnpc-pocket-service-desk' ),
						'option_none_value' => '0',
						'selected'          => $dashboard_page ? (int) $dashboard_page->ID : 0,
					) );
					?>
				</p>
				<p class="description"><?php echo esc_html__( 'Selecting an existing page will not modify its content. You can paste the canonical shortcode layout into it manually if needed.', 'pnpc-pocket-service-desk' ); ?></p>
				<p><button type="submit" class="button button-primary"><?php echo esc_html__( 'Save Existing Page', 'pnpc-pocket-service-desk' ); ?></button></p>
			</form>

			<hr />

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=start' ) ); ?>">
				<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>
				<input type="hidden" name="mode" value="create" />
				<input type="hidden" name="editor" value="<?php echo esc_attr( $editor ); ?>" />

				<h3><?php echo esc_html__( 'Create a New Dashboard Page', 'pnpc-pocket-service-desk' ); ?></h3>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="page_title"><?php echo esc_html__( 'Page Title', 'pnpc-pocket-service-desk' ); ?></label></th>
						<td><input name="page_title" id="page_title" type="text" class="regular-text" value="<?php echo esc_attr__( 'Support Dashboard', 'pnpc-pocket-service-desk' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="page_slug"><?php echo esc_html__( 'Page Slug', 'pnpc-pocket-service-desk' ); ?></label></th>
						<td><input name="page_slug" id="page_slug" type="text" class="regular-text" value="dashboard" /></td>
					</tr>
				</table>

				<p class="description"><?php echo esc_html__( 'This will create a published Page that contains the canonical shortcode layout in a single column.', 'pnpc-pocket-service-desk' ); ?></p>
				<p><button type="submit" class="button"><?php echo esc_html__( 'Create Dashboard Page', 'pnpc-pocket-service-desk' ); ?></button></p>
			</form>

			<hr />

			<h2><?php echo esc_html__( 'Canonical Shortcode Layout', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php echo esc_html__( 'Use this stack (top to bottom) on your Dashboard page:', 'pnpc-pocket-service-desk' ); ?></p>
			<textarea class="large-text code" rows="10" readonly><?php echo esc_textarea( $canonical ); ?></textarea>

		</div>
	<?php endif; ?>
</div>
