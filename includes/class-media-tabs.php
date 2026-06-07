<?php
/**
 * Media Library scope controls.
 *
 * @package SKVN_Shipment_Tracking
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Media Library hooks.
 *
 * @return void
 */
function skvn_tracking_media_tabs_register() {
	add_action( 'admin_enqueue_scripts', 'skvn_tracking_media_tabs_enqueue' );
	add_filter( 'ajax_query_attachments_args', 'skvn_tracking_media_tabs_filter_ajax_query' );
	add_action( 'pre_get_posts', 'skvn_tracking_media_tabs_filter_list_query' );
}

/**
 * Normalize a requested Media Library scope.
 *
 * @param mixed $raw_scope Untrusted request value.
 * @return string
 */
function skvn_tracking_media_tabs_normalize_scope( $raw_scope ) {
	if ( ! is_scalar( $raw_scope ) ) {
		return 'posts';
	}

	$scope = sanitize_key( wp_unslash( (string) $raw_scope ) );

	return in_array( $scope, array( 'posts', 'shipment' ), true )
		? $scope
		: 'posts';
}

/**
 * Read the scope used for the current request.
 *
 * Grid requests put custom collection props inside the raw query payload.
 *
 * @param bool $is_ajax Whether to inspect the Media Grid AJAX payload.
 * @return string
 */
function skvn_tracking_media_tabs_get_request_scope( $is_ajax = false ) {
	if (
		$is_ajax
		&& isset( $_REQUEST['query'] )
		&& is_array( $_REQUEST['query'] )
		&& isset( $_REQUEST['query']['skvn_tracking_scope'] )
	) {
		return skvn_tracking_media_tabs_normalize_scope(
			$_REQUEST['query']['skvn_tracking_scope']
		);
	}

	$raw_scope = isset( $_GET['skvn_tracking_scope'] )
		? $_GET['skvn_tracking_scope']
		: 'posts';

	return skvn_tracking_media_tabs_normalize_scope( $raw_scope );
}

/**
 * Add the shipment ownership condition without dropping other meta clauses.
 *
 * @param array  $query_args Query arguments.
 * @param string $scope      Normalized Media Library scope.
 * @return array
 */
function skvn_tracking_media_tabs_apply_scope( $query_args, $scope ) {
	$scope_clause = array(
		'key'     => '_skvn_shipment_id',
		'compare' => 'shipment' === $scope ? 'EXISTS' : 'NOT EXISTS',
	);

	if ( empty( $query_args['meta_query'] ) || ! is_array( $query_args['meta_query'] ) ) {
		$query_args['meta_query'] = array( $scope_clause );
		return $query_args;
	}

	$query_args['meta_query'] = array(
		'relation' => 'AND',
		$query_args['meta_query'],
		$scope_clause,
	);

	return $query_args;
}

/**
 * Filter the Media Grid attachment query.
 *
 * @param array $query_args WP_Query arguments.
 * @return array
 */
function skvn_tracking_media_tabs_filter_ajax_query( $query_args ) {
	if (
		! current_user_can( 'upload_files' )
		|| ! isset( $_REQUEST['query'] )
		|| ! is_array( $_REQUEST['query'] )
		|| ! isset( $_REQUEST['query']['skvn_tracking_scope'] )
	) {
		return $query_args;
	}

	$scope = skvn_tracking_media_tabs_get_request_scope( true );

	return skvn_tracking_media_tabs_apply_scope( $query_args, $scope );
}

/**
 * Filter the server-rendered Media Library list view.
 *
 * @param WP_Query $query Current query.
 * @return void
 */
function skvn_tracking_media_tabs_filter_list_query( $query ) {
	global $pagenow;

	if (
		! is_admin()
		|| ! $query->is_main_query()
		|| 'upload.php' !== $pagenow
		|| 'attachment' !== $query->get( 'post_type' )
	) {
		return;
	}

	$query_args = skvn_tracking_media_tabs_apply_scope(
		array( 'meta_query' => $query->get( 'meta_query' ) ),
		skvn_tracking_media_tabs_get_request_scope()
	);

	$query->set( 'meta_query', $query_args['meta_query'] );
}

/**
 * Enqueue the Media Library controls on upload.php only.
 *
 * @param string $hook_suffix Current admin page hook.
 * @return void
 */
function skvn_tracking_media_tabs_enqueue( $hook_suffix ) {
	if ( 'upload.php' !== $hook_suffix || ! current_user_can( 'upload_files' ) ) {
		return;
	}

	$script_path = SKVN_TRACKING_PLUGIN_DIR . 'assets/js/admin-media-tabs.js';
	$style_path  = SKVN_TRACKING_PLUGIN_DIR . 'assets/css/admin-media-tabs.css';

	wp_enqueue_style(
		'skvn-tracking-media-tabs',
		SKVN_TRACKING_PLUGIN_URL . 'assets/css/admin-media-tabs.css',
		array(),
		file_exists( $style_path ) ? (string) filemtime( $style_path ) : SKVN_TRACKING_VERSION
	);

	wp_enqueue_script(
		'skvn-tracking-media-tabs',
		SKVN_TRACKING_PLUGIN_URL . 'assets/js/admin-media-tabs.js',
		array( 'jquery', 'media' ),
		file_exists( $script_path ) ? (string) filemtime( $script_path ) : SKVN_TRACKING_VERSION,
		true
	);

	wp_localize_script(
		'skvn-tracking-media-tabs',
		'skvnTrackingMediaTabs',
		array(
			'initialScope' => skvn_tracking_media_tabs_get_request_scope(),
			'queryKey'     => 'skvn_tracking_scope',
			'labels'       => array(
				'group'    => __( 'Filter Media Library by usage', 'skvn-shipment-tracking' ),
				'posts'    => __( 'Post - Pages', 'skvn-shipment-tracking' ),
				'shipment' => __( 'Shipment Tracking', 'skvn-shipment-tracking' ),
			),
		)
	);
}
