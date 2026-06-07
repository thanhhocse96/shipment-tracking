<?php
/**
 * Shipment token and protected original-file delivery.
 *
 * @package SKVN_Shipment_Tracking
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register token and protected file hooks.
 *
 * @return void
 */
function skvn_tracking_access_control_register() {
	add_action( 'wp_after_insert_post', 'skvn_tracking_maybe_create_batch_token', 10, 2 );
	add_action( 'init', 'skvn_tracking_maybe_backfill_batch_tokens', 20 );
	add_action( 'admin_post_skvn_tracking_file', 'skvn_tracking_serve_protected_file' );
	add_action( 'admin_post_nopriv_skvn_tracking_file', 'skvn_tracking_serve_protected_file' );
}

/**
 * Generate a cryptographically secure token.
 *
 * @return string
 */
function skvn_tracking_generate_token() {
	try {
		return bin2hex( random_bytes( 16 ) );
	} catch ( Exception $exception ) {
		return substr(
			hash( 'sha256', wp_generate_password( 64, true, true ) . microtime( true ) ),
			0,
			32
		);
	}
}

/**
 * Ensure a shipment has a token after insertion.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Inserted post.
 * @return void
 */
function skvn_tracking_maybe_create_batch_token( $post_id, $post ) {
	if ( 'skvn_shipment' !== $post->post_type || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( '' === get_post_meta( $post_id, '_skvn_token', true ) ) {
		update_post_meta( $post_id, '_skvn_token', skvn_tracking_generate_token() );
	}
}

/**
 * Backfill tokens for shipments created before milestone 0.3.0.
 *
 * @return void
 */
function skvn_tracking_maybe_backfill_batch_tokens() {
	if ( '0.3.0' === get_option( 'skvn_tracking_token_migration_version', '' ) ) {
		return;
	}

	$batch_ids = get_posts(
		array(
			'post_type'      => 'skvn_shipment',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_skvn_token',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_skvn_token',
					'value'   => '',
					'compare' => '=',
				),
			),
		)
	);

	foreach ( $batch_ids as $batch_id ) {
		update_post_meta( $batch_id, '_skvn_token', skvn_tracking_generate_token() );
	}

	update_option( 'skvn_tracking_token_migration_version', '0.3.0', false );
}

/**
 * Rotate a shipment token.
 *
 * @param int $batch_id Shipment post ID.
 * @return string|WP_Error
 */
function skvn_tracking_rotate_batch_token( $batch_id ) {
	$batch_id = absint( $batch_id );
	$batch    = get_post( $batch_id );

	if ( ! $batch || 'skvn_shipment' !== $batch->post_type ) {
		return new WP_Error( 'skvn_tracking_invalid_batch', __( 'Invalid shipment.', 'skvn-shipment-tracking' ) );
	}

	if ( ! current_user_can( 'manage_skvn_tracking' ) ) {
		return new WP_Error( 'skvn_tracking_forbidden', __( 'You cannot rotate this token.', 'skvn-shipment-tracking' ) );
	}

	$token = skvn_tracking_generate_token();
	update_post_meta( $batch_id, '_skvn_token', $token );

	return $token;
}

/**
 * Validate a token against a shipment.
 *
 * @param int    $batch_id      Shipment post ID.
 * @param mixed  $request_token Untrusted token.
 * @return bool
 */
function skvn_tracking_validate_batch_token( $batch_id, $request_token ) {
	if ( ! is_scalar( $request_token ) ) {
		return false;
	}

	$request_token = skvn_tracking_sanitize_token( wp_unslash( (string) $request_token ) );

	if ( '' === $request_token ) {
		return false;
	}

	$stored_token = get_post_meta( absint( $batch_id ), '_skvn_token', true );

	return is_string( $stored_token )
		&& 32 === strlen( $stored_token )
		&& hash_equals( $stored_token, $request_token );
}

