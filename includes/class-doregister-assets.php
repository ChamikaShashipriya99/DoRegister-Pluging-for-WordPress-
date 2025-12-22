<?php
/**
 * Assets Handler Class
 */
class DoRegister_Assets {
    
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        
        wp_enqueue_script(
            'doregister-js',
            DOREGISTER_PLUGIN_URL . 'assets/js/doregister.js',
            array('jquery'),
            DOREGISTER_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('doregister-js', 'doregisterData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('doregister_registration'),
            'loginNonce' => wp_create_nonce('doregister_login'),
            'countries' => $this->get_countries_list()
        ));
    }
    
    /**
     * Enqueue styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'doregister-css',
            DOREGISTER_PLUGIN_URL . 'assets/css/doregister.css',
            array(),
            DOREGISTER_VERSION
        );
    }
    
    /**
     * Get countries list
     */
    private function get_countries_list() {
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

