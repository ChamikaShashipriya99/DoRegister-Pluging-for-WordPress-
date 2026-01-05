<?php
/**
 * Assets Handler Class
 * 
 * This class manages all CSS and JavaScript files for the DoRegister plugin.
 * It handles enqueuing (loading) scripts and styles on both frontend and admin pages.
 * 
 * WordPress Enqueue System:
 * - wp_enqueue_scripts: Hook for frontend scripts/styles
 * - admin_enqueue_scripts: Hook for admin area scripts/styles
 * - wp_localize_script: Passes PHP data to JavaScript
 * 
 * Why This Class Exists:
 * - Centralizes asset management (easier to maintain)
 * - Ensures proper dependency loading (jQuery before our scripts)
 * - Prevents conflicts with other plugins/themes
 * - Allows conditional loading (only load on specific pages)
 * 
 * @package DoRegister
 * @since 1.0.0
 */
class DoRegister_Assets {
    
    /**
     * Instance of this class (Singleton pattern)
     * 
     * Stores the single instance of this class.
     * Prevents multiple instances and duplicate asset enqueuing.
     * 
     * @since 1.0.0
     * @var null|DoRegister_Assets
     */
    private static $instance = null;
    
    /**
     * Get instance of this class (Singleton pattern)
     * 
     * Returns the single instance of this class.
     * If instance doesn't exist, creates it. Otherwise returns existing instance.
     * 
     * @since 1.0.0
     * @return DoRegister_Assets The single instance of this class
     */
    public static function get_instance() {
        // Check if instance already exists
        if (null === self::$instance) {
            // Create new instance if it doesn't exist
            self::$instance = new self();
        }
        // Return the existing or newly created instance
        return self::$instance;
    }
    
    /**
     * Constructor
     * 
     * Private constructor prevents direct instantiation (Singleton pattern).
     * Registers WordPress hooks to enqueue scripts and styles at the right time.
     * 
     * WordPress Hook Explanation:
     * - wp_enqueue_scripts: Fires when scripts/styles should be enqueued (frontend)
     * - admin_enqueue_scripts: Fires in admin area (backend)
     * - $hook parameter in admin_enqueue_scripts tells us which admin page is loading
     * 
     * @since 1.0.0
     */
    private function __construct() {
        // Register frontend JavaScript enqueuing
        // This hook fires on every frontend page load
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Register frontend CSS enqueuing
        // Same hook, different method (both fire on frontend)
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        
        // Register admin CSS enqueuing
        // admin_enqueue_scripts hook fires only in WordPress admin area
        // $hook parameter will be passed to enqueue_admin_styles() method
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }
    
    /**
     * Enqueue JavaScript files
     * 
     * Loads jQuery (dependency) and our custom JavaScript file.
     * Also passes PHP data to JavaScript using wp_localize_script().
     * 
     * Why jQuery is enqueued separately:
     * - WordPress includes jQuery, but we must explicitly enqueue it
     * - This ensures jQuery loads before our script (dependency)
     * - Prevents "jQuery is not defined" errors
     * 
     * wp_localize_script() Purpose:
     * - Makes PHP data available in JavaScript
     * - Provides AJAX URL, nonces, and country list
     * - JavaScript can access this via doregisterData object
     * 
     * @since 1.0.0
     * @return void
     */
    public function enqueue_scripts() {
        // Enqueue jQuery (WordPress built-in library)
        // This ensures jQuery is loaded before our custom script
        // wp_enqueue_script('jquery') loads WordPress's bundled jQuery
        wp_enqueue_script('jquery');
        
        // Enqueue our custom JavaScript file
        // wp_enqueue_script() parameters:
        // 1. Handle: Unique identifier for this script ('doregister-js')
        // 2. Source: URL to the JavaScript file
        // 3. Dependencies: Array of script handles that must load first (jQuery)
        // 4. Version: Plugin version (for cache busting - forces browser to reload on updates)
        // 5. In footer: true = load in footer, false = load in header (better for performance)
        wp_enqueue_script(
            'doregister-js', // Handle (unique identifier)
            DOREGISTER_PLUGIN_URL . 'assets/js/doregister.js', // Full URL to JavaScript file
            array('jquery'), // Dependencies: jQuery must load first
            DOREGISTER_VERSION, // Version number (for cache busting)
            true // Load in footer (better performance, DOM ready)
        );
        
        // Localize script: Pass PHP data to JavaScript
        // wp_localize_script() makes PHP variables available in JavaScript
        // This creates a JavaScript object: doregisterData.ajaxUrl, doregisterData.nonce, etc.
        wp_localize_script('doregister-js', 'doregisterData', array(
            // AJAX endpoint URL - where JavaScript sends AJAX requests
            'ajaxUrl' => admin_url('admin-ajax.php'), // WordPress AJAX handler URL
            
            // Security nonce for registration form
            // Nonces prevent CSRF attacks - must match server-side verification
            'nonce' => wp_create_nonce('doregister_registration'), // Creates unique token
            
            // Security nonce for login form
            // Different nonce for login (separate security context)
            'loginNonce' => wp_create_nonce('doregister_login'), // Creates unique token
            
            // Security nonce for profile update
            // Different nonce for profile editing (separate security context)
            'profileUpdateNonce' => wp_create_nonce('doregister_profile_update'), // Creates unique token
            
            // Country list for searchable dropdown
            // JavaScript uses this to populate country search functionality
            'countries' => $this->get_countries_list() // Array of country names
        ));
    }
    
