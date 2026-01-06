<?php
/**
 * Login Handler Class
 * 
 * Manages the frontend login form and shortcode.
 * This class creates a custom login interface (not using wp-login.php).
 * 
 * WordPress Shortcodes:
 * - Shortcodes allow embedding content via [shortcode_name] in posts/pages
 * - add_shortcode() registers a shortcode handler
 * - Handler function returns HTML that replaces [shortcode_name]
 * 
 * Security:
 * - wp_nonce_field(): Generates security token to prevent CSRF attacks
 * - Nonce is verified on server-side during form submission
 * 
 * @package DoRegister
 * @since 1.0.0
 */
class DoRegister_Login {
    
    /**
     * Instance of this class (Singleton pattern)
     * 
     * Prevents multiple instances and ensures shortcode is registered once.
     * 
     * @since 1.0.0
     * @var null|DoRegister_Login
     */
    private static $instance = null;
    
    /**
     * Get instance of this class (Singleton pattern)
     * 
     * Returns the single instance. Creates it if it doesn't exist.
     * 
     * @since 1.0.0
     * @return DoRegister_Login The single instance of this class
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
     * Registers the login form shortcode.
     * 
     * Shortcode Registration:
     * - [doregister_login] can be used in any post/page
     * - When WordPress encounters [doregister_login], it calls render_login_form()
     * - The returned HTML replaces the shortcode in the content
     * 
     * @since 1.0.0
     */
    private function __construct() {
        // Register shortcode: [doregister_login]
        // Parameters:
        // 1. Shortcode name: 'doregister_login'
        // 2. Callback function: array($this, 'render_login_form')
        //    - $this = current object instance
        //    - 'render_login_form' = method name to call
        add_shortcode('doregister_login', array($this, 'render_login_form'));
    }
    
