<?php
/**
 * Plugin Name: DoRegister
 * Description: Advanced multi-step user registration system with custom authentication
 * Version: 1.0.0
 * Author: Chamika Shashipriya
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DOREGISTER_VERSION', '1.0.0');
define('DOREGISTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DOREGISTER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DOREGISTER_PLUGIN_FILE', __FILE__);

// Include required files
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-database.php';

/**
 * Plugin activation function
 */
function doregister_activate() {
    // Ensure database class is loaded
    require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-database.php';
    
    // Create database table
    DoRegister_Database::create_table();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'doregister_activate');

/**
 * Plugin deactivation function
 */
function doregister_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'doregister_deactivate');

// Include remaining required files
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-registration.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-login.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-ajax.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-assets.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-admin.php';
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-profile.php';

/**
 * Main DoRegister Plugin Class
 */
class DoRegister {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
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
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Start session
        add_action('init', array($this, 'start_session'), 1);
        
        // Initialize components
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Start session
     */
    public function start_session() {
        if (!session_id()) {
            session_start();
        }
    }
    
    /**
     * Initialize plugin components
     */
    public function init() {
        // Initialize database
        DoRegister_Database::get_instance();
        
        // Initialize registration
        DoRegister_Registration::get_instance();
        
        // Initialize login
        DoRegister_Login::get_instance();
        
        // Initialize AJAX handlers
        DoRegister_Ajax::get_instance();
        
        // Initialize assets
        DoRegister_Assets::get_instance();
        
        // Initialize admin
        if (is_admin()) {
            DoRegister_Admin::get_instance();
        }
        
        // Initialize profile
        DoRegister_Profile::get_instance();
    }
}

// Initialize the plugin
DoRegister::get_instance();

