<?php
/**
 * Shared routing for tracking surfaces.
 *
 * @package SKVN_Shipment_Tracking
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register tracking route hooks.
 *
 * @return void
 */
function skvn_tracking_routing_register() {
	add_action( 'init', 'skvn_tracking_register_rewrite_rules' );
	add_action( 'init', 'skvn_tracking_maybe_flush_rewrite_rules', 99 );
	add_filter( 'query_vars', 'skvn_tracking_register_query_vars' );
	add_filter( 'redirect_canonical', 'skvn_tracking_disable_route_canonical_redirect', 10, 2 );
	add_action( 'template_redirect', 'skvn_tracking_dispatch_route' );
}

/**
 * Register plugin-owned tracking rewrites.
 *
 * @return void
 */
function skvn_tracking_register_rewrite_rules() {
	add_rewrite_rule(
		'^tracking/upload/?$',
		'index.php?skvn_tracking_route=upload',
		'top'
	);
	add_rewrite_rule(
		'^tracking/([^/]+)/?$',
		'index.php?skvn_tracking_route=batch&skvn_tracking_slug=$matches[1]',
		'top'
	);
}

/**
 * Flush rules once when this routing contract changes.
 *
 * @return void
 */
function skvn_tracking_maybe_flush_rewrite_rules() {
	if ( SKVN_TRACKING_VERSION === get_option( 'skvn_tracking_rewrite_version', '' ) ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( 'skvn_tracking_rewrite_version', SKVN_TRACKING_VERSION, false );
}

/**
 * Add route query vars.
 *
 * @param string[] $query_vars Registered public query vars.
 * @return string[]
 */
function skvn_tracking_register_query_vars( $query_vars ) {
	$query_vars[] = 'skvn_tracking_route';
	$query_vars[] = 'skvn_tracking_slug';

	return $query_vars;
}

/**
 * Prevent WordPress canonical redirects from taking over plugin routes.
 *
 * @param string|false $redirect_url  Proposed redirect.
 * @param string       $requested_url Requested URL.
 * @return string|false
 */
function skvn_tracking_disable_route_canonical_redirect( $redirect_url, $requested_url ) {
	unset( $requested_url );

	return get_query_var( 'skvn_tracking_route' ) ? false : $redirect_url;
}

/**
 * Send the robots policy shared by non-indexable tracking routes.
 *
 * @return void
 */
function skvn_tracking_send_noindex_headers() {
	header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
}

/**
 * Render a safe foundation page for a shipment slug.
 *
 * This milestone deliberately does not read private batch metadata.
 *
 * @param string $slug Requested shipment slug.
 * @return void
 */
function skvn_tracking_render_batch_foundation( $slug ) {
	status_header( 200 );
	skvn_tracking_send_noindex_headers();

	get_header();
	?>
	<main class="skvn-tracking-route skvn-tracking-route--batch">
		<div class="skvn-tracking-route__inner">
			<h1><?php esc_html_e( 'Shipment tracking', 'skvn-shipment-tracking' ); ?></h1>
			<p><?php esc_html_e( 'This shipment view is being prepared.', 'skvn-shipment-tracking' ); ?></p>
		</div>
	</main>
	<?php
	get_footer();
}

/**
 * Dispatch plugin-owned routes before theme template resolution.
 *
 * @return void
 */
function skvn_tracking_dispatch_route() {
	$route = sanitize_key( (string) get_query_var( 'skvn_tracking_route' ) );

	if ( 'upload' === $route ) {
		skvn_tracking_render_upload_portal();
		exit;
	}

	if ( 'batch' === $route ) {
		$slug = sanitize_title( (string) get_query_var( 'skvn_tracking_slug' ) );
		skvn_tracking_render_batch_foundation( $slug );
		exit;
	}
}
