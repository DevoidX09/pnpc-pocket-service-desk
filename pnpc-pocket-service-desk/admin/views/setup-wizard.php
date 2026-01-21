<?php
/**
 * Setup Wizard admin view - Modern 5-step wizard
 *
 * @var string $step Current step
 * @var int    $dashboard_page_id Dashboard page ID
 * @var WP_Post|null $dashboard_page Dashboard page object
 * @var WP_Post|null $dashboard_slug_candidate Candidate page with 'dashboard' slug
 * @var string $editor Selected editor type
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

// Handle backward compatibility: both 'done' and 'complete' refer to the final step
if ( 'done' === $step ) {
$step = 'complete';
}

$error = get_option( 'pnpc_psd_setup_error', '' );
if ( $error ) {
delete_option( 'pnpc_psd_setup_error' );
}

$canonical = "[pnpc_profile_settings]\n\n[pnpc_service_desk]\n\n[pnpc_create_ticket]\n\n[pnpc_services]\n\n[pnpc_my_tickets]\n";

// Define step mapping
$step_numbers = array(
'welcome'   => 1,
'scan'      => 2,
'builder'   => 3,
'shortcodes' => 4,
'complete'  => 5,
'start'     => 2, // Legacy step, map to scan
);

$current_step_number = isset( $step_numbers[ $step ] ) ? $step_numbers[ $step ] : 1;

/**
 * Render progress bar component
 */
function pnpc_psd_render_progress_bar( $current ) {
$steps = array(
1 => __( 'Welcome', 'pnpc-pocket-service-desk' ),
2 => __( 'Scan', 'pnpc-pocket-service-desk' ),
3 => __( 'Builder', 'pnpc-pocket-service-desk' ),
4 => __( 'Shortcodes', 'pnpc-pocket-service-desk' ),
5 => __( 'Complete', 'pnpc-pocket-service-desk' ),
);
?>
<ul class="pnpc-psd-progress-bar">
<?php foreach ( $steps as $num => $label ) : ?>
<?php
$class = '';
if ( $num < $current ) {
$class = 'completed';
} elseif ( $num === $current ) {
$class = 'active';
}
?>
<li class="pnpc-psd-progress-step <?php echo esc_attr( $class ); ?>">
<div class="step-circle"><?php echo absint( $num ); ?></div>
<div class="step-label"><?php echo esc_html( $label ); ?></div>
</li>
<?php endforeach; ?>
</ul>
<?php
}
?>

<div class="wrap">
<h1><?php echo esc_html__( 'Service Desk Setup Wizard', 'pnpc-pocket-service-desk' ); ?></h1>

<?php if ( $error ) : ?>
<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
<?php endif; ?>

<div class="pnpc-psd-wizard-container">

<?php
// ========================================
// STEP 1: WELCOME
// ========================================
if ( 'welcome' === $step ) :
pnpc_psd_render_progress_bar( 1 );
?>

<div class="pnpc-psd-welcome-hero">
<h1><?php echo esc_html__( 'Welcome to Service Desk Setup', 'pnpc-pocket-service-desk' ); ?></h1>
<p><?php echo esc_html__( 'Let\'s create your customer support portal in just a few minutes', 'pnpc-pocket-service-desk' ); ?></p>
</div>

<div class="pnpc-psd-time-estimate">
<span class="dashicons dashicons-clock"></span>
<strong><?php echo esc_html__( 'Estimated Time:', 'pnpc-pocket-service-desk' ); ?></strong>
<?php echo esc_html__( '2-3 minutes', 'pnpc-pocket-service-desk' ); ?>
</div>

<h2><?php echo esc_html__( 'What We\'ll Set Up', 'pnpc-pocket-service-desk' ); ?></h2>
<div class="pnpc-psd-features-grid">
<div class="pnpc-psd-feature-card">
<span class="dashicons dashicons-admin-page"></span>
<h3><?php echo esc_html__( 'Dashboard Page', 'pnpc-pocket-service-desk' ); ?></h3>
<p><?php echo esc_html__( 'A dedicated page where customers can view and manage their support tickets', 'pnpc-pocket-service-desk' ); ?></p>
</div>
<div class="pnpc-psd-feature-card">
<span class="dashicons dashicons-tickets"></span>
<h3><?php echo esc_html__( 'Ticket System', 'pnpc-pocket-service-desk' ); ?></h3>
<p><?php echo esc_html__( 'Create, view, and track support tickets with real-time updates', 'pnpc-pocket-service-desk' ); ?></p>
</div>
<div class="pnpc-psd-feature-card">
<span class="dashicons dashicons-admin-users"></span>
<h3><?php echo esc_html__( 'Customer Portal', 'pnpc-pocket-service-desk' ); ?></h3>
<p><?php echo esc_html__( 'Secure login area showing only each customer\'s own tickets', 'pnpc-pocket-service-desk' ); ?></p>
</div>
</div>

