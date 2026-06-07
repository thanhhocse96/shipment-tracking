<?php
/**
 * Plugin Name: SKVN Shipment Tracking
 * Description: Shipment media organization and tracking surfaces for SKVN Marine.
 * Version: 0.3.0
 * Requires PHP: 8.0
 * Text Domain: skvn-shipment-tracking
 */

defined( 'ABSPATH' ) || exit;

define( 'SKVN_TRACKING_VERSION', '0.3.0' );
define( 'SKVN_TRACKING_PLUGIN_FILE', __FILE__ );
define( 'SKVN_TRACKING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SKVN_TRACKING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SKVN_TRACKING_PLUGIN_DIR . 'includes/class-media-tabs.php';
require_once SKVN_TRACKING_PLUGIN_DIR . 'includes/class-post-type.php';
require_once SKVN_TRACKING_PLUGIN_DIR . 'includes/class-plugin-lifecycle.php';
require_once SKVN_TRACKING_PLUGIN_DIR . 'includes/class-image-pipeline.php';
require_once SKVN_TRACKING_PLUGIN_DIR . 'includes/class-access-control.php';
require_once SKVN_TRACKING_PLUGIN_DIR . 'includes/class-blurred-thumbnail.php';

skvn_tracking_media_tabs_register();
skvn_tracking_post_type_register_hooks();
skvn_tracking_plugin_lifecycle_register();
skvn_tracking_image_pipeline_register();
skvn_tracking_access_control_register();
skvn_tracking_blurred_thumbnail_register();

register_activation_hook( __FILE__, 'skvn_tracking_activate' );
register_deactivation_hook( __FILE__, 'skvn_tracking_deactivate' );
