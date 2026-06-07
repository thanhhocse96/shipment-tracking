<?php
/**
 * Plugin activation and deactivation.
 *
 * @package SKVN_Shipment_Tracking
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register lifecycle hooks that also cover in-place plugin upgrades.
 *
 * @return void
 */
function skvn_tracking_plugin_lifecycle_register() {
	add_action( 'plugins_loaded', 'skvn_tracking_maybe_upgrade' );
}

/**
 * Create the base shipment upload directory.
 *
 * @return bool
 */
function skvn_tracking_ensure_upload_base() {
	$uploads = wp_upload_dir();

	if ( ! empty( $uploads['error'] ) ) {
		return false;
	}

	$directory = trailingslashit( $uploads['basedir'] ) . 'shipments';

	if ( ! wp_mkdir_p( $directory ) ) {
		return false;
	}

	$index = trailingslashit( $directory ) . 'index.php';

	if ( ! file_exists( $index ) && is_writable( $directory ) ) {
		@file_put_contents( $index, "<?php\n// Silence is golden.\n" );
	}

	return true;
}

/**
 * Add plugin capabilities to administrators.
 *
 * @return void
 */
function skvn_tracking_add_capabilities() {
	$role = get_role( 'administrator' );

	if ( $role ) {
		$role->add_cap( 'manage_skvn_tracking' );
	}
}

/**
 * Plugin activation.
 *
 * @return void
 */
function skvn_tracking_activate() {
	skvn_tracking_register_post_type();
	skvn_tracking_add_capabilities();
	skvn_tracking_ensure_upload_base();
	update_option( 'skvn_tracking_version', SKVN_TRACKING_VERSION, false );
	flush_rewrite_rules();
}

/**
 * Plugin deactivation.
 *
 * @return void
 */
function skvn_tracking_deactivate() {
	flush_rewrite_rules();
}

/**
 * Apply non-destructive setup when an active plugin is updated in place.
 *
 * @return void
 */
function skvn_tracking_maybe_upgrade() {
	$installed_version = get_option( 'skvn_tracking_version', '' );

	if ( SKVN_TRACKING_VERSION === $installed_version ) {
		return;
	}

	skvn_tracking_add_capabilities();
	skvn_tracking_ensure_upload_base();
	update_option( 'skvn_tracking_version', SKVN_TRACKING_VERSION, false );
}
