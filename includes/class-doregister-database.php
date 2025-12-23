<?php
/**
 * Database Handler Class
 * 
 * Manages all database operations for the DoRegister plugin.
 * This class handles creating the custom table, inserting users, querying data,
 * and all database-related functionality.
 * 
 * Key Concepts:
 * - $wpdb: WordPress database abstraction class (handles all SQL queries)
 * - Custom table: wp_doregister_users (separate from wp_users)
 * - Prepared statements: Prevents SQL injection attacks
 * - Singleton pattern: Ensures only one instance exists
 * 
 * Database Table Structure:
 * - Stores user registration data (not using WordPress user system)
 * - Includes: name, email, password (hashed), phone, country, city, gender, DOB, interests, photo
 * - Uses WordPress password hashing (wp_hash_password / wp_check_password)
 * 
 * @package DoRegister
 * @since 1.0.0
 */
class DoRegister_Database {
    
    /**
     * Instance of this class (Singleton pattern)
     * 
     * Stores the single instance to prevent multiple database connections.
     * 
     * @since 1.0.0
     * @var null|DoRegister_Database
     */
    private static $instance = null;
    
    /**
     * Table name cache
     * 
     * Stores the full table name (with WordPress prefix) to avoid repeated concatenation.
     * WordPress uses table prefixes (e.g., 'wp_') for multi-site compatibility.
     * 
     * @since 1.0.0
     * @var null|string
     */
    private static $table_name = null;
    
    /**
     * Get instance of this class (Singleton pattern)
     * 
     * Returns the single instance. Creates it if it doesn't exist.
     * 
     * @since 1.0.0
     * @return DoRegister_Database The single instance of this class
     */
    public static function get_instance() {
        // Check if instance exists
        if (null === self::$instance) {
            // Create new instance
            self::$instance = new self();
        }
        // Return existing or new instance
        return self::$instance;
    }
    
    /**
     * Constructor
     * 
     * Private constructor prevents direct instantiation (Singleton pattern).
     * Sets up table name and ensures table exists.
     * 
     * Why check table existence in constructor:
     * - Safety net if activation hook didn't run
     * - Handles edge cases (table deleted manually, etc.)
     * - Ensures plugin works even if activation failed
     * 
     * @since 1.0.0
     */
    private function __construct() {
        // Access WordPress database object
        // $wpdb is global - WordPress's database abstraction layer
        global $wpdb;
        
        // Build table name with WordPress prefix
        // $wpdb->prefix is usually 'wp_' but can be customized
        // Example: 'wp_doregister_users' or 'myprefix_doregister_users'
        self::$table_name = $wpdb->prefix . 'doregister_users';
        
        // Safety check: Ensure table exists
        // If table doesn't exist (activation failed, manual deletion, etc.), create it
        if (!self::table_exists()) {
            self::create_table(); // Create table automatically
        }
    }
    
