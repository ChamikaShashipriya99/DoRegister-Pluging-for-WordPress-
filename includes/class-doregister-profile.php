<?php
/**
 * Profile Handler Class
 * 
 * Manages the frontend user profile page and shortcode.
 * Displays user information in a categorized, read-only format.
 * 
 * Session Management:
 * - Uses PHP sessions to track logged-in users
 * - $_SESSION['doregister_user_id'] stores the current user's ID
 * - Session must be started before accessing $_SESSION
 * 
 * Profile Display:
 * - Shows user data grouped by categories (matches registration steps)
 * - Displays profile photo (rounded rectangle)
 * - Shows all registration fields in organized sections
 * 
 * @package DoRegister
 * @since 1.0.0
 */
class DoRegister_Profile {
    
    /**
     * Instance of this class (Singleton pattern)
     * 
     * Prevents multiple instances and ensures shortcode is registered once.
     * 
     * @since 1.0.0
     * @var null|DoRegister_Profile
     */
    private static $instance = null;
    
    /**
     * Get instance of this class (Singleton pattern)
     * 
     * Returns the single instance. Creates it if it doesn't exist.
     * 
     * @since 1.0.0
     * @return DoRegister_Profile The single instance of this class
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
     * Registers the profile page shortcode.
     * 
     * @since 1.0.0
     */
    private function __construct() {
        // Register shortcode: [doregister_profile]
        // When WordPress encounters [doregister_profile], it calls render_profile_page()
        add_shortcode('doregister_profile', array($this, 'render_profile_page'));
    }
    
