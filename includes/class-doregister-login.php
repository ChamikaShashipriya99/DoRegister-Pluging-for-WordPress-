<?php
/**
 * Login Handler Class
 */
class DoRegister_Login {
    
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
        add_shortcode('doregister_login', array($this, 'render_login_form'));
    }
    
    /**
     * Render login form
     */
    public function render_login_form() {
        ob_start();
        ?>
        <div class="doregister-login-wrapper">
            <form id="doregister-login-form" class="doregister-form">
                <?php wp_nonce_field('doregister_login', 'doregister_login_nonce'); ?>
                
                <h2>Login</h2>
                
                <div class="doregister-field-group">
                    <label for="login_email">Email / Username <span class="required">*</span></label>
                    <input type="text" id="login_email" name="login_email" class="doregister-input" required>
                    <span class="doregister-error-message"></span>
                </div>
                
                <div class="doregister-field-group">
                    <label for="login_password">Password <span class="required">*</span></label>
                    <input type="password" id="login_password" name="login_password" class="doregister-input" required>
                    <span class="doregister-error-message"></span>
                </div>
                
                <div class="doregister-field-group">
                    <button type="submit" class="doregister-btn doregister-btn-submit">Login</button>
                </div>
                
                <div class="doregister-form-messages"></div>
            </form>
            
            <div class="doregister-form-footer">
                <p>Don't have an account? <a href="#" class="doregister-link-to-register">Register here</a></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

