<?php

class WP_Travel_Agency {

    public static function init() {
        self::load_dependencies();
        self::define_admin_hooks();
    }

    private static function load_dependencies() {
        require_once WP_TRAVEL_AGENCY_PATH . 'includes/class-wp-travel-agency-agent.php';
        require_once WP_TRAVEL_AGENCY_PATH . 'admin/class-wp-travel-agency-admin.php';
    }

    private static function define_admin_hooks() {
        add_action( 'init', array( 'WP_Travel_Agency_Agent', 'add_agent_role' ) );
        //add_action( 'init', array( 'WP_Travel_Agency_Agent', 'add_agent_profile_fields' ) );
        add_action( 'admin_menu', array( 'WP_Travel_Agency_Agent', 'add_admin_menus' ) );
        //add_action( 'admin_init', array( 'WP_Travel_Agency_Agent', 'add_agent_profile_fields' ) );
        //add_action('wp_enqueue_scripts', array('WP_Travel_Agency_Agent', 'enqueue_scripts_for_agent'));
        add_action('admin_enqueue_scripts', array('WP_Travel_Agency_Agent', 'enqueue_scripts_for_agent'));
        

        add_action('wp_ajax_update_lead_status', array('WP_Travel_Agency_Agent', 'ajax_update_lead_status'));
        add_action('wp_ajax_nopriv_update_lead_status', array('WP_Travel_Agency_Agent', 'ajax_update_lead_status'));
        
    }

    public static function activate() {
        self::load_dependencies();
        
        WP_Travel_Agency_Agent::add_agent_role(); 
        self::create_leads_table();
        self::create_leads_comments_table();
        self::create_cron_logs_table();
    }

    public static function deactivate() {
        self::load_dependencies();
        WP_Travel_Agency_Agent::remove_agent_role(); 
    }
    public static function create_cron_logs_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'travel_agency_cron_logs';

        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "
            CREATE TABLE IF NOT EXISTS `{$table_name}` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `execution_time` DATETIME NOT NULL,
                `email_count` INT(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
            ) $charset_collate;
        ";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public static function create_leads_comments_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'leads_comments';
        $charset_collate = $wpdb->get_charset_collate();
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                lead_id bigint(20) NOT NULL,
                text text NOT NULL,
                comment_date date NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id),
                KEY lead_id (lead_id)
            ) $charset_collate;";
        
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }


    private static function create_leads_table() {
        global $wpdb;
    
        $table_name = $wpdb->prefix . 'leads';
        $charset_collate = $wpdb->get_charset_collate();
    
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            
            $sql = "CREATE TABLE $table_name (
                id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone_number VARCHAR(20),
                special_offers TINYINT(1) DEFAULT 0,
                agent_preference VARCHAR(255),
                quote_type VARCHAR(255),
                flight_only_fields TEXT,
                vacation_packages_fields TEXT,
                cruise_package_fields TEXT,
                accommodations_only_fields TEXT,
                flight_hotel_city_packages_fields TEXT,
                europe_packages_fields TEXT,
                total_budget VARCHAR(20),
                budget_type VARCHAR(20),
                quote_comment Text,
                adventure_group_travel_fields TEXT,
                car_rental_only_fields TEXT,
                travel_insurance_only_fields TEXT,
                agent_id BIGINT(20) UNSIGNED,
                status ENUM('Pending', 'Successful', 'Fail', 'In Progress') DEFAULT 'Pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
            ) $charset_collate;";
    
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
    }
    
}
