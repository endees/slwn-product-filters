<?php
/**
 * Frontend class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SLWN_Product_Filters_Frontend {
    
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
        // WooCommerce hooks
        add_action('woocommerce_before_single_product_summary', array($this, 'before_product_summary'));
        add_action('woocommerce_after_single_product_summary', array($this, 'after_product_summary'));
        add_action('woocommerce_before_shop_loop', array($this, 'before_shop_loop'));
        add_action('woocommerce_after_shop_loop', array($this, 'after_shop_loop'));
        
        // Cart hooks
        add_action('woocommerce_before_cart', array($this, 'before_cart'));
        add_action('woocommerce_after_cart', array($this, 'after_cart'));
        
        // Checkout hooks
        add_action('woocommerce_before_checkout_form', array($this, 'before_checkout'));
        add_action('woocommerce_after_checkout_form', array($this, 'after_checkout'));
        
        // Shortcodes
        add_shortcode('slwn_product_filters', array($this, 'filters_shortcode_callback'));
        add_shortcode('slwn_filter_widget', array($this, 'filter_widget_shortcode_callback'));
    }
    
    /**
     * Before product summary
     */
    public function before_product_summary() {
        $options = get_option('slwn_product_filters_options');
        if (isset($options['enable_filters']) && $options['enable_filters']) {
            // Add custom content before product summary
        }
    }
    
    /**
     * After product summary
     */
    public function after_product_summary() {
        $options = get_option('slwn_product_filters_options');
        if (isset($options['enable_filters']) && $options['enable_filters']) {
            // Add custom content after product summary
        }
    }
    
    /**
     * Before shop loop
     */
    public function before_shop_loop() {
        // Add custom content before shop loop
    }
    
    /**
     * After shop loop
     */
    public function after_shop_loop() {
        // Add custom content after shop loop
    }
    
    /**
     * Before cart
     */
    public function before_cart() {
        // Add custom content before cart
    }
    
    /**
     * After cart
     */
    public function after_cart() {
        // Add custom content after cart
    }
    
    /**
     * Before checkout
     */
    public function before_checkout() {
        // Add custom content before checkout
    }
    
    /**
     * After checkout
     */
    public function after_checkout() {
        // Add custom content after checkout
    }
    
    /**
     * Product filters shortcode callback
     */
    public function filters_shortcode_callback($atts) {
        $atts = shortcode_atts(array(
            'type' => 'sidebar',
            'style' => 'default',
            'show_categories' => 'yes',
            'show_attributes' => 'yes',
            'show_price' => 'yes',
        ), $atts, 'slwn_product_filters');
        
        ob_start();
        ?>
        <div class="slwn-product-filters" data-type="<?php echo esc_attr($atts['type']); ?>" data-style="<?php echo esc_attr($atts['style']); ?>">
            <h3><?php _e('Filtruj produkty', 'slwn-product-filters'); ?></h3>
            
            <?php if ($atts['show_categories'] === 'yes'): ?>
                <div class="filter-section categories">
                    <h4><?php _e('Kategorie', 'slwn-product-filters'); ?></h4>
                    <!-- Categories filter will be added here -->
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_attributes'] === 'yes'): ?>
                <div class="filter-section attributes">
                    <h4><?php _e('Atrybuty', 'slwn-product-filters'); ?></h4>
                    <!-- Attributes filter will be added here -->
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_price'] === 'yes'): ?>
                <div class="filter-section price">
                    <h4><?php _e('Cena', 'slwn-product-filters'); ?></h4>
                    <!-- Price filter will be added here -->
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Filter widget shortcode callback
     */
    public function filter_widget_shortcode_callback($atts) {
        $atts = shortcode_atts(array(
            'filter_type' => 'category',
            'style' => 'list',
            'title' => '',
        ), $atts, 'slwn_filter_widget');
        
        ob_start();
        ?>
        <div class="slwn-filter-widget" data-filter-type="<?php echo esc_attr($atts['filter_type']); ?>" data-style="<?php echo esc_attr($atts['style']); ?>">
            <?php if (!empty($atts['title'])): ?>
                <h4><?php echo esc_html($atts['title']); ?></h4>
            <?php endif; ?>
            <!-- Filter widget content will be added here -->
        </div>
        <?php
        return ob_get_clean();
    }
}