    /**
     * Create custom database table
     * 
     * Creates the wp_doregister_users table with all required columns.
     * Uses WordPress's dbDelta() function (recommended) or falls back to direct query.
     * 
     * Table Design Decisions:
     * - email varchar(191): Prevents "key too long" error with utf8mb4 charset
     * - UNIQUE KEY on email: Prevents duplicate registrations
     * - AUTO_INCREMENT on id: Primary key that auto-increments
     * - DEFAULT NULL: Optional fields can be empty
     * - CURRENT_TIMESTAMP: Automatic timestamp on insert/update
     * 
     * Why dbDelta():
     * - WordPress recommended method for table creation
     * - Handles table updates if structure changes
     * - More robust than direct SQL queries
     * 
     * @since 1.0.0
     * @return bool True if table created successfully, false on failure
     */
    public static function create_table() {
        // Access WordPress database object
        global $wpdb;
        
        // Build full table name with prefix
        $table_name = $wpdb->prefix . 'doregister_users';
        
        // Get database charset and collation
        // This ensures table uses same encoding as WordPress (usually utf8mb4)
        // utf8mb4 supports emojis and all Unicode characters
        $charset_collate = $wpdb->get_charset_collate();
        
        // Check if table already exists
        // If exists, check if it needs updating (e.g., email column size)
        if (self::table_exists()) {
            // Update table structure if needed (e.g., fix email column size)
            self::maybe_update_table();
            return true; // Table exists, no need to create
        }
        
        // SQL CREATE TABLE statement
        // Column explanations:
        // - id: Primary key, auto-incrementing integer (bigint for large numbers)
        // - full_name: User's full name (varchar 255 characters max)
        // - email: Email address (varchar 191 - reduced from 255 to prevent index key length error)
        // - password: Hashed password (varchar 255 - hashes are long)
        // - phone_number: Phone number (varchar 50)
        // - country: Country name (varchar 100)
        // - city: City name (optional, DEFAULT NULL)
        // - gender: Gender selection (optional, DEFAULT NULL)
        // - date_of_birth: Date field (optional, DEFAULT NULL)
        // - interests: Serialized array of interests (text field for long data)
        // - profile_photo: URL to uploaded image (varchar 255)
        // - created_at: Timestamp when record created (auto-set)
        // - updated_at: Timestamp when record updated (auto-updated)
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, -- Primary key, auto-increment
            full_name varchar(255) NOT NULL, -- Required: User's full name
            email varchar(191) NOT NULL, -- Required: Email (191 to prevent index key length error)
            password varchar(255) NOT NULL, -- Required: Hashed password
            phone_number varchar(50) NOT NULL, -- Required: Phone number
            country varchar(100) NOT NULL, -- Required: Country
            city varchar(100) DEFAULT NULL, -- Optional: City
            gender varchar(20) DEFAULT NULL, -- Optional: Gender (male/female/other)
            date_of_birth date DEFAULT NULL, -- Optional: Date of birth
            interests text DEFAULT NULL, -- Optional: Serialized array of interests
            profile_photo varchar(255) DEFAULT NULL, -- Optional: URL to profile photo
            created_at datetime DEFAULT CURRENT_TIMESTAMP, -- Auto-set on insert
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Auto-update on change
            PRIMARY KEY (id), -- Primary key index on id column
            UNIQUE KEY email (email) -- Unique index on email (prevents duplicates)
        ) $charset_collate;"; // Append charset/collation (utf8mb4_unicode_ci)
        
        // Load WordPress upgrade functions (required for dbDelta)
        // dbDelta() is WordPress's recommended way to create/update tables
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Use dbDelta to create table (WordPress recommended method)
        // dbDelta() parses SQL and creates/updates table structure
        $result = dbDelta($sql);
        
        // Verify table was actually created
        if (!self::table_exists()) {
            // Fallback: If dbDelta failed, try direct SQL query
            // $wpdb->query() executes raw SQL
            $result = $wpdb->query($sql);
            
            // Check if query failed
            if ($result === false) {
                // Log error for debugging
                // $wpdb->last_error contains MySQL error message
                error_log('DoRegister: Failed to create table. Error: ' . $wpdb->last_error);
                error_log('DoRegister: SQL: ' . $sql);
                return false; // Return false on failure
            }
        }
        
        // Final verification: Double-check table exists
        if (!self::table_exists()) {
            error_log('DoRegister: Table creation verification failed');
            return false; // Still doesn't exist - return false
        }
        
