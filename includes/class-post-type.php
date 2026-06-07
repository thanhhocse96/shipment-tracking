<?php
/**
 * Shipment post type and metadata.
 *
 * @package SKVN_Shipment_Tracking
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register post type hooks.
 *
 * @return void
 */
function skvn_tracking_post_type_register_hooks() {
	add_action( 'init', 'skvn_tracking_register_post_type' );
	add_action( 'init', 'skvn_tracking_register_post_meta' );
}

/**
 * Return the capability map for shipment posts.
 *
 * @return array
 */
function skvn_tracking_get_post_type_capabilities() {
	$capability = 'manage_skvn_tracking';

	return array(
		'edit_post'              => $capability,
		'read_post'              => $capability,
		'delete_post'            => $capability,
		'edit_posts'             => $capability,
		'edit_others_posts'      => $capability,
		'publish_posts'          => $capability,
		'read_private_posts'     => $capability,
		'delete_posts'           => $capability,
		'delete_private_posts'   => $capability,
		'delete_published_posts' => $capability,
		'delete_others_posts'    => $capability,
		'edit_private_posts'     => $capability,
		'edit_published_posts'   => $capability,
		'create_posts'           => $capability,
	);
}

/**
 * Register the private shipment CPT.
 *
 * @return void
 */
function skvn_tracking_register_post_type() {
	register_post_type(
		'skvn_shipment',
		array(
			'labels'              => array(
				'name'          => __( 'Shipments', 'skvn-shipment-tracking' ),
				'singular_name' => __( 'Shipment', 'skvn-shipment-tracking' ),
				'add_new_item'  => __( 'Add Shipment', 'skvn-shipment-tracking' ),
				'edit_item'     => __( 'Edit Shipment', 'skvn-shipment-tracking' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'rewrite'             => false,
			'query_var'           => false,
			'has_archive'         => false,
			'supports'            => array( 'title' ),
			'capability_type'     => 'post',
			'capabilities'        => skvn_tracking_get_post_type_capabilities(),
			'map_meta_cap'        => false,
			'menu_icon'           => 'dashicons-images-alt2',
		)
	);
}

/**
 * Require the shipment management capability for private meta.
 *
 * @return bool
 */
function skvn_tracking_meta_auth_callback() {
	return current_user_can( 'manage_skvn_tracking' );
}

/**
 * Sanitize a YYYY-MM-DD date.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function skvn_tracking_sanitize_date( $value ) {
	$value = sanitize_text_field( (string) $value );
	$date  = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );

	return $date && $date->format( 'Y-m-d' ) === $value ? $value : '';
}

/**
 * Sanitize the internal batch lifecycle status.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function skvn_tracking_sanitize_batch_status( $value ) {
	$status = sanitize_key( (string) $value );

	return in_array( $status, array( 'draft', 'published', 'archived' ), true )
		? $status
		: 'draft';
}

/**
 * Sanitize a stored access token without generating one.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function skvn_tracking_sanitize_token( $value ) {
	$token = strtolower( sanitize_text_field( (string) $value ) );

	return preg_match( '/^[a-f0-9]{32}$/', $token ) ? $token : '';
}

/**
 * Sanitize the stored public projection.
 *
 * @param mixed $value Raw value.
 * @return array
 */
function skvn_tracking_sanitize_public_snapshot( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$snapshot = array();
	$allowed  = array( 'batch_title', 'product_type', 'year', 'thumbnail' );

	foreach ( $allowed as $key ) {
		if ( isset( $value[ $key ] ) && is_scalar( $value[ $key ] ) ) {
			$snapshot[ $key ] = sanitize_text_field( (string) $value[ $key ] );
		}
	}

	return $snapshot;
}

/**
 * Register private batch meta contracts.
 *
 * @return void
 */
function skvn_tracking_register_post_meta() {
	$text_meta = array(
		'_skvn_batch_title',
		'_skvn_client_name',
		'_skvn_container_number',
		'_skvn_product_type',
		'_skvn_thumb_blurred',
		'_skvn_last_viewed',
	);

	foreach ( $text_meta as $meta_key ) {
		register_post_meta(
			'skvn_shipment',
			$meta_key,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => 'skvn_tracking_meta_auth_callback',
			)
		);
	}

	register_post_meta(
		'skvn_shipment',
		'_skvn_batch_notes',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_textarea_field',
			'auth_callback'     => 'skvn_tracking_meta_auth_callback',
		)
	);

	register_post_meta(
		'skvn_shipment',
		'_skvn_closing_date',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => false,
			'sanitize_callback' => 'skvn_tracking_sanitize_date',
			'auth_callback'     => 'skvn_tracking_meta_auth_callback',
		)
	);

	register_post_meta(
		'skvn_shipment',
		'_skvn_token',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => false,
			'sanitize_callback' => 'skvn_tracking_sanitize_token',
			'auth_callback'     => 'skvn_tracking_meta_auth_callback',
		)
	);

	register_post_meta(
		'skvn_shipment',
		'_skvn_batch_status',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => 'draft',
			'show_in_rest'      => false,
			'sanitize_callback' => 'skvn_tracking_sanitize_batch_status',
			'auth_callback'     => 'skvn_tracking_meta_auth_callback',
		)
	);

	register_post_meta(
		'skvn_shipment',
		'_skvn_view_count',
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
		'skvn_shipment',
		'_skvn_thumb_source_id',
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
		'skvn_shipment',
		'_skvn_public_snapshot',
		array(
			'type'              => 'object',
			'single'            => true,
			'default'           => array(),
			'show_in_rest'      => false,
			'sanitize_callback' => 'skvn_tracking_sanitize_public_snapshot',
			'auth_callback'     => 'skvn_tracking_meta_auth_callback',
		)
	);
}
