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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
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
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook) {
        // Only load on our admin page
        if ($hook !== 'toplevel_page_doregister') {
            return;
        }
        
        wp_enqueue_style(
            'doregister-admin-css',
            DOREGISTER_PLUGIN_URL . 'assets/css/doregister.css',
            array(),
            DOREGISTER_VERSION
        );
        
        // Add inline admin styles
        wp_add_inline_style('doregister-admin-css', $this->get_admin_styles());
    }
    
    /**
     * Get admin styles
     */
    private function get_admin_styles() {
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

