<?php
/**
 * Admin Handler Class
 */
class DoRegister_Admin {
    
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'check_and_create_table'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }
    
    /**
     * Check and create table if needed
     */
    public function check_and_create_table() {
        // Check if user requested table creation
        if (isset($_GET['doregister_create_table']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'doregister_create_table')) {
            $result = DoRegister_Database::create_table();
            if ($result) {
                wp_redirect(admin_url('admin.php?page=doregister&table_created=1'));
                exit;
            } else {
                wp_redirect(admin_url('admin.php?page=doregister&table_error=1'));
                exit;
            }
        }
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'toplevel_page_doregister') {
            if (isset($_GET['table_created']) && $_GET['table_created'] == '1') {
                echo '<div class="notice notice-success is-dismissible"><p>Database table created successfully!</p></div>';
            }
            if (isset($_GET['table_error']) && $_GET['table_error'] == '1') {
                echo '<div class="notice notice-error is-dismissible"><p>Failed to create database table. Please check error logs.</p></div>';
            }
            if (!DoRegister_Database::table_exists()) {
                $create_url = wp_nonce_url(admin_url('admin.php?page=doregister&doregister_create_table=1'), 'doregister_create_table');
                echo '<div class="notice notice-warning is-dismissible"><p><strong>DoRegister:</strong> Database table does not exist. <a href="' . esc_url($create_url) . '">Click here to create it now</a>.</p></div>';
            }
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'DoRegister',
            'DoRegister',
            'manage_options',
            'doregister',
            array($this, 'render_admin_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'doregister',
            'All Registrations',
            'All Registrations',
            'manage_options',
            'doregister',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Handle bulk delete
        $notice = '';
        if (isset($_POST['doregister_bulk_action']) && $_POST['doregister_bulk_action'] === 'delete') {
            check_admin_referer('doregister_bulk_delete');
            if (current_user_can('manage_options')) {
                $ids = isset($_POST['doregister_ids']) ? (array) $_POST['doregister_ids'] : array();
                $deleted = DoRegister_Database::delete_users($ids);
                if ($deleted !== false) {
                    $notice = sprintf('%d record(s) deleted.', intval($deleted));
                } else {
                    $notice = 'Delete failed. Please try again.';
                }
            }
        }

        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $users = DoRegister_Database::get_all_users($per_page, $offset);
        $total_users = DoRegister_Database::get_total_users();
        $total_pages = ceil($total_users / $per_page);
        
        ?>
        <div class="wrap">
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
        jQuery(function($){
            // Select / deselect all checkboxes
            $('#doregister-select-all').on('change', function(){
                var checked = $(this).is(':checked');
                $('input[name="doregister_ids[]"]').prop('checked', checked);
            });

            // Confirm bulk delete
            $('#doregister-admin-form').on('submit', function(e){
                var action = $('select[name="doregister_bulk_action"]').val();
                if (action === 'delete') {
                    var selected = $('input[name="doregister_ids[]"]:checked').length;
                    if (!selected) {
                        alert('Please select at least one record to delete.');
                        e.preventDefault();
                        return;
                    }
                    var ok = confirm('Are you sure you want to delete the selected record(s)? This cannot be undone.');
                    if (!ok) {
                        e.preventDefault();
                    }
                }
            });
        });
        </script>
        <?php
    }
}