    /**
     * Enqueue CSS styles for frontend
     * 
     * Loads the main stylesheet for registration, login, and profile pages.
     * This runs on all frontend pages (we could optimize to only load on specific pages).
     * 
     * @since 1.0.0
     * @return void
     */
    public function enqueue_styles() {
        // Enqueue main CSS file
        // wp_enqueue_style() parameters:
        // 1. Handle: Unique identifier ('doregister-css')
        // 2. Source: URL to CSS file
        // 3. Dependencies: Array of style handles (none in this case)
        // 4. Version: Plugin version (for cache busting)
        wp_enqueue_style(
            'doregister-css', // Handle (unique identifier)
            DOREGISTER_PLUGIN_URL . 'assets/css/doregister.css', // Full URL to CSS file
            array(), // No dependencies (empty array)
            DOREGISTER_VERSION // Version number (for cache busting)
        );
    }
    
    /**
     * Enqueue CSS styles for admin area
     * 
     * Loads styles only on the DoRegister admin page.
     * The $hook parameter tells us which admin page is loading.
     * 
     * Why conditional loading:
     * - Improves performance (doesn't load on every admin page)
     * - Prevents style conflicts with other admin pages
     * - Only loads when needed
     * 
     * @since 1.0.0
     * @param string $hook The current admin page hook (e.g., 'toplevel_page_doregister')
     * @return void
     */
    public function enqueue_admin_styles($hook) {
        // Conditional loading: Only load on our admin page
        // $hook contains the page identifier (e.g., 'toplevel_page_doregister')
        // If it's not our page, exit early (don't load styles)
        if ($hook !== 'toplevel_page_doregister') {
            return; // Exit early - not our admin page
        }
        
        // Enqueue CSS file for admin area
        // Same CSS file as frontend, but enqueued separately for admin
        wp_enqueue_style(
            'doregister-admin-css', // Handle (different from frontend to avoid conflicts)
            DOREGISTER_PLUGIN_URL . 'assets/css/doregister.css', // Same CSS file
            array(), // No dependencies
            DOREGISTER_VERSION // Version number
        );
        
        // Add inline CSS styles
        // wp_add_inline_style() adds CSS directly to the page (not from a file)
        // This is used for admin-specific pagination styles
        // First parameter: Handle of the style to attach inline CSS to
        // Second parameter: CSS code as string
        wp_add_inline_style('doregister-admin-css', $this->get_admin_styles());
    }
    
