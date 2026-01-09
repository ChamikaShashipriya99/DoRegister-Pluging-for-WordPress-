<?php
/**
 * Registration Handler Class
 * 
 * Manages the multi-step registration form and shortcode.
 * This class creates a 5-step frontend registration form with progress tracking.
 * 
 * Multi-Step Form Structure:
 * - Step 1: Basic Information (name, email, password)
 * - Step 2: Contact Details (country, city, phone)
 * - Step 3: Personal Details (gender, DOB, interests)
 * - Step 4: Profile Media (photo upload)
 * - Step 5: Review & Confirm (summary before submission)
 * 
 * Form Features:
 * - Progress bar showing completion percentage
 * - Step indicator ("Step 1 of 5")
 * - Next/Back navigation buttons
 * - JavaScript handles step transitions and validation
 * - Auto-save to localStorage
 * 
 * @package DoRegister
 * @since 1.0.0
 */
class DoRegister_Registration {
    
    /**
     * Instance of this class (Singleton pattern)
     * 
     * Prevents multiple instances and ensures shortcode is registered once.
     * 
     * @since 1.0.0
     * @var null|DoRegister_Registration
     */
    private static $instance = null;
    
    /**
     * Get instance of this class (Singleton pattern)
     * 
     * Returns the single instance. Creates it if it doesn't exist.
     * 
     * @since 1.0.0
     * @return DoRegister_Registration The single instance of this class
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
     * Registers the registration form shortcode.
     * 
     * @since 1.0.0
     */
    private function __construct() {
        // Register shortcode: [doregister_form]
        // When WordPress encounters [doregister_form], it calls render_registration_form()
        add_shortcode('doregister_form', array($this, 'render_registration_form'));
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
     * Render registration form HTML
     * 
     * Generates the HTML markup for the multi-step registration form.
     * Creates all 5 steps with appropriate form fields.
     * 
     * Form Structure:
     * - Progress bar (visual indicator of completion)
     * - Step indicator (current step number)
     * - Form with 5 step divs (only one visible at a time)
     * - Navigation buttons (Next/Back)
     * - JavaScript handles step switching and validation
     * 
     * @since 1.0.0
     * @return string HTML markup for registration form
     */
    public function render_registration_form() {
        // Check if user is already logged in
        $user_id = $this->is_user_logged_in();
        
        if ($user_id) {
            // User is already logged in - show message instead of form
            $profile_url = home_url('/profile');
            return '<div class="doregister-message doregister-info">You are already logged in. <a href="' . esc_url($profile_url) . '" class="doregister-link-to-profile">Go to your profile</a> or <a href="#" class="doregister-btn-logout">logout</a>.</div>';
        }
        
        // Start output buffering - capture HTML output
        ob_start();
        ?>
        <!-- Registration Form Wrapper -->
        <div class="doregister-registration-wrapper">
            <!-- Progress Bar -->
            <!-- Visual indicator showing form completion percentage -->
            <!-- JavaScript updates width based on current step (20%, 40%, 60%, 80%, 100%) -->
            <div class="doregister-progress-bar">
                <!-- Progress Fill: Width updated by JavaScript -->
                <!-- style="width: 20%": Initial value (Step 1 = 20% complete) -->
                <div class="doregister-progress-fill" style="width: 20%;"></div>
            </div>
            
            <!-- Step Indicator -->
            <!-- Shows current step number (e.g., "Step 1 of 5") -->
            <div class="doregister-step-indicator">
                <!-- id="doregister-step-number": JavaScript updates this number -->
                <span class="doregister-current-step">Step <span id="doregister-step-number">1</span> of 5</span>
            </div>
            
            <!-- Registration Form -->
            <!-- id="doregister-registration-form": JavaScript uses this to handle form submission -->
            <form id="doregister-registration-form" class="doregister-form">
                <!-- Security Nonce Field -->
                <!-- wp_nonce_field(): Generates hidden input with security token -->
                <!-- Prevents CSRF (Cross-Site Request Forgery) attacks -->
                <?php wp_nonce_field('doregister_registration', 'doregister_registration_nonce'); ?>
                
                <!-- Step 1: Basic Information -->
                <!-- class="doregister-step-active": Makes this step visible initially -->
                <!-- data-step="1": JavaScript uses this to identify the step -->
                <div class="doregister-step doregister-step-active" data-step="1">
                    <h2>Basic Information</h2>
                    
                    <!-- Full Name Field -->
                    <div class="doregister-field-group">
                        <!-- Label with required indicator (*) -->
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <!-- Text input for full name -->
                        <!-- required: HTML5 validation -->
                        <input type="text" id="full_name" name="full_name" class="doregister-input" required>
                        <!-- Error message container (populated by JavaScript validation) -->
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <!-- Email Field -->
                    <div class="doregister-field-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <!-- type="email": HTML5 email validation (checks format) -->
                        <!-- JavaScript also validates uniqueness via AJAX -->
                        <input type="email" id="email" name="email" class="doregister-input" required>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="doregister-field-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <!-- Password Input Wrapper with Toggle Button -->
                        <div class="doregister-password-wrapper">
                            <!-- type="password": Hides input characters -->
                            <!-- JavaScript validates minimum length and strength -->
                            <input type="password" id="password" name="password" class="doregister-input doregister-password-input" required>
                            <!-- Password Visibility Toggle Button -->
                            <button type="button" class="doregister-password-toggle" aria-label="Show password">
                                <span class="doregister-password-toggle-icon">👁️</span>
                            </button>
                        </div>
                        <!-- Password strength meter container (populated by JavaScript) -->
                        <div class="doregister-password-strength"></div>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <!-- Confirm Password Field -->
                    <div class="doregister-field-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <!-- Confirm Password Input Wrapper with Toggle Button -->
                        <div class="doregister-password-wrapper">
                            <!-- JavaScript validates that this matches password field -->
                            <input type="password" id="confirm_password" name="confirm_password" class="doregister-input doregister-password-input" required>
                            <!-- Password Visibility Toggle Button -->
                            <button type="button" class="doregister-password-toggle" aria-label="Show password">
                                <span class="doregister-password-toggle-icon">👁️</span>
                            </button>
                        </div>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <!-- Navigation Buttons -->
                    <div class="doregister-form-navigation">
                        <!-- Next Button: Moves to Step 2 -->
                        <!-- type="button": Doesn't submit form (just triggers JavaScript) -->
                        <!-- data-next-step="2": JavaScript uses this to know which step to show -->
                        <button type="button" class="doregister-btn doregister-btn-next" data-next-step="2">Next</button>
                    </div>
                </div>
                
                <!-- Step 2: Contact Details -->
                <!-- No "doregister-step-active" class: Hidden initially (JavaScript shows it) -->
                <div class="doregister-step" data-step="2">
                    <h2>Contact Details</h2>
                    
                    <!-- Country Field (Searchable Dropdown) -->
                    <div class="doregister-field-group">
                        <label for="country">Country <span class="required">*</span></label>
                        <!-- Country Search Wrapper -->
                        <div class="doregister-country-wrapper">
                            <!-- Searchable text input -->
                            <!-- class="doregister-country-search": JavaScript uses this for search functionality -->
                            <!-- placeholder: Hint text for user -->
                            <input type="text" id="country" name="country" class="doregister-input doregister-country-search" placeholder="Search country..." required>
                            <!-- Dropdown container: JavaScript populates this with filtered countries -->
                            <div class="doregister-country-dropdown"></div>
                        </div>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <!-- City Field (Optional) -->
                    <div class="doregister-field-group">
                        <label for="city">City</label>
                        <!-- No "required" attribute: Optional field -->
                        <input type="text" id="city" name="city" class="doregister-input">
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <!-- Phone Number Field -->
                    <div class="doregister-field-group">
                        <label for="phone_number">Phone Number <span class="required">*</span></label>
                        <!-- type="tel": Optimized for phone number input (mobile keyboards) -->
                        <!-- JavaScript validates numeric and length -->
                        <input type="tel" id="phone_number" name="phone_number" class="doregister-input" required>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <!-- Navigation Buttons -->
                    <div class="doregister-form-navigation">
                        <!-- Back Button: Returns to Step 1 -->
                        <!-- data-prev-step="1": JavaScript uses this to know which step to show -->
                        <button type="button" class="doregister-btn doregister-btn-back" data-prev-step="1">Back</button>
                        <!-- Next Button: Moves to Step 3 -->
                        <button type="button" class="doregister-btn doregister-btn-next" data-next-step="3">Next</button>
                    </div>
                </div>
                
                <!-- Step 3: Personal Details -->
                <div class="doregister-step" data-step="3">
                    <h2>Personal Details</h2>
                    
                    <!-- Gender Field (Radio Buttons) -->
                    <div class="doregister-field-group">
                        <label>Gender</label>
                        <!-- Radio Group Container -->
                        <div class="doregister-radio-group">
                            <!-- Radio Option: Male -->
                            <!-- name="gender": All radio buttons share same name (only one can be selected) -->
                            <!-- value="male": Value sent to server if selected -->
                            <label class="doregister-radio-label">
                                <input type="radio" name="gender" value="male" class="doregister-radio">
                                <span>Male</span>
                            </label>
                            <!-- Radio Option: Female -->
                            <label class="doregister-radio-label">
                                <input type="radio" name="gender" value="female" class="doregister-radio">
                                <span>Female</span>
                            </label>
                            <!-- Radio Option: Other -->
                            <label class="doregister-radio-label">
                                <input type="radio" name="gender" value="other" class="doregister-radio">
                                <span>Other</span>
                            </label>
                        </div>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <!-- Date of Birth Field -->
                    <div class="doregister-field-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <!-- type="date": HTML5 date picker (browser shows calendar) -->
                        <!-- Optional field (no required attribute) -->
                        <input type="date" id="date_of_birth" name="date_of_birth" class="doregister-input">
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <!-- Interests Field (Checkboxes) -->
                    <div class="doregister-field-group">
                        <label>Interests <span class="required">*</span></label>
                        <!-- Checkbox Group Container -->
                        <div class="doregister-checkbox-group">
                            <!-- Checkbox Option: Technology -->
                            <!-- name="interests[]": Array notation - multiple values can be selected -->
                            <!-- [] in name: PHP receives as array $_POST['interests'] -->
                            <label class="doregister-checkbox-label">
                                <input type="checkbox" name="interests[]" value="technology" class="doregister-checkbox">
                                <span>Technology</span>
                            </label>
                            <!-- Checkbox Option: Sports -->
                            <label class="doregister-checkbox-label">
                                <input type="checkbox" name="interests[]" value="sports" class="doregister-checkbox">
                                <span>Sports</span>
                            </label>
                            <!-- Checkbox Option: Music -->
                            <label class="doregister-checkbox-label">
                                <input type="checkbox" name="interests[]" value="music" class="doregister-checkbox">
                                <span>Music</span>
                            </label>
                            <!-- Checkbox Option: Travel -->
                            <label class="doregister-checkbox-label">
                                <input type="checkbox" name="interests[]" value="travel" class="doregister-checkbox">
                                <span>Travel</span>
                            </label>
                            <!-- Checkbox Option: Reading -->
                            <label class="doregister-checkbox-label">
                                <input type="checkbox" name="interests[]" value="reading" class="doregister-checkbox">
                                <span>Reading</span>
                            </label>
                            <!-- Checkbox Option: Cooking -->
                            <label class="doregister-checkbox-label">
                                <input type="checkbox" name="interests[]" value="cooking" class="doregister-checkbox">
                                <span>Cooking</span>
                            </label>
                        </div>
                        <!-- JavaScript validates at least 3 interests selected -->
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <!-- Navigation Buttons -->
                    <div class="doregister-form-navigation">
                        <button type="button" class="doregister-btn doregister-btn-back" data-prev-step="2">Back</button>
                        <button type="button" class="doregister-btn doregister-btn-next" data-next-step="4">Next</button>
                    </div>
                </div>
                
                <!-- Step 4: Profile Media -->
                <div class="doregister-step" data-step="4">
                    <h2>Profile Photo</h2>
                    
                    <!-- Profile Photo Upload Field -->
                    <div class="doregister-field-group">
                        <label for="profile_photo">Profile Photo <span class="required">*</span></label>
                        <!-- File Input -->
                        <!-- type="file": Allows user to select file from device -->
                        <!-- accept="image/*": Restricts to image files only -->
                        <!-- JavaScript validates file type and size -->
                        <!-- File is uploaded via AJAX before final form submission -->
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/*" class="doregister-input doregister-file-input" required>
                        <!-- Image Preview Container -->
                        <!-- JavaScript displays selected image here using FileReader API -->
                        <div class="doregister-image-preview"></div>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <!-- Navigation Buttons -->
                    <div class="doregister-form-navigation">
                        <button type="button" class="doregister-btn doregister-btn-back" data-prev-step="3">Back</button>
                        <button type="button" class="doregister-btn doregister-btn-next" data-next-step="5">Next</button>
                    </div>
                </div>
                
                <!-- Step 5: Review & Confirm -->
                <div class="doregister-step" data-step="5">
                    <h2>Review & Confirm</h2>
                    
                    <!-- Review Summary Container -->
                    <!-- id="doregister-review-summary": JavaScript populates this with all form data -->
                    <!-- Shows read-only summary of all entered information -->
                    <div class="doregister-review-summary" id="doregister-review-summary">
                        <!-- Summary will be populated by JavaScript -->
                        <!-- JavaScript collects all form values and displays them here -->
                    </div>
                    
                    <!-- Navigation Buttons -->
                    <div class="doregister-form-navigation">
                        <!-- Back Button: Returns to Step 4 -->
                        <button type="button" class="doregister-btn doregister-btn-back" data-prev-step="4">Back</button>
                        <!-- Submit Button: Submits entire form -->
                        <!-- type="submit": Triggers form submission (not just JavaScript) -->
                        <!-- JavaScript intercepts this and submits via AJAX -->
                        <button type="submit" class="doregister-btn doregister-btn-submit">Submit Registration</button>
                    </div>
                </div>
                
                <!-- Form Messages Container -->
                <!-- JavaScript displays success/error messages here -->
                <!-- Examples: "Registration successful", "Email already exists" -->
                <div class="doregister-form-messages"></div>
            </form>
            
            <!-- Form Footer: Navigation Link -->
            <div class="doregister-form-footer">
                <!-- Link to login page -->
                <!-- href="#": JavaScript handles navigation -->
                <p>Already have an account? <a href="#" class="doregister-link-to-login">Login here</a></p>
            </div>
        </div>
        <?php
        // Return captured HTML output
        return ob_get_clean();
    }
}

