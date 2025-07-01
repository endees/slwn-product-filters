<?php
/**
 * Admin class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SLWN_Product_Filters_Admin {
    
    /**
     * Instance
     */
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
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        // Register settings
        register_setting('slwn_product_filters_settings', 'slwn_product_filters_options');
        
        // Add settings section
        add_settings_section(
            'slwn_product_filters_main',
            __('Główne ustawienia', 'slwn-product-filters'),
            array($this, 'settings_section_callback'),
            'slwn_product_filters_settings'
        );
        
        // Add settings fields
        add_settings_field(
            'enable_filters',
            __('Włącz filtry produktów', 'slwn-product-filters'),
            array($this, 'enable_filters_callback'),
            'slwn_product_filters_settings',
            'slwn_product_filters_main'
        );
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>' . __('Skonfiguruj ustawienia filtrów produktów WooCommerce.', 'slwn-product-filters') . '</p>';
    }
    
    /**
     * Enable filters callback
     */
    public function enable_filters_callback() {
        $options = get_option('slwn_product_filters_options');
        $value = isset($options['enable_filters']) ? $options['enable_filters'] : 0;
        
        echo '<input type="checkbox" id="enable_filters" name="slwn_product_filters_options[enable_filters]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="enable_filters">' . __('Zaznacz aby włączyć filtry produktów', 'slwn-product-filters') . '</label>';
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Display admin notices if needed
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Load scripts on widgets page and customizer
        if ('widgets.php' === $hook || 'customize.php' === $hook || strpos($hook, 'slwn-product-filters') !== false) {
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
            
            // Localize script for AJAX
            wp_localize_script('slwn-product-filters-admin', 'slwn_admin_ajax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('slwn_admin_nonce'),
            ));
        }
    }
    
    /**
     * Save settings
     */
    public function save_settings() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'slwn_product_filters_settings-options')) {
            update_option('slwn_product_filters_options', $_POST['slwn_product_filters_options']);
            
            add_settings_error(
                'slwn_product_filters_settings',
                'settings_updated',
                __('Ustawienia zostały zapisane.', 'slwn-product-filters'),
                'updated'
            );
        }
    }
}