    /**
     * Render profile page HTML
     * 
     * Generates the HTML markup for the user profile page.
     * Checks if user is logged in, retrieves user data, and displays it.
     * 
     * Authentication Check:
     * - Verifies user is logged in via session
     * - Redirects to login if not authenticated
     * 
     * Data Retrieval:
     * - Gets user ID from session
     * - Fetches user data from database
     * - Handles missing user gracefully
     * 
     * @since 1.0.0
     * @return string HTML markup for profile page, or error message
     */
    public function render_profile_page() {
        // SESSION MANAGEMENT: Ensure session is started
        // session_id() returns session ID if session exists, false if not
        // If no session exists, start one
        // Sessions store data on server, accessible via $_SESSION superglobal
        if (!session_id()) {
            session_start(); // Start PHP session
        }
        
        // AUTHENTICATION CHECK: Verify user is logged in
        // $_SESSION['doregister_user_id'] is set during login
        // If not set, user is not logged in
        if (!isset($_SESSION['doregister_user_id'])) {
            // User not logged in - show login prompt
            // home_url('/login'): Builds URL to login page (adjust '/login' to match your page slug)
            $login_url = home_url('/login');
            
            // Return error message with login link
            // esc_url(): Escapes URL for safe output (prevents XSS)
            return '<div class="doregister-message doregister-error">Please <a href="' . esc_url($login_url) . '" class="doregister-link-to-login">login</a> to view your profile.</div>';
        }
        
        // Get user ID from session
        // intval(): Converts to integer (sanitization - prevents injection)
        // $_SESSION['doregister_user_id'] was set during login
        $user_id = intval($_SESSION['doregister_user_id']);
        
        // Retrieve user data from database
        // DoRegister_Database::get_user_by_id(): Static method to fetch user
        // Returns user object or null if not found
        $user = DoRegister_Database::get_user_by_id($user_id);
        
        // Check if user was found
        if (!$user) {
            // User ID in session doesn't match any database record
            // This could happen if user was deleted
            return '<div class="doregister-message doregister-error">User not found.</div>';
        }
        
        // Start output buffering - capture HTML output
        ob_start();
        ?>
        <!-- Profile Page Wrapper -->
        <div class="doregister-profile-wrapper">
            <!-- Page Title -->
            <h2>My Profile</h2>
            
            <!-- Profile Header Section -->
            <!-- Contains profile photo, name, and email -->
            <div class="doregister-profile-header">
                <!-- Profile Photo Container -->
                <div class="doregister-profile-photo">
                    <?php if ($user->profile_photo): ?>
                        <!-- User has uploaded a profile photo -->
                        <!-- esc_url(): Escapes URL for safe output (prevents XSS) -->
                        <!-- Displays image with rounded rectangle styling (border-radius: 20px) -->
                        <img src="<?php echo esc_url($user->profile_photo); ?>" alt="Profile Photo">
                    <?php else: ?>
                        <!-- No profile photo uploaded - show placeholder icon -->
                        <!-- SVG icon: Generic user silhouette -->
                        <div class="doregister-no-photo">
                            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- User's Full Name -->
                <!-- esc_html(): Escapes HTML for safe output (prevents XSS) -->
                <div class="doregister-profile-name"><?php echo esc_html($user->full_name); ?></div>
                <!-- User's Email Address -->
                <div class="doregister-profile-email"><?php echo esc_html($user->email); ?></div>
            </div>
            
            <!-- Profile Content: Categorized User Data -->
            <!-- Categories match registration form steps for consistency -->
            <div class="doregister-profile-content">
                <!-- Category 1: Basic Information (Step 1 from registration) -->
                <div class="doregister-profile-category">
                    <h3 class="doregister-profile-category-title">Basic Information</h3>
                    <div class="doregister-profile-category-content">
                        <!-- Full Name Field -->
                        <div class="doregister-profile-field">
                            <strong>Full Name</strong>
                            <span><?php echo esc_html($user->full_name); ?></span>
                        </div>
                        <!-- Email Field -->
                        <div class="doregister-profile-field">
                            <strong>Email</strong>
                            <span><?php echo esc_html($user->email); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Category 2: Contact Details (Step 2 from registration) -->
                <div class="doregister-profile-category">
                    <h3 class="doregister-profile-category-title">Contact Details</h3>
                    <div class="doregister-profile-category-content">
                        <!-- Phone Number Field -->
                        <div class="doregister-profile-field">
                            <strong>Phone Number</strong>
                            <span><?php echo esc_html($user->phone_number); ?></span>
                        </div>
                        <!-- Country Field -->
                        <div class="doregister-profile-field">
                            <strong>Country</strong>
                            <span><?php echo esc_html($user->country); ?></span>
                        </div>
                        <!-- City Field (conditional - only show if exists) -->
                        <?php if ($user->city): ?>
                        <div class="doregister-profile-field">
                            <strong>City</strong>
                            <span><?php echo esc_html($user->city); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Category 3: Personal Details (Step 3 from registration) -->
                <div class="doregister-profile-category">
                    <h3 class="doregister-profile-category-title">Personal Details</h3>
                    <div class="doregister-profile-category-content">
                        <!-- Gender Field (conditional - only show if exists) -->
                        <?php if ($user->gender): ?>
                        <div class="doregister-profile-field">
                            <strong>Gender</strong>
                            <!-- ucfirst(): Capitalizes first letter (male -> Male) -->
                            <span><?php echo esc_html(ucfirst($user->gender)); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Date of Birth Field (conditional - only show if exists) -->
                        <?php if ($user->date_of_birth): ?>
                        <div class="doregister-profile-field">
                            <strong>Date of Birth</strong>
                            <!-- date(): Formats date string -->
                            <!-- strtotime(): Converts date string to timestamp -->
                            <!-- 'F j, Y': Format = "January 1, 2024" -->
                            <span><?php echo esc_html(date('F j, Y', strtotime($user->date_of_birth))); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Interests Field (conditional - only show if exists and is array) -->
                        <?php if ($user->interests && is_array($user->interests)): ?>
                        <div class="doregister-profile-field">
                            <strong>Interests</strong>
                            <!-- Interests Container: Displays as badges/tags -->
                            <div class="doregister-profile-interests">
                                <!-- Loop through interests array -->
                                <?php foreach ($user->interests as $interest): ?>
                                    <!-- Display each interest as a badge -->
                                    <!-- ucfirst(): Capitalizes first letter -->
                                    <span><?php echo esc_html(ucfirst($interest)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Category 4: Profile Media (Step 4 from registration) -->
                <div class="doregister-profile-category">
                    <h3 class="doregister-profile-category-title">Profile Media</h3>
                    <div class="doregister-profile-category-content">
                        <div class="doregister-profile-field doregister-profile-photo-field">
                            <strong>Profile Photo</strong>
                            <div class="doregister-profile-photo-display">
                                <?php if ($user->profile_photo): ?>
                                    <!-- Display profile photo if exists -->
                                    <img src="<?php echo esc_url($user->profile_photo); ?>" alt="Profile Photo">
                                <?php else: ?>
                                    <!-- No photo message -->
                                    <div class="doregister-no-photo-small">No photo uploaded</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Category 5: Account Information (additional info) -->
                <div class="doregister-profile-category">
                    <h3 class="doregister-profile-category-title">Account Information</h3>
                    <div class="doregister-profile-category-content">
                        <!-- Member Since: Registration date -->
                        <div class="doregister-profile-field">
                            <strong>Member Since</strong>
                            <!-- Format registration date nicely -->
                            <span><?php echo esc_html(date('F j, Y', strtotime($user->created_at))); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Actions: Logout Button -->
            <div class="doregister-profile-actions">
                <!-- Logout Button -->
                <!-- type="button": Doesn't submit form (just triggers JavaScript) -->
                <!-- class="doregister-btn-logout": JavaScript uses this to handle logout -->
                <button type="button" class="doregister-btn doregister-btn-logout">Logout</button>
            </div>
        </div>
        <?php
        // Return captured HTML output
        return ob_get_clean();
    }
}