<div style="text-align: center; margin: 40px 0;">
<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=scan' ) ); ?>" class="button button-primary button-hero">
<?php echo esc_html__( 'Get Started', 'pnpc-pocket-service-desk' ); ?>
</a>
<br><br>
<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=builder' ) ); ?>" class="button button-secondary">
<?php echo esc_html__( 'Skip to Manual Setup', 'pnpc-pocket-service-desk' ); ?>
</a>
</div>

<?php
// ========================================
// STEP 2: SCAN
// ========================================
elseif ( 'scan' === $step || 'start' === $step ) :
pnpc_psd_render_progress_bar( 2 );

// Scan for existing dashboard page and pages with 'dashboard' slug
$existing_dashboard_page = null;
if ( $dashboard_page_id ) {
$existing_dashboard_page = get_post( $dashboard_page_id );
}

$dashboard_slug_pages = get_posts( array(
'name'           => 'dashboard',
'post_type'      => 'page',
'post_status'    => 'any',
'posts_per_page' => 5,
) );
?>

<h2><?php echo esc_html__( 'Scanning for Existing Pages', 'pnpc-pocket-service-desk' ); ?></h2>
<p><?php echo esc_html__( 'We\'re checking if you already have a dashboard page set up.', 'pnpc-pocket-service-desk' ); ?></p>

<?php if ( $existing_dashboard_page ) : ?>
<div class="notice notice-success inline">
<p><strong><?php echo esc_html__( 'Dashboard Page Already Configured!', 'pnpc-pocket-service-desk' ); ?></strong></p>
</div>
<div class="pnpc-psd-scan-results">
<div class="pnpc-psd-scan-result-item">
<div class="result-info">
<h4><?php echo esc_html( get_the_title( $existing_dashboard_page ) ); ?></h4>
<p>
<?php echo esc_html__( 'Status:', 'pnpc-pocket-service-desk' ); ?>
<?php echo esc_html( ucfirst( $existing_dashboard_page->post_status ) ); ?>
&nbsp;|&nbsp;
<?php echo esc_html__( 'Slug:', 'pnpc-pocket-service-desk' ); ?>
<?php echo esc_html( $existing_dashboard_page->post_name ); ?>
</p>
</div>
<div class="result-actions">
<a href="<?php echo esc_url( get_permalink( $existing_dashboard_page ) ); ?>" class="button" target="_blank" rel="noopener noreferrer">
<?php echo esc_html__( 'View Page', 'pnpc-pocket-service-desk' ); ?>
</a>
<a href="<?php echo esc_url( get_edit_post_link( $existing_dashboard_page->ID ) ); ?>" class="button">
<?php echo esc_html__( 'Edit Page', 'pnpc-pocket-service-desk' ); ?>
</a>
</div>
</div>
</div>
<p>
<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=shortcodes' ) ); ?>" class="button button-primary">
<?php echo esc_html__( 'Continue to Shortcodes', 'pnpc-pocket-service-desk' ); ?>
</a>
<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=builder' ) ); ?>" class="button">
<?php echo esc_html__( 'Create New Page Instead', 'pnpc-pocket-service-desk' ); ?>
</a>
</p>
<?php elseif ( ! empty( $dashboard_slug_pages ) ) : ?>
<div class="notice notice-info inline">
<p><?php echo esc_html__( 'Found pages with "dashboard" slug. You can use one of these:', 'pnpc-pocket-service-desk' ); ?></p>
</div>
<div class="pnpc-psd-scan-results">
<?php foreach ( $dashboard_slug_pages as $page ) : ?>
<div class="pnpc-psd-scan-result-item">
<div class="result-info">
<h4><?php echo esc_html( get_the_title( $page ) ); ?></h4>
<p>
<?php echo esc_html__( 'Status:', 'pnpc-pocket-service-desk' ); ?>
<?php echo esc_html( ucfirst( $page->post_status ) ); ?>
&nbsp;|&nbsp;
<?php echo esc_html__( 'Slug:', 'pnpc-pocket-service-desk' ); ?>
<?php echo esc_html( $page->post_name ); ?>
</p>
</div>
<div class="result-actions">
<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=scan' ) ); ?>" style="display: inline;">
<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>
<input type="hidden" name="mode" value="use_existing" />
<input type="hidden" name="existing_page_id" value="<?php echo absint( $page->ID ); ?>" />
<button type="submit" class="button button-primary">
<?php echo esc_html__( 'Use This Page', 'pnpc-pocket-service-desk' ); ?>
</button>
</form>
<a href="<?php echo esc_url( get_permalink( $page ) ); ?>" class="button" target="_blank" rel="noopener noreferrer">
<?php echo esc_html__( 'Preview', 'pnpc-pocket-service-desk' ); ?>
</a>
</div>
</div>
<?php endforeach; ?>
</div>
<hr>
<p>
<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=builder' ) ); ?>" class="button button-primary">
<?php echo esc_html__( 'Create New Page Instead', 'pnpc-pocket-service-desk' ); ?>
</a>
</p>
<?php else : ?>
<div class="notice notice-warning inline">
<p><?php echo esc_html__( 'No existing dashboard page found. Let\'s create one!', 'pnpc-pocket-service-desk' ); ?></p>
</div>
<p>
<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=builder' ) ); ?>" class="button button-primary">
<?php echo esc_html__( 'Create Dashboard Page', 'pnpc-pocket-service-desk' ); ?>
</a>
</p>
<?php endif; ?>

