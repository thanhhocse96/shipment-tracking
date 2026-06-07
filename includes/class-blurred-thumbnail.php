<?php
/**
 * Server-side blurred thumbnail generation.
 *
 * @package SKVN_Shipment_Tracking
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register blurred thumbnail hooks.
 *
 * @return void
 */
function skvn_tracking_blurred_thumbnail_register() {
	add_action( 'skvn_tracking_attachment_processed', 'skvn_tracking_update_blurred_thumbnail', 10, 2 );
}

/**
 * Select the first Seal image, falling back to the first batch image.
 *
 * @param int $batch_id Shipment post ID.
 * @return int
 */
function skvn_tracking_get_blurred_thumbnail_source( $batch_id ) {
	$base_args = array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'orderby'        => 'date ID',
		'order'          => 'ASC',
	);

	$seal_args               = $base_args;
	$seal_args['meta_query'] = array(
		'relation' => 'AND',
		array(
			'key'     => '_skvn_shipment_id',
			'value'   => absint( $batch_id ),
			'compare' => '=',
			'type'    => 'NUMERIC',
		),
		array(
			'key'     => '_skvn_shipment_category',
			'value'   => 'seal',
			'compare' => '=',
		),
	);

	$source_ids = get_posts( $seal_args );

	if ( ! empty( $source_ids ) ) {
		return (int) $source_ids[0];
	}

	$base_args['meta_query'] = array(
		array(
			'key'     => '_skvn_shipment_id',
			'value'   => absint( $batch_id ),
			'compare' => '=',
			'type'    => 'NUMERIC',
		),
	);
	$source_ids              = get_posts( $base_args );

	return empty( $source_ids ) ? 0 : (int) $source_ids[0];
}

/**
 * Return the relative blurred thumbnail path for a batch.
 *
 * @param WP_Post $batch Shipment post.
 * @return string
 */
function skvn_tracking_get_blurred_thumbnail_relative_path( $batch ) {
	return '/shipments/' . skvn_tracking_get_batch_slug( $batch ) . '/blurred-thumb.webp';
}

/**
 * Resolve the public URL of an existing blurred thumbnail.
 *
 * @param int $batch_id Shipment post ID.
 * @return string
 */
function skvn_tracking_get_blurred_thumbnail_url( $batch_id ) {
	$relative_path = get_post_meta( absint( $batch_id ), '_skvn_thumb_blurred', true );

	if ( ! is_string( $relative_path ) || '' === $relative_path ) {
		return '';
	}

	$uploads = wp_upload_dir();
	$file    = $uploads['basedir'] . $relative_path;

	return file_exists( $file ) ? $uploads['baseurl'] . $relative_path : '';
}

/**
 * Generate a blurred thumbnail after shipment attachment processing.
 *
 * @param int $attachment_id Processed attachment ID.
 * @param int $batch_id      Shipment post ID.
 * @return void
 */
function skvn_tracking_update_blurred_thumbnail( $attachment_id, $batch_id ) {
	$batch = get_post( absint( $batch_id ) );

	if ( ! $attachment_id || ! $batch || 'skvn_shipment' !== $batch->post_type ) {
		return;
	}

	$source_id = skvn_tracking_get_blurred_thumbnail_source( $batch->ID );

	if ( ! $source_id ) {
		return;
	}

	$source_file = get_attached_file( $source_id );

	if ( ! $source_file || ! is_readable( $source_file ) ) {
		return;
	}

	$relative_path = skvn_tracking_get_blurred_thumbnail_relative_path( $batch );
	$uploads       = wp_upload_dir();
	$output_file   = $uploads['basedir'] . $relative_path;
	$stored_source = absint( get_post_meta( $batch->ID, '_skvn_thumb_source_id', true ) );

	if ( $stored_source === $source_id && file_exists( $output_file ) ) {
		return;
	}

	if ( ! wp_mkdir_p( dirname( $output_file ) ) ) {
		error_log( 'SKVN Tracking: unable to create blurred thumbnail directory.' );
		return;
	}

	$generated = skvn_tracking_generate_blurred_thumbnail_gd( $source_file, $output_file );

	if ( ! $generated ) {
		$generated = skvn_tracking_generate_blurred_thumbnail_imagick( $source_file, $output_file );
	}

	if ( ! $generated ) {
		error_log( 'SKVN Tracking: no supported image backend could create blurred-thumb.webp.' );
		return;
	}

	update_post_meta( $batch->ID, '_skvn_thumb_blurred', $relative_path );
	update_post_meta( $batch->ID, '_skvn_thumb_source_id', $source_id );
}

