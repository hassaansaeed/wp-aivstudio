<?php

class WP_Travel_Agency_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menus' ) );
        add_action('wpcf7_mail_sent', array($this, 'handle_form_submission'));
        add_action( 'admin_enqueue_scripts', array( $this, 'wp_travel_agency_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'wp_ajax_wp_travel_agency_get_posts_by_type', array( $this, 'ajax_get_posts_by_type' ) );

        add_action('wp_ajax_update_lead_status_admin', array('WP_Travel_Agency_Admin', 'ajax_update_lead_status_admin'));
        add_action('wp_ajax_nopriv_update_lead_status_admin', array('WP_Travel_Agency_Admin', 'ajax_update_lead_status_admin'));
        add_action('wp_ajax_delete_lead_admin', array('WP_Travel_Agency_Admin', 'ajax_delete_lead_admin'));
        add_action('wp_ajax_nopriv_delete_lead_admin', array('WP_Travel_Agency_Admin', 'ajax_delete_lead_admin'));


        add_action('init', array($this, 'wp_travel_agency_schedule_cron'));
        add_action('wp_travel_agency_send_pending_emails', array($this, 'send_pending_emails'));
        add_shortcode('travel_agency_booking_form', array($this, 'travel_agency_booking_form_shortcode'));
        add_shortcode('lead_list', array( $this, 'leads_page' ) );
        add_shortcode('lead_graphs', array( $this, 'statistics_page' ));
        add_shortcode('lead_dashboard', array( $this, 'dashboard_page' ));
        add_action('wp_ajax_save_booking_form', array($this, 'save_booking_form'));
        add_action('wp_ajax_nopriv_save_booking_form', array($this, 'save_booking_form'));

        add_action('wp_ajax_filter_leads_status', array($this, 'filter_leads_status_callback'));
        add_action('wp_ajax_nopriv_filter_leads_status', array($this, 'filter_leads_status_callback') );

        add_action('wp_ajax_filter_quote_type_counts_by_agent', array($this, 'filter_quote_type_counts_by_agent_callback'));
        add_action('wp_ajax_nopriv_filter_quote_type_counts_by_agent', array($this, 'filter_quote_type_counts_by_agent_callback'));
        add_action('wp_ajax_filter_completed_leads_by_quote_type_and_agent',array($this, 'filter_completed_leads_by_quote_type_and_agent'));
        add_action('wp_ajax_nopriv_filter_completed_leads_by_quote_type_and_agent', array($this, 'filter_completed_leads_by_quote_type_and_agent'));
        

        

    }
    function dashboard_page() //leads-dashboard
    {
        wp_enqueue_script('jquery');
        wp_enqueue_style('bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
        wp_enqueue_style('bootstrap-spinner-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css');
        wp_enqueue_style('sweetalert-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
        wp_enqueue_script('sweetalert-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js', array('jquery'), '11', true);
        wp_enqueue_script( 'wp-travel-agency-admin', plugins_url( 'js/wp-travel-agency-admin.js', __FILE__ ), array( 'jquery' ), null, true );
        wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js', array('jquery'), '4.3.1', true);
        wp_enqueue_script('wp-travel-agency-admin-status-js', plugin_dir_url(__FILE__) . 'js/wp-travel-agency-admin-status.js', array('jquery', 'bootstrap-js'), null, true);
        wp_localize_script( 'wp-travel-agency-admin', 'wpTravelAgencyAjax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wp_travel_agency_nonce' ),
        ));
        wp_localize_script( 'wp-travel-agency-admin-status-js', 'wpTravelAgencystatusupdatenyadminAjax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'status_nonce'    => wp_create_nonce( 'update_lead_status_admin' ),
            'delete_nonce'    => wp_create_nonce( 'delete_lead_admin' ),
        ));
         wp_enqueue_style( 'wp-travel-agency-admin-css', plugins_url( 'css/admin-style.css', __FILE__ ) );
        wp_enqueue_style( 'wp-travel-agency-status-admin-css', plugins_url( 'css/wp-travel-admin-popup.css', __FILE__ ) );
         ob_start();
         $this->statistics_page();
         $this->leads_page();
         return ob_get_clean();
    }

    function get_child_ages_list($data, $key_length, $key_value)
    {
        if(isset($data[$key_length]) && $data[$key_length] > 0)
        {
            $age_list = array();
            for ($x = 0; $x <= $data[$key_length]; $x++) {
                $final_key =$key_value.$x; 
                if(isset($data[$final_key]))
                {
                    $age_list [] = $data[$final_key];
                }
            }
            return $age_list;
        }
        return array();
    }

    function filter_completed_leads_by_quote_type_and_agent() {
        global $wpdb;
        $quote_type = isset($_POST['quote_type']) ? sanitize_text_field($_POST['quote_type']) : '';
        $agent = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : '';
    
        $table_name = $wpdb->prefix . 'leads';
        $filter_condition = 'WHERE 1=1';  
    
        if (!empty($quote_type)) {
            $filter_condition .= $wpdb->prepare(' AND quote_type = %s', $quote_type);
        }
        if (!empty($agent)) {
            $filter_condition .= $wpdb->prepare(' AND agent_id = %d', $agent);
        }
    
        $total_leads_query = "SELECT COUNT(*) AS total_leads FROM $table_name leads $filter_condition";
        $total_leads = (int)$wpdb->get_var($total_leads_query);
    
        $completed_leads_query = "SELECT COUNT(*) AS completed FROM $table_name leads $filter_condition AND status = 'Successful'";
        $completed_leads = (int)$wpdb->get_var($completed_leads_query);
    
        $success_percentage = ($total_leads > 0) ? ($completed_leads / $total_leads) * 100 : 0;
    
        wp_send_json_success([
            'totalLeads' => $total_leads,
            'completedLeads' => $completed_leads,
            'successPercentage' => round($success_percentage, 2) 
        ]);
    }

    function filter_leads_status_callback() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'leads';
        
        $quote_type = isset($_POST['quote_type']) ? sanitize_text_field($_POST['quote_type']) : '';
        $agent = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : '';
    
        $filter_condition = 'WHERE 1=1';
        if (!empty($quote_type)) {
            $filter_condition .= $wpdb->prepare(' AND quote_type = %s', $quote_type);
        }
        if (!empty($agent)) {
            $filter_condition .= $wpdb->prepare(' AND agent_id = %d', $agent);
        }

        $status_query = "SELECT status AS lead_status, COUNT(*) AS count 
                         FROM $table_name 
                         $filter_condition 
                         GROUP BY lead_status";
        $status_results = $wpdb->get_results($status_query);
    
        $statuses = [];
        $status_counts = [];
        foreach ($status_results as $row) {
            $statuses[] = $row->lead_status;
            $status_counts[] = (int)$row->count;
        }
    
        wp_send_json_success([
            'statuses' => $statuses,
            'statusCounts' => $status_counts
        ]);
    }

    function filter_quote_type_counts_by_agent_callback() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'leads';
    
        $agent = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : '';
    
        $filter_condition = 'WHERE 1=1';
        if (!empty($agent)) {
            $filter_condition .= $wpdb->prepare(' AND agent_id = %d', $agent);
        }
    
        $quote_type_options = [
            'Flight Only',
            'Vacation Packages',
            'Cruise Package',
            'Accommodations Only',
            'Flight & Hotel City Packages',
            'Europe Packages',
            'Adventure Group Travel',
            'Car Rental Only',
            'Travel Insurance Only'
        ];
    
        $quote_type_counts = array_fill(0, count($quote_type_options), 0);
    
        foreach ($quote_type_options as $index => $type) {
            $quote_type_query = $wpdb->prepare(
                "SELECT COUNT(*) AS count FROM $table_name $filter_condition AND quote_type = %s",
                $type
            );
            $quote_type_counts[$index] = (int)$wpdb->get_var($quote_type_query);
        }
    
        wp_send_json_success([
            'quoteTypeOptions' => $quote_type_options,
            'quoteTypeCounts' => $quote_type_counts
        ]);
    }
    
    
    function save_booking_form() {
        global $wpdb;

        $recaptcha_response = sanitize_text_field($_POST['recaptcha_response']);
        $settings = get_option('wp_travel_agency_settings', []);

       $secret_key = isset($settings['recaptcha_secret_key']) ? $settings['recaptcha_secret_key'] : '';

        $response = wp_remote_post("https://www.google.com/recaptcha/api/siteverify", [
            'body' => [
                'secret' => $secret_key,
                'response' => $recaptcha_response
            ]
        ]);

        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body);
        if ($result->success) {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'travel_booking_nonce')) {
                wp_send_json_error(array('message' => 'Invalid security token.'));
                return;
            }
            parse_str($_POST['form_data'], $data);
            $full_name = isset($data['full_name']) ? sanitize_text_field($data['full_name']) : '';
            $email = isset($data['email']) ? sanitize_email($data['email']) : '';
            $phone_number = isset($data['phone']) ? sanitize_text_field($data['phone']) : '';
            $special_offers = isset($data['promotions']) ? sanitize_text_field($data['promotions']) : '';
            $agent_preference = isset($data['agent_preference']) ? sanitize_text_field($data['agent_preference']) : '';
            $quote_type = isset($data['quote_type']) ? sanitize_text_field($data['quote_type']) : '';
            $quote_comment  = isset($data['quote_comment']) ? sanitize_text_field($data['quote_comment']) : '';
            $budget_type  = isset($data['budget_type']) ? sanitize_text_field($data['budget_type']) : '';
            $total_budget  = isset($data['total_budget']) ? sanitize_text_field($data['total_budget']) : '';

            $flight_only_fields = [];
            $vacation_packages_fields = [];
            $cruise_package_fields = [];
            $accommodations_only_fields = [];
            $flight_hotel_city_packages_fields = [];
            $europe_packages_fields = [];
            $adventure_group_travel_fields = [];
            $car_rental_only_fields = [];
            $travel_insurance_only_fields = [];
            switch ($quote_type) {
                case 'Flight Only':
                    $flight_only_fields = array(
                        'flight_destination' => isset($data['fof_flight_destination']) && is_array($data['fof_flight_destination']) ? 
                                                   array_map('sanitize_text_field', $data['fof_flight_destination']) : [],
                        'departure_city' => isset($data['fof_departure_city']) && is_array($data['fof_departure_city']) ? 
                                                array_map('sanitize_text_field', $data['fof_departure_city']) : [],
                        'departure_date' => isset($data['fof_departure_date']) ? sanitize_text_field($data['fof_departure_date']) : '',
                        'arrival_date' => isset($data['fof_arrival_date']) ? sanitize_text_field($data['fof_arrival_date']) : '',
                        'flight_options' => isset($data['fof_flight_options']) ? array_map('sanitize_text_field', $data['fof_flight_options']) : '',
                        'budget_type' => isset($data['budget_type']) ? sanitize_text_field($data['budget_type']) : '',
                        'total_budget' => isset($data['total_budget']) ? floatval($data['total_budget']) : 0,
                        'passenger_names' => $this->get_child_ages_list($data, 'fof_passenger_count', 'fof_passenger_names_'),
                        'passenger_dobs' => $this->get_child_ages_list($data, 'fof_passenger_count', 'fof_passenger_dobs_'),
                        'travel_insurance' => isset($data['fof_travel_insurance']) ? sanitize_text_field($data['fof_travel_insurance']) : '',
                       'payment_prefrence'  => isset($data['payment-prefrence']) ? sanitize_text_field($data['payment-prefrence']) : '',
                    );
                    break;
        
                case 'Vacation Packages':
                    $vacation_packages_fields = array(
                        'vacation_destination' => isset($data['vpf_vacation_destination']) ? sanitize_text_field($data['vpf_vacation_destination']) : '',
                        'departure_city' => isset($data['vpf_departure_city']) ? array_map('sanitize_text_field', $data['vpf_departure_city']) : [],
                       'vacation_start_date' => isset($data['vpf_vacation_start_date']) ? sanitize_text_field($data['vpf_vacation_start_date']) : '',
                       'vacation_end_date' => isset($data['vpf_vacation_end_date']) ? sanitize_text_field($data['vpf_vacation_end_date']) : '',
                        'flight_options' => isset($data['vpf_flight_options']) ? array_map('sanitize_text_field', $data['vpf_flight_options']) : '',
                        'number_of_adults' => isset($data['vpf_number_of_adults']) ? intval($data['vpf_number_of_adults']) : 0,
                        'number_of_children' => isset($data['vpf_number_of_children']) ? intval($data['vpf_number_of_children']) : 0,
                        'child_ages' => $this->get_child_ages_list($data, 'vpf_number_of_children', 'vpf_child_ages_'),
                        'number_of_rooms' => isset($data['vpf_number_of_rooms']) ? intval($data['vpf_number_of_rooms']) : 0,
                        'resort_preferences' => isset($data['vpf_resort_preferences']) ? array_map('sanitize_text_field', $data['vpf_resort_preferences']) : [],
                        'other_preference' => isset($data['vpf_other_preference']) ? sanitize_text_field($data['vpf_other_preference']) : '',
                        'budget_type' => isset($data['budget_type']) ? sanitize_text_field($data['budget_type']) : '',
                        'total_budget' => isset($data['total_budget']) ? floatval($data['total_budget']) : 0,
                        'payment_prefrence'  => isset($data['payment-prefrence']) ? sanitize_text_field($data['payment-prefrence']) : '',
                        'travel_insurance' => isset($data['vpf_travel_insurance']) ? sanitize_text_field($data['vpf_travel_insurance']) : 0,
                    );
                    
                    break;
                    
        
                case 'Cruise Package':
                    $cruise_package_fields = array(
                        'cruise_destination' => isset($data['cpf_cruise_destination']) ? sanitize_text_field($data['cpf_cruise_destination']) : '',
                        'departure_date' => isset($data['cpf_departure_date']) ? sanitize_text_field($data['cpf_departure_date']) : '',
                        'return_date' => isset($data['cpf_return_date']) ? sanitize_text_field($data['cpf_return_date']) : '',
                        'flight_quote_required' => isset($data['cpf_flight_quote_required']) ? sanitize_text_field($data['cpf_flight_quote_required']) : 'no',
                        'flight_departure_city' => isset($data['cpf_flight_departure_date']) ? array_map('sanitize_text_field', $data['cpf_flight_departure_date']) : [],
                        'cruise_type' => isset($data['cpf_cruise_type']) ? array_map('sanitize_text_field', $data['cpf_cruise_type']) : [],
                        'length_of_cruise' => isset($data['cpf_length_of_cruise']) ? array_map('sanitize_text_field', $data['cpf_length_of_cruise']) : '',
                        'preferred_cruise_line' => isset($data['cpf_preferred_cruise_line']) ? array_map('sanitize_text_field', $data['cpf_preferred_cruise_line']) : [],
                        'addons' => isset($data['cpf_addons']) ? array_map('sanitize_text_field', $data['cpf_addons']) : [],
                        'travel_insurance_quote' => isset($data['cpf_travel_insurance_quote']) ? sanitize_text_field($data['cpf_travel_insurance_quote']) : 'no',
                        'budget_type' => isset($data['budget_type']) ? sanitize_text_field($data['budget_type']) : '',
                        'total_budget' => isset($data['total_budget']) ? floatval($data['total_budget']) : 0,
                         'payment_prefrence'  => isset($data['payment-prefrence']) ? sanitize_text_field($data['payment-prefrence']) : '',
                    );
                 break;
                case 'Accommodations Only':
                    $accommodations_only_fields = array(
                        'accommodation_location' => isset($data['aof_accommodation_location']) ? sanitize_text_field($data['aof_accommodation_location']) : '',
                        'check_in_date' => isset($data['aof_check_in_date']) ? sanitize_text_field($data['aof_check_in_date']) : '',
                        'check_out_date' => isset($data['aof_check_out_date']) ? sanitize_text_field($data['aof_check_out_date']) : '',
                        'flexible_dates_option' => isset($data['aof_flexible_dates_option']) ? sanitize_text_field($data['aof_flexible_dates_option']) : 'no',
                        'number_of_adults' => isset($data['aof_number_of_adults']) ? intval($data['aof_number_of_adults']) : 1,
                        'number_of_children' => isset($data['aof_number_of_children']) ? intval($data['aof_number_of_children']) : 0,
                        'child_ages' => $this->get_child_ages_list($data, 'aof_number_of_children', 'aof_child_ages_'),
                        'number_of_rooms' => isset($data['aof_number_of_rooms']) ? intval($data['aof_number_of_rooms']) : 1,
                        'hotel_preferences' => isset($data['aof_hotel_preferences']) ? array_map('sanitize_text_field', $data['aof_hotel_preferences']) : [],
                          'other_preference' => isset($data['aof_other_preference']) ? sanitize_text_field($data['aof_other_preference']) : '',
                        'budget_type' => isset($data['budget_type']) ? sanitize_text_field($data['budget_type']) : '',
                        'total_budget' => isset($data['total_budget']) ? floatval($data['total_budget']) : 0,
                         'payment_prefrence'  => isset($data['payment-prefrence']) ? sanitize_text_field($data['payment-prefrence']) : '',
                    );
                    break;
    
                case 'Flight & Hotel City Packages':
                        $flight_hotel_city_packages_fields = array(
                            'package_destination' => isset($data['fhcp_package_destination']) ? sanitize_text_field($data['fhcp_package_destination']) : '',
                            'departure_city' => isset($data['fhcp_departure_city']) ? array_map('sanitize_text_field', $data['fhcp_departure_city']) : [],
                            'departure_date' => isset($data['fhcp_departure_date']) ? sanitize_text_field($data['fhcp_departure_date']) : '',
                             'arrival_date' => isset($data['fhcp_arrival_date']) ? sanitize_text_field($data['fhcp_arrival_date']) : '',
                            'flight_options' => isset($data['fhcp_flight_options']) ? array_map('sanitize_text_field', $data['fhcp_flight_options']) : [],
                            'number_of_adults' => isset($data['fhcp_number_of_adults']) ? intval($data['fhcp_number_of_adults']) : 1,
                            'number_of_children' => isset($data['fhcp_number_of_children']) ? intval($data['fhcp_number_of_children']) : 0,
                            'child_ages' => $this->get_child_ages_list($data, 'fhcp_number_of_children', 'fhcp_child_ages_'),
                            'accommodation_preferences' => isset($data['fhcp_accommodation_preferences']) ? array_map('sanitize_text_field', $data['fhcp_accommodation_preferences']) : [],
                            'other_preference' => isset($data['fhcp_other_preference']) ? sanitize_text_field($data['fhcp_other_preference']) : '',
                            'budget_type' => isset($data['budget_type']) ? sanitize_text_field($data['budget_type']) : '',
                        'total_budget' => isset($data['total_budget']) ? floatval($data['total_budget']) : 0,
                         'payment_prefrence'  => isset($data['payment-prefrence']) ? sanitize_text_field($data['payment-prefrence']) : '',
                            'travel_insurance_quote' => isset($data['fhcp_travel_insurance_quote']) ? sanitize_text_field($data['fhcp_travel_insurance_quote']) : 'no',
                        );
                        break;
    
                case 'Europe Packages':
                    $europe_packages_fields = array(
                        'package_destination' => isset($data['eu_package_destination']) ? sanitize_text_field($data['eu_package_destination']) : '',
                        'departure_city' => isset($data['eu_departure_city']) ? array_map('sanitize_text_field', $data['eu_departure_city']) : [],
                        'arrival_city' => isset($data['eu_arrival_city']) ? array_map('sanitize_text_field', $data['eu_arrival_city']) : [],
                        'departure_date' => isset($data['eu_departure_date']) ? sanitize_text_field($data['eu_departure_date']) : '',
                        'arrival_date' => isset($data['eu_arrival_date']) ? sanitize_text_field($data['eu_arrival_date']) : '',
                        'flight_options' => isset($data['eu_flight_options']) ? array_map('sanitize_text_field', $data['eu_flight_options']) : [],
                        'number_of_adults' => isset($data['eu_number_of_adults']) ? intval($data['eu_number_of_adults']) : 1,
                        'number_of_children' => isset($data['eu_number_of_children']) ? intval($data['eu_number_of_children']) : 0,
                        'child_ages' => $this->get_child_ages_list($data, 'eu_number_of_children', 'eu_child_ages_'),
                        'accommodation_preferences' => isset($data['eu_accommodation_preferences']) ? array_map('sanitize_text_field', $data['eu_accommodation_preferences']) : [],
                        'other_preference' => isset($data['eu_other_preference']) ? sanitize_text_field($data['eu_other_preference']) : '',
                        'budget_type' => isset($data['budget_type']) ? sanitize_text_field($data['budget_type']) : '',
                        'total_budget' => isset($data['total_budget']) ? floatval($data['total_budget']) : 0,
                         'payment_prefrence'  => isset($data['payment-prefrence']) ? sanitize_text_field($data['payment-prefrence']) : '',
                        'travel_insurance_quote' => isset($data['eu_travel_insurance_quote']) ? sanitize_text_field($data['eu_travel_insurance_quote']) : 'no',
                    );
                 break;
                
                case 'Adventure Group Travel':
                    $adventure_group_travel_fields = array(
                        'travel_destination' => isset($data['adventure_destination']) ? sanitize_text_field($data['adventure_destination']) : '',
                        'departure_date' => isset($data['adventure_departure_date']) ? sanitize_text_field($data['adventure_departure_date']) : '',
                        'arrival_date' => isset($data['adventure_arrival_date']) ? sanitize_text_field($data['adventure_arrival_date']) : '',
                        'preferred_adventure_companies' => isset($data['adventure_company']) ? array_map('sanitize_text_field', $data['adventure_company']) : [],
                        'flight_requirement' => isset($data['adventure_flight_requirement']) ? sanitize_text_field($data['adventure_flight_requirement']) : 'no',
                        'passenger_names' => $this->get_child_ages_list($data, 'adventure_passenger_count', 'adventure_passenger_name_'),
                        'passenger_dobs' => $this->get_child_ages_list($data, 'adventure_passenger_count', 'adventure_passenger_dob_'),
                        'budget_type' => isset($data['budget_type']) ? sanitize_text_field($data['budget_type']) : '',
                        'total_budget' => isset($data['total_budget']) ? floatval($data['total_budget']) : 0,
                         'payment_prefrence'  => isset($data['payment-prefrence']) ? sanitize_text_field($data['payment-prefrence']) : '',
                        'travel_insurance_quote' => isset($data['adventure_travel_insurance_quote']) ? sanitize_text_field($data['adventure_travel_insurance_quote']) : 'no',
                    );
                    break;
    
                case 'Car Rental Only':
                        $car_rental_only_fields = array(
                            'rental_location' => isset($data['rental_location']) ? sanitize_text_field($data['rental_location']) : '',
                            'departure_date' => isset($data['rental_departure_date']) ? sanitize_text_field($data['rental_departure_date']) : '',
                            'arrival_date' => isset($data['rental_arrival_date']) ? sanitize_text_field($data['rental_arrival_date']) : '',
                            'rental_type_preferences' => isset($data['rental_car_type_preferences']) ? array_map('sanitize_text_field', $data['rental_car_type_preferences']) : [],
                        );
                  break;
                case 'Travel Insurance Only':
                    $travel_insurance_only_fields = array(
                        'insurance_coverage' => isset($data['insurance_coverage']) ? sanitize_text_field($data['insurance_coverage']) : '',
                         'destination' => isset($data['insurance_destination']) ? sanitize_text_field($data['insurance_destination']) : '',
                        'departure_date' => isset($data['insurance_start_date']) ? sanitize_text_field($data['insurance_start_date']) : '',
                        'arrival_date' => isset($data['insurance_end_date']) ? sanitize_text_field($data['insurance_end_date']) : '',
                        'number_of_adults' => isset($data['insurance_number_of_adults']) ? intval($data['insurance_number_of_adults']) : 0,
                        'number_of_children' => isset($data['insurance_number_of_children']) ? intval($data['insurance_number_of_children']) : 0,
                         'child_ages' => $this->get_child_ages_list($data, 'insurance_number_of_children', 'insurance_child_ages_'),
                    );
                    break;
        
                default:
                    break;
            }

            $agent_id = 0;
    
            if (!empty($agent_preference) && $agent_preference != "First Available") {
                $agent_id = $wpdb->get_var($wpdb->prepare("
                    SELECT ID FROM {$wpdb->users} 
                    WHERE display_name = %s LIMIT 1", 
                    $agent_preference
                ));
            } 
        
            $table_name = $wpdb->prefix . 'leads'; 
    
            $result = $wpdb->insert(
                $table_name,
                array(
                    'full_name' => $full_name,
                    'email' => $email,
                    'agent_id' => $agent_id,
                    'status'  => 'Pending',
                    'phone_number' => $phone_number,
                    'special_offers' => $special_offers,
                    'agent_preference' => $agent_preference,
                    'quote_type' => $quote_type,
                    'quote_comment' => $quote_comment,
                    'total_budget' => $total_budget,
                    'budget_type' => $budget_type,
                    'flight_only_fields' => maybe_serialize($flight_only_fields),
                    'vacation_packages_fields' => maybe_serialize($vacation_packages_fields),
                    'cruise_package_fields' => maybe_serialize($cruise_package_fields),
                    'accommodations_only_fields' => maybe_serialize($accommodations_only_fields),
                    'flight_hotel_city_packages_fields' => maybe_serialize($flight_hotel_city_packages_fields),
                    'europe_packages_fields' => maybe_serialize($europe_packages_fields),
                    'adventure_group_travel_fields' => maybe_serialize($adventure_group_travel_fields),
                    'car_rental_only_fields' => maybe_serialize($car_rental_only_fields),
                    'travel_insurance_only_fields' => maybe_serialize($travel_insurance_only_fields)
                )
            );
            if ($result) {
            $lead_id = $wpdb->insert_id;
            $form_data = array();

                if (!empty($flight_only_fields)) {
                    $form_data = $flight_only_fields;
                }
                if (!empty($vacation_packages_fields)) {
                    $form_data = $vacation_packages_fields;
                }
                if (!empty($cruise_package_fields)) {
                    $form_data = $cruise_package_fields;
                }
                if (!empty($accommodations_only_fields)) {
                    $form_data = $accommodations_only_fields;
                }
                if (!empty($flight_hotel_city_packages_fields)) {
                    $form_data = $flight_hotel_city_packages_fields;
                }
                if (!empty($europe_packages_fields)) {
                    $form_data = $europe_packages_fields;
                }
                if (!empty($adventure_group_travel_fields)) {
                    $form_data = $adventure_group_travel_fields;
                }
                if (!empty($car_rental_only_fields)) {
                    $form_data = $car_rental_only_fields;
                }
                if (!empty($travel_insurance_only_fields)) {
                    $form_data = $travel_insurance_only_fields;
                }
                if (!empty($quote_comment)) {
                    $form_data["comment"] = $quote_comment;
                }
                if (!empty($special_offers)) {
                    $form_data["would_like_to_recieve_offers"] = $special_offers;
                }

                if (!empty($phone_number)) {
                    $form_data["phone_number"] = $phone_number;
                }
                 if (!empty($agent_preference)) {
                    $form_data["agent_preference"] = $agent_preference;
                }

            if (empty($agent_preference) || $agent_preference == "First Available") {
               self::send_quote_request_email_to_admin($quote_id, $full_name, $email, $quote_type, $form_data);
                self::send_email_to_user($full_name, $email, $quote_type, $lead_id, $form_data);
            }else{
                $agent_data = get_userdata($agent_id);
                self::send_email_to_agent($agent_data, $full_name, $email, $quote_type, $form_data, $lead_id);
                self::send_email_to_user($full_name, $email, $quote_type, $lead_id, $form_data);
                
            }
            wp_send_json_success(array('message' => "Quote Submitted Successfully!
                Please check your email inbox (or junk folder) for your quote confirmation. We’ll be in touch shortly!"));
        } else {
            wp_send_json_error(array('message' => 'Error saving booking. Please try again.'));
        }
        
    }
}

    function send_quote_request_email_to_admin($quote_id, $full_name, $email, $quote_type, $form_data) {
        $admin_email = get_option('admin_email'); 
        $subject = "New Quote Request - Quote ID: {$quote_id}"; 
    
        $message = "A new quote request has been made.\n\n";
        $message .= "Please assign an agent to this quote from the admin dashboard.\n\n"; 
        $message .= "Full Name: {$full_name}\n";
        $message .= "Email: {$email}\n";

        $message .= "Form Details:\n";
        $message .= "Quote Type: " . $quote_type . "\n";
                   foreach ($form_data as $key => $value) {
                       $message .= ucfirst(str_replace('_', ' ', $key)) . ": " . (is_array($value) ? implode(', ', $value) : $value) . "\n";
                   }
        $from_email = get_option('admin_email');
        $from_name = get_bloginfo('name');
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    

    function  send_email_to_user($full_name, $user_email, $quote_type, $lead_id,$form_data) {
        $subject = "Thank You for Filling Out our Booking Form – We’re Already Working on Your Perfect Trip! Quote #" . $lead_id;
        $message = "Hello " . $full_name . ",\n\n" .
                   "Thank you for filling out our booking form! We’re excited to start researching the best travel options tailored just for you. One of our Travel Gurus will connect with you soon.\n\n" .
                   "Please review the information you submitted below, and email us back if you need any changes or have questions while we work on your quote. If you don’t hear back from us within 24 hours, please reach out to us.\n\n". 
                   "At Travel Gurus, we guarantee the best prices on all your travels—without any additional booking fees—so you can be confident you’re getting the best possible options. Plus, with our expert advice, personalized service, 24/7 human support, and exclusive client discounts, you’ll enjoy so much more by choosing to book with our local, family-owned business.\n\n". 

                   "Form Details:\n";
                   "Quote Type: " . $quote_type . "\n";
                   foreach ($form_data as $key => $value) {
                       $message .= ucfirst(str_replace('_', ' ', $key)) . ": " . (is_array($value) ? implode(', ', $value) : $value) . "\n";
                   }
                   $message .= "\n\nThank you for choosing Travel Gurus\n\n".
                   "Edmonton Office\n".
                   "10423 - 102 Avenue (Downtown)\n".
                   "780-758-8747\n\n".
                   "Calgary Office\n".
                   "4623 Bowness Road NW (Montgomery)\n".
                   "403-787-0333";
    
        $from_email = get_option('admin_email');
        $from_name = get_bloginfo('name');
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
    
        
        wp_mail($user_email, $subject, $message, $headers);
    }
    

    public static function send_email_to_admin($admin_email, $full_name, $email, $quote_type) {
        $subject = "New Lead Submitted";
        $message = "Hello Admin,\n\n" .
                   "A new lead has been submitted.\n" .
                   "Client Name: " . $full_name . "\n" .
                   "Client Email: " . $email . "\n" .
                   "Quote Type: " . $quote_type . "\n\n" .
                   "Please log in to the admin panel to view the details.";
    
        $from_email = get_option('admin_email');
        $from_name = get_bloginfo('name');
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
    
        wp_mail($admin_email, $subject, $message, $headers);
    }
    

    public static function send_email_to_agent($agent_data, $full_name, $email, $quote_type, $form_data, $lead_id) {
        $to = $agent_data->user_email; 
        $subject = "New Booking Form Quote #". $lead_id . " ".  $full_name;
        $message = "Hello " . $agent_data->display_name . ",\n\n" .
                   "A new quote request has been assigned to you.\n" .
                   "Client Name: " . $full_name . "\n" .
                   "Client Email: " . $email . "\n" .
                   "Quote Type: " . $quote_type . "\n\n" .
                   "Form Details:\n";
               
                   foreach ($form_data as $key => $value) {
                       $message .= ucfirst(str_replace('_', ' ', $key)) . ": " . (is_array($value) ? implode(', ', $value) : $value) . "\n";
                   }
    
        $from_email = get_option('admin_email');
        $from_name = get_bloginfo('name');
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        $admin_email = get_option('admin_email'); 
        $headers[] = 'Cc: ' . $admin_email;
        wp_mail($to, $subject, $message, $headers);
    }
    

 

    function travel_agency_booking_form_shortcode() {
        wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
        wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js', [], null, true);
        wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), null, true);
        wp_enqueue_style('bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
        wp_enqueue_style('bootstrap-spinner-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css');
        wp_enqueue_script('travel-agency-booking-form-js', plugins_url('js/booking-form.js', __FILE__), array('jquery'), null, true);
        wp_enqueue_style('sweetalert-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
        wp_enqueue_script('sweetalert-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js', array('jquery'), '11', true);
        wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
        wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), null, true);
        wp_localize_script('travel-agency-booking-form-js', 'bookingForm', array(
            'pluginUrl' => plugins_url('', __FILE__),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('travel_booking_nonce'),
            'home_url' => "https://travelgurus.ca/"
        ));
        ob_start(); ?>
    
        <div class="container mt-5">
            <div class="row">
                <div class="col-12">
                    <form id="booking-form">
                        <!-- Personal Information -->
                        <div class="form-group">
                            <label for="full-name">Full Name (required)</label>
                            <input type="text" class="form-control form-control-lg" id="full-name" name="full_name" placeholder="Enter your full name">
                        </div>
    
                        <div class="form-group">
                            <label for="email">Email (required)</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="Enter your email address">
                        </div>
    
                        <div class="form-group">
                            <label for="phone">Phone Number (required)</label>
                            <input type="tel" class="form-control form-control-lg" id="phone" name="phone" placeholder="Enter your phone number">
                        </div>
                        <div class="form-group">
                            <label for="agent_preference">Travel Agent Preference</label>
                            <select class="form-control form-control-lg" id="agent_preference" name="agent_preference">
                                <option value="First Available" selected>First Available Agent</option>
                                <?php
                                    $agents = get_users(array(
                                        'role' => 'agent',
                                        'orderby' => 'display_name',
                                        'order' => 'ASC'
                                    ));

                                    
                                    foreach ($agents as $agent) {
                                        $agent_name = esc_html($agent->display_name);
                                        $agent_email = esc_attr($agent->user_email); 
                                        echo "<option value=\"{$agent_name}\">{$agent_name}</option>";
                                    }
                                    ?>
                            </select>
                        </div>
    
                        
    
                        <!-- Quote Type Selection -->
                        <div class="form-group">
                            <label for="quote-type">Quote Type (required)</label>
                            <select class="form-control form-control-lg" id="quote-type" name="quote_type">
                                <option value="">Select Quote Type</option>
                                <option value="Flight Only">Flight Only</option>
                                <option value="Vacation Packages">Vacation Packages</option>
                                <option value="Cruise Package">Cruise Package</option>
                                <option value="Accommodations Only">Accommodations Only</option>
                                <option value="Flight & Hotel City Packages">Flight & Hotel City Packages</option>
                                <option value="Europe Packages">Europe Packages</option>
                                <option value="Adventure Group Travel">Adventure Group Travel</option>
                                <option value="Car Rental Only">Car Rental Only</option>
                                <option value="Travel Insurance Only">Travel Insurance Only</option>
                            </select>
                        </div>
    
                        <!-- Conditional Fields -->
    
                        <!-- Flight Only Fields -->
                        <div class="conditional-group" id="flight-only-fields" style="display:none;">

                            <div class="form-group">
                                <label for="departure-city">Departure Airport</label>
                                <select id="fof_departure_city" class="form-control form-control-lg airport-select" multiple="multiple" name="fof_departure_city[]" style="width: 100%; max-width: 100px;"  data-placeholder="Please type an airport">
                                    <!-- Options will be populated dynamically -->
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="flight-destination">Arrival  Airport</label>
                                <select id="fof_flight_destination" class="form-control form-control-lg airport-select" multiple="multiple" name="fof_flight_destination[]" style="width: 100%; max-width: 100px;" data-placeholder="Please type an airport">
                                    <!-- Options will be populated dynamically -->
                                    

                                </select>
                                
                            </div>
                            
                            <div class="form-group">
                                <label for="flight-dates">Flight Dates </label>
                                <div class="row date-range d-flex justify-content-between">

                                    <div class="col-12 col-md-6 mb-2 mb-md-0 ">
                                        <div class="flex-fill me-2">
                                          <input type="text" class="form-control form-control-lg" id="fof_departure_date" name="fof_departure_date" placeholder="Departure Date" readonly>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="flex-fill me-2">
                                        <input type="text" class="form-control form-control-lg" id="fof_arrival_date" name="fof_arrival_date" placeholder="Arrival Date" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                               
                            </div>

                            
                            <div class="form-group">
                                <label for="flight-options">Flight Options</label>
                                <select multiple class="form-control form-control-lg" id="fof_flight-options" name="fof_flight_options[]">
                                    <option value="Flexible dates">Flexible Dates</option>
                                    <option value="Direct flights">Direct Flights</option>
                                    <option value="One stop max">1 Stop Max</option>
                                    <option value="Two stops max">2 Stops Max</option>
                                </select>
                            </div>
                            
                            
                            <div class="passenger-info">
                                <h4>Passenger Information</h4>
                                <div id="passengers-container">
                                    <div class="passenger-group">
                                        <div class="form-group">
                                            <label for="passenger-name-1">Full Name</label>
                                            <input type="text" class="form-control form-control-lg" id="fof_passenger-name-1" name="fof_passenger_names_1" placeholder="Enter full name">
                                        </div>
                                        <div class="form-group">
                                            <label for="passenger-dob-1">DOB</label>
                                            <input type="date" class="form-control form-control-lg" id="fof_passenger-dob-1" name="fof_passenger_dobs_1">
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-secondary" id="add-passenger">Add Passenger</button>
                                <input type="hidden" id="fof_passenger_count" name="fof_passenger_count" value="1">
                            </div>
                            
                            <div class="form-group">
                                <label for="travel-insurance">Would you like to add a Travel Protection Quote?</label>
                                <select class="form-control form-control-lg" id="fof_travel-insurance" name="fof_travel_insurance">
                                    <option value="">Select Yes or No</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>

                        </div>

    
                        <!-- Vacation Packages Fields -->
                        <div class="conditional-group" id="vacation-packages-fields" style="display:none;">
                            <div class="form-group">
                                <label for="vacation-destination">Vacation Destination</label>
                                <input type="text" class="form-control form-control-lg" id="vpf_vacation-destination" name="vpf_vacation_destination" placeholder="Enter vacation destination">
                            </div>
                            
                            <div class="form-group">
                                <label for="departure-city">Departure Airport</label>
                                <select class="form-control form-control-lg airport-select" multiple="multiple" name="vpf_departure_city[]" style="width: 100%; max-width: 100px;" data-placeholder="Please type an airport">
                                    

                                </select>
                                
                            </div>

                           
                            <div class="form-group">
                                <label for="flight-dates">First Available Date to Depart </label>
                                <div class="row date-range d-flex justify-content-between">

                                    <div class="col-12 col-md-6 mb-2 mb-md-0 ">
                                        <div class="flex-fill me-2">
                                          <input type="text" class="form-control form-control-lg" id="vpf_vacation_start_date" name="vpf_vacation_start_date" placeholder="First Available Date to Depart" readonly> 
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="flex-fill me-2">
                                        <input type="text" class="form-control form-control-lg" id="vpf_vacation_end_date" name="vpf_vacation_end_date" placeholder="Last Available Date to Return" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>    
                            
                            <div class="form-group">
                                <label for="flight-options">Flight Options</label>
                                <select multiple class="form-control form-control-lg" id="vpf_flight-options" name="vpf_flight_options[]">
                                    <option value="">Select an option</option>
                                    <option value="Flexible dates">Flexible Dates</option>
                                    <option value="Direct flights">Direct Flights</option>
                                    <option value="One stop max">1 Stop Max</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="number-of-adults">Number of Adults</label>
                                <input type="number" class="form-control form-control-lg" id="vpf_number-of-adults" name="vpf_number_of_adults" min="1" placeholder="Enter number of adults">
                            </div>

                            <div class="form-group">
                                <label for="number-of-children">Number of Children</label>
                                <input type="number" class="form-control form-control-lg" id="vpf_number-of-children" name="vpf_number_of_children" min="0" placeholder="Enter number of children" oninput="populateChildAgesvpf()">
                            </div>
                            
                            <div class="form-group" id="vpf-child-ages-container" style="display:none;">
                                <label>Ages of Children</label>
                                <div id="vpf-child-ages"></div>
                            </div>

                            <div class="form-group">
                                <label for="number-of-rooms">Number of Rooms</label>
                                <input type="number" class="form-control form-control-lg" id="vpf_number-of-rooms" name="vpf_number_of_rooms" min="1" placeholder="Enter number of rooms">
                            </div>

                            <div class="form-group">
                                <label for="resort-preferences">Accommodation Preferences</label>
                                <select multiple class="form-control form-control-lg" id="vpf_resort-preferences" name="vpf_resort_preferences[]" style="overflow:hidden;height: 250px;">
                                    <option value="5 Stars+">5 Stars+</option>
                                    <option value="4 Stars+">4 Stars+</option>
                                    <option value="Adults Only">Adults Only</option>
                                    <option value="All Inclusive">All Inclusive</option>
                                    <option value="Balcony Room">Balcony Room</option>
                                    <option value="Breakfast Included">Breakfast Included</option>
                                    <option value="Boutique">Boutique</option>
                                    <option value="Close to Airport">Close to Airport</option>
                                    <option value="Close to Town">Close to Town</option>
                                    <option value="Eco-Friendly">Eco-Friendly</option>
                                    <option value="Family-Friendly">Family-Friendly</option>
                                    <option value="Free Airport Shuttle">Free Airport Shuttle</option>
                                    <option value="Kids Water Park">Kids Water Park</option>
                                    <option value="Kitchenette">Kitchenette</option>
                                    <option value="Luxury">Luxury</option>
                                    <option value="Oceanview Room">Oceanview Room</option>
                                    <option value="Pet Friendly">Pet Friendly</option>
                                    <option value="Separate Bedroom">Separate Bedroom</option>
                                    <option value="Swim Up Room">Swim Up Room</option>
                                    <option value="Swimming Pool & Gym">Swimming Pool & Gym</option>
                                    <option value="Other">Other (please specify)</option>
                                </select>
                                <input type="text" class="form-control form-control-lg mt-2" id="vpf_other-preference" name="vpf_other_preference" placeholder="If other, please specify">
                            </div>

                        <div class="form-group payment-prefrence-vacation-div">
                                <label for="payment-prefrence-vacation">Payment Preference</label>
                                <select class="form-control form-control-lg" id="payment-prefrence-vacation" name="payment-prefrence">
                                    <option value="Full Payment" selected>Full Payment</option>
                                    <option value="Deposit (Vacation Packages 60 days or more away)">Deposit (Vacation Packages 60 days or more away)</option>
                                    <option value="24 Months Payment Plan">24 Months Payment Plan (On Approved Credit)</option>
                                </select>
                        </div>

                            <div class="form-group">
                                <label for="travel-insurance">Would you like to add a Travel Protection Quote?</label>
                                <select class="form-control form-control-lg" id="vpf_travel-insurance" name="vpf_travel_insurance">
                                    <option value="">Select Yes or No</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>

    
                        <!-- Cruise Package Fields -->
                        <div class="conditional-group" id="cruise-package-fields" style="display:none;">
                            <div class="form-group">
                                <label for="cruise-destination">Cruise Destination</label>
                                <input type="text" class="form-control form-control-lg" id="cpf_cruise-destination" name="cpf_cruise_destination" placeholder="Enter cruise destination">
                            </div>


                            <div class="form-group">
                                <label for="flight-dates">Dates </label>
                                <div class="row date-range d-flex justify-content-between">

                                    <div class="col-12 col-md-6 mb-2 mb-md-0 ">
                                        <div class="flex-fill me-2">
                                          <input type="text" class="form-control form-control-lg" id="cpf_departure_date" name="cpf_departure_date" placeholder="Departure Date" readonly>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="flex-fill me-2">
                                        <input type="text" class="form-control form-control-lg" id="cpf_return_date" name="cpf_return_date" placeholder="Arrival Date" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div> 

                            <div class="form-group">
                                <label>Flight Quote Required?</label>
                                <select class="form-control form-control-lg" id="cpf_flight-quote-required" name="cpf_flight_quote_required">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>
                            <div class="form-group" id="cpf_departure-city-container" style="display: none;">
                                <label for="departure-city">Departure Airport</label>
                                <select class="form-control form-control-lg airport-select" multiple="multiple" name="cpf_flight_departure_date[]" style="width: 100%; max-width: 100px;" data-placeholder="Please type an airport">
                                    

                                    
                                </select>
                            </div>
                           
                            <div class="form-group flight-quote-fields" style="display:none;">
                                <label>Flexible Dates Option?</label>
                                <select class="form-control form-control-lg" id="cpf_flexible-dates-option" name="cpf_flexible_dates_option">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="cruise-type">Type of Cruise</label>
                                <select multiple class="form-control form-control-lg" id="cpf_cruise-type" name="cpf_cruise_type[]">
                                    <option value="" selected>*Select All That Apply</option>
                                    <option value="Family Friendly">Family Friendly</option>
                                    <option value="Adults Only">Adults Only</option>
                                    <option value="Luxury">Luxury</option>
                                    <option value="River">River</option>
                                    <option value="Large">Large</option>
                                    <option value="Medium">Medium</option>
                                    <option value="Small">Small</option>
                                    <option value="World Cruise">World Cruise</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="length-of-cruise">Length of Cruise</label>
                                <select class="form-control form-control-lg" id="cpf_length-of-cruise" name="cpf_length_of_cruise[]">
                                    <option value="" selected>*Select All That Apply</option>
                                    <option value="2-6 Days">2-6 Days</option>
                                    <option value="7 Days" selected>7 Days</option>
                                    <option value="8-10 Days">8-10 Days</option>
                                    <option value="14+ Days">14+ Days</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="preferred-cruise-line">Preferred Cruise Line</label>
                                <select multiple class="form-control form-control-lg" id="cpf_preferred-cruise-line" name="cpf_preferred_cruise_line[]">
                                    <option value="" selected>*Select All That Apply</option>
                                    <option value="Ama Waterways">Ama Waterways</option>
                                    <option value="Atlas Ocean Voyages">Atlas Ocean Voyages</option>
                                    <option value="Avalon Waterways">Avalon Waterways</option>
                                    <option value="Azamara Cruises">Azamara Cruises</option>
                                    <option value="Carnival Cruises">Carnival Cruises</option>
                                    <option value="Celestyal Cruises">Celestyal Cruises</option>
                                    <option value="Celebrity Cruises">Celebrity Cruises</option>
                                    <option value="Costa Cruises">Costa Cruises</option>
                                    <option value="Crystal Cruises">Crystal Cruises</option>
                                    <option value="Cunard Cruises">Cunard Cruises</option>
                                    <option value="Disney Cruises">Disney Cruises</option>
                                    <option value="Emerald Cruises">Emerald Cruises</option>
                                    <option value="Explora Journeys">Explora Journeys</option>
                                    <option value="Holland America">Holland America</option>
                                    <option value="Hurtigruten Cruises">Hurtigruten Cruises</option>
                                    <option value="Linblad Expeditions">Linblad Expeditions</option>
                                    <option value="MSC Cruises">MSC Cruises</option>
                                    <option value="NCL Cruises">NCL Cruises</option>
                                    <option value="Oceania Cruises">Oceania Cruises</option>
                                    <option value="Princess Cruises">Princess Cruises</option>
                                    <option value="Regent Seven Seas Cruises">Regent Seven Seas Cruises</option>
                                    <option value="Royal Caribbean">Royal Caribbean</option>
                                    <option value="Scenic Cruises">Scenic Cruises</option>
                                    <option value="Seabourn Cruises">Seabourn Cruises</option>
                                    <option value="Silversea Cruises">Silversea Cruises</option>
                                    <option value="Star Clippers">Star Clippers</option>
                                    <option value="Tauk Cruises">Tauk Cruises</option>
                                    <option value="Uniworld Cruises">Uniworld Cruises</option>
                                    <option value="Viking Cruises">Viking Cruises</option>
                                    <option value="Virgin Voyages">Virgin Voyages</option>
                                    <option value="Windstar Cruises">Windstar Cruises</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Add-ons</label>
                                <div>
                                    <input type="checkbox" id="cpf_drink-packages" name="cpf_addons[]" value="drink-packages">
                                    <label for="drink-packages">Drink Packages</label>
                                </div>
                                <div>
                                    <input type="checkbox" id="cpf_prepaid-gratuities" name="cpf_addons[]" value="prepaid-gratuities">
                                    <label for="prepaid-gratuities">Prepaid Gratuities</label>
                                </div>
                                <div>
                                    <input type="checkbox" id="cpf_wifi-package" name="cpf_addons[]" value="wifi-package">
                                    <label for="wifi-package">WIFI Package</label>
                                </div>
                                <div>
                                    <input type="checkbox" id="cpf_excursions" name="cpf_addons[]" value="excursions">
                                    <label for="excursions">Excursions</label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Would you like to add a Travel Protection Quote?</label>
                                <select class="form-control form-control-lg" id="cpf_travel-insurance-quote" name="cpf_travel_insurance_quote">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>
                        </div>

    
                        <!-- Accommodations Only Fields -->
                        <div class="conditional-group" id="accommodations-only-fields" style="display:none;">
                            <div class="form-group">
                                <label for="accommodation-location">Accommodation Location</label>
                                <input type="text" class="form-control form-control-lg" id="aof_accommodation-location" name="aof_accommodation_location" placeholder="Enter accommodation location">
                            </div>
                            
                            <div class="form-group">
                                <label for="vacation-dates">Check in Date </label>
                                <div class="row date-range d-flex justify-content-between">

                                    <div class="col-12 col-md-6 mb-2 mb-md-0 ">
                                        <div class="flex-fill me-2">
                                          <input type="text" class="form-control form-control-lg" id="aof_check_in_date" name="aof_check_in_date" placeholder="Check in Date" readonly>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="flex-fill me-2">
                                          <input type="text" class="form-control form-control-lg" id="aof_check_out_date" name="aof_check_out_date" placeholder="Check out Date" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                
                            </div>
                            <div class="form-group">
                                <label>Flexible Dates Option?</label>
                                <select class="form-control form-control-lg" id="aof_flexible-dates-option" name="aof_flexible_dates_option">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="number-of-adults">Number of Adults</label>
                                <input type="number" class="form-control form-control-lg" id="aof_number-of-adults" name="aof_number_of_adults" min="1" value="1">
                            </div>
                            <div class="form-group">
                                <label for="number-of-children">Number of Children</label>
                                
                                <input type="number" class="form-control form-control-lg" id="aof_number-of-children" min="0" name="aof_number_of_children"  onchange="populateChildAgesaof()">
                            </div>
                            <div class="form-group" id="child-ages-containeraof" style="display:none;">
                                <label>Ages of Children</label>
                                <div id="child-ages-aof"></div>
                            </div>
                            <div class="form-group">
                                <label for="number-of-rooms">Number of Rooms</label>
                                <input type="number" class="form-control form-control-lg" id="aof_number-of-rooms" name="aof_number_of_rooms" min="1" value="1">
                            </div>
                            <div class="form-group">
                                <label for="hotel-preferences">Hotel Preferences</label>
                                <select multiple class="form-control form-control-lg" id="aof_hotel-preferences" name="aof_hotel_preferences[]">
                                   <option value="5 Stars+">5 Stars+</option>
                                    <option value="4 Stars+">4 Stars+</option>
                                    <option value="Adults Only">Adults Only</option>
                                    <option value="All Inclusive">All Inclusive</option>
                                    <option value="Balcony Room">Balcony Room</option>
                                    <option value="Breakfast Included">Breakfast Included</option>
                                    <option value="Boutique">Boutique</option>
                                    <option value="Close to Airport">Close to Airport</option>
                                    <option value="Close to Town">Close to Town</option>
                                    <option value="Eco-Friendly">Eco-Friendly</option>
                                    <option value="Family-Friendly">Family-Friendly</option>
                                    <option value="Free Airport Shuttle">Free Airport Shuttle</option>
                                    <option value="Kids Water Park">Kids Water Park</option>
                                    <option value="Kitchenette">Kitchenette</option>
                                    <option value="Luxury">Luxury</option>
                                    <option value="Oceanview Room">Oceanview Room</option>
                                    <option value="Pet Friendly">Pet Friendly</option>
                                    <option value="Separate Bedroom">Separate Bedroom</option>
                                    <option value="Swim Up Room">Swim Up Room</option>
                                    <option value="Swimming Pool & Gym">Swimming Pool & Gym</option>
                                    <option value="Other">Other (please specify)</option>
                                </select>
                                <input type="text" class="form-control form-control-lg mt-2" id="aof_other-preference" name="aof_other_preference" placeholder="If other, please specify">
                            </div>

                        </div>

    
                        <!-- Flight & Hotel City Packages Fields -->
                        <div class="conditional-group" id="flight-hotel-city-packages-fields" style="display:none;">
                            <div class="form-group">
                                <label for="fhcp-package-destination">Package Destination</label>
                                <input type="text" class="form-control form-control-lg" id="fhcp-package-destination" name="fhcp_package_destination" placeholder="Enter package destination">
                            </div>
                            <div class="form-group">
                                <label for="fhcp-departure-city">Departure Airport</label>
                                <select class="form-control form-control-lg airport-select" multiple="multiple" name="fhcp_departure_city[]" style="width: 100%; max-width: 100px;" data-placeholder="Please type an airport">
                                    

                                </select>
                                
                            </div>

                            <div class="form-group">
                                <label for="flight-dates">Date </label>

                                <div class="row date-range d-flex justify-content-between">

                                    <div class="col-12 col-md-6 mb-2 mb-md-0 ">
                                        <div class="flex-fill me-2">
                                         <input type="text" class="form-control form-control-lg" id="fhcp_departure_date" name="fhcp_departure_date" placeholder="Departure Date" readonly>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="flex-fill me-2">
                                           <input type="text" class="form-control form-control-lg" id="fhcp_arrival_date" name="fhcp_arrival_date" placeholder="Arrival Date" readonly>
                                        </div>
                                    </div>
                                </div>
                            <div class="form-group">
                                <label>Flight Options</label>
                                <select multiple class="form-control form-control-lg" id="fhcp-flight-options" name="fhcp_flight_options[]">
                                    <option value="Flexible dates">Flexible Dates</option>
                                    <option value="Direct flights">Direct Flights</option>
                                    <option value="One stop max">1 Stop Max</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="fhcp-number-of-adults">Number of Adults</label>
                                <input type="number" class="form-control form-control-lg" id="fhcp-number-of-adults" name="fhcp_number_of_adults" min="1" value="1">
                            </div>
                            <div class="form-group">
                                <label for="fhcp-number-of-children">Number of Children</label>
                                <input type="number" class="form-control form-control-lg" id="fhcp-number-of-children" name="fhcp_number_of_children" min="0" value="0" onchange="populateChildAgesForFHCP()">
                            </div>
                            <div class="form-group" id="fhcp-child-ages-container" style="display:none;">
                                <label>Ages of Children</label>
                                <div id="fhcp-child-ages"></div>
                            </div>
                            <div class="form-group">
                                <label>Accommodation Preferences</label>
                                <select multiple class="form-control form-control-lg" id="fhcp-accommodation-preferences" name="fhcp_accommodation_preferences[]">
                                    <option value="5 Stars+">5 Stars+</option>
                                    <option value="4 Stars+">4 Stars+</option>
                                    <option value="Adults Only">Adults Only</option>
                                    <option value="All Inclusive">All Inclusive</option>
                                    <option value="Balcony Room">Balcony Room</option>
                                    <option value="Breakfast Included">Breakfast Included</option>
                                    <option value="Boutique">Boutique</option>
                                    <option value="Close to Airport">Close to Airport</option>
                                    <option value="Close to Town">Close to Town</option>
                                    <option value="Eco-Friendly">Eco-Friendly</option>
                                    <option value="Family-Friendly">Family-Friendly</option>
                                    <option value="Free Airport Shuttle">Free Airport Shuttle</option>
                                    <option value="Kids Water Park">Kids Water Park</option>
                                    <option value="Kitchenette">Kitchenette</option>
                                    <option value="Luxury">Luxury</option>
                                    <option value="Oceanview Room">Oceanview Room</option>
                                    <option value="Pet Friendly">Pet Friendly</option>
                                    <option value="Separate Bedroom">Separate Bedroom</option>
                                    <option value="Swim Up Room">Swim Up Room</option>
                                    <option value="Swimming Pool & Gym">Swimming Pool & Gym</option>
                                    <option value="Other">Other (please specify)</option>
                                </select>
                                <input type="text" class="form-control form-control-lg mt-2" id="fhcp_other-preference" name="fhcp_other_preference" placeholder="If other, please specify">
                            </div>

                            <div class="form-group">
                                <label>Would you like to add a Travel Protection Quote?</label>
                                <select class="form-control form-control-lg" id="fhcp-travel-insurance-quote" name="fhcp_travel_insurance_quote">
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>
                    </div>

    
                        <!-- Europe Packages Fields -->
                        <div class="conditional-group" id="europe-packages-fields" style="display:none;">
                            <div class="form-group">
                                <label for="eu-package-destination">Europe Package Destination</label>
                                <input type="text" class="form-control form-control-lg" id="eu-package-destination" name="eu_package_destination" placeholder="Enter Europe package destination">
                            </div>

                            <div class="form-group">
                                <label for="eu-departure-city">Departure Airport</label>
                                <select class="form-control form-control-lg airport-select" multiple="multiple" name="eu_departure_city[]" style="width: 100%; max-width: 100px;" data-placeholder="Please type an airport">
                                    
 
                                </select>
                                
                            </div>
                            <div class="form-group">
                                <label for="eu-arrival-city">Arrival Airport</label>
                                
                                <select class="form-control form-control-lg airport-select" multiple="multiple" name="eu_arrival_city[]" style="width: 100%; max-width: 100px;" data-placeholder="Please type an airport">
                                    

                                </select>
                            </div>
                            <div class="form-group">
                                <label for="flight-dates">Flight Dates </label>

                                <div class="row date-range d-flex justify-content-between">

                                    <div class="col-12 col-md-6 mb-2 mb-md-0 ">
                                        <div class="flex-fill me-2">
                                          <input type="text" class="form-control form-control-lg" id="eu_departure_date" name="eu_departure_date" placeholder="Departure Date" readonly>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="flex-fill me-2">
                                           <input type="text" class="form-control form-control-lg" id="eu_arrival_date" name="eu_arrival_date" placeholder="Arrival Date" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Error Message -->
                                <div id="date-error" class="error-message text-danger mt-2" style="display: none;">
                                    Arrival date must be greater than departure date.
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Flight Options</label>
                                <select multiple class="form-control form-control-lg" id="eu-flight-options" name="eu_flight_options[]">
                                     <option value="Flexible dates">Flexible Dates</option>
                                     <option value="Direct flights">Direct Flights</option>
                                     <option value="One stop max">1 Stop Max</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="eu-number-of-adults">Number of Adults</label>
                                <input type="number" class="form-control form-control-lg" id="eu-number-of-adults" name="eu_number_of_adults" min="1" value="1">
                            </div>
                            <div class="form-group">
                                <label for="eu-number-of-children">Number of Children</label>
                                <input type="number" class="form-control form-control-lg" id="eu-number-of-children" name="eu_number_of_children" min="0" value="0" onchange="populateChildAgesForEU()">
                            </div>
                            <div class="form-group" id="eu-child-ages-container" style="display:none;">
                                <label>Ages of Children</label>
                                <div id="eu-child-ages"></div>
                            </div>
                            <div class="form-group">
                                <label>Accommodation Preferences</label>
                                <select multiple class="form-control form-control-lg" id="eu-accommodation-preferences" name="eu_accommodation_preferences[]">
                                    <option value="5 Stars+">5 Stars+</option>
                                    <option value="4 Stars+">4 Stars+</option>
                                    <option value="Adults Only">Adults Only</option>
                                    <option value="All Inclusive">All Inclusive</option>
                                    <option value="Balcony Room">Balcony Room</option>
                                    <option value="Breakfast Included">Breakfast Included</option>
                                    <option value="Boutique">Boutique</option>
                                    <option value="Close to Airport">Close to Airport</option>
                                    <option value="Close to Town">Close to Town</option>
                                    <option value="Eco-Friendly">Eco-Friendly</option>
                                    <option value="Family-Friendly">Family-Friendly</option>
                                    <option value="Free Airport Shuttle">Free Airport Shuttle</option>
                                    <option value="Kids Water Park">Kids Water Park</option>
                                    <option value="Kitchenette">Kitchenette</option>
                                    <option value="Luxury">Luxury</option>
                                    <option value="Oceanview Room">Oceanview Room</option>
                                    <option value="Pet Friendly">Pet Friendly</option>
                                    <option value="Separate Bedroom">Separate Bedroom</option>
                                    <option value="Swim Up Room">Swim Up Room</option>
                                    <option value="Swimming Pool & Gym">Swimming Pool & Gym</option>
                                    <option value="Other">Other (please specify)</option>
                                </select>
                                <input type="text" class="form-control form-control-lg mt-2" id="eu_other-preference" name="eu_other_preference" placeholder="If other, please specify">
                            </div>

                            <div class="form-group">
                                <label>Would you like to add a Travel Protection Quote?</label>
                                <select class="form-control form-control-lg" id="eu-travel-insurance-quote" name="eu_travel_insurance_quote">
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>

    
                        <!-- Adventure Group Travel Fields -->
                        <div class="conditional-group" id="adventure-group-travel-fields" style="display:none;">

                            <div class="form-group" >
                                <label for="adventure-destination">Adventure Travel Destination</label>
                                <input type="text" class="form-control form-control-lg" id="adventure_destination" name="adventure_destination" placeholder="Enter destination">
                            </div>

                            

                            <div class="form-group">
                                <label for="flight-dates">Flight Dates </label>

                                <div class="row date-range d-flex justify-content-between">

                                    <div class="col-12 col-md-6 mb-2 mb-md-0 ">
                                        <div class="flex-fill me-2">
                                          <input type="text" class="form-control form-control-lg" id="adventure_departure_date" name="adventure_departure_date" placeholder="Departure Date" readonly>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="flex-fill me-2">
                                           <input type="text" class="form-control form-control-lg" id="adventure_arrival_date" name="adventure_arrival_date" placeholder="Arrival Date" readonly>
                                        </div>
                                    </div>
                                </div>

                                
                                <!-- Error Message -->
                                
                            </div>

                            <div class="form-group">
                                <label for="adventure-company">Preferred Adventure Company</label>
                                <select multiple class="form-control form-control-lg" id="adventure-company" name="adventure_company[]">
                                    <option value="Abercrombie & Kent">Abercrombie & Kent</option>
                                    <option value="Adventure Canada">Adventure Canada</option>
                                    <option value="Anderson Vacations">Anderson Vacations</option>
                                    <option value="Big Five Tours">Big Five Tours</option>
                                    <option value="Busabout">Busabout</option>
                                    <option value="&Beyond">&Beyond</option>
                                    <option value="CIE Tours">CIE Tours</option>
                                    <option value="Collette">Collette</option>
                                    <option value="Contiki (18-35 years old)">Contiki (18-35 years old)</option>
                                    <option value="Cosmos">Cosmos</option>
                                    <option value="Costsaver">Costsaver</option>
                                    <option value="Discover Canada Tours">Discover Canada Tours</option>
                                    <option value="Egypt & Beyond">Egypt & Beyond</option>
                                    <option value="Exodus Travel">Exodus Travel</option>
                                    <option value="Exotic Journeys">Exotic Journeys</option>
                                    <option value="Exoticca">Exoticca</option>
                                    <option value="G Adventures">G Adventures</option>
                                    <option value="Globus">Globus</option>
                                    <option value="Goway Travel">Goway Travel</option>
                                    <option value="Green Olive Tours">Green Olive Tours</option>
                                    <option value="Happy Peru Tours">Happy Peru Tours</option>
                                    <option value="Hurtiguten">Hurtiguten</option>
                                    <option value="Insight Vacations">Insight Vacations</option>
                                    <option value="Intrepid Travel">Intrepid Travel</option>
                                    <option value="Just You">Just You</option>
                                    <option value="Kensington Tours">Kensington Tours</option>
                                    <option value="Luxury Gold">Luxury Gold</option>
                                    <option value="National Geographic Journeys">National Geographic Journeys</option>
                                    <option value="On The Go Tours">On The Go Tours</option>
                                    <option value="Roadtrips Sports Travel">Roadtrips Sports Travel</option>
                                    <option value="Royal Irish Tours">Royal Irish Tours</option>
                                    <option value="Senses of Cuba">Senses of Cuba</option>
                                    <option value="Sourhbound Chile">Sourhbound Chile</option>
                                    <option value="SPB Tours">SPB Tours</option>
                                    <option value="Tauck">Tauck</option>
                                    <option value="Topdeck">Topdeck</option>
                                    <option value="Tourcan Vacations">Tourcan Vacations</option>
                                    <option value="Trafalgar">Trafalgar</option>
                                    <option value="Ya’lla Tours">Ya’lla Tours</option>
                                    <!-- Add more companies as needed -->
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Flight Requirement</label>
                                <select class="form-control form-control-lg" id="adventure-flight-requirement" name="adventure_flight_requirement">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Passenger Information</label>
                                <button type="button" class="btn btn-primary" onclick="addPassenger()">Add Passenger</button>
                                <div id="adventure-passenger-info-container"></div>
                                <input type="hidden" id="adventure_passenger_count" name="adventure_passenger_count" value="0">
                            </div>

                            <div class="form-group">
                                <label>Would you like to add a Travel Protection Quote?</label>
                                <select class="form-control form-control-lg" id="adventure-travel-insurance-quote" name="adventure_travel_insurance_quote">
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>

    
                        <!-- Car Rental Only Fields -->
                        <div class="conditional-group" id="car-rental-only-fields" style="display:none;">
                            <div class="form-group">
                                <label for="rental-location">Car Rental Location</label>
                                <input type="text" class="form-control form-control-lg" id="rental-location" name="rental_location" placeholder="Enter car rental location">
                            </div>
                            <div class="form-group">
                                <label for="rental-type">Car Rental Type</label>
                                <select multiple class="form-control form-control-lg" id="rental_car_type_preferences" name="rental_car_type_preferences[]">
                                   <option value="Bus">Bus</option>
                                  <option value="Convertible">Convertible</option>
                                  <option value="Economic">Economic</option>
                                  <option value="Full Size">Full Size</option>
                                  <option value="Luxury Car">Luxury Car</option>
                                  <option value="SUV">SUV</option>
                                  <option value="Van">Van</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="flight-dates">Rental Dates </label>

                                <div class="row date-range d-flex justify-content-between">

                                    <div class="col-12 col-md-6 mb-2 mb-md-0 ">
                                        <div class="flex-fill me-2">
                                          <input type="text" class="form-control form-control-lg" id="rental_departure_date" name="rental_departure_date" placeholder="Departure Date" readonly>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="flex-fill me-2">
                                           <input type="text" class="form-control form-control-lg" id="rental_arrival_date" name="rental_arrival_date" placeholder="Arrival Date" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Error Message -->
                                <div id="date-error" class="error-message text-danger mt-2" style="display: none;">
                                    Arrival date must be greater than departure date.
                                </div>
                            </div>
                        </div>
    
                        <!-- Travel Insurance Only Fields -->
                        <div class="conditional-group" id="travel-insurance-only-fields" style="display:none;">
                            <div class="form-group">
                                <label for="insurance-coverage">Insurance Coverage Amount</label>
                                <input type="number" class="form-control form-control-lg" id="insurance-coverage" name="insurance_coverage" placeholder="Enter coverage amount">
                            </div>
                            <div class="form-group">
                                <label for="eu-package-destination">Destination</label>
                                <input type="text" class="form-control form-control-lg" id="insurance-destination" name="insurance_destination" placeholder="Enter destination">
                            </div>
                                                                            
                            <div class="form-group">
                              <label for="flight-dates">Dates </label>
                                <div class="row date-range d-flex justify-content-between">

                                    <div class="col-12 col-md-6 mb-2 mb-md-0 ">
                                        <div class="flex-fill me-2">
                                          <input type="text" class="form-control form-control-lg" id="insurance_start_date" name="insurance_start_date" placeholder="Start Date" readonly>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="flex-fill me-2">
                                           <input type="text" class="form-control form-control-lg" id="insurance_end_date" name="insurance_end_date" placeholder="End Date" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>    
                            <div class="form-group">
                                <label for="insurance-number-of-adults">Number of Adults</label>
                                <input type="number" class="form-control form-control-lg" id="insurance-number-of-adults" name="insurance_number_of_adults" min="1" value="1">
                            </div>
                            <div class="form-group">
                                <label for="insurance-number-of-children">Number of Children</label>
                                <input type="number" class="form-control form-control-lg" id="insurance-number-of-children" name="insurance_number_of_children" min="0" value="0" onchange="populateChildAgesForInsurance()">
                            </div>
                            <div class="form-group" id="insurance-child-ages-container" style="display:none;">
                                <label>Ages of Children</label>
                                <div id="insurance-child-ages"></div>
                            </div>
                        </div>

                        <div class="form-group budget-type">
                                <label for="total-budget">Budget</label>
                                <select class="form-control form-control-lg" id="budget-type" name="budget_type">
                                    <option value="Per Person">Per Person</option>
                                    <option value="Per Room">Per Room</option>
                                </select>
                        </div>
                         

                         <div class="form-group budget-total">
                                <label for="total-budget">Total Budget (CAD)</label>
                                <input type="number" class="form-control form-control-lg" id="total-budget" name="total_budget" placeholder="Enter total budget in CAD" min="0">
                        </div>

                        <div class="form-group payment-prefrence-div">
                                <label for="payment-prefrence">Payment Preference</label>
                                <select class="form-control form-control-lg" id="payment-prefrence" name="payment-prefrence">
                                    <option value="Full Payment" selected>Full Payment</option>
                                    <option value="24 Months Payment Plan">24 Months Payment Plan (On Approved Credit)</option>
                                </select>
                        </div>

                        <div class="form-group">
                                <label for="comments">Comments</label>
                                <textarea class="form-control form-control-lg" id="quote_comment" name="quote_comment" rows="3" placeholder="Enter any comments or special requests"></textarea>
                        </div>

                         



                        <!-- Promotions Opt-in -->
                         
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="promotions" name="promotions" value="yes">
                                <label class="form-check-label" for="promotions">
                                    I would like to receive special offers and promotions.
                                </label>
                            </div>
                        </div>
                        <div class="recaptcha-container">
                            <?php 
                             $settings = get_option('wp_travel_agency_settings', []);
                             $site_key = isset($settings['recaptcha_site_key']) ? esc_attr($settings['recaptcha_site_key']) : '';
                            ?>
                            
                            
                            <div class="g-recaptcha" data-sitekey="<?php echo $site_key; ?>"></div>
                        </div>
                         <br>
                        <!-- Submit Button -->
                        <div class="form-group">
                            
                            <button id="quote-submit-btn" type="submit" class="btn btn-primary"><span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span><span class="button-text">Submit Quote</span></button>
                            <style>
                                .select2-search__field
                                {
                                    width:100%!important;
                                }
                            </style>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    
       
    
        <?php
        return ob_get_clean();
    }

    public function wp_travel_agency_schedule_cron() {
        
        $existing_event = wp_next_scheduled('wp_travel_agency_send_pending_emails');
        if ($existing_event) {
            wp_unschedule_event($existing_event, 'wp_travel_agency_send_pending_emails');
        }

        $settings = get_option('wp_travel_agency_settings', array());
        $email_frequency = isset($settings['email_frequency']) ? $settings['email_frequency'] : 'daily';

        switch ($email_frequency) {
            case 'daily':
                wp_schedule_event(time(), 'daily', 'wp_travel_agency_send_pending_emails');
                break;
            case 'weekly':
                wp_schedule_event(time(), 'weekly', 'wp_travel_agency_send_pending_emails');
                break;
            case 'monthly':
                wp_schedule_event(time(), 'monthly', 'wp_travel_agency_send_pending_emails');
                break;
        }
    }

    public function send_pending_emails() {
        global $wpdb;

        $settings = get_option('wp_travel_agency_settings', array());
        $email_frequency = isset($settings['email_frequency']) ? $settings['email_frequency'] : 'daily';

        $last_execution_time = $wpdb->get_var("SELECT MAX(execution_time) FROM {$wpdb->prefix}travel_agency_cron_logs");

        if ($email_frequency == 'daily') {
            if ($last_execution_time &&  strtotime($last_execution_time) > strtotime('-1 day')) {
                return; 
            }
        } elseif ($email_frequency == 'weekly') {
            
            if ($last_execution_time &&  strtotime($last_execution_time) > strtotime('-1 week')) {
                return; 
            }
        } elseif ($email_frequency == 'monthly') {
            
            if ($last_execution_time &&  strtotime($last_execution_time) > strtotime('-1 month')) {
                return; 
            }
        }

        $leads_query = "
            SELECT * 
            FROM {$wpdb->prefix}leads l
            JOIN {$wpdb->prefix}users u ON l.agent_id = u.ID
            WHERE l.status = 'Pending'
        ";

        $pending_leads = $wpdb->get_results($leads_query);
        $email_count = 0;

        if (!empty($pending_leads)) {
            foreach ($pending_leads as $lead) {
                $agent_email = $lead->user_email;
                $lead_id = $lead->id;  
                $full_name = $lead->full_name;

                
                $subject = 'Pending Lead Status Update Required';
                $message = "
                    Hi Agent,

                    You have a pending lead with the following details:
                    Lead ID: {$lead_id}
                    Lead Name: {$full_name}

                    Please update the status of this lead.

                    Thank you.
                ";
                $from_email = get_option('admin_email');
                $from_name = get_bloginfo('name');
                $headers = array('Content-Type: text/html; charset=UTF-8');
                $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';

                
                if (wp_mail($agent_email, $subject, $message, $headers)) {
                    $email_count++;  
                }
            }
        }

        
        $wpdb->insert(
            "{$wpdb->prefix}travel_agency_cron_logs",
            array(
                'execution_time' => current_time('mysql'),
                'email_count'    => $email_count
            ),
            array('%s', '%d')
        );
    }
    

    public function wp_travel_agency_admin_styles() {
        wp_enqueue_style( 'wp-travel-agency-admin-css', plugins_url( 'css/admin-style.css', __FILE__ ) );
        wp_enqueue_style( 'wp-travel-agency-status-admin-css', plugins_url( 'css/wp-travel-admin-popup.css', __FILE__ ) );
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_script( 'wp-travel-agency-admin', plugins_url( 'js/wp-travel-agency-admin.js', __FILE__ ), array( 'jquery' ), null, true );
        wp_enqueue_script('wp-travel-agency-admin-status-js', plugin_dir_url(__FILE__) . 'js/wp-travel-agency-admin-status.js', array('jquery', 'bootstrap-js'), null, true);
        wp_localize_script( 'wp-travel-agency-admin', 'wpTravelAgencyAjax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wp_travel_agency_nonce' ),
        ));
        wp_localize_script( 'wp-travel-agency-admin-status-js', 'wpTravelAgencystatusupdatenyadminAjax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'status_nonce'    => wp_create_nonce( 'update_lead_status_admin' ),
            'delete_nonce'    => wp_create_nonce( 'delete_lead_admin' ),
        ));
    }

    public static function ajax_delete_lead_admin() {
        
        check_ajax_referer('delete_lead_admin');

        
        $lead_id = intval($_POST['lead_id']);

        if ($lead_id) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'leads'; 
            
            $deleted = $wpdb->delete(
                $table_name,
                array('id' => $lead_id), 
                array('%d') 
            );

            if ($deleted) {
                wp_send_json_success(array('message' => 'Lead deleted successfully.'));
            } else {
                wp_send_json_error(array('message' => 'Failed to delete the lead.'));
            }
        } else {
            wp_send_json_error(array('message' => 'Invalid lead ID.'));
        }

        wp_die(); 
    }
    
    

   

    public function add_admin_menus() {
        
        add_menu_page(
            __( 'Travel Agency', 'wp-travel-agency' ),  
            __( 'Travel Agency', 'wp-travel-agency' ),  
            'manage_options',                          
            'wp_travel_agency_settings',               
            array( $this, 'settings_page' ),           
            'dashicons-admin-site-alt3',               
            25                                         
        );

        
        add_submenu_page(
            'wp_travel_agency_settings',               
            __( 'Settings', 'wp-travel-agency' ),      
            __( 'Settings', 'wp-travel-agency' ),      
            'manage_options',                          
            'wp_travel_agency_settings',               
            array( $this, 'settings_page' )            
        );

     /*    
        add_submenu_page(
            'wp_travel_agency_settings',               
            __( 'Leads', 'wp-travel-agency' ),         
            __( 'Leads', 'wp-travel-agency' ),         
            'manage_options',                          
            'wp_travel_agency_leads',                  
            array( $this, 'leads_page' )               
        );
       add_submenu_page(
            'wp_travel_agency_settings',               
            __( 'Statistics and Graphs', 'wp-travel-agency' ), 
            __( 'Statistics and Graphs', 'wp-travel-agency' ), 
            'manage_options',                          
            'wp_travel_agency_statistics',             
            array( $this, 'statistics_page' )        
        );
        */
        add_submenu_page(
            'null',
            __('Lead Comments', 'wp-travel-agency'),
            __('Lead Comments', 'wp-travel-agency'),
            'read',
            'lead_comments_admin',
            array(__CLASS__, 'render_comments_admin_page')
        );
    }

    public static function render_comments_admin_page() {
        
        global $wpdb;
        
        $lead_id = isset($_GET['lead_id']) ? intval($_GET['lead_id']) : 0;
    
        if ($lead_id === 0) {
            echo '<p>' . esc_html__('Invalid Lead ID.', 'wp-travel-agency') . '</p>';
            return;
        }
        $current_user_id = get_current_user_id();
    
        $table_name = $wpdb->prefix . 'leads_comments'; 
        $comments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE lead_id = %d", $lead_id));
    
        $lead_table = $wpdb->prefix . 'leads';
        $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM $lead_table WHERE id = %d", $lead_id));
      
       
    
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Comments for Lead:', 'wp-travel-agency') . ' ' . esc_html($lead->full_name); ?></h1>
            
            <p>
                <strong><?php esc_html_e('Full Name:', 'wp-travel-agency'); ?></strong> <?php echo esc_html($lead->full_name); ?><br>
                <strong><?php esc_html_e('Email:', 'wp-travel-agency'); ?></strong> <?php echo esc_html($lead->email); ?><br>
                <strong><?php esc_html_e('Phone:', 'wp-travel-agency'); ?></strong> <?php echo esc_html($lead->phone_number); ?><br>
                <strong><?php esc_html_e('Type:', 'wp-travel-agency'); ?></strong> <?php echo esc_html($lead->quote_type); ?><br>
            </p>
    
            <h2><?php esc_html_e('Comments', 'wp-travel-agency'); ?></h2>
            <ul>
                <?php if (!empty($comments)) : ?>
                    <?php foreach ($comments as $comment) : ?>
                        <li>
                            <p>Comment date :<strong><?php echo esc_html($comment->comment_date); ?></strong></p>
                            <p><?php echo esc_html($comment->text); ?></p>
                            <?php if (isset($comment->created_at)): ?>
                            <p><em>Created at: <?php echo esc_html(date('F j, Y \a\t g:i A', strtotime($comment->created_at))); ?></em></p>
                        <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p><?php esc_html_e('No comments available for this lead.', 'wp-travel-agency'); ?></p>
                <?php endif; ?>
            </ul>
    
            
            <a href="<?php echo esc_url(admin_url('admin.php?page=wp_travel_agency_leads')); ?>" class="button"><?php esc_html_e('Back to Leads', 'wp-travel-agency'); ?></a>
        </div>
        <?php
    }

    

    public function statistics_page() {


        global $wpdb;
        $table_name = $wpdb->prefix . 'leads';

        $month_filter = isset($_GET['month']) ? intval($_GET['month']) : null;
        $year_filter = isset($_GET['year']) ? intval($_GET['year']) : null;
        $current_month = date('n');
        $current_year = date('Y');

        $filter_condition = '';
        if ($month_filter && $year_filter) {
            $filter_condition = $wpdb->prepare(
                " AND MONTH(leads.created_at) = %d AND YEAR(leads.created_at) = %d",
                $month_filter,
                $year_filter
            );
        } elseif ($year_filter) {
            $filter_condition = $wpdb->prepare(
                " AND YEAR(leads.created_at) = %d",
                $year_filter
            );
        } elseif ($month_filter) {
            $filter_condition = $wpdb->prepare(
                " AND MONTH(leads.created_at) = %d",
                $month_filter
            );
        }else {

            $filter_condition = $wpdb->prepare(
                " AND MONTH(leads.created_at) = %d AND YEAR(leads.created_at) = %d",
                $current_month,
                $current_year
            );
        }

        $status_query = "SELECT status AS lead_status, COUNT(*) AS count FROM $table_name leads WHERE 1=1 $filter_condition GROUP BY lead_status";
        $status_results = $wpdb->get_results($status_query);

        $statuses = [];
        $status_counts = [];
        foreach ($status_results as $row) {
            $statuses[] = $row->lead_status;
            $status_counts[] = (int)$row->count;
        }

        $quote_type_options = [
            'Flight Only',
            'Vacation Packages',
            'Cruise Package',
            'Accommodations Only',
            'Flight & Hotel City Packages',
            'Europe Packages',
            'Adventure Group Travel',
            'Car Rental Only',
            'Travel Insurance Only'
        ];

        $quote_type_counts = array_fill(0, count($quote_type_options), 0);
        foreach ($quote_type_options as $index => $type) {
            $quote_type_query = $wpdb->prepare(
                "SELECT COUNT(*) AS count FROM $table_name leads WHERE quote_type = %s $filter_condition",
                $type
            );
            $quote_type_counts[$index] = (int)$wpdb->get_var($quote_type_query);
        }

        $agents = get_users(array(
            'role'    => 'agent',
            'fields'  => array('ID', 'display_name'),
        ));

        $agent_labels = [];
        $agent_counts = [];
        $avg_response_times = [];
        foreach ($agents as $agent) {
            $agent_labels[] = $agent->display_name;

            $agent_query = $wpdb->prepare(
                "SELECT COUNT(*) AS count FROM $table_name leads WHERE agent_id = %d $filter_condition",
                $agent->ID
            );
            $agent_counts[] = (int)$wpdb->get_var($agent_query);

//            $response_time_query = $wpdb->prepare(
//                "SELECT AVG(TIMESTAMPDIFF(HOUR, leads.created_at, comments.created_at)) AS avg_time
//                FROM {$wpdb->prefix}leads_comments AS comments
//                JOIN $table_name leads ON comments.lead_id = leads.id
//                WHERE leads.agent_id = %d $filter_condition",
//                $agent->ID
//            );

	        $response_time_query = $wpdb->prepare(
		        "SELECT AVG(TIMESTAMPDIFF(HOUR, leads.created_at, leads.status_change_time)) AS avg_time 
                FROM {$wpdb->prefix}leads AS leads 
                WHERE leads.agent_id = %d AND leads.status_change_time IS NOT NULL $filter_condition",
		        $agent->ID
	        );


//	        SELECT AVG(TIMESTAMPDIFF(HOUR, leads.created_at, leads.status_change_time)) AS overall_avg_time
//            FROM {$wpdb->prefix}leads AS leads
//            WHERE 1=1 $filter_condition
//                      AND leads.status_change_time is not null;


            $avg_time_in_minutes = (float)$wpdb->get_var($response_time_query) ?: 0;
            $avg_response_times[] = $avg_time_in_minutes;
//            var_dump($avg_response_times) ; die;

        }

        $total_leads_query = "SELECT COUNT(*) AS total FROM $table_name leads WHERE 1=1 $filter_condition";
        $total_leads = (int)$wpdb->get_var($total_leads_query);

        $completed_leads_query = "SELECT COUNT(*) AS completed FROM $table_name leads WHERE status = 'Successful' $filter_condition";
        $completed_leads = (int)$wpdb->get_var($completed_leads_query);

        $success_percentage = ($total_leads > 0) ? ($completed_leads / $total_leads) * 100 : 0;

//        $overall_response_time_query = "
//        SELECT AVG(TIMESTAMPDIFF(HOUR, leads.created_at, comments.created_at)) AS overall_avg_time
//        FROM {$wpdb->prefix}leads_comments AS comments
//        JOIN $table_name leads ON comments.lead_id = leads.id
//        WHERE 1=1 $filter_condition
//        ";

	    $overall_response_time_query = "
        SELECT AVG(TIMESTAMPDIFF(HOUR, leads.created_at, leads.status_change_time)) AS overall_avg_time
            FROM {$wpdb->prefix}leads AS leads
            WHERE 1=1 $filter_condition
            AND leads.status_change_time is not null;
        ";

	    $overall_avg_response_time = (float)$wpdb->get_var($overall_response_time_query) ?: 0;

//        var_dump($overall_avg_response_time); die();





	    ob_start();
        echo '<div class="container-fluid mt-5">';
            echo '<div class="text-center mb-4">';
                echo '<h1 class="display-4">' . __( 'Statistics and Graphs', 'wp-travel-agency' ) . '</h1>';
                echo '<p class="lead text-muted">A detailed overview of leads performance and agent activity</p>';
            echo '</div>';

            echo '<div class="mb-4">';
                    echo '<form method="get" action="" class="form-inline">';
                        echo '<input type="hidden" name="page" value="wp_travel_agency_statistics">';
                        echo '<div class="form-group mr-3">';
                            echo '<label for="month" class="mr-2">Month:</label>';
                            echo '<select name="month" id="month" class="form-control form-control-lg">';
                                echo '<option value="">All</option>';
                                for ($m = 1; $m <= 12; $m++) {
                                    $selected = ($m == $month_filter || ($month_filter === null && $m == $current_month)) ? 'selected' : '';
                                    echo '<option value="' . $m . '" ' . $selected . '>' . date('F', mktime(0, 0, 0, $m, 10)) . '</option>';
                                }
                            echo '</select>';
                        echo '</div>';

                        echo '<div class="form-group mr-3">';
                            echo '<label for="year" class="mr-2">Year:</label>';
                            echo '<select name="year" id="year" class="form-control form-control-lg">';
                                echo '<option value="">All</option>';
                                for ($y = date('Y'); $y >= 2000; $y--) {
                                    $selected = ($y == $year_filter || ($year_filter === null && $y == $current_year)) ? 'selected' : '';
                                    echo '<option value="' . $y . '" ' . $selected . '>' . $y . '</option>';
                                }
                            echo '</select>';
                        echo '</div>';

                        echo '<button style="margin-top:15px;background-color:#026EB9;color:#fff;font-size:14px;border:solid 2px #026EB9;" type="submit" class="btn btn-primary">Filter</button>';
                    echo '</form>';
            echo '</div>';
            if ($total_leads > 0) {


                echo '<div class="row">';
                    echo '<div class="col-md-6 mb-4">';
                        echo '<div class="card shadow-sm">';
                            echo '<div class="card-body">';
                                echo '<h2 class="h5 card-title">' . __( 'Leads Status Distribution', 'wp-travel-agency' ) . '</h2>';

                                echo '<form id="filter-form-leads-status" class="mb-4">';
                                    echo '<div class="form-group">';
                                        echo '<label for="quote_type">' . __( 'Quote Type', 'wp-travel-agency' ) . '</label>';
                                        echo '<select id="quote_type" class="form-control form-control-lg">';
                                            echo '<option value="">' . __( 'All', 'wp-travel-agency' ) . '</option>';
                                            echo '<option value="Flight Only">' . __( 'Flight Only', 'wp-travel-agency' ) . '</option>';
                                            echo '<option value="Vacation Packages">' . __( 'Vacation Packages', 'wp-travel-agency' ) . '</option>';
                                            echo '<option value="Cruise Package">' . __( 'Cruise Package', 'wp-travel-agency' ) . '</option>';
                                            echo '<option value="Accommodations Only">' . __( 'Accommodations Only', 'wp-travel-agency' ) . '</option>';
                                            echo '<option value="Flight & Hotel City Packages">' . __( 'Flight & Hotel City Packages', 'wp-travel-agency' ) . '</option>';
                                            echo '<option value="Europe Packages">' . __( 'Europe Packages', 'wp-travel-agency' ) . '</option>';
                                            echo '<option value="Adventure Group Travel">' . __( 'Adventure Group Travel', 'wp-travel-agency' ) . '</option>';
                                            echo '<option value="Car Rental Only">' . __( 'Car Rental Only', 'wp-travel-agency' ) . '</option>';
                                            echo '<option value="Travel Insurance Only">' . __( 'Travel Insurance Only', 'wp-travel-agency' ) . '</option>';
                                        echo '</select>';
                                    echo '</div>';

                                    echo '<div class="form-group">';
                                        echo '<label for="agent">' . __( 'Agent', 'wp-travel-agency' ) . '</label>';
                                        echo '<select id="agent" class="form-control form-control-lg">';
                                            echo '<option value="">' . __( 'All', 'wp-travel-agency' ) . '</option>';

                                            $args = array(
                                                'role'    => 'agent',
                                                'orderby' => 'display_name',
                                                'order'   => 'ASC',
                                            );
                                            $agents = get_users($args);
                                            foreach ($agents as $agent) {
                                                echo '<option value="' . esc_attr($agent->ID) . '">' . esc_html($agent->display_name) . '</option>';
                                            }

                                            echo '<!-- Add more agents as needed -->';
                                        echo '</select>';
                                    echo '</div>';

                                    echo '<button style="background-color:#026EB9;color:#fff;font-size:14px;border:solid 2px #026EB9;" type="submit" class="btn btn-primary">' . __( 'Filter', 'wp-travel-agency' ) . '</button>';
                                echo '</form>';
                                echo '<div class="chart-container">
                                    <canvas id="leads-status-chart"></canvas>
                                </div>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';

                        echo '<div class="col-md-6 mb-4">';
                        echo '<div class="card shadow-sm">';
                            echo '<div class="card-body">';
                                echo '<h2 class="h5 card-title">' . __( 'Average Response Time by Agent', 'wp-travel-agency' ) . '</h2>';
                                echo '<div class="chart-container">
                                    <canvas id="response-time-chart"></canvas>
                                </div>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';
                echo '</div>';

                    echo '<div class="row">';
                    echo '<div class="col-md-6 mb-4">';
                        echo '<div class="card shadow-sm">';
                            echo '<div class="card-body">';
                                echo '<h2 class="h5 card-title">' . __( 'Leads by Quote Type', 'wp-travel-agency' ) . '</h2>';
                                echo '<form id="filter-form-quote-type" class="mb-4">';


                                echo '<div class="form-group">';
                                    echo '<label for="agent">' . __( 'Agent', 'wp-travel-agency' ) . '</label>';
                                    echo '<select id="agent_quote_type" class="form-control form-control-lg">';
                                        echo '<option value="">' . __( 'All', 'wp-travel-agency' ) . '</option>';

                                        $args = array(
                                            'role'    => 'agent',
                                            'orderby' => 'display_name',
                                            'order'   => 'ASC',
                                        );
                                        $agents = get_users($args);
                                        foreach ($agents as $agent) {
                                            echo '<option value="' . esc_attr($agent->ID) . '">' . esc_html($agent->display_name) . '</option>';
                                        }

                                        echo '<!-- Add more agents as needed -->';
                                    echo '</select>';
                                echo '</div>';

                                echo '<button style="background-color:#026EB9;color:#fff;font-size:14px;border:solid 2px #026EB9;" type="submit" class="btn btn-primary">' . __( 'Filter', 'wp-travel-agency' ) . '</button>';
                            echo '</form>';
                                echo '<div class="chart-container">
                                    <canvas id="leads-quote-type-chart"  style="width: 100%; height: 420px;"></canvas>
                                </div>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';


                    echo '<div class="col-md-6 mb-4">';
                        echo '<div class="card shadow-sm">';
                            echo '<div class="card-body">';
                                echo '<h2 class="h5 card-title">' . __( 'Leads by Agent', 'wp-travel-agency' ) . '</h2>';
                                echo '<div class="chart-container">
                                    <canvas id="leads-agent-chart" style="width: 100%; height: 420px;"></canvas>
                                </div>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';
                    echo '</div>';

                echo '<div class="row">';
                    echo '<div class="col-md-6 mb-4">';
                        echo '<div class="card shadow-sm">';
                            echo '<div class="card-body">';
                                echo '<h2 class="h5 card-title">' . __( 'Average Success Percentage of Leads', 'wp-travel-agency' ) . '</h2>';

                                echo '<form id="filter-form-leads-status-percentage" class="mb-4">';
                                echo '<div class="form-group">';
                                    echo '<label for="quote_type">' . __( 'Quote Type', 'wp-travel-agency' ) . '</label>';
                                    echo '<select id="quote_type_perc" class="form-control form-control-lg">';
                                        echo '<option value="">' . __( 'All', 'wp-travel-agency' ) . '</option>';
                                        echo '<option value="Flight Only">' . __( 'Flight Only', 'wp-travel-agency' ) . '</option>';
                                        echo '<option value="Vacation Packages">' . __( 'Vacation Packages', 'wp-travel-agency' ) . '</option>';
                                        echo '<option value="Cruise Package">' . __( 'Cruise Package', 'wp-travel-agency' ) . '</option>';
                                        echo '<option value="Accommodations Only">' . __( 'Accommodations Only', 'wp-travel-agency' ) . '</option>';
                                        echo '<option value="Flight & Hotel City Packages">' . __( 'Flight & Hotel City Packages', 'wp-travel-agency' ) . '</option>';
                                        echo '<option value="Europe Packages">' . __( 'Europe Packages', 'wp-travel-agency' ) . '</option>';
                                        echo '<option value="Adventure Group Travel">' . __( 'Adventure Group Travel', 'wp-travel-agency' ) . '</option>';
                                        echo '<option value="Car Rental Only">' . __( 'Car Rental Only', 'wp-travel-agency' ) . '</option>';
                                        echo '<option value="Travel Insurance Only">' . __( 'Travel Insurance Only', 'wp-travel-agency' ) . '</option>';
                                    echo '</select>';
                                echo '</div>';

                                echo '<div class="form-group">';
                                    echo '<label for="agent">' . __( 'Agent', 'wp-travel-agency' ) . '</label>';
                                    echo '<select id="agent_perc" class="form-control form-control-lg">';
                                        echo '<option value="">' . __( 'All', 'wp-travel-agency' ) . '</option>';

                                        $args = array(
                                            'role'    => 'agent',
                                            'orderby' => 'display_name',
                                            'order'   => 'ASC',
                                        );
                                        $agents = get_users($args);
                                        foreach ($agents as $agent) {
                                            echo '<option value="' . esc_attr($agent->ID) . '">' . esc_html($agent->display_name) . '</option>';
                                        }

                                        echo '<!-- Add more agents as needed -->';
                                    echo '</select>';
                                echo '</div>';

                                echo '<button style="background-color:#026EB9;color:#fff;font-size:14px;border:solid 2px #026EB9;" type="submit" class="btn btn-primary">' . __( 'Filter', 'wp-travel-agency' ) . '</button>';
                            echo '</form>';
                                echo '<div class="chart-container">
                                    <canvas id="success-percentage-chart"></canvas>
                                </div>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';

                    echo '<div class="col-md-6 mb-4">';
                    echo '<div class="card shadow-sm">';
                        echo '<div class="card-body">';
                            echo '<h2 class="h5 card-title">' . __( 'Average Response Time by Agency (Hours)', 'wp-travel-agency' ) . '</h2>';
                            echo '<div class="chart-container">
                                <canvas id="aaverage-responsetimeforagents" style="width: 100%; height: 420px;"></canvas>
                            </div>';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';
            } else {

                echo '<div class="alert alert-warning text-center">';
                    echo __( 'Not enough leads to generate graphs.', 'wp-travel-agency' );
                echo '</div>';
            }
        echo '</div>';


        echo ob_get_clean();

        ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            function displayMessage(chartId, message) {
                const chartElement = document.getElementById(chartId);
                const parentElement = chartElement.parentElement;
                parentElement.innerHTML = `<p>${message}</p>`;
            }

            function generateRandomColors(count) {
                 return ["#fd7f6f", "#7eb0d5", "#b2e061", "#bd7ebe", "#ffb55a", "#ffee65", "#beb9db", "#fdcce5", "#8bd3c7", "#1a53ff", "#7c1158"];
            }


            function getStatusColor(status) {
                switch (status) {
                    case 'Successful':
                        return '#28a745';
                    case 'Fail':
                        return '#dc3545';
                    case 'Pending':
                        return '#ffc107';
                    case 'In Progress':
                        return '#007bff';
                    default:
                        return '#6f42c1';
                }
            }

            document.addEventListener("DOMContentLoaded", function () {


                setInterval(function() {
                    location.reload();
                }, 300000);
                jQuery('#filter-form-leads-status').on('submit', function(e) {
                    e.preventDefault();

                    var quoteType = jQuery('#quote_type').val();
                    var agent = jQuery('#agent').val();
                    let leadsStatusChart;

                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'filter_leads_status',
                            quote_type: quoteType,
                            agent: agent
                        },
                        success: function(response) {
                            if (response.success) {
                                var statuses = response.data.statuses;
                                var statusCounts = response.data.statusCounts;

                                var backgroundColors = statuses.map(status => getStatusColor(status));

                                var ctxStatus = document.getElementById('leads-status-chart').getContext('2d');

                                if (Chart.getChart('leads-status-chart')) {

                                    Chart.getChart('leads-status-chart').destroy();
                                }

                                leadsStatusChart = new Chart(ctxStatus, {
                                    type: 'pie',
                                    data: {
                                        labels: statuses,
                                        datasets: [{
                                            data: statusCounts,
                                            backgroundColor: backgroundColors,
                                            hoverOffset: 4
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        plugins: {
                                            legend: {
                                                display: true,
                                                position: 'bottom'
                                            }
                                        }
                                    }
                                });
                            } else {
                                alert('Error fetching filtered data.');
                            }
                        }


                    });
                });



                jQuery('#filter-form-leads-status-percentage').on('submit', function(e) {
                    e.preventDefault();

                    var quoteType = jQuery('#quote_type_perc').val();
                    var agent = jQuery('#agent_perc').val();
                    let leadsStatusChart;
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'filter_completed_leads_by_quote_type_and_agent',
                            quote_type: quoteType,
                            agent: agent
                        },

                        success: function(response) {

                            if (response.success) {
                                const successPercentage = response.data.successPercentage;

                                if (Chart.getChart('success-percentage-chart')) {
                                    Chart.getChart('success-percentage-chart').destroy();
                                }
                                var ctxSuccessPercentage = document.getElementById('success-percentage-chart').getContext('2d');

                                if (successPercentage !== undefined && successPercentage !== null  ) {

                                    successPercentageChart = new Chart(ctxSuccessPercentage, {
                                        type: 'pie',
                                        data: {
                                            labels: ['Successful', 'Pending'],
                                            datasets: [{
                                                label: ['<?php esc_html_e('Percentage %', 'wp-travel-agency'); ?>'],
                                                data: [successPercentage, 100 - successPercentage],
                                                backgroundColor: ['#28a745', '#ffc107'],
                                                hoverOffset: 4
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            plugins: {
                                                legend: {
                                                    display: true,
                                                    position: 'bottom'
                                                }
                                            }
                                        }
                                    });
                                } else {
                                    displayMessage('success-percentage-chart', 'No data available for Success Percentage.');
                                }
                            }

                        }


                    });
                });

                jQuery('#filter-form-quote-type').on('submit', function(e) {
                    e.preventDefault();

                    var agent = jQuery('#agent_quote_type').val();

                    let leadsStatusChart;
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'filter_quote_type_counts_by_agent',
                            agent: agent
                        },
                        success: function(response) {
                            if (response.success) {
                                const quoteTypeOptions = response.data.quoteTypeOptions;
                                const quoteTypeCounts = response.data.quoteTypeCounts;
                                var ctxQuoteType = document.getElementById('leads-quote-type-chart').getContext('2d');

                                if (Chart.getChart('leads-quote-type-chart')) {

                                    Chart.getChart('leads-quote-type-chart').destroy();
                                }


                                if (quoteTypeOptions.length > 0 && quoteTypeCounts.length > 0) {

                                    leadsQuoteTypeChart = new Chart(ctxQuoteType, {
                                        type: 'bar',
                                        data: {
                                            labels: quoteTypeOptions,
                                            datasets: [{
                                                data: quoteTypeCounts,
                                                backgroundColor: generateRandomColors(),
                                                hoverOffset: 4
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            plugins: {
                                                legend: { display: false }
                                            },
                                            scales: {
                                                x: {
                                                    beginAtZero: true,
                                                    title: { display: true, text: 'Quote Type' }
                                                },
                                                y: {
                                                    beginAtZero: true,
                                                    title: { display: true, text: 'Lead Count' }
                                                }
                                            }
                                        }
                                    });
                                } else {

                                    console.log('No data available to display.');
                                }
                            } else {

                                console.error('Failed to retrieve data.');
                            }
                        }


                    });
                });

                // General Agency Response Time Chart
                const overallAvgResponseTime = <?php echo json_encode($overall_avg_response_time); ?>;
                if (overallAvgResponseTime) {
                    var ctxResponseTime = document.getElementById('aaverage-responsetimeforagents').getContext('2d');
                    new Chart(ctxResponseTime, {
                        type: 'bar',
                        data: {
                            labels: ['<?php esc_html_e('General Agency Response Time', 'wp-travel-agency'); ?>'],
                            datasets: [{
                                label: '<?php esc_html_e('General Agency Response Time (Hours)', 'wp-travel-agency'); ?>',
                                data: [overallAvgResponseTime],
                                backgroundColor: '#ffc107',
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: true, position: 'bottom' } },
                            scales: {
                                x: { title: { display: true, text: 'Response Time Metrics' } },
                                y: { beginAtZero: true, title: { display: true, text: 'Average Response Time (Hours)' } }
                            }
                        }
                    });
                } else {
                    displayMessage('aaverage-responsetimeforagents', 'No data available for General Agency Response Time.');
                }

                // Leads Status Chart
                const statuses = <?php echo json_encode($statuses); ?>;
                const statusCounts = <?php echo json_encode($status_counts); ?>;
                if (statuses.length > 0 && statusCounts.length > 0) {
                    const backgroundColors = statuses.map(status => getStatusColor(status));
                    var ctxStatus = document.getElementById('leads-status-chart').getContext('2d');
                    new Chart(ctxStatus, {
                        type: 'pie',
                        data: {
                            labels: statuses,
                            datasets: [{
                                data: statusCounts,
                                backgroundColor: backgroundColors,
                                hoverOffset: 4
                            }]
                        },
                        options: { responsive: true, plugins: { legend: { display: true, position: 'bottom' } } }
                    });
                } else {
                    displayMessage('leads-status-chart', 'No data available for Leads Status.');
                }

                // Leads Quote Type Chart
                const quoteTypeOptions = <?php echo json_encode($quote_type_options); ?>;
                const quoteTypeCounts = <?php echo json_encode($quote_type_counts); ?>;
                if (quoteTypeOptions.length > 0 && quoteTypeCounts.length > 0) {
                    var ctxQuoteType = document.getElementById('leads-quote-type-chart').getContext('2d');
                    new Chart(ctxQuoteType, {
                        type: 'bar',
                        data: {
                            labels: quoteTypeOptions,
                            datasets: [{
                                data: quoteTypeCounts,
                                backgroundColor: generateRandomColors(),
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { beginAtZero: true, title: { display: true, text: 'Quote Type' } },
                                y: { beginAtZero: true, title: { display: true, text: 'Lead Count' } }
                            }
                        }
                    });
                } else {
                    displayMessage('leads-quote-type-chart', 'No data available for Leads Quote Type.');
                }

                // Leads by Agent Chart
                const agentLabels = <?php echo json_encode($agent_labels); ?>;
                const agentCounts = <?php echo json_encode($agent_counts); ?>;
                if (agentLabels.length > 0 && agentCounts.length > 0) {
                    var agentColors = generateRandomColors();
                    var ctxAgent = document.getElementById('leads-agent-chart').getContext('2d');
                    new Chart(ctxAgent, {
                        type: 'bar',
                        data: {
                            labels: agentLabels,
                            datasets: [{
                                data: agentCounts,
                                backgroundColor: agentColors,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { beginAtZero: true, title: { display: true, text: 'Agents' } },
                                y: { beginAtZero: true, title: { display: true, text: 'Lead Count' } }
                            }
                        }
                    });
                } else {
                    displayMessage('leads-agent-chart', 'No data available for Leads by Agent.');
                }

                // Response Time by Agent Chart
                const avgResponseTimes = <?php echo json_encode($avg_response_times); ?>;
                const allValuesZero = avgResponseTimes.every(value => value === 0);
                if (avgResponseTimes.length > 0 && !allValuesZero) {
                    var ctxResponseTime = document.getElementById('response-time-chart').getContext('2d');
                    var agentColors = generateRandomColors(agentLabels.length);
                    new Chart(ctxResponseTime, {
                        type: 'pie',
                        data: {
                            labels: agentLabels,
                            datasets: [{
                                label: '<?php esc_html_e('Average Response Time by Agency (Hours)', 'wp-travel-agency'); ?>',
                                data: avgResponseTimes,
                                backgroundColor: agentColors,
                                hoverOffset: 4
                            }]
                        },
                        options: { responsive: true, plugins: { legend: { display: true, position: 'bottom' } } }
                    });
                } else {
                    displayMessage('response-time-chart', 'No data available for Response Time by Agency.');
                }

                // Success Percentage Chart
                const successPercentage = <?php echo $success_percentage; ?>;
                if (successPercentage) {
                    var ctxSuccessPercentage = document.getElementById('success-percentage-chart').getContext('2d');
                    new Chart(ctxSuccessPercentage, {
                        type: 'pie',
                        data: {
                            labels: ['Successful', 'Pending'],
                            datasets: [{
                                label: '<?php esc_html_e('Percentage %', 'wp-travel-agency'); ?>',
                                data: [successPercentage, 100 - successPercentage],
                                backgroundColor: ['#28a745', '#ffc107'],
                                hoverOffset: 4
                            }]
                        },
                        options: { responsive: true, plugins: { legend: { display: true, position: 'bottom' } } }
                    });
                } else {
                    displayMessage('success-percentage-chart', 'No data available for Success Percentage.');
                }
            });
        </script>


        <?php
    }
    
    
    
    
    
    
    
    public function ajax_get_posts_by_type() {
        check_ajax_referer( 'wp_travel_agency_nonce', 'nonce' );
    
        $post_type = sanitize_text_field( $_POST['post_type'] );
    
        if ( ! empty( $post_type ) ) {
            $posts = get_posts( array(
                'post_type'   => $post_type,
                'numberposts' => -1,
            ));
    
            if ( ! empty( $posts ) ) {
                wp_send_json_success( array( 'posts' => $posts ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'No posts found.', 'wp-travel-agency' ) ) );
            }
        } else {
            wp_send_json_error( array( 'message' => __( 'Invalid post type.', 'wp-travel-agency' ) ) );
        }
    }

    public function settings_page() {
        $error_message = '';
        $success_message = '';
    
        if ( isset( $_POST['wp_travel_agency_form_submit'] ) && check_admin_referer( 'wp_travel_agency_save_form_id' ) ) {
            $email_frequency = sanitize_text_field( $_POST['wp_travel_agency_email_frequency'] );
            $recaptcha_site_key = sanitize_text_field( $_POST['wp_travel_agency_recaptcha_site_key'] );
            $recaptcha_secret_key = sanitize_text_field( $_POST['wp_travel_agency_recaptcha_secret_key'] );
    


                $settings = [
                    'email_frequency' => $email_frequency,
                    'recaptcha_site_key' => $recaptcha_site_key,
                    'recaptcha_secret_key' => $recaptcha_secret_key,
                ];
    
                update_option( 'wp_travel_agency_settings', $settings );
    
                $success_message = esc_html__( 'Settings saved successfully.', 'wp-travel-agency' );
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $success_message ) . '</p></div>';
        }
    
        
        $saved_settings = get_option( 'wp_travel_agency_settings', [
            'email_frequency' => 'daily',
            'recaptcha_site_key' => '',
            'recaptcha_secret_key' => '',
        ] );
    
        $email_frequencyselected = isset($saved_settings['email_frequency']) ? $saved_settings['email_frequency'] : 'daily';
        $recaptcha_site_key = isset($saved_settings['recaptcha_site_key']) ? $saved_settings['recaptcha_site_key'] : '';
        $recaptcha_secret_key = isset($saved_settings['recaptcha_secret_key']) ? $saved_settings['recaptcha_secret_key'] : '';

        $post_types = get_post_types( [], 'objects' );
    
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Travel Agency Settings', 'wp-travel-agency' ); ?></h1>
    
            <?php
            if ( ! empty( $error_message ) ) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error_message ) . '</p></div>';
            }
            ?>
    
            <form method="post" action="">
                <?php wp_nonce_field( 'wp_travel_agency_save_form_id' ); ?>
    
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wp_travel_agency_recaptcha_site_key"><?php echo esc_html__( 'Form reCAPTCHA Site Key', 'wp-travel-agency' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wp_travel_agency_recaptcha_site_key" name="wp_travel_agency_recaptcha_site_key" class="regular-text" value="<?php echo esc_attr( $recaptcha_site_key ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wp_travel_agency_recaptcha_secret_key"><?php echo esc_html__( 'Form reCAPTCHA Secret Key', 'wp-travel-agency' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wp_travel_agency_recaptcha_secret_key" name="wp_travel_agency_recaptcha_secret_key" class="regular-text" value="<?php echo esc_attr( $recaptcha_secret_key ); ?>">
                        </td>
                    </tr>
                    <tr>
                    <th scope="row">
                        <label for="wp_travel_agency_email_frequency"><?php echo esc_html__( 'Email Frequency (Cron Job)', 'wp-travel-agency' ); ?></label>
                    </th>
                    <td>
                        <select id="wp_travel_agency_email_frequency" name="wp_travel_agency_email_frequency" class="regular-text">
                            <option value="daily" <?php selected( $email_frequencyselected, 'daily' ); ?>><?php echo esc_html__( 'Once a Day', 'wp-travel-agency' ); ?></option>
                            <option value="weekly" <?php selected( $email_frequencyselected, 'weekly' ); ?>><?php echo esc_html__( 'Once a Week', 'wp-travel-agency' ); ?></option>
                            <option value="monthly" <?php selected( $email_frequencyselected, 'monthly' ); ?>><?php echo esc_html__( 'Once a Month', 'wp-travel-agency' ); ?></option>
                        </select>
                    </td>
                </tr>
                </table>
    
                <input type="submit" name="wp_travel_agency_form_submit" value="<?php esc_attr_e( 'Save Settings', 'wp-travel-agency' ); ?>" class="button button-primary">
            </form>
            <br><br><br>
            <h2><?php esc_html_e( 'Settings page', 'wp-travel-agency' ); ?></h2>
            <div>
                <a target="_blank" href="https://travelgurus.ca/leads-dashboard/">Lead Dshboard</a
            </div>
        </div>
        <?php
    }
    
    
    
    public function leads_page() {
        global $wpdb;

        $count = 1;
        $big = 999999999;
        $table_name = $wpdb->prefix . 'leads';
        $per_page = 60;
        if ( get_query_var('paged') ) { $paged = get_query_var('paged'); }
elseif ( get_query_var('page') ) { $paged = get_query_var('page'); }
else { $paged = 1; }
        $offset = ($paged - 1) * $per_page;

        $search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $agent_filter = isset($_GET['agent']) ? intval($_GET['agent']) : '';
        $quote_type_filter = isset($_GET['quote_type']) ? sanitize_text_field($_GET['quote_type']) : '';

        $sql = "SELECT * FROM $table_name WHERE 1=1";

        if (!empty($search_query)) {
            $search_query_esc = $wpdb->esc_like($search_query);
            $sql .= " AND (full_name LIKE '%$search_query_esc%' OR email LIKE '%$search_query_esc%' OR phone_number LIKE '%$search_query_esc%')";
        }

        if (!empty($status_filter)) {
            $status_filter_esc = esc_sql($status_filter);
            $sql .= " AND status = '$status_filter_esc'";
        }

        if (!empty($quote_type_filter)) {
            $quote_type_filter_esc = esc_sql($quote_type_filter);
            $sql .= " AND quote_type = '$quote_type_filter_esc'";
        }

        if (!empty($agent_filter)) {
            $agent_filter_esc = intval($agent_filter);
            $sql .= " AND agent_id = $agent_filter_esc";
        }
        $sql .= " ORDER BY id DESC";

        $sql .= " LIMIT $per_page OFFSET $offset";

        $leads = $wpdb->get_results($sql);

        $count_sql = "SELECT COUNT(*) FROM $table_name WHERE 1=1";

        if (!empty($search_query)) {
            $count_sql .= " AND (full_name LIKE '%$search_query_esc%' OR email LIKE '%$search_query_esc%' OR phone_number LIKE '%$search_query_esc%')";
        }

        if (!empty($status_filter)) {
            $count_sql .= " AND status = '$status_filter_esc'";
        }

        if (!empty($quote_type_filter)) {
            $count_sql .= " AND quote_type = '$quote_type_filter_esc'";
        }

        if (!empty($agent_filter)) {
            $count_sql .= " AND agent_id = $agent_filter_esc";
        }

        $total_leads = $wpdb->get_var($count_sql);
        $total_pages = ceil($total_leads / $per_page);

    
        
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Leads Management', 'wp-travel-agency'); ?></h1>
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($paged); ?>" />
                <p>
                    <input type="text" style="margin:05px;width:45%;" class="form-control-lg" name="search" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_attr_e('Search by name, email, or phone', 'wp-travel-agency'); ?>" />
                    <select name="status" style="margin:05px;width:45%;" class="form-control-lg">
                        <option value=""><?php esc_html_e('All Statuses', 'wp-travel-agency'); ?></option>
                        <?php
                        $status_options = $wpdb->get_col("SELECT DISTINCT status FROM $table_name ORDER BY status");
                        foreach ($status_options as $status) : ?>
                            <option value="<?php echo esc_attr($status); ?>" <?php selected($status_filter, $status); ?>>
                                <?php echo esc_html($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="agent" style="margin:05px;width:45%;" class="form-control-lg">
                        <option value=""><?php esc_html_e('All Agents', 'wp-travel-agency'); ?></option>
                        <?php
                        $agents = get_users(array(
                            'role'    => 'agent',
                            'fields'  => array('ID', 'display_name'),
                        ));
                        foreach ($agents as $agent) : ?>
                            <option value="<?php echo esc_attr($agent->ID); ?>" <?php selected($agent_filter, $agent->ID); ?>>
                                <?php echo esc_html($agent->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="quote_type" style="margin:05px;width:45%;" class="form-control-lg">
                        <option value=""><?php esc_html_e('All Quote Types', 'wp-travel-agency'); ?></option>
                        <?php
                        $quote_type_options = [
                            'Flight Only',
                            'Vacation Packages',
                            'Cruise Package',
                            'Accommodations Only',
                            'Flight & Hotel City Packages',
                            'Europe Packages',
                            'Adventure Group Travel',
                            'Car Rental Only',
                            'Travel Insurance Only'
                        ];
                        foreach ($quote_type_options as $quote_type) : ?>
                            <option value="<?php echo esc_attr($quote_type); ?>" <?php selected($quote_type_filter, $quote_type); ?>>
                                <?php echo esc_html($quote_type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input class="form-control-lg" type="submit" style="background-color:#026EB9;color:#fff;font-size:18px;border:solid 2px #026EB9;" value="<?php esc_attr_e('Filter', 'wp-travel-agency'); ?>" class="button-primary" />
                </p>
            </form>
            <div class="table-responsive">
                <div class="table-scroll">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr style="background-color:#026EB9">
                                <th style="width: 50px;"><?php esc_html_e('#', 'wp-travel-agency'); ?></th>
                                <th style="width: 50px;"><?php esc_html_e('Lead ID', 'wp-travel-agency'); ?></th>
                                <th style="width: 120px;"><?php esc_html_e('Full Name', 'wp-travel-agency'); ?></th>
                                <th style="width: 200px;"><?php esc_html_e('Email', 'wp-travel-agency'); ?></th>
                                <th style="width: 80px;"><?php esc_html_e('Phone Number', 'wp-travel-agency'); ?></th>
                                <th style="width: 150px;"><?php esc_html_e('Agent Preference', 'wp-travel-agency'); ?></th>
                                <th style="width: 110px;"><?php esc_html_e('Quote Type', 'wp-travel-agency'); ?></th>
                                <th style="width: 230px;"><?php esc_html_e('Quote Comment', 'wp-travel-agency'); ?></th>
                                <th style="width: 80px;"><?php esc_html_e('Status', 'wp-travel-agency'); ?></th>
                                
                                <th style="width: 230px;"><?php esc_html_e('Details', 'wp-travel-agency'); ?></th>
                                <th style="width: 100px;"><?php esc_html_e('Actions', 'wp-travel-agency'); ?></th>
                                <th style="width: 200px;"><?php esc_html_e('View Comments', 'wp-travel-agency'); ?></th>
                                <th style="width: 100px;"><?php esc_html_e('Delete', 'wp-travel-agency'); ?></th>
                                <th style="width: 100px;"><?php esc_html_e('Created At', 'wp-travel-agency'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($leads)) : ?>
                                <?php foreach ($leads as $lead) : ?>
                                    <tr>
                                    <td><?php echo $count; ?></td>
                                    <td><?php echo esc_html($lead->id); ?></td>
                                        <td><?php echo esc_html($lead->full_name); ?></td>
                                        <td><?php echo esc_html($lead->email); ?></td>
                                        <td><?php echo esc_html($lead->phone_number); ?></td>
                                        <td><?php echo esc_html($lead->agent_preference); ?></td>
                                        <td><?php echo esc_html($lead->quote_type); ?></td>
                                        <td><?php echo esc_html($lead->quote_comment); ?></td>
                                        <td><?php echo esc_html($lead->status); ?></td>
                                        <td>
                                            <?php
                                            $details = '';
                                            switch ($lead->quote_type) {
                                                case 'Flight Only':
                                                    $details = unserialize($lead->flight_only_fields);
                                                    break;
                                                case 'Vacation Packages':
                                                    $details = unserialize($lead->vacation_packages_fields);
                                                    break;
                                                case 'Cruise Package':
                                                    $details = unserialize($lead->cruise_package_fields);
                                                    
                                                    break;
                                                case 'Accommodations Only':
                                                    $details = unserialize($lead->accommodations_only_fields);
                                                    break;
                                                case 'Flight & Hotel City Packages':
                                                    $details = unserialize($lead->flight_hotel_city_packages_fields);
                                                    break;
                                                case 'Europe Packages':
                                                    $details = unserialize($lead->europe_packages_fields);
                                                    break;
                                                case 'Adventure Group Travel':
                                                    $details = unserialize($lead->adventure_group_travel_fields);
                                                    break;
                                                case 'Car Rental Only':
                                                    $details = unserialize($lead->car_rental_only_fields);
                                                    break;
                                                case 'Travel Insurance Only':
                                                    $details = unserialize($lead->travel_insurance_only_fields);
                                                    break;
                                                default:
                                                    $details = [];
                                                    break;
                                            }
                                             echo '<p style="display:none;" id="details_'.$lead->id. '">';
                                            if (!empty($details)) {
                                                foreach ($details as $key => $value) {
                                                    // Check if the value is an array
                                                    if (is_array($value)) {
                                                        // Convert array to string
                                                        $value = implode(', ', $value);
                                                    }
                                                    echo '<strong>' . esc_html(ucwords(str_replace('_', ' ', $key))) . ':</strong> ' . esc_html($value) . '<br>';
                                                }
                                            } else {
                                                esc_html_e('No details available', 'wp-travel-agency');
                                            }
                                            echo '</p><a href="#" class="show_detail" data-id = "details_' . $lead->id. '"> View Details </a>';
                                            ?>
                                        </td>
                                        <td>
                                            <button style="background-color:#026EB9;color:#fff;font-size:14px;border:solid 2px #026EB9;" class="update-status-button-admin button" data-lead-id="<?php echo esc_attr($lead->id); ?>" data-agent-id="<?php echo esc_attr($lead->agent_id); ?>" data-lead-phone="<?php echo esc_attr($lead->phone_number); ?>" data-lead-name="<?php echo esc_attr($lead->full_name); ?>" data-lead-email="<?php echo esc_attr($lead->email); ?>" data-current-status="<?php echo esc_attr($lead->status); ?>"><?php esc_html_e('Update Status', 'wp-travel-agency'); ?></button>
                                        </td>
                                        <td>
                                            <?php 
                                            $table_name = $wpdb->prefix . 'leads_comments'; 
        $comments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE lead_id = %d", $lead->id));

                                            ?>
                    <?php if (!empty($comments)) : ?>
                        <div id="<?php echo 'comment_'. $lead->id?>" style="display:none;">
                    <?php foreach ($comments as $comment) : ?>
                        <li>
                            <p>Comment date :<strong><?php echo esc_html($comment->comment_date); ?></strong></p>
                            <p><?php echo esc_html($comment->text); ?></p>
                            <?php if (isset($comment->created_at)): ?>
                            <p><em>Created at: <?php echo esc_html(date('F j, Y \a\t g:i A', strtotime($comment->created_at))); ?></em></p>
                        <?php endif; ?>
                        </li>
                        <?php $count +=1;?>
                    <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div id="<?php echo 'comment_'. $lead->id?>"><?php esc_html_e('No comments available for this lead.', 'wp-travel-agency'); ?></div>
                <?php endif; ?>
                                        <a href="#" class="show_comment" data-id="<?php echo 'comment_'. $lead->id?>" class="button"><?php esc_html_e('View Comments', 'wp-travel-agency'); ?></a>
                                        </td>
                                        <td>
                                            <button style="background-color:#026EB9;color:#fff;font-size:14px;border:solid 2px #026EB9;" class="delete-lead-button button delete-lead-button-admin" data-lead-id="<?php echo esc_attr($lead->id); ?>"><?php esc_html_e('Delete Lead', 'wp-travel-agency'); ?></button>
                                        </td>

                                        <td><?php echo esc_html(date('F j, Y, g:i A', strtotime($lead->created_at))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6"><?php esc_html_e('No leads found.', 'wp-travel-agency'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="update-status-popup-admin" style="display:none;">
                <div class="wp-dialog" style="height: 500px;overflow: auto;">
                    <h2><?php esc_html_e('Update Lead Status for Lead ID:', 'wp-travel-agency'); ?> 
                      <span id="lead-id-display"></span>
                    </h2>
                    <p>
                        <strong><?php esc_html_e('Lead User Details:', 'wp-travel-agency'); ?></strong><br>
                        <?php esc_html_e('Full Name:', 'wp-travel-agency'); ?> <span id="lead-full-name"></span><br>
                        <?php esc_html_e('Email:', 'wp-travel-agency'); ?> <span id="lead-email"></span><br>
                        <?php esc_html_e('Phone:', 'wp-travel-agency'); ?> <span id="lead-phone"></span>
                    </p>
                    <form id="update-status-form-admin">
                        <input type="hidden" name="lead_id" id="lead_id" value="">
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Status', 'wp-travel-agency'); ?></th>
                                    <td>
                                        <select name="status" id="status" class="form-control-lg">
                                            <option value="Pending"><?php esc_html_e('Pending', 'wp-travel-agency'); ?></option>
                                            <option value="Successful"><?php esc_html_e('Successful', 'wp-travel-agency'); ?></option>
                                            <option value="Fail"><?php esc_html_e('Fail', 'wp-travel-agency'); ?></option>
                                            <option value="In Progress"><?php esc_html_e('In Progress', 'wp-travel-agency'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Agent', 'wp-travel-agency'); ?></th>
                                    <td>
                                        <select name="agent_id" id="agent_id" class="form-control-lg">
                                            <option value=""><?php esc_html_e('Select Agent', 'wp-travel-agency'); ?></option>
                                            <?php
                                            $agents = get_users( array( 'role' => 'agent' ) );
                                            foreach ( $agents as $agent ) {
                                                echo '<option value="' . esc_attr( $agent->ID ) . '">' . esc_html( $agent->display_name ) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Comment', 'wp-travel-agency'); ?></th>
                                    <td>
                                        <textarea class="form-control form-control-lg" name="comment" id="comment" rows="10" placeholder="<?php esc_attr_e('Enter your comments here...', 'wp-travel-agency'); ?>"></textarea>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e('Comment Date', 'wp-travel-agency'); ?></th>
                                    <td>
                                        <?php $current_date = esc_attr(date('Y-m-d')); ?>
                                        <input class="form-control-lg" type="date" name="comment_date" id="comment_date" value="<?php echo $current_date; ?>" min="<?php echo $current_date; ?>" max="<?php echo $current_date; ?>">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="submit">
                            <button style="background-color:#026EB9;color:#fff;font-size:14px;border:solid 2px #026EB9;" type="submit" class="button button-primary form-control-lg"><?php esc_html_e('Update', 'wp-travel-agency'); ?></button>
                            <button style="background-color:#d33;color:#fff;font-size:14px;border:solid 2px #026EB9;" type="button" id="close-popup-admin" class="form-control-lg button"><?php esc_html_e('Close', 'wp-travel-agency'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
    
            <?php
            $page_links = paginate_links(array(
                'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
               'format' => '?paged=%#%',
                'prev_text' => __('« Previous', 'wp-travel-agency'),
                'next_text' => __('Next »', 'wp-travel-agency'),
                'total'     => $total_pages,
                'current'   => $paged,
            ));
    
            if ($page_links) {
                echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
            }
            ?>
        </div>
        <?php
    }  

    public static function ajax_update_lead_status_admin() {
        if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'update_lead_status_admin')) {
            wp_send_json_error(array('message' => 'Nonce verification failed.'));
        }
        global $wpdb;
        
        $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $comment = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';
        $comment_date = isset($_POST['comment_date']) ? sanitize_text_field($_POST['comment_date']) : '';
        $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
        
        if (!$lead_id || empty($new_status)) {
            wp_send_json_error(array('message' => 'Invalid lead ID or status.'));
        }

        $lead_row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}leads WHERE id = %d", 
            $lead_id
        ), ARRAY_A);

        $table_name = $wpdb->prefix . 'leads';
        $update_data = array('status' => $new_status);
        $where_data = array('id' => $lead_id);
        $format = array('%s');
        $where_format = array('%d');

	    $update_data = array('status' => $new_status);
	    if ($lead_row['status'] !== $new_status) {
		    $current_timestamp = current_time('timestamp'); // This will give the current time in the site's timezone (e.g., PKR)
            $adjusted_timestamp = $current_timestamp + (5 * 60 * 60);
		    $update_data['status_change_time'] = date('Y-m-d H:i:s', $adjusted_timestamp);
		    $format[] = '%s';
	    }

        if (!empty($agent_id) && $lead_row['agent_id'] != $agent_id) {
            $agent_data = get_userdata($agent_id);
            if ($agent_data) {
                $agent_name = $agent_data->display_name;
                $update_data['agent_id'] = $agent_id;
                $update_data['agent_preference'] = $agent_name;
                $format[] = '%d';  
                $format[] = '%s';  
            }
        }

        $updated = $wpdb->update(
            $table_name,
            $update_data,
            $where_data,
            $format,
            $where_format
        );

        if ($updated !== false) {
            
            if (!empty($comment)) {
                $comment_table_name = $wpdb->prefix . 'leads_comments';

                $wpdb->insert(
                    $comment_table_name,
                    array(
                        'lead_id' => $lead_id,
                        'text' => $comment,
                        'comment_date' => $comment_date,
                        'created_at' => current_time('mysql') 
                    ),
                    array('%d', '%s', '%s', '%s')
                );
            }
            if (!empty($agent_id)  && $lead_row['agent_id'] != $agent_id) {
                $to = $agent_data->user_email;
                $table_name = $wpdb->prefix . 'leads';
    
                // Fetch client details using lead_id
                $lead = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE id = %d", 
                    $lead_id
                ));
                $agent_details = '';
                                            switch ($lead->quote_type) {
                                                case 'Flight Only':
                                                    $agent_details = unserialize($lead->flight_only_fields);
                                                    break;
                                                case 'Vacation Packages':
                                                    $agent_details = unserialize($lead->vacation_packages_fields);
                                                    break;
                                                case 'Cruise Package':
                                                    $agent_details = unserialize($lead->cruise_package_fields);
                                                    
                                                    break;
                                                case 'Accommodations Only':
                                                    $agent_details = unserialize($lead->accommodations_only_fields);
                                                    break;
                                                case 'Flight & Hotel City Packages':
                                                    $agent_details = unserialize($lead->flight_hotel_city_packages_fields);
                                                    break;
                                                case 'Europe Packages':
                                                    $agent_details = unserialize($lead->europe_packages_fields);
                                                    break;
                                                case 'Adventure Group Travel':
                                                    $agent_details = unserialize($lead->adventure_group_travel_fields);
                                                    break;
                                                case 'Car Rental Only':
                                                    $agent_details = unserialize($lead->car_rental_only_fields);
                                                    break;
                                                case 'Travel Insurance Only':
                                                    $agent_details = unserialize($lead->travel_insurance_only_fields);
                                                    break;
                                                default:
                                                    $agent_details = [];
                                                    break;
                                            }
                $full_name = $lead->full_name;
                $email = $lead->email;
                $quote_type = $lead->quote_type;
                $agent_phone_number = $lead->phone_number;
                $agent_comment = $lead->quote_comment;
                $agent_subject = "New Quote Request Assigned to You";
                $agent_message = "Hello " . $agent_data->display_name . ",\n\n" .
                           "A new quote request has been assigned to you.\n" .
                           "Client Name: " . $full_name . "\n" .
                           "Client Email: " . $email . "\n" .
                           "Phone Number: " . $agent_phone_number. "\n" .
                           "Form Details:\n". 
                            "Quote Type: " . $quote_type . "\n";
                           foreach ($agent_details as $key => $value) {
                               $agent_message .= ucfirst(str_replace('_', ' ', $key)) . ": " . (is_array($value) ? implode(', ', $value) : $value) . "\n";
                           }
                           $agent_message .= "Comment: " . $agent_comment. "\n" ;
                $from_email = get_option('admin_email');
                $from_name = get_bloginfo('name');
                
                $agent_headers = array('Content-Type: text/plain; charset=UTF-8');
                $agent_headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
                wp_mail($to, $agent_subject, $agent_message, $agent_headers);
            }
            wp_send_json_success(array('message' => 'Status updated successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status.'));
        }

    }
  
    
}

new WP_Travel_Agency_Admin();