<?php
// ========================================
// STEP 3: BUILDER
// ========================================
elseif ( 'builder' === $step ) :
pnpc_psd_render_progress_bar( 3 );
?>

<h2><?php echo esc_html__( 'Choose Your Page Builder', 'pnpc-pocket-service-desk' ); ?></h2>
<p><?php echo esc_html__( 'Select how you want to build your dashboard page. We\'ll add the necessary shortcodes automatically.', 'pnpc-pocket-service-desk' ); ?></p>

<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=builder' ) ); ?>" id="builder-form">
<?php wp_nonce_field( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' ); ?>
<input type="hidden" name="mode" value="create" />
<input type="hidden" name="editor" id="selected-editor" value="<?php echo esc_attr( defined( 'ELEMENTOR_VERSION' ) ? 'elementor' : 'block' ); ?>" />

<div class="pnpc-psd-builder-grid">
<div class="pnpc-psd-builder-card <?php echo defined( 'ELEMENTOR_VERSION' ) ? 'selected' : ''; ?>" data-editor="elementor">
<?php if ( defined( 'ELEMENTOR_VERSION' ) ) : ?>
<span class="badge badge-recommended"><?php echo esc_html__( 'Recommended', 'pnpc-pocket-service-desk' ); ?></span>
<?php endif; ?>
<div class="builder-icon">
<span class="dashicons dashicons-editor-table"></span>
</div>
<h3><?php echo esc_html__( 'Elementor', 'pnpc-pocket-service-desk' ); ?></h3>
<p><?php echo esc_html__( 'Visual drag-and-drop builder with advanced styling options and flexibility.', 'pnpc-pocket-service-desk' ); ?></p>
<?php if ( ! defined( 'ELEMENTOR_VERSION' ) ) : ?>
<p style="color: #d63638; font-size: 12px; margin-top: 8px;">
<?php echo esc_html__( 'Elementor not detected', 'pnpc-pocket-service-desk' ); ?>
</p>
<?php endif; ?>
</div>

<div class="pnpc-psd-builder-card <?php echo ! defined( 'ELEMENTOR_VERSION' ) ? 'selected' : ''; ?>" data-editor="block">
<span class="badge badge-info"><?php echo esc_html__( 'Standard', 'pnpc-pocket-service-desk' ); ?></span>
<div class="builder-icon">
<span class="dashicons dashicons-layout"></span>
</div>
<h3><?php echo esc_html__( 'Block Editor', 'pnpc-pocket-service-desk' ); ?></h3>
<p><?php echo esc_html__( 'WordPress native Gutenberg editor with block-based content creation.', 'pnpc-pocket-service-desk' ); ?></p>
</div>

<div class="pnpc-psd-builder-card" data-editor="diy">
<div class="builder-icon">
<span class="dashicons dashicons-admin-tools"></span>
</div>
<h3><?php echo esc_html__( 'DIY (Manual)', 'pnpc-pocket-service-desk' ); ?></h3>
<p><?php echo esc_html__( 'Skip page creation and manually add shortcodes to your own custom page.', 'pnpc-pocket-service-desk' ); ?></p>
</div>
</div>

<div class="pnpc-psd-form-section">
<h3><?php echo esc_html__( 'Page Details', 'pnpc-pocket-service-desk' ); ?></h3>
<table class="form-table" role="presentation">
<tr>
<th scope="row">
<label for="page_title"><?php echo esc_html__( 'Page Title', 'pnpc-pocket-service-desk' ); ?></label>
</th>
<td>
<input name="page_title" id="page_title" type="text" class="regular-text" value="<?php echo esc_attr__( 'Support Dashboard', 'pnpc-pocket-service-desk' ); ?>" />
<p class="description"><?php echo esc_html__( 'The title of your dashboard page', 'pnpc-pocket-service-desk' ); ?></p>
</td>
</tr>
<tr>
<th scope="row">
<label for="page_slug"><?php echo esc_html__( 'Page Slug', 'pnpc-pocket-service-desk' ); ?></label>
</th>
<td>
<input name="page_slug" id="page_slug" type="text" class="regular-text" value="dashboard" />
<p class="description"><?php echo esc_html__( 'URL-friendly version (e.g., yoursite.com/dashboard)', 'pnpc-pocket-service-desk' ); ?></p>
</td>
</tr>
</table>
</div>

<p>
<button type="submit" class="button button-primary button-large">
<?php echo esc_html__( 'Create Dashboard Page', 'pnpc-pocket-service-desk' ); ?>
</button>
<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=scan' ) ); ?>" class="button">
<?php echo esc_html__( 'Back', 'pnpc-pocket-service-desk' ); ?>
</a>
</p>
</form>

<script>
(function() {
var cards = document.querySelectorAll('.pnpc-psd-builder-card');
var editorInput = document.getElementById('selected-editor');

if (editorInput) {
cards.forEach(function(card) {
card.addEventListener('click', function() {
cards.forEach(function(c) { c.classList.remove('selected'); });
this.classList.add('selected');
editorInput.value = this.getAttribute('data-editor');
});
});
}
})();
</script>

<?php
// ========================================
// STEP 4: SHORTCODES
// ========================================
elseif ( 'shortcodes' === $step ) :
pnpc_psd_render_progress_bar( 4 );

$shortcodes = array(
array(
'code'        => '[pnpc_service_desk]',
'title'       => __( 'Main Dashboard', 'pnpc-pocket-service-desk' ),
'description' => __( 'The primary dashboard widget with login gate. Shows ticket overview, profile settings, and navigation for logged-in customers.', 'pnpc-pocket-service-desk' ),
),
array(
'code'        => '[pnpc_profile_settings]',
'title'       => __( 'Profile Settings', 'pnpc-pocket-service-desk' ),
'description' => __( 'User profile management area where customers can update their account information and preferences.', 'pnpc-pocket-service-desk' ),
),
array(
'code'        => '[pnpc_create_ticket]',
'title'       => __( 'Create Ticket Form', 'pnpc-pocket-service-desk' ),
'description' => __( 'Ticket submission form allowing customers to create new support requests with attachments and priority selection.', 'pnpc-pocket-service-desk' ),
),
array(
'code'        => '[pnpc_my_tickets]',
'title'       => __( 'My Tickets List', 'pnpc-pocket-service-desk' ),
'description' => __( 'Displays customer\'s tickets in a tabbed interface (Open, In Progress, Resolved). Includes search and filter options.', 'pnpc-pocket-service-desk' ),
),
array(
'code'        => '[pnpc_ticket_detail]',
'title'       => __( 'Ticket Detail View', 'pnpc-pocket-service-desk' ),
'description' => __( 'Full ticket view showing conversation history, attachments, status updates, and reply functionality.', 'pnpc-pocket-service-desk' ),
),
array(
'code'        => '[pnpc_services]',
'title'       => __( 'Services (WooCommerce)', 'pnpc-pocket-service-desk' ),
'description' => __( 'Displays available support services/products from WooCommerce. Requires WooCommerce to be active.', 'pnpc-pocket-service-desk' ),
'badge'       => ! class_exists( 'WooCommerce' ) ? __( 'Requires WooCommerce', 'pnpc-pocket-service-desk' ) : '',
),
);
?>

<h2><?php echo esc_html__( 'Available Shortcodes', 'pnpc-pocket-service-desk' ); ?></h2>
<p><?php echo esc_html__( 'Here are all the shortcodes you can use to build your support dashboard. The recommended layout is already applied to your page.', 'pnpc-pocket-service-desk' ); ?></p>

<div class="pnpc-psd-shortcodes-list">
<?php foreach ( $shortcodes as $shortcode ) : ?>
<div class="pnpc-psd-shortcode-item">
<div class="shortcode-header">
<div>
<code class="shortcode-code"><?php echo esc_html( $shortcode['code'] ); ?></code>
</div>
<button type="button" class="button copy-button" data-clipboard="<?php echo esc_attr( $shortcode['code'] ); ?>">
<?php echo esc_html__( 'Copy', 'pnpc-pocket-service-desk' ); ?>
</button>
</div>
<h3 class="shortcode-title">
<?php echo esc_html( $shortcode['title'] ); ?>
<?php if ( ! empty( $shortcode['badge'] ) ) : ?>
<span class="badge badge-required" style="font-size: 10px; padding: 2px 8px; margin-left: 8px;">
<?php echo esc_html( $shortcode['badge'] ); ?>
</span>
<?php endif; ?>
</h3>
<p class="shortcode-description"><?php echo esc_html( $shortcode['description'] ); ?></p>
</div>
<?php endforeach; ?>
</div>

<div class="pnpc-psd-form-section">
<h3><?php echo esc_html__( 'Recommended Shortcode Layout', 'pnpc-pocket-service-desk' ); ?></h3>
<p><?php echo esc_html__( 'Copy this complete layout to use all features in a single-column design:', 'pnpc-pocket-service-desk' ); ?></p>
<textarea class="large-text code" rows="8" readonly onclick="this.select()"><?php echo esc_textarea( $canonical ); ?></textarea>
<p>
<button type="button" class="button copy-layout-button">
<?php echo esc_html__( 'Copy Layout', 'pnpc-pocket-service-desk' ); ?>
</button>
</p>
</div>

<p>
<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=complete' ) ); ?>" class="button button-primary button-large">
<?php echo esc_html__( 'Continue to Finish', 'pnpc-pocket-service-desk' ); ?>
</a>
</p>

<script>
(function() {
document.querySelectorAll('.copy-button').forEach(function(button) {
button.addEventListener('click', function() {
var text = this.getAttribute('data-clipboard');
navigator.clipboard.writeText(text).then(function() {
button.textContent = '<?php echo esc_js( __( 'Copied!', 'pnpc-pocket-service-desk' ) ); ?>';
setTimeout(function() {
button.textContent = '<?php echo esc_js( __( 'Copy', 'pnpc-pocket-service-desk' ) ); ?>';
}, 2000);
}).catch(function(err) {
console.error('Failed to copy text:', err);
});
});
});

var copyLayoutButton = document.querySelector('.copy-layout-button');
if (copyLayoutButton) {
copyLayoutButton.addEventListener('click', function() {
var textarea = this.parentElement.previousElementSibling;
navigator.clipboard.writeText(textarea.value).then(function() {
copyLayoutButton.textContent = '<?php echo esc_js( __( 'Copied!', 'pnpc-pocket-service-desk' ) ); ?>';
setTimeout(function() {
copyLayoutButton.textContent = '<?php echo esc_js( __( 'Copy Layout', 'pnpc-pocket-service-desk' ) ); ?>';
}, 2000);
}).catch(function(err) {
console.error('Failed to copy text:', err);
});
});
}
});
})();
</script>

