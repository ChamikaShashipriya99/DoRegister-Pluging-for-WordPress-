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
        // Check session first, then check persistent cookies for "Remember Me"
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
            if (DoRegister_Ajax::verify_auth_token($cookie_user_id, $cookie_token)) {
                // Token is valid - restore session from cookie
                $user_id = $cookie_user_id;
                $_SESSION['doregister_user_id'] = $user_id;
                
                // Get user email for session
                $user = DoRegister_Database::get_user_by_id($user_id);
                if ($user) {
                    $_SESSION['doregister_user_email'] = $user->email;
                }
            } else {
                // Invalid token - clear cookies
                setcookie('doregister_user_id', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
                setcookie('doregister_user_token', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            }
        }
        
        // If still not authenticated, show login prompt
        if (!$user_id) {
            // User not logged in - show login prompt
            // home_url('/login'): Builds URL to login page (adjust '/login' to match your page slug)
            $login_url = home_url('/login');
            
            // Return error message with login link
            // esc_url(): Escapes URL for safe output (prevents XSS)
            return '<div class="doregister-message doregister-error">Please <a href="' . esc_url($login_url) . '" class="doregister-link-to-login">login</a> to view your profile.</div>';
        }
        
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
        <div class="doregister-profile-wrapper" data-user-id="<?php echo esc_attr($user_id); ?>">
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
                
                <!-- VIEW MODE: Basic Information (Read-Only) -->
                <div class="doregister-profile-view-mode">
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
                </div>
                
                <!-- EDIT MODE: Basic Information Form (Editable) -->
                <div class="doregister-profile-edit-mode" style="display: none;">
                    <!-- Profile Edit Form -->
                    <form id="doregister-profile-edit-form" class="doregister-form">
                        <!-- Security Nonce Field -->
                        <?php wp_nonce_field('doregister_profile_update', 'doregister_profile_update_nonce'); ?>
                        
                        <!-- Hidden User ID Field -->
                        <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                        
                        <!-- Category 1: Basic Information (Editable) -->
                        <div class="doregister-profile-category">
                            <h3 class="doregister-profile-category-title">Basic Information</h3>
                            <div class="doregister-profile-category-content">
                                <!-- Full Name Field (Editable) -->
                                <div class="doregister-field-group">
                                    <label for="profile_full_name">Full Name <span class="required">*</span></label>
                                    <input type="text" id="profile_full_name" name="full_name" class="doregister-input" value="<?php echo esc_attr($user->full_name); ?>" required>
                                    <span class="doregister-error-message"></span>
                                </div>
                                
                                <!-- Email Field (Editable) -->
                                <div class="doregister-field-group">
                                    <label for="profile_email">Email <span class="required">*</span></label>
                                    <input type="email" id="profile_email" name="email" class="doregister-input" value="<?php echo esc_attr($user->email); ?>" required>
                                    <span class="doregister-error-message"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Category 2: Contact Details (Editable) -->
                        <div class="doregister-profile-category">
                            <h3 class="doregister-profile-category-title">Contact Details</h3>
                            <div class="doregister-profile-category-content">
                                <!-- Phone Number Field (Editable) -->
                                <div class="doregister-field-group">
                                    <label for="profile_phone_number">Phone Number <span class="required">*</span></label>
                                    <input type="tel" id="profile_phone_number" name="phone_number" class="doregister-input" value="<?php echo esc_attr($user->phone_number); ?>" required>
                                    <span class="doregister-error-message"></span>
                                </div>
                                
                                <!-- Country Field (Editable - Searchable Dropdown) -->
                                <div class="doregister-field-group">
                                    <label for="profile_country">Country <span class="required">*</span></label>
                                    <div class="doregister-country-wrapper">
                                        <input type="text" id="profile_country" name="country" class="doregister-input doregister-country-search" placeholder="Search country..." value="<?php echo esc_attr($user->country); ?>" required>
                                        <div class="doregister-country-dropdown"></div>
                                    </div>
                                    <span class="doregister-error-message"></span>
                                </div>
                                
                                <!-- City Field (Editable - Optional) -->
                                <div class="doregister-field-group">
                                    <label for="profile_city">City</label>
                                    <input type="text" id="profile_city" name="city" class="doregister-input" value="<?php echo esc_attr($user->city ?? ''); ?>">
                                    <span class="doregister-error-message"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Category 3: Personal Details (Editable) -->
                        <div class="doregister-profile-category">
                            <h3 class="doregister-profile-category-title">Personal Details</h3>
                            <div class="doregister-profile-category-content">
                                <!-- Gender Field (Editable - Radio Buttons) -->
                                <div class="doregister-field-group">
                                    <label>Gender</label>
                                    <div class="doregister-radio-group doregister-gender-group">
                                        <label class="doregister-radio-label doregister-gender-card">
                                            <input type="radio" name="gender" value="male" class="doregister-radio" <?php checked($user->gender, 'male'); ?>>
                                            <span><span class="doregister-gender-emoji">👨</span><span class="doregister-gender-text">Male</span></span>
                                        </label>
                                        <label class="doregister-radio-label doregister-gender-card">
                                            <input type="radio" name="gender" value="female" class="doregister-radio" <?php checked($user->gender, 'female'); ?>>
                                            <span><span class="doregister-gender-emoji">👩</span><span class="doregister-gender-text">Female</span></span>
                                        </label>
                                        <label class="doregister-radio-label doregister-gender-card">
                                            <input type="radio" name="gender" value="other" class="doregister-radio" <?php checked($user->gender, 'other'); ?>>
                                            <span><span class="doregister-gender-emoji">🌈</span><span class="doregister-gender-text">Other</span></span>
                                        </label>
                                    </div>
                                    <span class="doregister-error-message"></span>
                                </div>
                                
                                <!-- Date of Birth Field (Editable) -->
                                <div class="doregister-field-group">
                                    <label for="profile_date_of_birth">Date of Birth</label>
                                    <input type="date" id="profile_date_of_birth" name="date_of_birth" class="doregister-input" value="<?php echo esc_attr($user->date_of_birth ?? ''); ?>">
                                    <span class="doregister-error-message"></span>
                                </div>
                                
                                <!-- Interests Field (Editable - Checkboxes) -->
                                <div class="doregister-field-group">
                                    <label>Interests <span class="required">*</span></label>
                                    <div class="doregister-checkbox-group doregister-interests-group">
                                        <?php
                                        $available_interests = array('technology', 'sports', 'music', 'travel', 'reading', 'cooking');
                                        $user_interests = is_array($user->interests) ? $user->interests : array();
                                        foreach ($available_interests as $interest):
                                            // Map each interest to an emoji
                                            $emoji = '';
                                            switch ($interest) {
                                                case 'technology':
                                                    $emoji = '💻';
                                                    break;
                                                case 'sports':
                                                    $emoji = '⚽';
                                                    break;
                                                case 'music':
                                                    $emoji = '🎵';
                                                    break;
                                                case 'travel':
                                                    $emoji = '✈️';
                                                    break;
                                                case 'reading':
                                                    $emoji = '📚';
                                                    break;
                                                case 'cooking':
                                                    $emoji = '🍳';
                                                    break;
                                                default:
                                                    $emoji = '⭐';
                                            }
                                        ?>
                                        <label class="doregister-checkbox-label doregister-interest-card">
                                            <input type="checkbox" name="interests[]" value="<?php echo esc_attr($interest); ?>" class="doregister-checkbox" <?php checked(in_array($interest, $user_interests)); ?>>
                                            <span>
                                                <span class="doregister-interest-emoji"><?php echo esc_html($emoji); ?></span>
                                                <span class="doregister-interest-text"><?php echo esc_html(ucfirst($interest)); ?></span>
                                            </span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <span class="doregister-error-message"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Category 4: Profile Media (Editable) -->
                        <div class="doregister-profile-category">
                            <h3 class="doregister-profile-category-title">Profile Media</h3>
                            <div class="doregister-profile-category-content">
                                <!-- Profile Photo Upload Field -->
                                <div class="doregister-field-group">
                                    <label for="profile_photo_upload">Profile Photo <span class="required">*</span></label>
                                    <!-- Current Photo Preview -->
                                    <div class="doregister-profile-photo-preview">
                                        <?php if ($user->profile_photo): ?>
                                            <img src="<?php echo esc_url($user->profile_photo); ?>" alt="Current Profile Photo" class="doregister-current-photo">
                                        <?php else: ?>
                                            <div class="doregister-no-photo-small">No photo uploaded</div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- File Input -->
                                    <input type="file" id="profile_photo_upload" name="profile_photo_upload" accept="image/*" class="doregister-input doregister-file-input">
                                    <!-- New Photo Preview Container (populated by JavaScript) -->
                                    <div class="doregister-image-preview"></div>
                                    <!-- Hidden field to store uploaded photo URL -->
                                    <input type="hidden" id="profile_photo" name="profile_photo" value="<?php echo esc_attr($user->profile_photo ?? ''); ?>">
                                    <span class="doregister-error-message"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Category 5: Password Change (Optional) -->
                        <div class="doregister-profile-category">
                            <h3 class="doregister-profile-category-title">Change Password</h3>
                            <div class="doregister-profile-category-content">
                                <!-- Change Password Toggle -->
                                <div class="doregister-field-group">
                                    <label class="doregister-checkbox-label">
                                        <input type="checkbox" id="change_password_toggle" name="change_password" class="doregister-checkbox" value="true">
                                        <span>Change Password</span>
                                    </label>
                                </div>
                                
                                <!-- Password Fields (Hidden by default) -->
                                <div class="doregister-password-change-fields" style="display: none;">
                                    <!-- New Password Field -->
                                    <div class="doregister-field-group">
                                        <label for="profile_password">New Password <span class="required">*</span></label>
                                        <div class="doregister-password-wrapper">
                                            <input type="password" id="profile_password" name="password" class="doregister-input doregister-password-input">
                                            <button type="button" class="doregister-password-toggle" aria-label="Show password">
                                                <span class="doregister-password-toggle-icon">👁️</span>
                                            </button>
                                        </div>
                                        <!-- Password Requirements Checklist -->
                                        <div class="doregister-password-requirements">
                                            <div class="doregister-password-requirement" data-requirement="length">
                                                <span class="doregister-requirement-icon">✗</span>
                                                <span class="doregister-requirement-text">At least 8 characters</span>
                                            </div>
                                            <div class="doregister-password-requirement" data-requirement="uppercase">
                                                <span class="doregister-requirement-icon">✗</span>
                                                <span class="doregister-requirement-text">One capital letter</span>
                                            </div>
                                            <div class="doregister-password-requirement" data-requirement="lowercase">
                                                <span class="doregister-requirement-icon">✗</span>
                                                <span class="doregister-requirement-text">One lowercase letter</span>
                                            </div>
                                            <div class="doregister-password-requirement" data-requirement="number">
                                                <span class="doregister-requirement-icon">✗</span>
                                                <span class="doregister-requirement-text">One number</span>
                                            </div>
                                            <div class="doregister-password-requirement" data-requirement="special">
                                                <span class="doregister-requirement-icon">✗</span>
                                                <span class="doregister-requirement-text">One special character</span>
                                            </div>
                                        </div>
                                        <!-- Password strength meter container (populated by JavaScript) -->
                                        <div class="doregister-password-strength"></div>
                                        <span class="doregister-error-message"></span>
                                    </div>
                                    
                                    <!-- Confirm Password Field -->
                                    <div class="doregister-field-group">
                                        <label for="profile_confirm_password">Confirm New Password <span class="required">*</span></label>
                                        <div class="doregister-password-wrapper">
                                            <input type="password" id="profile_confirm_password" name="confirm_password" class="doregister-input doregister-password-input">
                                            <button type="button" class="doregister-password-toggle" aria-label="Show password">
                                                <span class="doregister-password-toggle-icon">👁️</span>
                                            </button>
                                        </div>
                                        <span class="doregister-error-message"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- VIEW MODE: Contact Details (Read-Only) -->
                <div class="doregister-profile-view-mode">
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
                </div>
                
                <!-- VIEW MODE: Personal Details (Read-Only) -->
                <div class="doregister-profile-view-mode">
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
                </div>
                
                <!-- VIEW MODE: Profile Media (Read-Only) -->
                <div class="doregister-profile-view-mode">
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
                </div>
                
                <!-- VIEW MODE: Account Information (Read-Only) -->
                <div class="doregister-profile-view-mode">
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
            </div>
            
            <!-- Profile Actions: Edit & Logout Buttons (View Mode) -->
            <div class="doregister-profile-actions doregister-profile-view-mode">
                <!-- Edit Profile Button -->
                <button type="button" class="doregister-btn doregister-btn-edit">Edit Profile</button>
                <!-- Logout Button -->
                <!-- type="button": Doesn't submit form (just triggers JavaScript) -->
                <!-- class="doregister-btn-logout": JavaScript uses this to handle logout -->
                <button type="button" class="doregister-btn doregister-btn-logout">Logout</button>
            </div>
            
            <!-- Profile Actions: Save & Cancel Buttons (Edit Mode) -->
            <div class="doregister-profile-actions doregister-profile-edit-mode" style="display: none;">
                <!-- Form Messages Container -->
                <div class="doregister-form-messages"></div>
                
                <!-- Save and Cancel Buttons -->
                <div class="doregister-profile-edit-actions">
                    <button type="button" class="doregister-btn doregister-btn-cancel">Cancel</button>
                    <button type="submit" class="doregister-btn doregister-btn-save">Save Changes</button>
                </div>
            </div>
        </div>
        <?php
        // Return captured HTML output
        return ob_get_clean();
    }
}

