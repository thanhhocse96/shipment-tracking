<?php
/**
 * Shipment image upload context and attachment processing.
 *
 * @package SKVN_Shipment_Tracking
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register image pipeline hooks.
 *
 * @return void
 */
function skvn_tracking_image_pipeline_register() {
	add_filter( 'upload_dir', 'skvn_tracking_filter_upload_dir' );
	add_action( 'add_attachment', 'skvn_tracking_process_uploaded_attachment', 5 );
	add_action( 'thumbpress_file_meta_refreshed', 'skvn_tracking_process_uploaded_attachment', 5, 1 );
	add_action( 'init', 'skvn_tracking_register_attachment_meta' );
}

/**
 * Register private shipment metadata stored on attachments.
 *
 * @return void
 */
function skvn_tracking_register_attachment_meta() {
	register_post_meta(
		'attachment',
		'_skvn_shipment_id',
		array(
			'type'              => 'integer',
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => false,
			'sanitize_callback' => 'absint',
			'auth_callback'     => 'skvn_tracking_meta_auth_callback',
		)
	);

	register_post_meta(
		'attachment',
		'_skvn_shipment_category',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => 'uncategorized',
			'show_in_rest'      => false,
			'sanitize_callback' => 'skvn_tracking_normalize_image_category',
			'auth_callback'     => 'skvn_tracking_meta_auth_callback',
		)
	);
}

/**
 * Return supported shipment image categories.
 *
 * @return array
 */
function skvn_tracking_get_image_categories() {
	return array(
		'seal'          => 'seal & door check',
		'temperature'   => 'temperature monitoring',
		'cargo'         => 'cargo rows',
		'uncategorized' => 'uncategorized',
	);
}

/**
 * Normalize a category to the internal enum.
 *
 * @param mixed $category Raw category.
 * @return string
 */
function skvn_tracking_normalize_image_category( $category ) {
	$category   = sanitize_key( (string) $category );
	$categories = skvn_tracking_get_image_categories();

	return isset( $categories[ $category ] ) ? $category : 'uncategorized';
}

/**
 * Set request-scoped context before calling the WordPress media upload API.
 *
 * @param int    $batch_id Shipment post ID.
 * @param string $category Manual category or uncategorized.
 * @param string $caption  Optional staff-provided ALT text.
 * @return bool
 */
function skvn_tracking_set_upload_context( $batch_id, $category = 'uncategorized', $caption = '' ) {
	global $skvn_tracking_upload_context;

	$batch = get_post( absint( $batch_id ) );

	if ( ! $batch || 'skvn_shipment' !== $batch->post_type ) {
		return false;
	}

	$skvn_tracking_upload_context = array(
		'batch_id' => (int) $batch->ID,
		'category' => skvn_tracking_normalize_image_category( $category ),
		'caption'  => sanitize_text_field( (string) $caption ),
	);

	return true;
}

/**
 * Clear the current request-scoped upload context.
 *
 * @return void
 */
function skvn_tracking_clear_upload_context() {
	global $skvn_tracking_upload_context;

	$skvn_tracking_upload_context = null;
}

/**
 * Get the current request-scoped upload context.
 *
 * @return array|null
 */
function skvn_tracking_get_upload_context() {
	global $skvn_tracking_upload_context;

	return is_array( $skvn_tracking_upload_context )
		? $skvn_tracking_upload_context
		: null;
}

/**
 * Resolve a stable batch slug for storage and ALT text.
 *
 * @param WP_Post $batch Shipment post.
 * @return string
 */
function skvn_tracking_get_batch_slug( $batch ) {
	$slug = $batch->post_name;

	if ( '' === $slug ) {
		$batch_title = get_post_meta( $batch->ID, '_skvn_batch_title', true );
		$slug        = sanitize_title( $batch_title ? $batch_title : $batch->post_title );
	}

	return $slug ? $slug : 'shipment-' . $batch->ID;
}

/**
 * Write access-denial files into an original image directory.
 *
 * @param string $directory Absolute directory path.
 * @return void
 */
function skvn_tracking_protect_original_directory( $directory ) {
	if ( ! wp_mkdir_p( $directory ) ) {
		return;
	}

	$htaccess = trailingslashit( $directory ) . '.htaccess';
	$index    = trailingslashit( $directory ) . 'index.php';

	if ( ! file_exists( $htaccess ) && is_writable( $directory ) ) {
		@file_put_contents(
			$htaccess,
			"Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"
		);
	}

	if ( ! file_exists( $index ) && is_writable( $directory ) ) {
		@file_put_contents( $index, "<?php\n// Silence is golden.\n" );
	}
}

/**
 * Route contextual uploads into the shipment's private original directory.
 *
 * @param array $uploads WordPress upload directory data.
 * @return array
 */
