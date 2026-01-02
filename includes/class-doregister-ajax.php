<?php
/**
 * AJAX Handler Class
 * 
 * This class handles all AJAX (Asynchronous JavaScript and XML) requests for the DoRegister plugin.
 * AJAX allows the frontend to communicate with the server without reloading the page.
 * 
 * Key Concepts:
 * - wp_ajax_{action}: Handles AJAX requests from logged-in WordPress users
 * - wp_ajax_nopriv_{action}: Handles AJAX requests from non-logged-in users (public)
 * - Both hooks are needed because registration/login happens before users are logged in
 * 
 * @package DoRegister
 * @since 1.0.0
 */
class DoRegister_Ajax {
    
    /**
     * Instance of this class (Singleton pattern)
     * 
     * Singleton pattern ensures only one instance of this class exists.
     * This prevents multiple registrations of the same AJAX handlers.
     * 
     * @since 1.0.0
     * @var null|DoRegister_Ajax
     */
    private static $instance = null;
    
    /**
     * Get instance of this class (Singleton pattern)
     * 
     * This method implements the Singleton design pattern.
     * If an instance doesn't exist, it creates one. Otherwise, returns the existing instance.
     * This ensures AJAX handlers are only registered once, preventing duplicate requests.
     * 
     * @since 1.0.0
     * @return DoRegister_Ajax The single instance of this class
     */
    public static function get_instance() {
        // Check if instance already exists
        if (null === self::$instance) {
            // Create new instance if it doesn't exist
            self::$instance = new self();
        }
        // Return the existing or newly created instance
        return self::$instance;
    }
    
    /**
     * Constructor
     * 
     * Private constructor prevents direct instantiation (part of Singleton pattern).
     * Registers all AJAX action hooks when the class is first instantiated.
     * 
     * WordPress AJAX Hook Explanation:
     * - wp_ajax_{action}: Fires for logged-in users
     * - wp_ajax_nopriv_{action}: Fires for non-logged-in users (public)
     * - Both are needed because registration/login happens before authentication
     * 
     * @since 1.0.0
     */
    private function __construct() {
        // Registration AJAX handlers
        // These handle the multi-step registration form submission
        // wp_ajax_* = logged-in users, wp_ajax_nopriv_* = public (non-logged-in)
        // Both are needed because users register BEFORE they're logged in
        add_action('wp_ajax_doregister_register', array($this, 'handle_registration'));
        add_action('wp_ajax_nopriv_doregister_register', array($this, 'handle_registration'));
        
        // Login AJAX handlers
        // Handles user authentication via AJAX (no page reload)
        // Both hooks needed because login happens before user is authenticated
        add_action('wp_ajax_doregister_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_doregister_login', array($this, 'handle_login'));
        
        // Logout AJAX handlers
        // Destroys session and logs user out
        // Both hooks needed for consistency, though logout typically requires login first
        add_action('wp_ajax_doregister_logout', array($this, 'handle_logout'));
        add_action('wp_ajax_nopriv_doregister_logout', array($this, 'handle_logout'));
        
        // Upload profile photo AJAX handlers
        // Handles file upload during registration (Step 4)
        // Uses WordPress media library functions for secure file handling
        add_action('wp_ajax_doregister_upload_photo', array($this, 'handle_photo_upload'));
        add_action('wp_ajax_nopriv_doregister_upload_photo', array($this, 'handle_photo_upload'));
        
        // Check email exists AJAX handlers
        // Real-time email validation during registration (checks if email is already taken)
        // Called via AJAX when user leaves the email field (on blur event)
        add_action('wp_ajax_doregister_check_email', array($this, 'check_email_exists'));
        add_action('wp_ajax_nopriv_doregister_check_email', array($this, 'check_email_exists'));
    }
    