    /**
     * Get admin-specific CSS styles
     * 
     * Returns CSS code as a string for admin pagination styling.
     * These styles are added inline (not from a file) using wp_add_inline_style().
     * 
     * Why inline styles:
     * - Small amount of CSS (not worth a separate file)
     * - Admin-specific (only needed on one page)
     * - Easy to modify programmatically
     * 
     * @since 1.0.0
     * @return string CSS code for admin pagination styling
     */
    private function get_admin_styles() {
        // Return CSS as a string
        // This CSS will be added inline to the admin page
        return '
        /* DoRegister Admin Pagination Styles */
        .doregister-admin-pagination .tablenav {
            margin: 20px 0;
            padding: 15px 0;
            border-top: 1px solid #ddd;
            clear: both;
        }
        
        .doregister-admin-pagination .tablenav-pages {
            float: right;
            margin: 0;
        }
        
        .doregister-admin-pagination .tablenav-pages a,
        .doregister-admin-pagination .tablenav-pages span {
            display: inline-block;
            min-width: 32px;
            height: 32px;
            line-height: 32px;
            padding: 0 8px;
            margin: 0 2px;
            text-align: center;
            text-decoration: none;
            border: 1px solid #c3c4c7;
            background: #fff;
            color: #2c3338;
            border-radius: 4px;
            transition: all 0.2s ease;
            font-size: 13px;
        }
        
        .doregister-admin-pagination .tablenav-pages a:hover {
            background: #f0f0f1;
            border-color: #8c8f94;
            color: #135e96;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .doregister-admin-pagination .tablenav-pages .current {
            background: linear-gradient(135deg, #000000 0%, #555555 100%);
            color: #fff;
            border-color: #000;
            font-weight: 600;
            cursor: default;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
        
        .doregister-admin-pagination .tablenav-pages .current:hover {
            background: linear-gradient(135deg, #000000 0%, #555555 100%);
            color: #fff;
            transform: none;
        }
        
        .doregister-admin-pagination .tablenav-pages .prev-page,
        .doregister-admin-pagination .tablenav-pages .next-page {
            font-weight: 600;
            font-size: 14px;
        }
        
        .doregister-admin-pagination .tablenav-pages .prev-page:hover,
        .doregister-admin-pagination .tablenav-pages .next-page:hover {
            background: #4CAF50;
            border-color: #4CAF50;
            color: #fff;
        }
        
        .doregister-admin-pagination .tablenav-pages .dots {
            border: none;
            background: transparent;
            cursor: default;
            color: #50575e;
            min-width: auto;
            padding: 0 4px;
        }
        
        .doregister-admin-pagination .tablenav-pages .dots:hover {
            background: transparent;
            border: none;
            transform: none;
            box-shadow: none;
        }
        
        .doregister-admin-pagination .tablenav-pages .displaying-num {
            margin-right: 15px;
            line-height: 32px;
            color: #646970;
            font-size: 13px;
            font-weight: 500;
        }
        ';
    }
    
    /**
     * Get list of countries for searchable dropdown
     * 
     * Returns an array of country names used in the registration form.
     * This list is passed to JavaScript via wp_localize_script().
     * 
     * Why a method instead of a constant:
     * - Can be easily extended (add more countries)
     * - Could be loaded from database or external API in future
     * - Keeps code organized
     * 
     * Usage in JavaScript:
     * - Accessed via doregisterData.countries
     * - Used for country search/filter functionality
     * - Populates dropdown as user types
     * 
     * @since 1.0.0
     * @return array Array of country names (strings)
     */
    private function get_countries_list() {
        // Return array of country names
        // These are used in Step 2 of registration form
        // JavaScript filters this list as user types in country field
        return array(
            'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany',
            'France', 'Italy', 'Spain', 'Netherlands', 'Belgium',
            'Switzerland', 'Austria', 'Sweden', 'Norway', 'Denmark',
            'Finland', 'Poland', 'Portugal', 'Greece', 'Ireland',
            'India', 'China', 'Japan', 'South Korea', 'Singapore',
            'Malaysia', 'Thailand', 'Indonesia', 'Philippines', 'Vietnam',
            'Brazil', 'Mexico', 'Argentina', 'Chile', 'Colombia',
            'South Africa', 'Egypt', 'Nigeria', 'Kenya', 'Morocco',
            'Russia', 'Turkey', 'Saudi Arabia', 'UAE', 'Israel',
            'New Zealand', 'Bangladesh', 'Pakistan', 'Sri Lanka', 'Nepal'
        );
    }
}