        return true; // Success - table created
    }
    
    /**
     * Check if database table exists
     * 
     * Queries the database to see if our custom table exists.
     * Uses MySQL SHOW TABLES command to check.
     * 
     * @since 1.0.0
     * @return bool True if table exists, false if not
     */
    public static function table_exists() {
        global $wpdb;
        
        // Build table name with prefix
        $table_name = $wpdb->prefix . 'doregister_users';
        
        // Query database: SHOW TABLES LIKE 'table_name'
        // $wpdb->get_var() returns a single value (the table name if found, null if not)
        // Compare result with expected table name
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    }
    
    /**
     * Update table structure if needed
     * 
     * Checks if table needs structural updates (e.g., email column size).
     * This handles cases where table was created with old structure.
     * 
     * Why this exists:
     * - Early versions may have used varchar(255) for email
     * - utf8mb4 charset causes "key too long" error with varchar(255)
     * - This method fixes existing tables automatically
     * 
     * @since 1.0.0
     * @return void
     */
    public static function maybe_update_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'doregister_users';
        
        // Get column information for email field
        // SHOW COLUMNS returns column metadata (name, type, null, default, etc.)
        // $wpdb->get_row() returns a single row as an object
        $column_info = $wpdb->get_row("SHOW COLUMNS FROM $table_name WHERE Field = 'email'");
        
        // Check if column info was retrieved and has Type property
        if ($column_info && isset($column_info->Type)) {
            // Extract size from column type using regex
            // Example: "varchar(255)" -> matches 255
            // preg_match() returns matches in $matches array
            preg_match('/varchar\((\d+)\)/', $column_info->Type, $matches);
            
            // Get current size (second element in matches array is the captured number)
            // intval() converts string to integer
            $current_size = isset($matches[1]) ? intval($matches[1]) : 0;
            
            // If email column is larger than 191, update it
            // 191 is the maximum safe size for indexed varchar with utf8mb4
            if ($current_size > 191) {
                // ALTER TABLE modifies existing table structure
                // MODIFY changes column definition
                $wpdb->query("ALTER TABLE $table_name MODIFY email varchar(191) NOT NULL");
            }
        }
    }
    
    /**
     * Get table name with WordPress prefix
     * 
     * Returns the full table name (with prefix) for use in queries.
     * Uses cached value if available, otherwise builds it.
     * 
     * Why cache the table name:
     * - Avoids repeated string concatenation
     * - Ensures consistency across all methods
     * - Better performance (minor optimization)
     * 
     * @since 1.0.0
     * @return string Full table name (e.g., 'wp_doregister_users')
     */
    public static function get_table_name() {
        // Check if table name is cached
        if (null === self::$table_name) {
            // Not cached - build it
            global $wpdb;
            self::$table_name = $wpdb->prefix . 'doregister_users';
        }
        // Return cached or newly built table name
        return self::$table_name;
    }
    
    /**
     * Insert new user into database
     * 
     * Creates a new user record in the custom table.
     * Handles password hashing, data serialization, and NULL value conversion.
     * 
     * Process Flow:
     * 1. Ensure table exists
     * 2. Set default values for missing fields
     * 3. Hash password (one-way encryption)
     * 4. Serialize array data (interests)
     * 5. Convert empty strings to NULL for optional fields
     * 6. Build format array for prepared statement
     * 7. Insert into database
     * 8. Return user ID or false on failure
     * 
     * @since 1.0.0
     * @param array $data User data array (full_name, email, password, etc.)
     * @return int|false User ID on success, false on failure
     */
    public static function insert_user($data) {
        global $wpdb;
        
        // Safety check: Ensure table exists before inserting
        // If table was deleted or activation failed, create it now
        if (!self::table_exists()) {
            $created = self::create_table();
            if (!$created) {
                // Log error if table creation failed
                error_log('DoRegister: Could not create table before insert');
                return false; // Can't insert without table
            }
        }
        
        // Get table name (with prefix)
        $table_name = self::get_table_name();
        
        // Define default values for all fields
        // wp_parse_args() will merge $data with these defaults
        // Missing fields will get default empty string
        $defaults = array(
            'full_name' => '', // Default empty
            'email' => '', // Default empty
            'password' => '', // Default empty
            'phone_number' => '', // Default empty
            'country' => '', // Default empty
            'city' => '', // Default empty (optional field)
            'gender' => '', // Default empty (optional field)
            'date_of_birth' => '', // Default empty (optional field)
            'interests' => '', // Default empty (optional field)
            'profile_photo' => '' // Default empty (optional field)
        );
        
        // Merge provided data with defaults
        // wp_parse_args() fills in missing keys with default values
        // Example: If $data has no 'city', it gets '' from defaults
        $data = wp_parse_args($data, $defaults);
        
        // PASSWORD HASHING: Convert plain text password to secure hash
        // wp_hash_password() uses bcrypt or argon2 (secure one-way hashing)
        // Hashed passwords cannot be reversed (only verified)
        // Only hash if password is provided (safety check)
        if (!empty($data['password'])) {
            $data['password'] = wp_hash_password($data['password']);
        }
        
        // SERIALIZATION: Convert array to string for database storage
        // Interests come as array from form (checkboxes: ['technology', 'sports'])
        // Database TEXT field can't store arrays directly, so we serialize
        // serialize() converts array to string: "a:2:{i:0;s:10:"technology";i:1;s:6:"sports";}"
        // Later we'll unserialize when reading from database
        if (is_array($data['interests'])) {
            $data['interests'] = serialize($data['interests']);
        }
        
        // Prepare data array for database insertion
        // Convert empty strings to NULL for optional fields
        // NULL is better than empty string for optional fields (cleaner database)
        $insert_data = array(
            'full_name' => $data['full_name'], // Required field
            'email' => $data['email'], // Required field
            'password' => $data['password'], // Required field (now hashed)
            'phone_number' => $data['phone_number'], // Required field
            'country' => $data['country'], // Required field
            'city' => !empty($data['city']) ? $data['city'] : null, // Optional: NULL if empty
            'gender' => !empty($data['gender']) ? $data['gender'] : null, // Optional: NULL if empty
            'date_of_birth' => !empty($data['date_of_birth']) ? $data['date_of_birth'] : null, // Optional: NULL if empty
            'interests' => !empty($data['interests']) ? $data['interests'] : null, // Optional: NULL if empty (serialized string)
            'profile_photo' => !empty($data['profile_photo']) ? $data['profile_photo'] : null // Optional: NULL if empty
        );
        
        // Build format array for prepared statement
        // $wpdb->insert() requires format specifiers for each value
        // '%s' = string, '%d' = integer, null = NULL value
        // This prevents SQL injection by properly escaping values
        $format = array();
        foreach ($insert_data as $value) {
            // If value is NULL, format is null (no escaping needed)
            // Otherwise, use '%s' (string format - WordPress will escape it)
            $format[] = ($value === null) ? null : '%s';
        }
        
        // Insert user into database
        // $wpdb->insert() is WordPress's safe way to insert data
        // Parameters:
        // 1. Table name
        // 2. Data array (column => value)
        // 3. Format array (tells WordPress how to escape each value)
        // Returns: Number of rows affected (1 on success, false on failure)
        $result = $wpdb->insert(
            $table_name, // Table to insert into
            $insert_data, // Data to insert
            $format // Format specifiers for escaping
        );
        
        // Check if insert failed
        if ($result === false) {
            // Log error for debugging
            // $wpdb->last_error contains MySQL error message
            // $wpdb->last_query contains the SQL query that failed
            error_log('DoRegister Insert Error: ' . $wpdb->last_error);
            error_log('DoRegister Insert Query: ' . $wpdb->last_query);
            return false; // Return false on failure
        }
        
        // Success - return the ID of the newly inserted user
        // $wpdb->insert_id contains the AUTO_INCREMENT value (user ID)
        return $wpdb->insert_id;
    }
    
    /**
     * Get user by email address
     * 
     * Retrieves a single user record from database by email.
     * Used for login authentication (find user by email, then verify password).
     * 
     * Security: Uses prepared statement to prevent SQL injection.
     * 
     * @since 1.0.0
     * @param string $email User's email address
     * @return object|null User object if found, null if not found
     */
    public static function get_user_by_email($email) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        // Query database using prepared statement
        // $wpdb->prepare() escapes the email value to prevent SQL injection
        // %s = string placeholder (email will be escaped and inserted)
        // $wpdb->get_row() returns a single row as an object
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE email = %s", // SQL query with placeholder
                $email // Value to insert (will be escaped)
            )
        );
    }
    
    /**
     * Get user by ID
     * 
     * Retrieves a single user record from database by user ID.
     * Used for profile page (get logged-in user's data).
     * 
     * Also unserializes interests array if it exists.
     * 
     * @since 1.0.0
     * @param int $id User ID (primary key)
     * @return object|null User object if found, null if not found
     */
    public static function get_user_by_id($id) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        // Query database using prepared statement
        // %d = integer placeholder (ID will be cast to integer)
        $user = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d", // SQL query with integer placeholder
                $id // User ID (will be cast to integer)
            )
        );
        
        // Unserialize interests if they exist
        // Interests are stored as serialized string in database
        // maybe_unserialize() safely unserializes (handles both serialized and non-serialized data)
        if ($user && $user->interests) {
            $user->interests = maybe_unserialize($user->interests);
        }
        
        return $user;
    }
    
    /**
     * Verify password against hash
     * 
     * Compares a plain text password with a stored hash.
     * Uses WordPress's secure password verification (handles bcrypt, argon2, etc.).
     * 
     * How it works:
     * - Passwords are hashed with wp_hash_password() (one-way encryption)
     * - wp_check_password() compares plain text with hash
     * - Returns true if password matches, false if not
     * - Automatically handles different hash algorithms
     * 
     * @since 1.0.0
     * @param string $password Plain text password (from user input)
     * @param string $hash Stored password hash (from database)
     * @return bool True if password matches, false if not
     */
    public static function verify_password($password, $hash) {
        // WordPress function that securely verifies password
        // Handles multiple hash algorithms (bcrypt, argon2, old MD5, etc.)
        // Returns true if password matches hash, false otherwise
        return wp_check_password($password, $hash);
    }
    
    /**
     * Get all users with pagination
     * 
     * Retrieves multiple user records from database.
     * Used for admin dashboard to display list of registrations.
     * 
     * Pagination:
     * - LIMIT: Maximum number of records to return
     * - OFFSET: Number of records to skip (for pagination)
     * - Example: LIMIT 10 OFFSET 20 = records 21-30
     * 
     * @since 1.0.0
     * @param int $limit Maximum number of users to return (default: 50)
     * @param int $offset Number of users to skip (default: 0, for pagination)
     * @return array Array of user objects
     */
    public static function get_all_users($limit = 50, $offset = 0) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        // Query database with pagination
        // ORDER BY created_at DESC: Newest users first
        // LIMIT: Maximum number of records
        // OFFSET: Skip this many records (for pagination)
        // %d = integer placeholder (both limit and offset are integers)
        $users = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $limit, // Maximum records to return
                $offset // Number of records to skip
            )
        );
        
        // Unserialize interests for each user
        // Loop through all users and unserialize their interests
        // This converts serialized string back to array
        foreach ($users as $user) {
            if ($user->interests) {
                $user->interests = maybe_unserialize($user->interests);
            }
        }
        
        return $users; // Return array of user objects
    }
    
    /**
     * Get total number of registered users
     * 
     * Counts all records in the table.
     * Used for pagination (calculate total pages).
     * 
     * @since 1.0.0
     * @return int Total number of users in database
     */
    public static function get_total_users() {
        global $wpdb;
        $table_name = self::get_table_name();
        
        // COUNT(*) counts all rows in the table
        // $wpdb->get_var() returns a single value (the count)
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    /**
     * Delete multiple users by their IDs
     * 
     * Bulk delete operation for admin dashboard.
     * Deletes multiple user records in a single query.
     * 
     * Security:
     * - Sanitizes all IDs to integers (prevents SQL injection)
     * - Uses prepared statement with IN clause
     * 
     * @since 1.0.0
     * @param array $ids Array of user IDs to delete
     * @return int|false Number of rows deleted, or false on failure
     */
    public static function delete_users($ids) {
        global $wpdb;
        $table_name = self::get_table_name();

        // Validate input: Must be non-empty array
        if (empty($ids) || !is_array($ids)) {
            return 0; // No IDs provided - return 0 (nothing deleted)
        }

        // SANITIZATION: Convert all IDs to integers
        // array_map('intval', $ids): Converts each ID to integer (removes non-numeric)
        // array_filter(): Removes falsy values (0, false, null, empty strings)
        // This ensures only valid integer IDs remain
        $ids = array_filter(array_map('intval', $ids));
        
        // Check if any valid IDs remain after sanitization
        if (empty($ids)) {
            return 0; // No valid IDs - return 0
        }

        // Build placeholders for prepared statement
        // IN clause needs multiple placeholders: WHERE id IN (%d, %d, %d)
        // array_fill(0, count($ids), '%d'): Creates array of '%d' placeholders
        // implode(','): Joins them with commas: '%d,%d,%d'
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        
        // Prepare SQL query with IN clause
        // $wpdb->prepare() will replace %d placeholders with sanitized IDs
        $query = $wpdb->prepare("DELETE FROM $table_name WHERE id IN ($placeholders)", $ids);

        // Execute delete query
        // $wpdb->query() returns number of rows affected
        $result = $wpdb->query($query);
        return $result; // Return number of deleted rows
    }
    
    /**
     * Check if email address already exists in database
     * 
     * Used for email uniqueness validation during registration.
     * Prevents duplicate accounts with same email.
     * 
     * @since 1.0.0
     * @param string $email Email address to check
     * @return bool True if email exists, false if not
     */
    public static function email_exists($email) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        // Count records with matching email
        // COUNT(*) returns number of matching rows
        // %s = string placeholder (email will be escaped)
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE email = %s",
                $email
            )
        );
        
        // Return true if count is greater than 0 (email exists)
        // Return false if count is 0 (email doesn't exist)
        return $count > 0;
    }
}

