<?php
/**
 * Admin Handler Class
 * 
 * Handles all WordPress admin dashboard functionality for the DoRegister plugin.
 * This includes creating the admin menu, displaying user registrations,
 * bulk delete operations, pagination, and table management.
 * 
 * @package DoRegister
 * @since 1.0.0
 */
class DoRegister_Admin {
    
    /**
     * Instance of this class (Singleton pattern)
     * 
     * @since 1.0.0
     * @var null|DoRegister_Admin
     */
    private static $instance = null;
    
    /**
     * Get instance of this class (Singleton pattern)
     * 
     * Ensures only one instance of the admin class exists
     * 
     * @since 1.0.0
     * @return DoRegister_Admin Instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     * 
     * Private constructor to prevent direct instantiation (Singleton pattern).
     * Sets up WordPress admin hooks for menu, notices, and table management.
     * 
     * @since 1.0.0
     */
    private function __construct() {
        // Add admin menu item to WordPress admin sidebar
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Check and handle table creation requests
        add_action('admin_init', array($this, 'check_and_create_table'));
        
        // Display admin notices (success, error, warning messages)
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }
    
    /**
     * Check and create database table if needed
     * 
     * Handles manual table creation request from admin interface.
     * Verifies nonce for security and redirects with success/error message.
     * 
     * @since 1.0.0
     * @return void
     */
    public function check_and_create_table() {
        // Check if user requested table creation via admin interface
        if (isset($_GET['doregister_create_table']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'doregister_create_table')) {
            // Attempt to create the database table
            $result = DoRegister_Database::create_table();
            
            if ($result) {
                // Redirect with success message
                wp_redirect(admin_url('admin.php?page=doregister&table_created=1'));
                exit;
            } else {
                // Redirect with error message
                wp_redirect(admin_url('admin.php?page=doregister&table_error=1'));
                exit;
            }
        }
    }
    
    /**
     * Show admin notices
     * 
     * Displays WordPress admin notices for table creation status and warnings.
     * Only shows on the DoRegister admin page.
     * 
     * @since 1.0.0
     * @return void
     */
    public function show_admin_notices() {
        $screen = get_current_screen();
        
        // Only show notices on our admin page
        if ($screen && $screen->id === 'toplevel_page_doregister') {
            // Success notice: Table created successfully
            if (isset($_GET['table_created']) && $_GET['table_created'] == '1') {
                echo '<div class="notice notice-success is-dismissible"><p>Database table created successfully!</p></div>';
            }
            
            // Error notice: Table creation failed
            if (isset($_GET['table_error']) && $_GET['table_error'] == '1') {
                echo '<div class="notice notice-error is-dismissible"><p>Failed to create database table. Please check error logs.</p></div>';
            }
            
            // Warning notice: Table doesn't exist (with create link)
            if (!DoRegister_Database::table_exists()) {
                $create_url = wp_nonce_url(admin_url('admin.php?page=doregister&doregister_create_table=1'), 'doregister_create_table');
                echo '<div class="notice notice-warning is-dismissible"><p><strong>DoRegister:</strong> Database table does not exist. <a href="' . esc_url($create_url) . '">Click here to create it now</a>.</p></div>';
            }
        }
    }
    