<?php
// ========================================
// STEP 5: COMPLETE
// ========================================
elseif ( 'complete' === $step ) :
pnpc_psd_render_progress_bar( 5 );
?>

<div class="pnpc-psd-success-icon">
<span class="dashicons dashicons-yes-alt"></span>
</div>

<div style="text-align: center;">
<h2><?php echo esc_html__( 'Setup Complete!', 'pnpc-pocket-service-desk' ); ?></h2>
<p style="font-size: 16px;"><?php echo esc_html__( 'Your customer support dashboard is ready to use.', 'pnpc-pocket-service-desk' ); ?></p>
</div>

<?php if ( $dashboard_page ) : ?>
<div class="card" style="max-width: 700px; margin: 20px auto; padding: 20px;">
<h3 style="margin-top: 0;"><?php echo esc_html__( 'Dashboard Page Created', 'pnpc-pocket-service-desk' ); ?></h3>
<p>
<strong><?php echo esc_html__( 'Page Title:', 'pnpc-pocket-service-desk' ); ?></strong>
<?php echo esc_html( get_the_title( $dashboard_page ) ); ?>
</p>
<p>
<strong><?php echo esc_html__( 'Permalink:', 'pnpc-pocket-service-desk' ); ?></strong><br>
<a href="<?php echo esc_url( get_permalink( $dashboard_page ) ); ?>" target="_blank" rel="noopener noreferrer">
<?php echo esc_html( get_permalink( $dashboard_page ) ); ?>
</a>
</p>
</div>
<?php endif; ?>

