<?php
/**
 * Staff Upload Portal shell.
 *
 * @package SKVN_Shipment_Tracking
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Upload Portal hooks.
 *
 * @return void
 */
function skvn_tracking_upload_portal_register() {
	// Rendering is dispatched by class-routing.php.
}

/**
 * Enqueue portal styles for the current request.
 *
 * @return void
 */
function skvn_tracking_enqueue_upload_portal_styles() {
	wp_enqueue_style(
		'skvn-tracking-upload-portal',
		SKVN_TRACKING_PLUGIN_URL . 'assets/css/upload-portal.css',
		array(),
		SKVN_TRACKING_VERSION
	);
}

/**
 * Enqueue the authorized portal application and its non-secret contract.
 *
 * @return void
 */
function skvn_tracking_enqueue_upload_portal_app() {
	wp_enqueue_script(
		'skvn-tracking-upload-portal',
		SKVN_TRACKING_PLUGIN_URL . 'assets/js/upload-portal/upload-portal.js',
		array(),
		SKVN_TRACKING_VERSION,
		true
	);

	wp_localize_script(
		'skvn-tracking-upload-portal',
		'skvnTrackingUploadPortal',
		array(
			'schemaVersion' => '1.0',
			'maxFileSize'   => 20 * MB_IN_BYTES,
			'allowedTypes'  => array( 'image/webp', 'image/jpeg', 'image/png' ),
			'labels'        => array(
				'empty'           => __( 'No images selected', 'skvn-shipment-tracking' ),
				'selected'        => __( '%d images selected', 'skvn-shipment-tracking' ),
				'backendDisabled' => __( 'The upload endpoint will be connected in milestone 0.5.0. Your local selection is unchanged.', 'skvn-shipment-tracking' ),
			),
		)
	);
}

/**
 * Render the login form inside the tracking surface.
 *
 * @return void
 */
function skvn_tracking_render_upload_login() {
	?>
	<section class="skvn-tracking-upload-login" aria-labelledby="skvn-tracking-login-title">
		<p class="skvn-tracking-upload__eyebrow"><?php esc_html_e( 'Staff portal', 'skvn-shipment-tracking' ); ?></p>
		<h1 id="skvn-tracking-login-title"><?php esc_html_e( 'Sign in to prepare a shipment', 'skvn-shipment-tracking' ); ?></h1>
		<p><?php esc_html_e( 'Use your WordPress staff account. You will remain on this page after signing in.', 'skvn-shipment-tracking' ); ?></p>
		<?php
		wp_login_form(
			array(
				'echo'           => true,
				'redirect'       => home_url( '/tracking/upload/' ),
				'remember'       => true,
				'label_username' => __( 'Username or email address', 'skvn-shipment-tracking' ),
				'label_password' => __( 'Password', 'skvn-shipment-tracking' ),
				'label_log_in'   => __( 'Sign in', 'skvn-shipment-tracking' ),
			)
		);
		?>
	</section>
	<?php
}

/**
 * Render one file drop zone.
 *
 * @param string $zone_id Zone identifier.
 * @param string $title   Human-readable title.
 * @param string $hint    Zone hint.
 * @return void
 */
function skvn_tracking_render_upload_zone( $zone_id, $title, $hint ) {
	$input_id = 'skvn-tracking-files-' . $zone_id;
	?>
	<section class="skvn-tracking-zone" id="skvn-tracking-zone-<?php echo esc_attr( $zone_id ); ?>" data-zone="<?php echo esc_attr( $zone_id ); ?>">
		<div class="skvn-tracking-zone__heading">
			<div>
				<h2><?php echo esc_html( $title ); ?></h2>
				<p><?php echo esc_html( $hint ); ?></p>
			</div>
			<span class="skvn-tracking-zone__count" data-zone-count>0</span>
		</div>
		<label class="skvn-tracking-zone__drop" for="<?php echo esc_attr( $input_id ); ?>" tabindex="0">
			<strong><?php esc_html_e( 'Drop images here', 'skvn-shipment-tracking' ); ?></strong>
			<span><?php esc_html_e( 'or choose WebP, JPEG or PNG files', 'skvn-shipment-tracking' ); ?></span>
		</label>
		<input
			class="skvn-tracking-zone__input"
			id="<?php echo esc_attr( $input_id ); ?>"
			type="file"
			accept="image/webp,image/jpeg,image/png"
			multiple
			data-zone-input
		>
		<div class="skvn-tracking-zone__previews" data-zone-previews></div>
		<button class="skvn-tracking-zone__more" type="button" data-zone-more hidden></button>
	</section>
	<?php
}

