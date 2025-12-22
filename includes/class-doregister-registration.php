<?php
/**
 * Registration Handler Class
 */
class DoRegister_Registration {
    
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
        add_shortcode('doregister_form', array($this, 'render_registration_form'));
    }
    
    /**
     * Render registration form
     */
    public function render_registration_form() {
        ob_start();
        ?>
        <div class="doregister-registration-wrapper">
            <div class="doregister-progress-bar">
                <div class="doregister-progress-fill" style="width: 20%;"></div>
            </div>
            
            <div class="doregister-step-indicator">
                <span class="doregister-current-step">Step <span id="doregister-step-number">1</span> of 5</span>
            </div>
            
            <form id="doregister-registration-form" class="doregister-form">
                <?php wp_nonce_field('doregister_registration', 'doregister_registration_nonce'); ?>
                
                <!-- Step 1: Basic Information -->
                <div class="doregister-step doregister-step-active" data-step="1">
                    <h2>Basic Information</h2>
                    
                    <div class="doregister-field-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="doregister-input" required>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <div class="doregister-field-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="doregister-input" required>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <div class="doregister-field-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" class="doregister-input" required>
                        <div class="doregister-password-strength"></div>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <div class="doregister-field-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="doregister-input" required>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <div class="doregister-form-navigation">
                        <button type="button" class="doregister-btn doregister-btn-next" data-next-step="2">Next</button>
                    </div>
                </div>
                
                <!-- Step 2: Contact Details -->
                <div class="doregister-step" data-step="2">
                    <h2>Contact Details</h2>
                    
                    <div class="doregister-field-group">
                        <label for="phone_number">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone_number" name="phone_number" class="doregister-input" required>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <div class="doregister-field-group">
                        <label for="country">Country <span class="required">*</span></label>
                        <div class="doregister-country-wrapper">
                            <input type="text" id="country" name="country" class="doregister-input doregister-country-search" placeholder="Search country..." required>
                            <div class="doregister-country-dropdown"></div>
                        </div>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <div class="doregister-field-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" class="doregister-input">
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <div class="doregister-form-navigation">
                        <button type="button" class="doregister-btn doregister-btn-back" data-prev-step="1">Back</button>
                        <button type="button" class="doregister-btn doregister-btn-next" data-next-step="3">Next</button>
                    </div>
                </div>
                
                <!-- Step 3: Personal Details -->
                <div class="doregister-step" data-step="3">
                    <h2>Personal Details</h2>
                    
                    <div class="doregister-field-group">
                        <label>Gender</label>
                        <div class="doregister-radio-group">
                            <label class="doregister-radio-label">
                                <input type="radio" name="gender" value="male" class="doregister-radio">
                                <span>Male</span>
                            </label>
                            <label class="doregister-radio-label">
                                <input type="radio" name="gender" value="female" class="doregister-radio">
                                <span>Female</span>
                            </label>
                            <label class="doregister-radio-label">
                                <input type="radio" name="gender" value="other" class="doregister-radio">
                                <span>Other</span>
                            </label>
                        </div>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <div class="doregister-field-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="doregister-input">
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <div class="doregister-field-group">
                        <label>Interests <span class="required">*</span></label>
                        <div class="doregister-checkbox-group">
                            <label class="doregister-checkbox-label">
                                <input type="checkbox" name="interests[]" value="technology" class="doregister-checkbox">
                                <span>Technology</span>
                            </label>
                            <label class="doregister-checkbox-label">
                                <input type="checkbox" name="interests[]" value="sports" class="doregister-checkbox">
                                <span>Sports</span>
                            </label>
                            <label class="doregister-checkbox-label">
                                <input type="checkbox" name="interests[]" value="music" class="doregister-checkbox">
                                <span>Music</span>
                            </label>
                            <label class="doregister-checkbox-label">
                                <input type="checkbox" name="interests[]" value="travel" class="doregister-checkbox">
                                <span>Travel</span>
                            </label>
                            <label class="doregister-checkbox-label">
                                <input type="checkbox" name="interests[]" value="reading" class="doregister-checkbox">
                                <span>Reading</span>
                            </label>
                            <label class="doregister-checkbox-label">
                                <input type="checkbox" name="interests[]" value="cooking" class="doregister-checkbox">
                                <span>Cooking</span>
                            </label>
                        </div>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <div class="doregister-form-navigation">
                        <button type="button" class="doregister-btn doregister-btn-back" data-prev-step="2">Back</button>
                        <button type="button" class="doregister-btn doregister-btn-next" data-next-step="4">Next</button>
                    </div>
                </div>
                
                <!-- Step 4: Profile Media -->
                <div class="doregister-step" data-step="4">
                    <h2>Profile Photo</h2>
                    
                    <div class="doregister-field-group">
                        <label for="profile_photo">Profile Photo <span class="required">*</span></label>
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/*" class="doregister-input doregister-file-input" required>
                        <div class="doregister-image-preview"></div>
                        <span class="doregister-error-message"></span>
                    </div>
                    
                    <div class="doregister-form-navigation">
                        <button type="button" class="doregister-btn doregister-btn-back" data-prev-step="3">Back</button>
                        <button type="button" class="doregister-btn doregister-btn-next" data-next-step="5">Next</button>
                    </div>
                </div>
                
                <!-- Step 5: Review & Confirm -->
                <div class="doregister-step" data-step="5">
                    <h2>Review & Confirm</h2>
                    
                    <div class="doregister-review-summary" id="doregister-review-summary">
                        <!-- Summary will be populated by JavaScript -->
                    </div>
                    
                    <div class="doregister-form-navigation">
                        <button type="button" class="doregister-btn doregister-btn-back" data-prev-step="4">Back</button>
                        <button type="submit" class="doregister-btn doregister-btn-submit">Submit Registration</button>
                    </div>
                </div>
                
                <div class="doregister-form-messages"></div>
            </form>
            
            <div class="doregister-form-footer">
                <p>Already have an account? <a href="#" class="doregister-link-to-login">Login here</a></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