<div class="pnpc-psd-important-notice">
<div class="notice-header">
<div class="notice-icon">
<span class="dashicons dashicons-warning"></span>
</div>
<h3><?php echo esc_html__( 'Important: Add Menu Link', 'pnpc-pocket-service-desk' ); ?></h3>
</div>
<div class="notice-content">
<p><strong><?php echo esc_html__( 'Your customers need a way to access the dashboard!', 'pnpc-pocket-service-desk' ); ?></strong></p>
<p><?php echo esc_html__( 'Add a menu link to your dashboard page so customers can log in and access their support portal.', 'pnpc-pocket-service-desk' ); ?></p>
<ul class="notice-steps">
<li><?php echo esc_html__( 'Go to Appearance → Menus', 'pnpc-pocket-service-desk' ); ?></li>
<li><?php echo esc_html__( 'Add your dashboard page to the main navigation menu', 'pnpc-pocket-service-desk' ); ?></li>
<li><?php echo esc_html__( 'Label it "Customer Login", "Support Portal", or similar', 'pnpc-pocket-service-desk' ); ?></li>
</ul>
<p>
<a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>" class="button button-primary">
<span class="dashicons dashicons-menu" style="margin-top: 3px;"></span>
<?php echo esc_html__( 'Go to Menus', 'pnpc-pocket-service-desk' ); ?>
</a>
</p>
<p style="margin-top: 12px; color: #666; font-size: 13px;">
<em><?php echo esc_html__( 'Why is this important? Logged-out users will be prompted to log in, and logged-in users will see only their own tickets.', 'pnpc-pocket-service-desk' ); ?></em>
</p>
</div>
</div>