/**
 * Render the authorized desktop portal shell.
 *
 * @return void
 */
function skvn_tracking_render_upload_shell() {
	$zones = array(
		'seal'          => array(
			'title' => __( 'Seal & Door Check', 'skvn-shipment-tracking' ),
			'hint'  => __( 'Container seal, lock and door condition.', 'skvn-shipment-tracking' ),
		),
		'temperature'   => array(
			'title' => __( 'Temperature Monitoring', 'skvn-shipment-tracking' ),
			'hint'  => __( 'Thermometers, displays and temperature records.', 'skvn-shipment-tracking' ),
		),
		'cargo'         => array(
			'title' => __( 'Cargo Rows', 'skvn-shipment-tracking' ),
			'hint'  => __( 'Cargo condition and loading rows.', 'skvn-shipment-tracking' ),
		),
		'uncategorized' => array(
			'title' => __( 'Uncategorized', 'skvn-shipment-tracking' ),
			'hint'  => __( 'Filename detection is used here when possible.', 'skvn-shipment-tracking' ),
		),
	);
	?>
	<div class="skvn-tracking-upload skvn-tracking-upload--desktop" data-upload-portal>
		<header class="skvn-tracking-upload__header">
			<div>
				<p class="skvn-tracking-upload__eyebrow"><?php esc_html_e( 'Shipment workspace', 'skvn-shipment-tracking' ); ?></p>
				<h1><?php esc_html_e( 'Prepare a photo batch', 'skvn-shipment-tracking' ); ?></h1>
			</div>
			<div class="skvn-tracking-upload__summary" aria-live="polite">
				<strong data-total-count><?php esc_html_e( 'No images selected', 'skvn-shipment-tracking' ); ?></strong>
				<button class="skvn-tracking-button" type="button" data-reset disabled>
					<?php esc_html_e( 'Reset selection', 'skvn-shipment-tracking' ); ?>
				</button>
				<button class="skvn-tracking-button skvn-tracking-button--primary" type="submit" form="skvn-tracking-upload-form" data-submit disabled>
					<?php esc_html_e( 'Create draft', 'skvn-shipment-tracking' ); ?>
				</button>
			</div>
		</header>

		<nav class="skvn-tracking-upload__zone-nav" aria-label="<?php esc_attr_e( 'Photo zones', 'skvn-shipment-tracking' ); ?>">
			<?php foreach ( $zones as $zone_id => $zone ) : ?>
				<a href="#skvn-tracking-zone-<?php echo esc_attr( $zone_id ); ?>"><?php echo esc_html( $zone['title'] ); ?></a>
			<?php endforeach; ?>
		</nav>

		<form id="skvn-tracking-upload-form" class="skvn-tracking-upload__form" data-upload-form>
			<?php wp_nonce_field( 'skvn_tracking_upload', 'skvn_tracking_nonce' ); ?>
			<section class="skvn-tracking-upload__metadata" aria-labelledby="skvn-tracking-metadata-title">
				<div class="skvn-tracking-upload__section-heading">
					<p class="skvn-tracking-upload__eyebrow"><?php esc_html_e( 'Step 1', 'skvn-shipment-tracking' ); ?></p>
					<h2 id="skvn-tracking-metadata-title"><?php esc_html_e( 'Shipment details', 'skvn-shipment-tracking' ); ?></h2>
				</div>
				<div class="skvn-tracking-fields">
					<label class="skvn-tracking-field skvn-tracking-field--wide">
						<span><?php esc_html_e( 'Batch or folder name', 'skvn-shipment-tracking' ); ?></span>
						<input type="text" name="batch_title" required autocomplete="off">
					</label>
					<label class="skvn-tracking-field">
						<span><?php esc_html_e( 'Client name', 'skvn-shipment-tracking' ); ?></span>
						<input type="text" name="client_name" autocomplete="organization">
					</label>
					<label class="skvn-tracking-field">
						<span><?php esc_html_e( 'Container number', 'skvn-shipment-tracking' ); ?></span>
						<input type="text" name="container_number" autocomplete="off">
					</label>
					<label class="skvn-tracking-field">
						<span><?php esc_html_e( 'Closing date', 'skvn-shipment-tracking' ); ?></span>
						<input type="date" name="closing_date">
					</label>
					<label class="skvn-tracking-field">
						<span><?php esc_html_e( 'Product type', 'skvn-shipment-tracking' ); ?></span>
						<input type="text" name="product_type" autocomplete="off">
					</label>
					<label class="skvn-tracking-field skvn-tracking-field--wide">
						<span><?php esc_html_e( 'Additional information', 'skvn-shipment-tracking' ); ?></span>
						<textarea name="batch_notes" rows="4"></textarea>
					</label>
				</div>
			</section>

			<section class="skvn-tracking-upload__photos" aria-labelledby="skvn-tracking-photos-title">
				<div class="skvn-tracking-upload__section-heading">
					<p class="skvn-tracking-upload__eyebrow"><?php esc_html_e( 'Step 2', 'skvn-shipment-tracking' ); ?></p>
					<h2 id="skvn-tracking-photos-title"><?php esc_html_e( 'Add shipment photos', 'skvn-shipment-tracking' ); ?></h2>
					<p><?php esc_html_e( 'Maximum 20 MB per file. Drag a preview to another zone to reassign it.', 'skvn-shipment-tracking' ); ?></p>
				</div>
				<div class="skvn-tracking-upload__errors" data-error-list aria-live="polite" hidden></div>
				<?php
				foreach ( $zones as $zone_id => $zone ) {
					skvn_tracking_render_upload_zone( $zone_id, $zone['title'], $zone['hint'] );
				}
				?>
			</section>
		</form>

		<div class="skvn-tracking-modal" data-gallery-modal hidden>
			<div class="skvn-tracking-modal__backdrop" data-modal-close></div>
			<div class="skvn-tracking-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="skvn-tracking-gallery-title">
				<div class="skvn-tracking-modal__header">
					<h2 id="skvn-tracking-gallery-title" data-modal-title></h2>
					<button type="button" class="skvn-tracking-modal__close" data-modal-close aria-label="<?php esc_attr_e( 'Close gallery', 'skvn-shipment-tracking' ); ?>">&times;</button>
				</div>
				<div class="skvn-tracking-modal__grid" data-modal-grid></div>
			</div>
		</div>

		<p class="skvn-tracking-upload__notice" data-submit-notice role="status" hidden></p>
	</div>
	<?php
}

/**
 * Render the staff upload route.
 *
 * @return void
 */
function skvn_tracking_render_upload_portal() {
	$can_manage = is_user_logged_in() && current_user_can( 'manage_skvn_tracking' );

	status_header( is_user_logged_in() && ! $can_manage ? 403 : 200 );
	nocache_headers();
	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
	skvn_tracking_send_noindex_headers();
	skvn_tracking_enqueue_upload_portal_styles();

	if ( $can_manage ) {
		skvn_tracking_enqueue_upload_portal_app();
	}

	get_header();
	?>
	<main class="skvn-tracking-upload-page">
		<?php
		if ( ! is_user_logged_in() ) {
			skvn_tracking_render_upload_login();
		} elseif ( ! $can_manage ) {
			?>
			<section class="skvn-tracking-upload-login">
				<h1><?php esc_html_e( 'Access denied', 'skvn-shipment-tracking' ); ?></h1>
				<p><?php esc_html_e( 'Your account does not have permission to manage shipment tracking.', 'skvn-shipment-tracking' ); ?></p>
			</section>
			<?php
		} else {
			skvn_tracking_render_upload_shell();
		}
		?>
	</main>
	<?php
	get_footer();
}
