<?php
/**
 * Plugin Name: SKVN Shipment Tracking
 * Description: Shipment media organization and tracking surfaces for SKVN Marine.
 * Version: 0.1.0
 * Requires PHP: 8.0
 * Text Domain: skvn-shipment-tracking
 */

defined( 'ABSPATH' ) || exit;

define( 'SKVN_TRACKING_VERSION', '0.1.0' );
define( 'SKVN_TRACKING_PLUGIN_FILE', __FILE__ );
define( 'SKVN_TRACKING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SKVN_TRACKING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SKVN_TRACKING_PLUGIN_DIR . 'includes/class-media-tabs.php';

skvn_tracking_media_tabs_register();

