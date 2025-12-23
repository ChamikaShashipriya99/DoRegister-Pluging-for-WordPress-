<?php
/**
 * Plugin Name: DoRegister
 * Description: Advanced multi-step user registration system with custom authentication, frontend login, and user profile management. Features include AJAX-powered registration form, localStorage auto-save, custom database table, and admin dashboard.
 * Author: Chamika Shashipriya
 * Author URI: https://my-portfolio-html-css-js-sigma.vercel.app/
 * 
 * @package DoRegister
 * @since 1.0.0
 */

// Exit if accessed directly
// Prevents direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define plugin constants
 * These constants are used throughout the plugin for file paths and URLs
 */
define('DOREGISTER_VERSION', '1.0.0'); // Plugin version number
define('DOREGISTER_PLUGIN_DIR', plugin_dir_path(__FILE__)); // Plugin directory path
define('DOREGISTER_PLUGIN_URL', plugin_dir_url(__FILE__)); // Plugin directory URL
define('DOREGISTER_PLUGIN_FILE', __FILE__); // Main plugin file path

/**
 * Include database class first
 * Required for activation hook to create database table
 */
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-database.php';

/**
 * Plugin activation hook
 * Runs when the plugin is activated
 * 
 * @since 1.0.0
 * @return void
 */
function doregister_activate() {
    // Ensure database class is loaded (safety check)
    require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-database.php';
    
    // Create custom database table for user registrations
    DoRegister_Database::create_table();
    
    // Flush rewrite rules to ensure permalinks work correctly
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'doregister_activate');

/**
 * Plugin deactivation hook
 * Runs when the plugin is deactivated
 * 
 * @since 1.0.0
 * @return void
 */
function doregister_deactivate() {
    // Flush rewrite rules on deactivation
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'doregister_deactivate');

/**
 * Include all required class files
 * These classes handle different aspects of the plugin functionality
 */
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-registration.php'; // Registration form handler
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-login.php'; // Login form handler
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-ajax.php'; // AJAX request handlers
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-assets.php'; // CSS/JS asset enqueuing
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-admin.php'; // Admin dashboard
require_once DOREGISTER_PLUGIN_DIR . 'includes/class-doregister-profile.php'; // User profile page

/**
 * Main DoRegister Plugin Class
 * 
 * This is the core plugin class that initializes all components.
 * Uses singleton pattern to ensure only one instance exists.
 * 
 * @since 1.0.0
 */
class DoRegister {
    
    /**
     * Instance of this class
     * 
     * @since 1.0.0
     * @var null|DoRegister
     */
    private static $instance = null;
    
    /**
     * Get instance of this class (Singleton pattern)
     * 
     * Ensures only one instance of the plugin class exists
     * 
     * @since 1.0.0
     * @return DoRegister Instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     * 
     * Private constructor to prevent direct instantiation (Singleton pattern)
     * Initializes hooks when class is instantiated
     * 
     * @since 1.0.0
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     * 
     * Sets up all action hooks needed for the plugin to function
     * 
     * @since 1.0.0
     * @return void
     */
    private function init_hooks() {
        // Start PHP session for user authentication
        // Priority 1 ensures it runs early in the init hook
        add_action('init', array($this, 'start_session'), 1);
        
        // Initialize all plugin components after WordPress is fully loaded
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Start PHP session
     * 
     * Initializes PHP session if not already started.
     * Required for custom authentication system (not using wp_users table).
     * 
     * @since 1.0.0
     * @return void
     */
    public function start_session() {
        if (!session_id()) {
            session_start();
        }
    }
    
    /**
     * Initialize plugin components
     * 
     * Initializes all plugin classes and components.
     * Called after WordPress is fully loaded via 'plugins_loaded' hook.
     * 
     * @since 1.0.0
     * @return void
     */
    public function init() {
        // Initialize database handler (creates table if doesn't exist)
        DoRegister_Database::get_instance();
        
        // Initialize registration form handler (shortcode: [doregister_form])
        DoRegister_Registration::get_instance();
        
        // Initialize login form handler (shortcode: [doregister_login])
        DoRegister_Login::get_instance();
        
        // Initialize AJAX handlers (registration, login, logout, file upload)
        DoRegister_Ajax::get_instance();
        
        // Initialize assets manager (enqueues CSS and JavaScript)
        DoRegister_Assets::get_instance();
        
        // Initialize admin dashboard (only in admin area)
        if (is_admin()) {
            DoRegister_Admin::get_instance();
        }
        
        // Initialize profile page handler (shortcode: [doregister_profile])
        DoRegister_Profile::get_instance();
    }
}

/**
 * Initialize the plugin
 * 
 * Creates the main plugin instance and starts the plugin
 * 
 * @since 1.0.0
 */
DoRegister::get_instance();

