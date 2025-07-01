<?php
/**
 * Plugin Name: Woocommerce Product Filters
 * Plugin URI: https://slawinsky.pl/wtyczki/woocommerce-product-filters
 * Description: Wtyczka umożliwiająca filtrowanie produktów WooCommerce po atrybutach i kategoriach. Różne rodzaje stylów filtrów: lista rozwijana, przyciski, checkboxy (wielokrotny wybór) i suwaki zakresu.
 * Version: 1.1.0
 * Author: Maciej Sławiński
 * Author URI: https://slawinsky.pl
 * Text Domain: slwn-product-filters
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.2
 * WC requires at least: 5.0
 * WC tested up to: 7.8
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

// Define plugin constants
define('SLWN_PRODUCT_FILTERS_VERSION', '1.1.0');
define('SLWN_PRODUCT_FILTERS_URL', plugin_dir_url(__FILE__));
define('SLWN_PRODUCT_FILTERS_PATH', plugin_dir_path(__FILE__));
define('SLWN_PRODUCT_FILTERS_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class SLWN_Product_Filters {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
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
        $this->includes();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('widgets_init', array($this, 'register_widgets'));
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        
        // WooCommerce hooks
        add_action('woocommerce_init', array($this, 'woocommerce_init'));
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once SLWN_PRODUCT_FILTERS_PATH . 'includes/class-admin.php';
        require_once SLWN_PRODUCT_FILTERS_PATH . 'includes/class-frontend.php';
        require_once SLWN_PRODUCT_FILTERS_PATH . 'includes/class-ajax.php';
        require_once SLWN_PRODUCT_FILTERS_PATH . 'includes/class-widgets.php';
        require_once SLWN_PRODUCT_FILTERS_PATH . 'includes/functions.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize admin
        if (is_admin()) {
            SLWN_Product_Filters_Admin::get_instance();
        }
        
        // Initialize frontend
        SLWN_Product_Filters_Frontend::get_instance();
        
        // Initialize AJAX
        SLWN_Product_Filters_Ajax::get_instance();
    }
    
    /**
     * Declare WooCommerce HPOS compatibility
     */
    public function declare_wc_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('slwn-product-filters', false, dirname(SLWN_PRODUCT_FILTERS_BASENAME) . '/languages/');
    }
    
    /**
     * Add admin menu
     */
    public function admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Product Filters', 'slwn-product-filters'),
            __('Product Filters', 'slwn-product-filters'),
            'manage_woocommerce',
            'slwn-product-filters',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        include SLWN_PRODUCT_FILTERS_PATH . 'admin/views/admin-page.php';
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_scripts($hook_suffix) {
        if ('woocommerce_page_slwn-product-filters' === $hook_suffix) {
            wp_enqueue_script(
                'slwn-product-filters-admin',
                SLWN_PRODUCT_FILTERS_URL . 'assets/js/admin.js',
                array('jquery'),
                SLWN_PRODUCT_FILTERS_VERSION,
                true
            );
            
            wp_enqueue_style(
                'slwn-product-filters-admin',
                SLWN_PRODUCT_FILTERS_URL . 'assets/css/admin.css',
                array(),
                SLWN_PRODUCT_FILTERS_VERSION
            );
        }
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function frontend_scripts() {
        wp_enqueue_script(
            'slwn-product-filters-frontend',
            SLWN_PRODUCT_FILTERS_URL . 'assets/js/frontend.js',
            array('jquery'),
            SLWN_PRODUCT_FILTERS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'slwn-product-filters-frontend',
            SLWN_PRODUCT_FILTERS_URL . 'assets/css/frontend.css',
            array(),
            SLWN_PRODUCT_FILTERS_VERSION
        );
        
        // Localize script
        wp_localize_script('slwn-product-filters-frontend', 'slwn_product_filters_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('slwn_product_filters_nonce')
        ));
    }
    
    /**
     * WooCommerce init
     */
    public function woocommerce_init() {
        // WooCommerce specific initialization
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        if (function_exists('slwn_register_product_filter_widgets')) {
            slwn_register_product_filter_widgets();
        }
    }
}

// Initialize plugin
function slwn_product_filters() {
    return SLWN_Product_Filters::get_instance();
}

// Hook after plugins loaded
add_action('plugins_loaded', 'slwn_product_filters');

// Declare WooCommerce HPOS compatibility before WooCommerce init
add_action('before_woocommerce_init', function() {
    if (class_exists('SLWN_Product_Filters')) {
        $instance = SLWN_Product_Filters::get_instance();
        $instance->declare_wc_compatibility();
    }
});

// Activation hook
register_activation_hook(__FILE__, 'slwn_product_filters_activate');
function slwn_product_filters_activate() {
    // Activation code
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'slwn_product_filters_deactivate');
function slwn_product_filters_deactivate() {
    // Deactivation code
    flush_rewrite_rules();
}