    /**
     * Handle registration AJAX request
     * 
     * This method processes the final submission of the multi-step registration form.
     * It's called when the user completes Step 5 (Review & Confirm) and clicks "Submit Registration".
     * 
     * Process Flow:
     * 1. Security check (nonce verification)
     * 2. Sanitize all input data
     * 3. Validate all fields (server-side validation - double-check of frontend validation)
     * 4. Check for errors and return them if found
     * 5. Insert user into database
     * 6. Create session for automatic login
     * 7. Return success/error response as JSON
     * 
     * @since 1.0.0
     * @return void (sends JSON response and exits)
     */
    public function handle_registration() {
        // SECURITY: Verify nonce (Number Used Once)
        // Nonces prevent CSRF (Cross-Site Request Forgery) attacks
        // The nonce was created in JavaScript and sent with the form data
        // If nonce doesn't match or is missing, reject the request
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'doregister_registration')) {
            // wp_send_json_error() sends JSON response and exits script execution
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        // SANITIZATION: Clean all input data to prevent XSS attacks
        // sanitize_text_field() removes HTML tags, special characters, and trims whitespace
        // sanitize_email() validates and sanitizes email addresses
        // The ?? operator (null coalescing) provides default empty string if POST value doesn't exist
        $full_name = sanitize_text_field($_POST['full_name'] ?? ''); // User's full name
        $email = sanitize_email($_POST['email'] ?? ''); // Email address (validated format)
        $password = $_POST['password'] ?? ''; // Password (NOT sanitized - we need raw password for hashing)
        $confirm_password = $_POST['confirm_password'] ?? ''; // Password confirmation
        $phone_number = sanitize_text_field($_POST['phone_number'] ?? ''); // Phone number
        $country = sanitize_text_field($_POST['country'] ?? ''); // Selected country
        $city = sanitize_text_field($_POST['city'] ?? ''); // City (optional field)
        $gender = sanitize_text_field($_POST['gender'] ?? ''); // Gender (optional: male/female/other)
        $date_of_birth = sanitize_text_field($_POST['date_of_birth'] ?? ''); // Date of birth (optional)
        // Interests is an array (checkboxes), so we map sanitize_text_field to each value
        $interests = isset($_POST['interests']) ? array_map('sanitize_text_field', $_POST['interests']) : array();
        $profile_photo = sanitize_text_field($_POST['profile_photo'] ?? ''); // Profile photo URL (uploaded via separate AJAX)
        
        // Initialize errors array to collect validation errors
        $errors = array();
        
        // VALIDATION: Server-side validation (double-check of frontend validation)
        // Even though frontend validates, we MUST validate server-side for security
        // Frontend validation can be bypassed, server-side cannot
        
        // Validate full name: required field
        if (empty($full_name)) {
            $errors['full_name'] = 'Full name is required.';
        }
        
        // Validate email: required, must be valid format, and must be unique
        if (empty($email) || !is_email($email)) {
            // is_email() is WordPress function that validates email format
            $errors['email'] = 'Valid email is required.';
        } elseif (DoRegister_Database::email_exists($email)) {
            // Check if email already exists in database (prevents duplicate accounts)
            $errors['email'] = 'Email already exists.';
        }
        
        // Validate password: required and minimum 8 characters
        if (empty($password) || strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }
        
        // Validate password confirmation: must match password
        if ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
        
        // Validate phone number: required and must match pattern (numbers, +, -, spaces, parentheses)
        if (empty($phone_number)) {
            $errors['phone_number'] = 'Phone number is required.';
        } elseif (!preg_match('/^[0-9+\-\s()]+$/', $phone_number)) {
            // preg_match() uses regex to validate phone format
            // Pattern allows: digits, +, -, spaces, and parentheses
            $errors['phone_number'] = 'Invalid phone number format.';
        }
        
        // Validate country: required field
        if (empty($country)) {
            $errors['country'] = 'Country is required.';
        }
        
        // Validate interests: at least one must be selected
        if (empty($interests) || count($interests) < 1) {
            $errors['interests'] = 'Please select at least one interest.';
        }
        
        // Validate profile photo: required (must be uploaded)
        if (empty($profile_photo)) {
            $errors['profile_photo'] = 'Profile photo is required.';
        }
        
        // If validation errors exist, return them to frontend
        // Frontend JavaScript will display these errors next to the relevant fields
        if (!empty($errors)) {
            // wp_send_json_error() sends JSON with 'success: false' and exits
            wp_send_json_error(array('errors' => $errors, 'message' => 'Please fix the errors below.'));
        }
        
        // Prepare user data array for database insertion
        // This array structure matches the database table columns
        $user_data = array(
            'full_name' => $full_name,
            'email' => $email,
            'password' => $password, // Will be hashed in insert_user() method
            'phone_number' => $phone_number,
            'country' => $country,
            'city' => $city, // Optional field
            'gender' => $gender, // Optional field
            'date_of_birth' => $date_of_birth ? $date_of_birth : null, // Convert empty string to null for database
            'interests' => $interests, // Array will be serialized in insert_user() method
            'profile_photo' => $profile_photo // URL of uploaded image
        );
        
        // Ensure PHP session is started for user authentication
        // Sessions store user login state (we're not using WordPress user system)
        if (!session_id()) {
            session_start();
        }
        
        // Insert user into custom database table
        // insert_user() handles password hashing and data serialization
        $user_id = DoRegister_Database::insert_user($user_data);
        
        // Check if user was successfully created
        if ($user_id) {
            // User created successfully - set session variables for automatic login
            // These session variables are checked in profile page to verify user is logged in
            $_SESSION['doregister_user_id'] = $user_id; // Store user ID in session
            $_SESSION['doregister_user_email'] = $email; // Store email in session
            
            // Send success response to frontend
            // wp_send_json_success() sends JSON with 'success: true' and exits
            wp_send_json_success(array(
                'message' => 'Registration successful!',
                'redirect_url' => home_url('/profile') // URL to redirect user after registration
            ));
        } else {
            // User creation failed - get error details
            global $wpdb; // Access WordPress database object
            
            $error_message = 'Registration failed. Please try again.';
            
            // Get database error if available (for debugging)
            // $wpdb->last_error contains the last MySQL error message
            if (!empty($wpdb->last_error)) {
                $error_message .= ' Error: ' . $wpdb->last_error;
                // Log error to WordPress debug log (if WP_DEBUG_LOG is enabled)
                // This helps developers debug issues without exposing errors to users
                error_log('DoRegister Insert Error: ' . $wpdb->last_error);
            }
            
            // Send error response to frontend
            wp_send_json_error(array('message' => $error_message));
        }
    }
    
    /**
     * Handle login AJAX request
     * 
     * Authenticates user credentials and creates a session if valid.
     * This is called when user submits the login form.
     * 
     * Process Flow:
     * 1. Verify nonce (security)
     * 2. Sanitize email input
     * 3. Validate required fields
     * 4. Look up user by email
     * 5. Verify password matches stored hash
     * 6. Create session if credentials are valid
     * 7. Return success/error response
     * 
     * Security Note: Generic error messages ("Invalid email or password") prevent
     * attackers from determining which field is incorrect (email enumeration).
     * 
     * @since 1.0.0
     * @return void (sends JSON response and exits)
     */
    public function handle_login() {
        // SECURITY: Verify nonce to prevent CSRF attacks
        // Different nonce action name than registration ('doregister_login' vs 'doregister_registration')
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'doregister_login')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        // SANITIZATION: Clean input data
        $email = sanitize_email($_POST['login_email'] ?? ''); // Email or username (sanitized as email)
        $password = $_POST['login_password'] ?? ''; // Password (raw, needed for verification)
        $remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] === 'true'; // Remember Me checkbox
        
        // Initialize errors array
        $errors = array();
        
        // VALIDATION: Check required fields
        if (empty($email)) {
            $errors['login_email'] = 'Email is required.';
        }
        
        if (empty($password)) {
            $errors['login_password'] = 'Password is required.';
        }
        
        // If validation errors, return them immediately
        if (!empty($errors)) {
            wp_send_json_error(array('errors' => $errors, 'message' => 'Please fill in all fields.'));
        }
        
        // AUTHENTICATION: Look up user in database by email
        // Returns user object if found, false if not found
        $user = DoRegister_Database::get_user_by_email($email);
        
        // Check if user exists
        if (!$user) {
            // Generic error message prevents email enumeration attacks
            // Don't reveal whether email exists or not
            wp_send_json_error(array('errors' => array('login_email' => 'Invalid email or password.'), 'message' => 'Invalid credentials.'));
        }
        
        // PASSWORD VERIFICATION: Compare submitted password with stored hash
        // verify_password() uses wp_check_password() which uses bcrypt/argon2 hashing
        // This is secure because:
        // 1. Passwords are hashed (one-way encryption) - cannot be reversed
        // 2. Uses WordPress's secure password hashing functions
        // 3. Automatically handles different hash algorithms
        if (!DoRegister_Database::verify_password($password, $user->password)) {
            // Generic error message (same as above) for security
            wp_send_json_error(array('errors' => array('login_password' => 'Invalid email or password.'), 'message' => 'Invalid credentials.'));
        }
        
        // SUCCESS: Credentials are valid - create session
        // Session variables are checked in profile page to verify user is logged in
        $_SESSION['doregister_user_id'] = $user->id; // Store user ID
        $_SESSION['doregister_user_email'] = $user->email; // Store email
        
        // PERSISTENT LOGIN: Set cookies if "Remember Me" is checked
        if ($remember_me) {
            // Generate secure token for persistent login
            // This token is stored in database and cookie for verification
            $token = wp_generate_password(32, false); // Generate 32-character random token
            
            // Store token in user meta (or we can create a separate table for tokens)
            // For simplicity, we'll store it in a cookie and verify it matches a hash
            // In production, you might want to store tokens in database with expiration
            
            // Set cookie expiration: 30 days if "Remember Me", otherwise session cookie
            $expiration = time() + (30 * DAY_IN_SECONDS); // 30 days from now
            
            // Set secure cookies using WordPress functions
            // COOKIEPATH and COOKIE_DOMAIN are WordPress constants
            setcookie('doregister_user_id', $user->id, $expiration, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            setcookie('doregister_user_token', $this->generate_auth_token($user->id, $user->email), $expiration, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            
            // Also set session expiration for longer duration
            // Note: PHP sessions typically expire when browser closes, but we can extend this
            // For "Remember Me", we rely on cookies primarily
        } else {
            // Not "Remember Me": Set session cookies (expire when browser closes)
            // Clear any existing persistent cookies
            setcookie('doregister_user_id', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            setcookie('doregister_user_token', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        }
        
        // Send success response with redirect URL
        wp_send_json_success(array(
            'message' => 'Login successful!',
            'redirect_url' => home_url('/profile') // Redirect to profile page after login
        ));
    }
    
    /**
     * Handle logout AJAX request
     * 
     * Destroys the user's session and clears persistent cookies, effectively logging them out.
     * Called when user clicks the logout button on profile page.
     * 
     * Process Flow:
     * 1. Check if session exists
     * 2. Unset session variables
     * 3. Destroy session
     * 4. Clear persistent cookies
     * 5. Return success response
     * 
     * @since 1.0.0
     * @return void (sends JSON response and exits)
     */
    public function handle_logout() {
        // Check if session is active before trying to destroy it
        // session_id() returns the session ID if session exists, false if not
        if (session_id()) {
            // Unset session variables (clear user data from session)
            unset($_SESSION['doregister_user_id']); // Remove user ID
            unset($_SESSION['doregister_user_email']); // Remove email
            
            // Destroy the entire session
            // This removes all session data and invalidates the session ID
            session_destroy();
        }
        
        // CLEAR PERSISTENT COOKIES: Remove "Remember Me" cookies
        // Set cookies with expiration in the past to delete them
        setcookie('doregister_user_id', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        setcookie('doregister_user_token', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        
        // Send success response
        // Note: No nonce check needed here - logout is safe even if called maliciously
        // Worst case: user gets logged out (which they wanted anyway)
        wp_send_json_success(array(
            'message' => 'Logged out successfully.',
            'redirect_url' => home_url('/login') // Redirect to login page after logout
        ));
    }
    
    /**
     * Generate authentication token for persistent login
     * 
     * Creates a secure token based on user ID and email.
     * This token is stored in a cookie and verified on subsequent visits.
     * 
     * Security:
     * - Uses WordPress's wp_hash() function (secure hashing)
     * - Combines user ID, email, and a secret salt
     * - Token changes if user data changes (prevents token reuse)
     * 
     * @since 1.0.0
     * @param int $user_id User ID
     * @param string $email User email
     * @return string Hashed authentication token
     */
    private function generate_auth_token($user_id, $email) {
        // Create token using user ID, email, and WordPress salt
        // wp_hash() uses WordPress's secure hashing with salt
        // AUTH_SALT and SECURE_AUTH_SALT are WordPress constants
        $token_data = $user_id . '|' . $email . '|' . AUTH_SALT;
        return wp_hash($token_data);
    }
    
    /**
     * Verify authentication token from cookie
     * 
     * Validates the token stored in cookie against user data.
     * Used for "Remember Me" persistent login functionality.
     * 
     * @since 1.0.0
     * @param int $user_id User ID from cookie
     * @param string $token Token from cookie
     * @return bool True if token is valid, false otherwise
     */
    public static function verify_auth_token($user_id, $token) {
        // Get user from database
        $user = DoRegister_Database::get_user_by_id($user_id);
        
        // If user doesn't exist, token is invalid
        if (!$user) {
            return false;
        }
        
        // Generate expected token and compare
        $expected_token = wp_hash($user_id . '|' . $user->email . '|' . AUTH_SALT);
        
        // Use hash_equals() for timing-safe comparison (prevents timing attacks)
        return hash_equals($expected_token, $token);
    }
    
    /**
     * Handle profile photo upload AJAX request
     * 
     * Processes file upload during registration Step 4 (Profile Media).
     * Uses WordPress media library functions for secure file handling.
     * 
     * Process Flow:
     * 1. Verify nonce (security)
     * 2. Check if file was uploaded
     * 3. Validate file type (images only)
     * 4. Validate file size (max 5MB)
     * 5. Use WordPress upload handler
     * 6. Create attachment in media library
     * 7. Generate image metadata (thumbnails, etc.)
     * 8. Return file URL and attachment ID
     * 
     * Security Features:
     * - File type validation (MIME type checking)
     * - File size limits
     * - WordPress sanitization functions
     * - Media library integration (WordPress handles security)
     * 
     * @since 1.0.0
     * @return void (sends JSON response and exits)
     */
    public function handle_photo_upload() {
        // SECURITY: Verify nonce to prevent unauthorized uploads
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'doregister_registration')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        // Check if file was actually uploaded
        // $_FILES superglobal contains uploaded file information
        if (!isset($_FILES['profile_photo'])) {
            wp_send_json_error(array('message' => 'No file uploaded.'));
        }
        
        // Get file information from $_FILES array
        // $_FILES['profile_photo'] contains: name, type, tmp_name, error, size
        $file = $_FILES['profile_photo'];
        
        // VALIDATION: Check file type (MIME type)
        // Only allow image files for security (prevents executable uploads)
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
        // wp_check_filetype() validates file extension and MIME type
        $file_type = wp_check_filetype($file['name']);
        
        // Check if uploaded file's MIME type is in allowed list
        // $file['type'] is the MIME type reported by browser (can be spoofed, but we check it anyway)
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.'));
        }
        
        // VALIDATION: Check file size (prevent server overload)
        // 5 * 1024 * 1024 = 5MB in bytes
        // $file['size'] is in bytes
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'File size exceeds 5MB limit.'));
        }
        
        // Load WordPress file handling functions
        // These are required for wp_handle_upload() and attachment creation
        require_once(ABSPATH . 'wp-admin/includes/file.php'); // File upload handling
        require_once(ABSPATH . 'wp-admin/includes/media.php'); // Media library functions
        require_once(ABSPATH . 'wp-admin/includes/image.php'); // Image processing (thumbnails, etc.)
        
        // Use WordPress upload handler
        // wp_handle_upload() moves file to uploads directory and validates it
        // 'test_form' => false means don't check for form submission (we're using AJAX)
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        // Check if upload failed
        // wp_handle_upload() returns array with 'error' key if something went wrong
        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }
        
        // Create attachment post in WordPress media library
        // Attachments are stored as custom post type 'attachment'
        $attachment = array(
            'post_mime_type' => $upload['type'], // MIME type (e.g., 'image/jpeg')
            'post_title' => sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME)), // Filename without extension, sanitized
            'post_content' => '', // No content for images
            'post_status' => 'inherit' // Inherit status from parent (standard for attachments)
        );
        
        // Insert attachment into database
        // wp_insert_attachment() creates the attachment post and returns attachment ID
        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        
        // Generate attachment metadata (thumbnails, image sizes, etc.)
        // wp_generate_attachment_metadata() creates different image sizes (thumbnail, medium, large)
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        
        // Update attachment with metadata
        // wp_update_attachment_metadata() saves the metadata to database
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        // Return success with file URL and attachment ID
        // Frontend JavaScript stores the URL and uses it in the registration form
        wp_send_json_success(array(
            'url' => $upload['url'], // Full URL to uploaded image (e.g., http://site.com/wp-content/uploads/2024/01/image.jpg)
            'attachment_id' => $attach_id // WordPress attachment ID (can be used to get image in different sizes)
        ));
    }
    
    /**
     * Check if email exists in database (AJAX)
     * 
     * Real-time email validation during registration.
     * Called when user leaves the email field (on blur event in JavaScript).
     * 
     * Purpose:
     * - Provides instant feedback to user if email is already taken
     * - Prevents user from completing entire form only to find email is taken
     * - Improves user experience with real-time validation
     * 
     * Security Note:
     * - This could be used for email enumeration (finding which emails are registered)
     * - For higher security, you might want to add rate limiting or require nonce
     * - Current implementation prioritizes user experience over security
     * 
     * @since 1.0.0
     * @return void (sends JSON response and exits)
     */
    public function check_email_exists() {
        // Sanitize email input
        $email = sanitize_email($_POST['email'] ?? '');
        
        // Validate email is provided
        if (empty($email)) {
            wp_send_json_error(array('message' => 'Email is required.'));
        }
        
        // Check if email exists in database
        // email_exists() returns true if email is found, false if not
        $exists = DoRegister_Database::email_exists($email);
        
        // Return result as JSON
        // Frontend JavaScript uses this to show/hide error message
        wp_send_json_success(array('exists' => $exists));
    }
}

