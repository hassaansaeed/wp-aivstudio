<?php

class WP_Travel_Agency_Agent {

    public static function add_agent_role() {
        add_role(
            'agent',
            __( 'Agent', 'wp-travel-agency' ),
            array(
                'read' => true,
                'manage_leads' => true,
                'edit_posts' => false,
                'delete_posts' => false,
            )
        );
    }

    public static function remove_agent_role() {
        remove_role( 'agent' );
    }

    public static function add_agent_profile_fields() {
        
    }


    public static function add_admin_menus() {
        if ( current_user_can( 'agent' ) ) {
            add_menu_page(
                __( 'Leads', 'wp-travel-agency' ),
                __( 'Leads', 'wp-travel-agency' ),
                'read',
                'leads',
                array( __CLASS__, 'render_leads_page' ),
                'dashicons-list-view',
                6
            );
            add_submenu_page(
                'null',
                __('Lead Comments', 'wp-travel-agency'),
                __('Lead Comments', 'wp-travel-agency'),
                'read',
                'lead_comments',
                array(__CLASS__, 'render_comments_page')
            );
            

            
        }
    }
    

   


    public static function render_leads_page() {
        global $wpdb;
    
        $table_name = $wpdb->prefix . 'leads';
        
        $per_page = 10;
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($paged - 1) * $per_page;
        $current_user_id = get_current_user_id();
        
        $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $quote_type_filter = isset($_GET['quote_type']) ? sanitize_text_field($_GET['quote_type']) : '';
        $agent_filter = isset($_GET['agent_id']) ? intval($_GET['agent_id']) : 0;
        
        $sql = "SELECT * FROM $table_name WHERE agent_id = %d";
        
        if (!empty($search_query)) {
            $sql .= $wpdb->prepare(
                " AND (full_name LIKE %s OR email LIKE %s OR phone_number LIKE %s)",
                '%' . $wpdb->esc_like($search_query) . '%',
                '%' . $wpdb->esc_like($search_query) . '%',
                '%' . $wpdb->esc_like($search_query) . '%'
            );
        }
    
        if (!empty($status_filter)) {
            $sql .= $wpdb->prepare(" AND status = %s", $status_filter);
        }
    
        if (!empty($quote_type_filter)) {
            $sql .= $wpdb->prepare(" AND quote_type = %s", $quote_type_filter);
        }
    
        if (!empty($agent_filter)) {
            $sql .= $wpdb->prepare(" AND agent_id = %d", $agent_filter);
        }
        $sql .= " ORDER BY id DESC";
    
        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);
    
        $leads = $wpdb->get_results($wpdb->prepare($sql, $current_user_id));
    
        $total_leads = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE agent_id = $current_user_id");
        $total_pages = ceil($total_leads / $per_page);

        $status_counts = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM $table_name 
            WHERE agent_id = $current_user_id 
            GROUP BY status
        ");
        
        $statuses = [];
        $counts = [];

        foreach ($status_counts as $status_count) {
            $statuses[] = $status_count->status;
            $counts[] = $status_count->count;
        }


    
        
        ?>
        
