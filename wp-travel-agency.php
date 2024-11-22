<?php
/**
 * Plugin Name: WP Travel Agency
 * Description: A plugin to add agents with location and service type fields for travel agency management.
 * Version: 1.0
 * Author: AIVSTUDIOS
 * Text Domain: wp-travel-agency
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_TRAVEL_AGENCY_VERSION', '1.0' );
define( 'WP_TRAVEL_AGENCY_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_TRAVEL_AGENCY_URL', plugin_dir_url( __FILE__ ) );

require_once WP_TRAVEL_AGENCY_PATH . 'includes/class-wp-travel-agency.php';
require_once WP_TRAVEL_AGENCY_PATH . 'admin/class-wp-travel-agency-admin.php';


register_activation_hook( __FILE__, array( 'WP_Travel_Agency', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WP_Travel_Agency', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'WP_Travel_Agency', 'init' ) );