    /**
     * Check if user is currently logged in
     * 
     * Checks both session and persistent cookies (Remember Me) to determine
     * if user is authenticated.
     * 
     * @since 1.0.0
     * @return int|null User ID if logged in, null otherwise
     */
    private function is_user_logged_in() {
        // Ensure session is started
        if (!session_id()) {
            session_start();
        }
        
        $user_id = null;
        
        // Check session (standard login)
        if (isset($_SESSION['doregister_user_id'])) {
            $user_id = intval($_SESSION['doregister_user_id']);
        }
        // Check persistent cookies (Remember Me login)
        elseif (isset($_COOKIE['doregister_user_id']) && isset($_COOKIE['doregister_user_token'])) {
            $cookie_user_id = intval($_COOKIE['doregister_user_id']);
            $cookie_token = sanitize_text_field($_COOKIE['doregister_user_token']);
            
            // Verify token is valid
            if (class_exists('DoRegister_Ajax') && DoRegister_Ajax::verify_auth_token($cookie_user_id, $cookie_token)) {
                // Token is valid - restore session from cookie
                $user_id = $cookie_user_id;
                $_SESSION['doregister_user_id'] = $user_id;
                
                // Get user email for session
                if (class_exists('DoRegister_Database')) {
                    $user = DoRegister_Database::get_user_by_id($user_id);
                    if ($user) {
                        $_SESSION['doregister_user_email'] = $user->email;
                    }
                }
            } else {
                // Invalid token - clear cookies
                setcookie('doregister_user_id', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
                setcookie('doregister_user_token', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            }
        }
        
        return $user_id;
    }
    
    /**
     * Render login form HTML
     * 
     * Generates the HTML markup for the login form.
     * Uses output buffering to capture HTML and return it as string.
     * 
     * Output Buffering (ob_start/ob_get_clean):
     * - ob_start(): Starts capturing output (instead of sending to browser)
     * - ob_get_clean(): Returns captured output and clears buffer
     * - Allows us to build HTML in PHP and return it as string
     * 
     * Form Structure:
     * - Email/Username field (text input)
     * - Password field (password input - hidden characters)
     * - Submit button
     * - Error message containers (populated by JavaScript)
     * - Navigation link to registration page
     * 
     * @since 1.0.0
     * @return string HTML markup for login form
     */
    public function render_login_form() {
        // Check if user is already logged in
        $user_id = $this->is_user_logged_in();
        
        if ($user_id) {
            // User is already logged in - show message instead of form
            $profile_url = home_url('/profile');
            return '<div class="doregister-message doregister-info">You are already logged in. <a href="' . esc_url($profile_url) . '" class="doregister-link-to-profile">Go to your profile</a> or <a href="#" class="doregister-btn-logout">logout</a>.</div>';
        }
        
        // Start output buffering - capture all HTML output
        ob_start();
        ?>
        <!-- Login Form Wrapper -->
        <div class="doregister-login-wrapper">
            <!-- Login Form -->
            <!-- id="doregister-login-form": JavaScript uses this to handle form submission -->
            <form id="doregister-login-form" class="doregister-form">
                <!-- Security Nonce Field -->
                <!-- wp_nonce_field(): Generates hidden input with security token -->
                <!-- Parameters:
                     1. Action name: 'doregister_login' (identifies this form)
                     2. Field name: 'doregister_login_nonce' (name of hidden input)
                -->
                <!-- This prevents CSRF (Cross-Site Request Forgery) attacks -->
                <?php wp_nonce_field('doregister_login', 'doregister_login_nonce'); ?>
                
                <!-- Form Title -->
                <h2>Login</h2>
                
                <!-- Email/Username Field -->
                <div class="doregister-field-group">
                    <!-- Label for accessibility and UX -->
                    <label for="login_email">Email / Username <span class="required">*</span></label>
                    <!-- Text input for email/username -->
                    <!-- type="text": Allows both email and username input -->
                    <!-- id="login_email": Unique identifier (used by JavaScript and label) -->
                    <!-- name="login_email": Field name sent to server on submit -->
                    <!-- class="doregister-input": CSS class for styling -->
                    <!-- required: HTML5 validation (browser checks if empty) -->
                    <input type="text" id="login_email" name="login_email" class="doregister-input" required>
                    <!-- Error message container (populated by JavaScript validation) -->
                    <span class="doregister-error-message"></span>
                </div>
                
                <!-- Password Field -->
                <div class="doregister-field-group">
                    <!-- Label for accessibility -->
                    <label for="login_password">Password <span class="required">*</span></label>
                    <!-- Password input (characters are hidden) -->
                    <!-- type="password": Hides input characters (shows dots/asterisks) -->
                    <!-- id="login_password": Unique identifier -->
                    <!-- name="login_password": Field name sent to server -->
                    <!-- required: HTML5 validation -->
                    <input type="password" id="login_password" name="login_password" class="doregister-input" required>
                    <!-- Error message container -->
                    <span class="doregister-error-message"></span>
                </div>
                
                <!-- Remember Me Checkbox -->
                <div class="doregister-field-group">
                    <!-- Remember Me checkbox for persistent login -->
                    <!-- Allows user to stay logged in across browser sessions -->
                    <label class="doregister-remember-me-label">
                        <input type="checkbox" id="remember_me" name="remember_me" class="doregister-checkbox">
                        <span>Remember me</span>
                    </label>
                </div>
                
                <!-- Submit Button -->
                <div class="doregister-field-group">
                    <!-- Submit button triggers form submission -->
                    <!-- type="submit": Submits the form when clicked -->
                    <!-- class="doregister-btn doregister-btn-submit": CSS classes for styling -->
                    <button type="submit" class="doregister-btn doregister-btn-submit">Login</button>
                </div>
                
                <!-- Form Messages Container -->
                <!-- JavaScript displays success/error messages here -->
                <!-- Examples: "Login successful", "Invalid credentials" -->
                <div class="doregister-form-messages"></div>
            </form>
            
            <!-- Form Footer: Navigation Link -->
            <div class="doregister-form-footer">
                <!-- Link to registration page -->
                <!-- href="#": JavaScript will handle navigation (prevents page reload) -->
                <!-- class="doregister-link-to-register": JavaScript uses this class to handle click -->
                <p>Don't have an account? <a href="#" class="doregister-link-to-register">Register here</a></p>
            </div>
        </div>
        <?php
        // Return captured HTML output
        // ob_get_clean() returns the buffered content and clears the buffer
        return ob_get_clean();
    }
}