        <div class="wrap">
            <h1><?php echo esc_html__('Leads Management', 'wp-travel-agency'); ?></h1>
             <h2><?php esc_html_e('Leads Status Distribution', 'wp-travel-agency'); ?></h2>
            <div class="d-flex justify-content-center align-items-center">
                <div class="col-12 col-md-6"> 
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="h5 card-title text-center">Leads Status Chart</h2> <!-- Title added -->
                            <canvas id="leads-status-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div id="no-data-message" style="display: none; color: red; text-align: center;"></div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
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
                document.addEventListener("DOMContentLoaded", function() {
                    var statuses = <?php echo json_encode($statuses); ?>;
                    var counts = <?php echo json_encode($counts); ?>;
                    var backgroundColors = statuses.map(status => getStatusColor(status));
                    // Get the canvas context
                    var ctx = document.getElementById('leads-status-chart').getContext('2d');

                    // Check if there is data for the chart
                    if (statuses.length === 0 || counts.length === 0) {
                        // Show a message if there is no data
                        var messageContainer = document.getElementById('no-data-message');
                        messageContainer.style.display = 'block'; // Show the message
                        messageContainer.textContent = '<?php esc_html_e("No data available for leads status.", "wp-travel-agency"); ?>';
                        
                        // Optionally, you can also hide the chart or set it to null
                        document.getElementById('leads-status-chart').style.display = 'none';
                    } else {
                        // Initialize the chart if data is present
                        var leadsStatusChart = new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: statuses,
                                datasets: [{
                                    label: '<?php esc_html_e('Leads Status', 'wp-travel-agency'); ?>',
                                    data: counts,
                                     backgroundColor: backgroundColors,
                                    hoverOffset: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'bottom',
                                    }
                                }
                            }
                        });
                    }
                });

            </script>
    
            <form method="get">
            <input type="hidden" name="page" value="leads">
                
                <input type="text" name="s" placeholder="<?php esc_attr_e('Search by name, email, or phone', 'wp-travel-agency'); ?>" value="<?php echo esc_attr($search_query); ?>" />
                
                <select name="status">
                    <option value=""><?php esc_html_e('All Statuses', 'wp-travel-agency'); ?></option>
                    <option value="Pending" <?php selected($status_filter, 'Pending'); ?>><?php esc_html_e('Pending', 'wp-travel-agency'); ?></option>
                    <option value="Successful" <?php selected($status_filter, 'Successful'); ?>><?php esc_html_e('Approved', 'wp-travel-agency'); ?></option>
                    <option value="Fail" <?php selected($status_filter, 'Fail'); ?>><?php esc_html_e('Rejected', 'wp-travel-agency'); ?></option>
                    <option value="In Progress" <?php selected($status_filter, 'In Progress'); ?>><?php esc_html_e('In Progress', 'wp-travel-agency'); ?></option>
                    
                </select>
                
                <select name="quote_type">
                    <option value=""><?php esc_html_e('All Quote Types', 'wp-travel-agency'); ?></option>
                    <option value="Flight Only" <?php selected($quote_type_filter, 'Flight Only'); ?>><?php esc_html_e('Flight Only', 'wp-travel-agency'); ?></option>
                    <option value="Vacation Packages" <?php selected($quote_type_filter, 'Vacation Packages'); ?>><?php esc_html_e('Vacation Packages', 'wp-travel-agency'); ?></option>
                    <option value="Cruise Package" <?php selected($quote_type_filter, 'Cruise Package'); ?>><?php esc_html_e('Cruise Package', 'wp-travel-agency'); ?></option>
                    <option value="Accommodations Only" <?php selected($quote_type_filter, 'Accommodations Only'); ?>><?php esc_html_e('Accommodations Only', 'wp-travel-agency'); ?></option>
                    <option value="Flight & Hotel City Packages" <?php selected($quote_type_filter, 'Flight & Hotel City Packages'); ?>><?php esc_html_e('Flight & Hotel City Packages', 'wp-travel-agency'); ?></option>
                    <option value="Europe Packages" <?php selected($quote_type_filter, 'Europe Packages'); ?>><?php esc_html_e('Europe Packages', 'wp-travel-agency'); ?></option>
                    <option value="Adventure Group Travel" <?php selected($quote_type_filter, 'Adventure Group Travel'); ?>><?php esc_html_e('Adventure Group Travel', 'wp-travel-agency'); ?></option>
                    <option value="Car Rental Only" <?php selected($quote_type_filter, 'Car Rental Only'); ?>><?php esc_html_e('Car Rental Only', 'wp-travel-agency'); ?></option>
                    <option value="Travel Insurance Only" <?php selected($quote_type_filter, 'Travel Insurance Only'); ?>><?php esc_html_e('Travel Insurance Only', 'wp-travel-agency'); ?></option>

                </select>
    
               
                
                <button type="submit" class="button button-primary"><?php esc_html_e('Search', 'wp-travel-agency'); ?></button>
            </form>
            
            <div class="table-responsive">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php esc_html_e('Full Name', 'wp-travel-agency'); ?></th>
                            <th style="width: 150px;"><?php esc_html_e('Email', 'wp-travel-agency'); ?></th>
                            <th style="width: 200px;"><?php esc_html_e('Phone Number', 'wp-travel-agency'); ?></th>
                            <th style="width: 120px;"><?php esc_html_e('Agent Preference', 'wp-travel-agency'); ?></th>
                            <th style="width: 150px;"><?php esc_html_e('Quote Type', 'wp-travel-agency'); ?></th>
                            <th style="width: 120px;"><?php esc_html_e('Quote Comment', 'wp-travel-agency'); ?></th>
                            <th style="width: 200px;"><?php esc_html_e('Status', 'wp-travel-agency'); ?></th>
                            <th style="width: 150px;"><?php esc_html_e('Details', 'wp-travel-agency'); ?></th>
                            <th style="width: 150px;"><?php esc_html_e('Actions', 'wp-travel-agency'); ?></th>
                            <th style="width: 100px;"><?php esc_html_e('View Comments', 'wp-travel-agency'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($leads)) : ?>
                            <?php foreach ($leads as $lead) : ?>
                                <tr>
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
                                            ?>
                                        </td>
                                    <td>
                                        <button class="update-status-button button" data-lead-id="<?php echo esc_attr($lead->id); ?>" data-lead-phone="<?php echo esc_attr($lead->phone_number); ?>" data-lead-name="<?php echo esc_attr($lead->full_name); ?>" data-lead-email="<?php echo esc_attr($lead->email); ?>" data-current-status="<?php echo esc_attr($lead->status); ?>"><?php esc_html_e('Update Status', 'wp-travel-agency'); ?></button>
                                    </td>
                                    <td>
                                      <a href="<?php echo esc_url(add_query_arg('lead_id', $lead->id, admin_url('admin.php?page=lead_comments'))); ?>" class="button"><?php esc_html_e('View Comments', 'wp-travel-agency'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7"><?php esc_html_e('No leads found.', 'wp-travel-agency'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div id="update-status-popup" class="hidden">
                <div class="wp-dialog">
                    <h2><?php esc_html_e('Update Lead Status for Lead ID:', 'wp-travel-agency'); ?>
                      <span id="lead-id-display"></span> 
                    </h2>
                    <p>
                        <strong><?php esc_html_e('Lead User Details:', 'wp-travel-agency'); ?></strong><br>
                        <?php esc_html_e('Full Name:', 'wp-travel-agency'); ?> <span id="lead-full-name"></span><br>
                        <?php esc_html_e('Email:', 'wp-travel-agency'); ?> <span id="lead-email"></span><br>
                        <?php esc_html_e('Phone:', 'wp-travel-agency'); ?> <span id="lead-phone"></span>
                    </p>
                    <form id="update-status-form">
                        <input type="hidden" name="lead_id" id="lead_id" value="">
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Status', 'wp-travel-agency'); ?></th>
                                    <td>
                                        <select name="status" id="status">
                                            <option value="Pending"><?php esc_html_e('Pending', 'wp-travel-agency'); ?></option>
                                            <option value="Successful"><?php esc_html_e('Successful', 'wp-travel-agency'); ?></option>
                                            <option value="Fail"><?php esc_html_e('Fail', 'wp-travel-agency'); ?></option>
                                            <option value="In Progress"><?php esc_html_e('In Progress', 'wp-travel-agency'); ?></option>
                                            <!-- <option value="Completed"><?php //esc_html_e('Completed', 'wp-travel-agency'); ?></option> -->
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Comment', 'wp-travel-agency'); ?></th>
                                    <td>
                                        <textarea name="comment" id="comment" rows="5" placeholder="<?php esc_attr_e('Enter your comments here...', 'wp-travel-agency'); ?>"></textarea>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e('Comment Date', 'wp-travel-agency'); ?></th>
                                    <td>
                                        <input type="date" name="comment_date" id="comment_date" value="<?php echo esc_attr(date('Y-m-d')); ?>">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php esc_html_e('Update', 'wp-travel-agency'); ?></button>
                            <button type="button" id="close-popup" class="button"><?php esc_html_e('Close', 'wp-travel-agency'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
    
            <?php
            $page_links = paginate_links(array(
                'base'      => add_query_arg('paged', '%#%'),
                'format'    => '',
                'prev_text' => __('« Previous', 'wp-travel-agency'),
                'next_text' => __('Next »', 'wp-travel-agency'),
                'total'     => $total_pages,
                'current'   => $paged,
                'type'      => 'plain',
            ));
            
            if ($page_links) {
                echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
            }
            ?>
        </div>
    
        <?php
    }

    public static function render_comments_page() {
        
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
      
        if ($lead->agent_id != $current_user_id) {
            echo '<p>' . esc_html__('You do not have permission to view comments for this lead.', 'wp-travel-agency') . '</p>';
            return;
        }
    
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
    
            
            <a href="<?php echo esc_url(admin_url('admin.php?page=leads')); ?>" class="button"><?php esc_html_e('Back to Leads', 'wp-travel-agency'); ?></a>
        </div>
        <?php
    }
    
    

    public static function enqueue_scripts_for_agent() {
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js', array('jquery'), '4.3.1', true);
        wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css', array(), '4.3.1');
        wp_enqueue_style('wp-travel-agency-css', plugin_dir_url(__FILE__) . 'css/wp-travel-agent-popup.css');
        wp_enqueue_style('sweetalert-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
        wp_enqueue_script('sweetalert-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js', array('jquery'), '11', true);
        wp_enqueue_script('wp-travel-agency-js', plugin_dir_url(__FILE__) . 'js/wp-travel-agency-agent-status.js', array('jquery', 'bootstrap-js'), null, true);
        wp_localize_script('wp-travel-agency-js', 'wpTravelAgencyagentajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'status_nonce_agent'    => wp_create_nonce( 'update_lead_status_agent' ),
        ));
       
        wp_enqueue_style( 'select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' );
        wp_enqueue_script( 'select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), null, true );
        
        
    }

    public static function ajax_update_lead_status() {
        if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'update_lead_status_agent')) {
            wp_send_json_error(array('message' => 'Nonce verification failed.'));
        }
        global $wpdb;
        
        $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $comment = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';
        $comment_date = isset($_POST['comment_date']) ? sanitize_text_field($_POST['comment_date']) : '';
    
    
        if (!$lead_id || empty($new_status)) {
            wp_send_json_error(array('message' => 'Invalid lead ID or status.'));
        }
    
        $table_name = $wpdb->prefix . 'leads';
        $updated = $wpdb->update(
            $table_name,
            array('status' => $new_status),
            array('id' => $lead_id),
            array('%s'),
            array('%d')
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
            wp_send_json_success(array('message' => 'Status updated successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status.'));
        }
    }
    
    
    
}
