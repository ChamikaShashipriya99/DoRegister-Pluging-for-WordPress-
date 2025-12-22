<?php
/**
 * Database Handler Class
 */
class DoRegister_Database {
    
    private static $instance = null;
    private static $table_name = null;
    
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
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'doregister_users';
        
        // Ensure table exists (safety check)
        if (!self::table_exists()) {
            self::create_table();
        }
    }
    
    /**
     * Create custom database table
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'doregister_users';
        $charset_collate = $wpdb->get_charset_collate();
        
        // Check if table already exists
        if (self::table_exists()) {
            // Check if table needs to be updated (email column size)
            self::maybe_update_table();
            return true;
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            full_name varchar(255) NOT NULL,
            email varchar(191) NOT NULL,
            password varchar(255) NOT NULL,
            phone_number varchar(50) NOT NULL,
            country varchar(100) NOT NULL,
            city varchar(100) DEFAULT NULL,
            gender varchar(20) DEFAULT NULL,
            date_of_birth date DEFAULT NULL,
            interests text DEFAULT NULL,
            profile_photo varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";
        
        // Try using dbDelta first (WordPress recommended method)
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        // Verify table was created
        if (!self::table_exists()) {
            // If dbDelta failed, try direct query
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                // Log the error
                error_log('DoRegister: Failed to create table. Error: ' . $wpdb->last_error);
                error_log('DoRegister: SQL: ' . $sql);
                return false;
            }
        }
        
        // Final verification
        if (!self::table_exists()) {
            error_log('DoRegister: Table creation verification failed');
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if table exists
     */
    public static function table_exists() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'doregister_users';
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    }
    
    /**
     * Update table structure if needed
     */
    public static function maybe_update_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'doregister_users';
        
        // Check current email column size
        $column_info = $wpdb->get_row("SHOW COLUMNS FROM $table_name WHERE Field = 'email'");
        
        if ($column_info && isset($column_info->Type)) {
            // Extract size from type (e.g., "varchar(255)" -> 255)
            preg_match('/varchar\((\d+)\)/', $column_info->Type, $matches);
            $current_size = isset($matches[1]) ? intval($matches[1]) : 0;
            
            // If email column is larger than 191, update it
            if ($current_size > 191) {
                $wpdb->query("ALTER TABLE $table_name MODIFY email varchar(191) NOT NULL");
            }
        }
    }
    
    /**
     * Get table name
     */
    public static function get_table_name() {
        if (null === self::$table_name) {
            global $wpdb;
            self::$table_name = $wpdb->prefix . 'doregister_users';
        }
        return self::$table_name;
    }
    
    /**
     * Insert user
     */
    public static function insert_user($data) {
        global $wpdb;
        
        // Ensure table exists - create if it doesn't
        if (!self::table_exists()) {
            $created = self::create_table();
            if (!$created) {
                error_log('DoRegister: Could not create table before insert');
                return false;
            }
        }
        
        $table_name = self::get_table_name();
        
        $defaults = array(
            'full_name' => '',
            'email' => '',
            'password' => '',
            'phone_number' => '',
            'country' => '',
            'city' => '',
            'gender' => '',
            'date_of_birth' => '',
            'interests' => '',
            'profile_photo' => ''
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Hash password
        if (!empty($data['password'])) {
            $data['password'] = wp_hash_password($data['password']);
        }
        
        // Serialize interests if array
        if (is_array($data['interests'])) {
            $data['interests'] = serialize($data['interests']);
        }
        
        // Prepare data array - convert empty strings to null for optional fields
        $insert_data = array(
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone_number' => $data['phone_number'],
            'country' => $data['country'],
            'city' => !empty($data['city']) ? $data['city'] : null,
            'gender' => !empty($data['gender']) ? $data['gender'] : null,
            'date_of_birth' => !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
            'interests' => !empty($data['interests']) ? $data['interests'] : null,
            'profile_photo' => !empty($data['profile_photo']) ? $data['profile_photo'] : null
        );
        
        // Build format array - use null for NULL values
        $format = array();
        foreach ($insert_data as $value) {
            $format[] = ($value === null) ? null : '%s';
        }
        
        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            $format
        );
        
        if ($result === false) {
            // Log error for debugging
            error_log('DoRegister Insert Error: ' . $wpdb->last_error);
            error_log('DoRegister Insert Query: ' . $wpdb->last_query);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get user by email
     */
    public static function get_user_by_email($email) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE email = %s",
                $email
            )
        );
    }
    
    /**
     * Get user by ID
     */
    public static function get_user_by_id($id) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $user = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $id
            )
        );
        
        if ($user && $user->interests) {
            $user->interests = maybe_unserialize($user->interests);
        }
        
        return $user;
    }
    
    /**
     * Verify password
     */
    public static function verify_password($password, $hash) {
        return wp_check_password($password, $hash);
    }
    
    /**
     * Get all users
     */
    public static function get_all_users($limit = 50, $offset = 0) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $users = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
        
        foreach ($users as $user) {
            if ($user->interests) {
                $user->interests = maybe_unserialize($user->interests);
            }
        }
        
        return $users;
    }
    
    /**
     * Get total users count
     */
    public static function get_total_users() {
        global $wpdb;
        $table_name = self::get_table_name();
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    /**
     * Delete users by IDs
     *
     * @param array $ids
     * @return int|false Number of rows deleted or false on failure
     */
    public static function delete_users($ids) {
        global $wpdb;
        $table_name = self::get_table_name();

        if (empty($ids) || !is_array($ids)) {
            return 0;
        }

        // Sanitize IDs to integers and drop invalids
        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) {
            return 0;
        }

        // Build placeholders for prepared statement
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $query        = $wpdb->prepare("DELETE FROM $table_name WHERE id IN ($placeholders)", $ids);

        $result = $wpdb->query($query);
        return $result;
    }
    
    /**
     * Check if email exists
     */
    public static function email_exists($email) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE email = %s",
                $email
            )
        );
        
        return $count > 0;
    }
}

