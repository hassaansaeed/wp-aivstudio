<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

remove_role( 'agent' );

global $wpdb;
$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key IN ('location_city', 'service_type')" );
$table_name = $wpdb->prefix . 'leads';
$wpdb->query("DROP TABLE IF EXISTS $table_name"); 
$comments_table_name = $wpdb->prefix . 'leads_comments';
$wpdb->query("DROP TABLE IF EXISTS $comments_table_name");
$travel_agency_cron_logstable_name = $wpdb->prefix . 'leads_comments';
$wpdb->query("DROP TABLE IF EXISTS $travel_agency_cron_logstable_name");
