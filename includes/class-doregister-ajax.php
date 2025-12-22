<?php
/**
 * AJAX Handler Class
 */
class DoRegister_Ajax {
    
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
        // Registration AJAX
        add_action('wp_ajax_doregister_register', array($this, 'handle_registration'));
        add_action('wp_ajax_nopriv_doregister_register', array($this, 'handle_registration'));
        
        // Login AJAX
        add_action('wp_ajax_doregister_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_doregister_login', array($this, 'handle_login'));
        
        // Logout AJAX
        add_action('wp_ajax_doregister_logout', array($this, 'handle_logout'));
        add_action('wp_ajax_nopriv_doregister_logout', array($this, 'handle_logout'));
        
        // Upload profile photo AJAX
        add_action('wp_ajax_doregister_upload_photo', array($this, 'handle_photo_upload'));
        add_action('wp_ajax_nopriv_doregister_upload_photo', array($this, 'handle_photo_upload'));
        
        // Check email exists AJAX
        add_action('wp_ajax_doregister_check_email', array($this, 'check_email_exists'));
        add_action('wp_ajax_nopriv_doregister_check_email', array($this, 'check_email_exists'));
    }
    
    /**
     * Handle registration
     */
    public function handle_registration() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'doregister_registration')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        // Sanitize and validate input
        $full_name = sanitize_text_field($_POST['full_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $phone_number = sanitize_text_field($_POST['phone_number'] ?? '');
        $country = sanitize_text_field($_POST['country'] ?? '');
        $city = sanitize_text_field($_POST['city'] ?? '');
        $gender = sanitize_text_field($_POST['gender'] ?? '');
        $date_of_birth = sanitize_text_field($_POST['date_of_birth'] ?? '');
        $interests = isset($_POST['interests']) ? array_map('sanitize_text_field', $_POST['interests']) : array();
        $profile_photo = sanitize_text_field($_POST['profile_photo'] ?? '');
        
        $errors = array();
        
        // Validate required fields
        if (empty($full_name)) {
            $errors['full_name'] = 'Full name is required.';
        }
        
        if (empty($email) || !is_email($email)) {
            $errors['email'] = 'Valid email is required.';
        } elseif (DoRegister_Database::email_exists($email)) {
            $errors['email'] = 'Email already exists.';
        }
        
        if (empty($password) || strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }
        
        if ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
        
        if (empty($phone_number)) {
            $errors['phone_number'] = 'Phone number is required.';
        } elseif (!preg_match('/^[0-9+\-\s()]+$/', $phone_number)) {
            $errors['phone_number'] = 'Invalid phone number format.';
        }
        
        if (empty($country)) {
            $errors['country'] = 'Country is required.';
        }
        
        if (empty($interests) || count($interests) < 1) {
            $errors['interests'] = 'Please select at least one interest.';
        }
        
        if (empty($profile_photo)) {
            $errors['profile_photo'] = 'Profile photo is required.';
        }
        
        // If there are errors, return them
        if (!empty($errors)) {
            wp_send_json_error(array('errors' => $errors, 'message' => 'Please fix the errors below.'));
        }
        
        // Prepare user data
        $user_data = array(
            'full_name' => $full_name,
            'email' => $email,
            'password' => $password,
            'phone_number' => $phone_number,
            'country' => $country,
            'city' => $city,
            'gender' => $gender,
            'date_of_birth' => $date_of_birth ? $date_of_birth : null,
            'interests' => $interests,
            'profile_photo' => $profile_photo
        );
        
        // Ensure session is started
        if (!session_id()) {
            session_start();
        }
        
        // Insert user
        $user_id = DoRegister_Database::insert_user($user_data);
        
        if ($user_id) {
            // Set session
            $_SESSION['doregister_user_id'] = $user_id;
            $_SESSION['doregister_user_email'] = $email;
            
            wp_send_json_success(array(
                'message' => 'Registration successful!',
                'redirect_url' => home_url('/profile')
            ));
        } else {
            global $wpdb;
            $error_message = 'Registration failed. Please try again.';
            
            // Get database error if available
            if (!empty($wpdb->last_error)) {
                $error_message .= ' Error: ' . $wpdb->last_error;
                // Log error for debugging (remove in production or use proper logging)
                error_log('DoRegister Insert Error: ' . $wpdb->last_error);
            }
            
            wp_send_json_error(array('message' => $error_message));
        }
    }
    
    /**
     * Handle login
     */
    public function handle_login() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'doregister_login')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        $email = sanitize_email($_POST['login_email'] ?? '');
        $password = $_POST['login_password'] ?? '';
        
        $errors = array();
        
        if (empty($email)) {
            $errors['login_email'] = 'Email is required.';
        }
        
        if (empty($password)) {
            $errors['login_password'] = 'Password is required.';
        }
        
        if (!empty($errors)) {
            wp_send_json_error(array('errors' => $errors, 'message' => 'Please fill in all fields.'));
        }
        
        // Get user by email
        $user = DoRegister_Database::get_user_by_email($email);
        
        if (!$user) {
            wp_send_json_error(array('errors' => array('login_email' => 'Invalid email or password.'), 'message' => 'Invalid credentials.'));
        }
        
        // Verify password
        if (!DoRegister_Database::verify_password($password, $user->password)) {
            wp_send_json_error(array('errors' => array('login_password' => 'Invalid email or password.'), 'message' => 'Invalid credentials.'));
        }
        
        // Set session
        $_SESSION['doregister_user_id'] = $user->id;
        $_SESSION['doregister_user_email'] = $user->email;
        
        wp_send_json_success(array(
            'message' => 'Login successful!',
            'redirect_url' => home_url('/profile')
        ));
    }
    
    /**
     * Handle logout
     */
    public function handle_logout() {
        if (session_id()) {
            unset($_SESSION['doregister_user_id']);
            unset($_SESSION['doregister_user_email']);
            session_destroy();
        }
        
        wp_send_json_success(array(
            'message' => 'Logged out successfully.',
            'redirect_url' => home_url('/login')
        ));
    }
    
    /**
     * Handle photo upload
     */
    public function handle_photo_upload() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'doregister_registration')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        if (!isset($_FILES['profile_photo'])) {
            wp_send_json_error(array('message' => 'No file uploaded.'));
        }
        
        $file = $_FILES['profile_photo'];
        
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.'));
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'File size exceeds 5MB limit.'));
        }
        
        // Use WordPress media upload
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }
        
        // Create attachment
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        wp_send_json_success(array(
            'url' => $upload['url'],
            'attachment_id' => $attach_id
        ));
    }
    
    /**
     * Check if email exists
     */
    public function check_email_exists() {
        $email = sanitize_email($_POST['email'] ?? '');
        
        if (empty($email)) {
            wp_send_json_error(array('message' => 'Email is required.'));
        }
        
        $exists = DoRegister_Database::email_exists($email);
        
        wp_send_json_success(array('exists' => $exists));
    }
}