/**
 * Calculate dimensions constrained to the configured thumbnail maximum.
 *
 * @param int $width  Source width.
 * @param int $height Source height.
 * @return array
 */
function skvn_tracking_get_blurred_thumbnail_dimensions( $width, $height ) {
	$max_dimension = 640;
	$scale         = min( 1, $max_dimension / max( $width, $height ) );

	return array(
		max( 1, (int) round( $width * $scale ) ),
		max( 1, (int) round( $height * $scale ) ),
	);
}

/**
 * Generate the blurred WebP using GD.
 *
 * @param string $source_file Source WebP path.
 * @param string $output_file Output WebP path.
 * @return bool
 */
function skvn_tracking_generate_blurred_thumbnail_gd( $source_file, $output_file ) {
	if (
		! function_exists( 'imagecreatefromwebp' )
		|| ! function_exists( 'imagewebp' )
		|| ! function_exists( 'imagefilter' )
	) {
		return false;
	}

	$source = @imagecreatefromwebp( $source_file );

	if ( false === $source ) {
		return false;
	}

	list( $width, $height )         = skvn_tracking_get_blurred_thumbnail_dimensions(
		imagesx( $source ),
		imagesy( $source )
	);
	$thumbnail                     = imagecreatetruecolor( $width, $height );

	imagealphablending( $thumbnail, false );
	imagesavealpha( $thumbnail, true );
	imagecopyresampled(
		$thumbnail,
		$source,
		0,
		0,
		0,
		0,
		$width,
		$height,
		imagesx( $source ),
		imagesy( $source )
	);

	for ( $pass = 0; $pass < 14; $pass++ ) {
		imagefilter( $thumbnail, IMG_FILTER_GAUSSIAN_BLUR );
	}

	$temp_file = $output_file . '.tmp-' . wp_generate_password( 8, false, false );
	$written   = @imagewebp( $thumbnail, $temp_file, 52 );

	imagedestroy( $thumbnail );
	imagedestroy( $source );

	if ( ! $written ) {
		@unlink( $temp_file );
		return false;
	}

	return @rename( $temp_file, $output_file );
}

/**
 * Generate the blurred WebP using Imagick.
 *
 * @param string $source_file Source WebP path.
 * @param string $output_file Output WebP path.
 * @return bool
 */
function skvn_tracking_generate_blurred_thumbnail_imagick( $source_file, $output_file ) {
	if ( ! class_exists( 'Imagick' ) ) {
		return false;
	}

	try {
		$image = new Imagick( $source_file );
		$image->setIteratorIndex( 0 );
		$image->thumbnailImage( 640, 640, true, true );
		$image->gaussianBlurImage( 12, 8 );
		$image->setImageFormat( 'webp' );
		$image->setImageCompressionQuality( 52 );
		$image->stripImage();

		$temp_file = $output_file . '.tmp-' . wp_generate_password( 8, false, false );
		$written   = $image->writeImage( $temp_file );
		$image->clear();
		$image->destroy();

		if ( ! $written ) {
			@unlink( $temp_file );
			return false;
		}

		return @rename( $temp_file, $output_file );
	} catch ( Exception $exception ) {
		return false;
	}
}