<?php if ( $dashboard_page ) : ?>
<div style="text-align: center; margin: 30px 0;">
<h3><?php echo esc_html__( 'Quick Actions', 'pnpc-pocket-service-desk' ); ?></h3>
<div class="pnpc-psd-quick-actions" style="justify-content: center;">
<a class="button button-primary button-large" href="<?php echo esc_url( get_permalink( $dashboard_page ) ); ?>" target="_blank" rel="noopener noreferrer">
<?php echo esc_html__( 'View Dashboard', 'pnpc-pocket-service-desk' ); ?>
</a>
<a class="button button-large" href="<?php echo esc_url( get_edit_post_link( $dashboard_page->ID ) ); ?>">
<?php echo esc_html__( 'Edit Page', 'pnpc-pocket-service-desk' ); ?>
</a>
<?php if ( 'elementor' === $editor && defined( 'ELEMENTOR_VERSION' ) ) : ?>
<a class="button button-large" href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $dashboard_page->ID ) . '&action=elementor' ) ); ?>">
<?php echo esc_html__( 'Edit with Elementor', 'pnpc-pocket-service-desk' ); ?>
</a>
<?php endif; ?>
</div>
</div>
<?php endif; ?>

<hr style="margin: 40px 0;">

<div style="text-align: center;">
<p>
<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=welcome' ) ); ?>">
<?php echo esc_html__( 'Run Wizard Again', 'pnpc-pocket-service-desk' ); ?>
</a>
<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=shortcodes' ) ); ?>">
<?php echo esc_html__( 'View Shortcodes Reference', 'pnpc-pocket-service-desk' ); ?>
</a>
</p>
</div>

<?php endif; ?>

</div>
</div>