function skvn_tracking_filter_upload_dir( $uploads ) {
	$context = skvn_tracking_get_upload_context();

	if ( ! $context ) {
		return $uploads;
	}

	$batch = get_post( $context['batch_id'] );

	if ( ! $batch || 'skvn_shipment' !== $batch->post_type ) {
		return $uploads;
	}

	$relative_path = '/shipments/' . skvn_tracking_get_batch_slug( $batch ) . '/original';
	$directory     = $uploads['basedir'] . $relative_path;

	skvn_tracking_protect_original_directory( $directory );

	$uploads['path']   = $directory;
	$uploads['url']    = $uploads['baseurl'] . $relative_path;
	$uploads['subdir'] = $relative_path;

	return $uploads;
}

/**
 * Normalize a source filename for ALT text and category detection.
 *
 * @param string $filename Source filename or path.
 * @return string
 */
function skvn_tracking_clean_image_filename( $filename ) {
	$name = pathinfo( wp_basename( $filename ), PATHINFO_FILENAME );
	$name = strtolower( $name );
	$name = preg_replace( '/[_-]+/', ' ', $name );
	$name = preg_replace( '/\(\s*(\d+)\s*\)/', ' $1 ', $name );
	$name = preg_replace( '/\s+/', ' ', $name );

	return trim( $name );
}

/**
 * Auto-detect a category from a cleaned filename.
 *
 * @param string $filename Source filename or path.
 * @return string
 */
function skvn_tracking_detect_image_category( $filename ) {
	$name = skvn_tracking_clean_image_filename( $filename );

	if ( preg_match( '/\b(seal|door)\b/', $name ) ) {
		return 'seal';
	}

	if ( false !== strpos( $name, 'data logger' ) || preg_match( '/\btemp\b/', $name ) ) {
		return 'temperature';
	}

	if ( preg_match( '/^\d+(?:\s+\d+)?$/', $name ) ) {
		return 'cargo';
	}

	return 'uncategorized';
}

/**
 * Prefix numeric cargo filenames for readable ALT text.
 *
 * @param string $clean_name Cleaned filename.
 * @param string $category   Resolved category.
 * @return string
 */
function skvn_tracking_format_image_filename( $clean_name, $category ) {
	if ( 'cargo' === $category && preg_match( '/^\d+(?:\s+\d+)?$/', $clean_name ) ) {
		return 'row ' . $clean_name;
	}

	return $clean_name;
}

/**
 * Resolve attachment context from the active upload or existing attachment meta.
 *
 * @param int $attachment_id Attachment ID.
 * @return array|null
 */
function skvn_tracking_resolve_attachment_context( $attachment_id ) {
	$context = skvn_tracking_get_upload_context();

	if ( $context ) {
		return $context;
	}

	$batch_id = absint( get_post_meta( $attachment_id, '_skvn_shipment_id', true ) );

	if ( ! $batch_id ) {
		return null;
	}

	return array(
		'batch_id' => $batch_id,
		'category' => skvn_tracking_normalize_image_category(
			get_post_meta( $attachment_id, '_skvn_shipment_category', true )
		),
		'caption'  => '',
	);
}

/**
 * Process a shipment WebP attachment.
 *
 * @param int $attachment_id Attachment ID.
 * @return void
 */
function skvn_tracking_process_uploaded_attachment( $attachment_id ) {
	$attachment_id = absint( $attachment_id );

	if ( ! $attachment_id || 'image/webp' !== get_post_mime_type( $attachment_id ) ) {
		return;
	}

	$file = get_attached_file( $attachment_id );

	if ( ! $file || false === strpos( wp_normalize_path( $file ), '/shipments/' ) ) {
		return;
	}

	$context = skvn_tracking_resolve_attachment_context( $attachment_id );

	if ( ! $context ) {
		return;
	}

	$batch = get_post( $context['batch_id'] );

	if ( ! $batch || 'skvn_shipment' !== $batch->post_type ) {
		return;
	}

	$category = skvn_tracking_normalize_image_category( $context['category'] );

	if ( 'uncategorized' === $category ) {
		$category = skvn_tracking_detect_image_category( $file );
	}

	update_post_meta( $attachment_id, '_skvn_shipment_id', (int) $batch->ID );
	update_post_meta( $attachment_id, '_skvn_shipment_category', $category );

	$caption = trim( (string) $context['caption'] );

	if ( '' === $caption ) {
		$attachment = get_post( $attachment_id );
		$caption    = $attachment ? trim( (string) $attachment->post_excerpt ) : '';
	}

	if ( '' === $caption ) {
		$categories = skvn_tracking_get_image_categories();
		$clean_name = skvn_tracking_clean_image_filename( $file );
		$clean_name = skvn_tracking_format_image_filename( $clean_name, $category );
		$caption    = sprintf(
			'%s - %s - %s',
			skvn_tracking_get_batch_slug( $batch ),
			$categories[ $category ],
			$clean_name
		);
	}

	update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $caption ) );

	do_action( 'skvn_tracking_attachment_processed', $attachment_id, (int) $batch->ID, $category );
}