    /**
     * Add admin menu to WordPress admin sidebar
     * 
     * Creates the main menu item and submenu for DoRegister in WordPress admin.
     * Uses 'manage_options' capability (admin only).
     * 
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu() {
        // Add top-level menu item
        add_menu_page(
            'DoRegister',                    // Page title
            'DoRegister',                    // Menu title
            'manage_options',                // Capability required (admin only)
            'doregister',                    // Menu slug
            array($this, 'render_admin_page'), // Callback function
            'dashicons-groups',              // Icon (WordPress dashicon)
            30                               // Position in menu (30 = after Comments)
        );
        
        // Add submenu item (same page, different label)
        add_submenu_page(
            'doregister',                    // Parent menu slug
            'All Registrations',             // Page title
            'All Registrations',             // Menu title
            'manage_options',                // Capability required
            'doregister',                    // Menu slug (same as parent)
            array($this, 'render_admin_page') // Callback function
        );
    }
    
    /**
     * Render admin page
     * 
     * Main admin page that displays all user registrations in a table format.
     * Handles bulk delete operations, pagination, and displays user data.
     * 
     * Features:
     * - Bulk delete with confirmation
     * - Pagination (10 records per page)
     * - Select all checkbox functionality
     * - Displays all registration fields
     * - Shows profile photos
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_admin_page() {
        // Handle bulk delete operation
        // Process bulk delete request if submitted
        $notice = '';
        if (isset($_POST['doregister_bulk_action']) && $_POST['doregister_bulk_action'] === 'delete') {
            // Verify nonce for security
            check_admin_referer('doregister_bulk_delete');
            
            // Check user has permission to delete
            if (current_user_can('manage_options')) {
                // Get selected user IDs from checkboxes
                $ids = isset($_POST['doregister_ids']) ? (array) $_POST['doregister_ids'] : array();
                
                // Delete users and get count of deleted records
                $deleted = DoRegister_Database::delete_users($ids);
                
                if ($deleted !== false) {
                    // Success message with count
                    $notice = sprintf('%d record(s) deleted.', intval($deleted));
                } else {
                    // Error message
                    $notice = 'Delete failed. Please try again.';
                }
            }
        }

        // Pagination setup
        // Get current page number from URL (default: page 1)
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 10; // Number of records per page
        $offset = ($page - 1) * $per_page; // Calculate offset for database query
        
        // Fetch users for current page
        $users = DoRegister_Database::get_all_users($per_page, $offset);
        
        // Get total count for pagination
        $total_users = DoRegister_Database::get_total_users();
        $total_pages = ceil($total_users / $per_page); // Calculate total number of pages
        
        ?>
        <div class="wrap doregister-admin-pagination">
            <h1>DoRegister - User Registrations</h1>
            
            <?php if (!DoRegister_Database::table_exists()): ?>
                <div class="notice notice-error">
                    <p><strong>Warning:</strong> The database table does not exist. 
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=doregister&doregister_create_table=1'), 'doregister_create_table')); ?>" class="button button-primary">Create Table Now</a></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($notice)): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($notice); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="doregister-admin-stats">
                <p><strong>Total Registrations:</strong> <?php echo esc_html($total_users); ?></p>
            </div>

            <form method="post" id="doregister-admin-form">
                <?php wp_nonce_field('doregister_bulk_delete'); ?>
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select name="doregister_bulk_action">
                            <option value="">Bulk actions</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="submit" class="button action">Apply</button>
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <input type="checkbox" id="doregister-select-all" />
                        </td>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Country</th>
                        <th>City</th>
                        <th>Gender</th>
                        <th>Date of Birth</th>
                        <th>Interests</th>
                        <th>Profile Photo</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="12">No registrations found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="doregister_ids[]" value="<?php echo esc_attr($user->id); ?>">
                                </th>
                                <td><?php echo esc_html($user->id); ?></td>
                                <td><?php echo esc_html($user->full_name); ?></td>
                                <td><?php echo esc_html($user->email); ?></td>
                                <td><?php echo esc_html($user->phone_number); ?></td>
                                <td><?php echo esc_html($user->country); ?></td>
                                <td><?php echo esc_html($user->city ? $user->city : '-'); ?></td>
                                <td><?php echo esc_html($user->gender ? ucfirst($user->gender) : '-'); ?></td>
                                <td><?php echo esc_html($user->date_of_birth ? date('Y-m-d', strtotime($user->date_of_birth)) : '-'); ?></td>
                                <td>
                                    <?php 
                                    if ($user->interests && is_array($user->interests)) {
                                        echo esc_html(implode(', ', $user->interests));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($user->profile_photo): ?>
                                        <img src="<?php echo esc_url($user->profile_photo); ?>" alt="Profile" style="max-width: 50px; height: auto;">
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(date('Y-m-d H:i', strtotime($user->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </form>

            <?php if ($total_pages > 1): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        $page_links = paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $page
                        ));
                        echo $page_links;
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <script>
        /**
         * Admin page JavaScript
         * Handles checkbox selection and bulk delete confirmation
         */
        jQuery(function($){
            /**
             * Select / deselect all checkboxes
             * When "Select All" checkbox is clicked, toggle all row checkboxes
             */
            $('#doregister-select-all').on('change', function(){
                var checked = $(this).is(':checked');
                $('input[name="doregister_ids[]"]').prop('checked', checked);
            });

            /**
             * Confirm bulk delete before submission
             * Shows confirmation dialog and validates selection
             */
            $('#doregister-admin-form').on('submit', function(e){
                var action = $('select[name="doregister_bulk_action"]').val();
                
                // Only show confirmation for delete action
                if (action === 'delete') {
                    // Count selected checkboxes
                    var selected = $('input[name="doregister_ids[]"]:checked').length;
                    
                    // Validate: at least one record must be selected
                    if (!selected) {
                        alert('Please select at least one record to delete.');
                        e.preventDefault();
                        return;
                    }
                    
                    // Show confirmation dialog
                    var ok = confirm('Are you sure you want to delete the selected record(s)? This cannot be undone.');
                    if (!ok) {
                        // Cancel form submission if user clicks "Cancel"
                        e.preventDefault();
                    }
                }
            });
        });
        </script>
        <?php
    }
}