/**
 * Build a protected original-file URL.
 *
 * Call only after authorization; the returned URL contains the private token.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $token         Valid shipment token.
 * @return string
 */
function skvn_tracking_get_protected_file_url( $attachment_id, $token ) {
	return add_query_arg(
		array(
			'action'        => 'skvn_tracking_file',
			'attachment_id' => absint( $attachment_id ),
			'token'         => skvn_tracking_sanitize_token( $token ),
		),
		admin_url( 'admin-post.php' )
	);
}

/**
 * Record one authorized client-view request.
 *
 * Call once from the future client page controller, never per image request.
 *
 * @param int $batch_id Shipment post ID.
 * @return void
 */
function skvn_tracking_record_client_view( $batch_id ) {
	$batch_id = absint( $batch_id );
	$batch    = get_post( $batch_id );

	if ( ! $batch || 'skvn_shipment' !== $batch->post_type ) {
		return;
	}

	update_post_meta( $batch_id, '_skvn_last_viewed', current_time( 'mysql' ) );
	update_post_meta(
		$batch_id,
		'_skvn_view_count',
		absint( get_post_meta( $batch_id, '_skvn_view_count', true ) ) + 1
	);
}

/**
 * Fail a protected file request without exposing resource details.
 *
 * @return void
 */
function skvn_tracking_protected_file_not_found() {
	status_header( 404 );
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
	exit;
}

/**
 * Stream an original attachment only when its batch token is valid.
 *
 * @return void
 */
function skvn_tracking_serve_protected_file() {
	$attachment_id = isset( $_GET['attachment_id'] ) ? absint( $_GET['attachment_id'] ) : 0;
	$request_token = isset( $_GET['token'] ) ? $_GET['token'] : '';
	$batch_id      = absint( get_post_meta( $attachment_id, '_skvn_shipment_id', true ) );
	$attachment    = get_post( $attachment_id );
	$batch         = get_post( $batch_id );

	if (
		! $attachment
		|| 'attachment' !== $attachment->post_type
		|| ! $batch
		|| 'skvn_shipment' !== $batch->post_type
		|| ! skvn_tracking_validate_batch_token( $batch_id, $request_token )
	) {
		skvn_tracking_protected_file_not_found();
	}

	$file      = get_attached_file( $attachment_id );
	$real_file = $file ? realpath( $file ) : false;
	$uploads   = wp_upload_dir();
	$root      = realpath( trailingslashit( $uploads['basedir'] ) . 'shipments' );
	$mime_type = get_post_mime_type( $attachment_id );

	if (
		! $real_file
		|| ! $root
		|| ! is_string( $mime_type )
		|| 0 !== strpos( $mime_type, 'image/' )
		|| ! is_file( $real_file )
		|| ! is_readable( $real_file )
	) {
		skvn_tracking_protected_file_not_found();
	}

	$normalized_file = wp_normalize_path( $real_file );
	$normalized_root = trailingslashit( wp_normalize_path( $root ) );

	if (
		0 !== strpos( $normalized_file, $normalized_root )
		|| false === strpos( $normalized_file, '/original/' )
	) {
		skvn_tracking_protected_file_not_found();
	}

	$handle = fopen( $real_file, 'rb' );

	if ( false === $handle ) {
		skvn_tracking_protected_file_not_found();
	}

	while ( ob_get_level() ) {
		ob_end_clean();
	}

	nocache_headers();
	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
	header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
	header( 'X-Content-Type-Options: nosniff', true );
	header( 'Content-Type: ' . $mime_type );
	header( 'Content-Length: ' . (string) filesize( $real_file ) );
	header( 'Content-Disposition: inline; filename="' . sanitize_file_name( wp_basename( $real_file ) ) . '"' );

	while ( ! feof( $handle ) ) {
		$chunk = fread( $handle, 1024 * 1024 );

		if ( false === $chunk ) {
			break;
		}

		echo $chunk;
		flush();
	}

	fclose( $handle );
	exit;
}
