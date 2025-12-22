<?php
/**
 * Profile Handler Class
 */
class DoRegister_Profile {
    
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
        add_shortcode('doregister_profile', array($this, 'render_profile_page'));
    }
    
    /**
     * Render profile page
     */
    public function render_profile_page() {
        // Ensure session is started
        if (!session_id()) {
            session_start();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['doregister_user_id'])) {
            $login_url = home_url('/login');
            return '<div class="doregister-message doregister-error">Please <a href="' . esc_url($login_url) . '" class="doregister-link-to-login">login</a> to view your profile.</div>';
        }
        
        $user_id = intval($_SESSION['doregister_user_id']);
        $user = DoRegister_Database::get_user_by_id($user_id);
        
        if (!$user) {
            return '<div class="doregister-message doregister-error">User not found.</div>';
        }
        
        ob_start();
        ?>
        <div class="doregister-profile-wrapper">
            <h2>My Profile</h2>
            
            <div class="doregister-profile-header">
                <div class="doregister-profile-photo">
                    <?php if ($user->profile_photo): ?>
                        <img src="<?php echo esc_url($user->profile_photo); ?>" alt="Profile Photo">
                    <?php else: ?>
                        <div class="doregister-no-photo">
                            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="doregister-profile-name"><?php echo esc_html($user->full_name); ?></div>
                <div class="doregister-profile-email"><?php echo esc_html($user->email); ?></div>
            </div>
            
            <div class="doregister-profile-content">
                <!-- Step 1: Basic Information -->
                <div class="doregister-profile-category">
                    <h3 class="doregister-profile-category-title">Basic Information</h3>
                    <div class="doregister-profile-category-content">
                        <div class="doregister-profile-field">
                            <strong>Full Name</strong>
                            <span><?php echo esc_html($user->full_name); ?></span>
                        </div>
                        <div class="doregister-profile-field">
                            <strong>Email</strong>
                            <span><?php echo esc_html($user->email); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Contact Details -->
                <div class="doregister-profile-category">
                    <h3 class="doregister-profile-category-title">Contact Details</h3>
                    <div class="doregister-profile-category-content">
                        <div class="doregister-profile-field">
                            <strong>Phone Number</strong>
                            <span><?php echo esc_html($user->phone_number); ?></span>
                        </div>
                        <div class="doregister-profile-field">
                            <strong>Country</strong>
                            <span><?php echo esc_html($user->country); ?></span>
                        </div>
                        <?php if ($user->city): ?>
                        <div class="doregister-profile-field">
                            <strong>City</strong>
                            <span><?php echo esc_html($user->city); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Step 3: Personal Details -->
                <div class="doregister-profile-category">
                    <h3 class="doregister-profile-category-title">Personal Details</h3>
                    <div class="doregister-profile-category-content">
                        <?php if ($user->gender): ?>
                        <div class="doregister-profile-field">
                            <strong>Gender</strong>
                            <span><?php echo esc_html(ucfirst($user->gender)); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($user->date_of_birth): ?>
                        <div class="doregister-profile-field">
                            <strong>Date of Birth</strong>
                            <span><?php echo esc_html(date('F j, Y', strtotime($user->date_of_birth))); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($user->interests && is_array($user->interests)): ?>
                        <div class="doregister-profile-field">
                            <strong>Interests</strong>
                            <div class="doregister-profile-interests">
                                <?php foreach ($user->interests as $interest): ?>
                                    <span><?php echo esc_html(ucfirst($interest)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Step 4: Profile Media -->
                <div class="doregister-profile-category">
                    <h3 class="doregister-profile-category-title">Profile Media</h3>
                    <div class="doregister-profile-category-content">
                        <div class="doregister-profile-field doregister-profile-photo-field">
                            <strong>Profile Photo</strong>
                            <div class="doregister-profile-photo-display">
                                <?php if ($user->profile_photo): ?>
                                    <img src="<?php echo esc_url($user->profile_photo); ?>" alt="Profile Photo">
                                <?php else: ?>
                                    <div class="doregister-no-photo-small">No photo uploaded</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Account Information -->
                <div class="doregister-profile-category">
                    <h3 class="doregister-profile-category-title">Account Information</h3>
                    <div class="doregister-profile-category-content">
                        <div class="doregister-profile-field">
                            <strong>Member Since</strong>
                            <span><?php echo esc_html(date('F j, Y', strtotime($user->created_at))); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="doregister-profile-actions">
                <button type="button" class="doregister-btn doregister-btn-logout">Logout</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

