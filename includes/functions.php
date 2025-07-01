<?php
/**
 * Helper functions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin option
 * 
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function slwn_product_filters_get_option($key, $default = null) {
    $options = get_option('slwn_product_filters_options', array());
    return isset($options[$key]) ? $options[$key] : $default;
}

/**
 * Update plugin option
 * 
 * @param string $key
 * @param mixed $value
 * @return bool
 */
function slwn_product_filters_update_option($key, $value) {
    $options = get_option('slwn_product_filters_options', array());
    $options[$key] = $value;
    return update_option('slwn_product_filters_options', $options);
}

/**
 * Check if WooCommerce is active
 * 
 * @return bool
 */
function slwn_product_filters_is_woocommerce_active() {
    return class_exists('WooCommerce');
}

/**
 * Get current product ID
 * 
 * @return int|null
 */
function slwn_product_filters_get_current_product_id() {
    global $product;
    
    if (is_product() && $product) {
        return $product->get_id();
    }
    
    return null;
}

/**
 * Get product by ID
 * 
 * @param int $product_id
 * @return WC_Product|null
 */
function slwn_product_filters_get_product($product_id) {
    if (!slwn_product_filters_is_woocommerce_active()) {
        return null;
    }
    
    return wc_get_product($product_id);
}

/**
 * Format price
 * 
 * @param float $price
 * @return string
 */
function slwn_product_filters_format_price($price) {
    if (!slwn_product_filters_is_woocommerce_active()) {
        return number_format($price, 2);
    }
    
    return wc_price($price);
}

/**
 * Log message
 * 
 * @param string $message
 * @param string $level
 */
function slwn_product_filters_log($message, $level = 'info') {
    if (!slwn_product_filters_is_woocommerce_active()) {
        error_log($message);
        return;
    }
    
    $logger = wc_get_logger();
    $logger->log($level, $message, array('source' => 'slwn-product-filters'));
}

/**
 * Get template
 * 
 * @param string $template_name
 * @param array $args
 * @param string $template_path
 * @param string $default_path
 */
function slwn_product_filters_get_template($template_name, $args = array(), $template_path = '', $default_path = '') {
    if (!empty($args) && is_array($args)) {
        extract($args);
    }
    
    $located = slwn_product_filters_locate_template($template_name, $template_path, $default_path);
    
    if (!file_exists($located)) {
        _doing_it_wrong(__FUNCTION__, sprintf(__('%s nie istnieje.', 'slwn-product-filters'), '<code>' . $located . '</code>'), '1.0.0');
        return;
    }
    
    include $located;
}

/**
 * Locate template
 * 
 * @param string $template_name
 * @param string $template_path
 * @param string $default_path
 * @return string
 */
function slwn_product_filters_locate_template($template_name, $template_path = '', $default_path = '') {
    if (!$template_path) {
        $template_path = 'slwn-product-filters/';
    }
    
    if (!$default_path) {
        $default_path = SLWN_PRODUCT_FILTERS_PATH . 'templates/';
    }
    
    // Look within passed path within the theme
    $template = locate_template(array(
        trailingslashit($template_path) . $template_name,
        $template_name,
    ));
    
    // Get default template
    if (!$template) {
        $template = $default_path . $template_name;
    }
    
    return apply_filters('slwn_product_filters_locate_template', $template, $template_name, $template_path);
}

/**
 * Register product filter widgets
 */
function slwn_register_product_filter_widgets() {
    if (class_exists('SLWN_Product_Filter_Attributes_Widget')) {
        register_widget('SLWN_Product_Filter_Attributes_Widget');
    }
    
    if (class_exists('SLWN_Product_Filter_Categories_Widget')) {
        register_widget('SLWN_Product_Filter_Categories_Widget');
    }
    
    if (class_exists('SLWN_Product_Filter_Buttons_Widget')) {
        register_widget('SLWN_Product_Filter_Buttons_Widget');
    }
    
    if (class_exists('SLWN_Product_Filter_Reset_Widget')) {
        register_widget('SLWN_Product_Filter_Reset_Widget');
    }
    
    if (class_exists('SLWN_Product_Filter_Submit_Widget')) {
        register_widget('SLWN_Product_Filter_Submit_Widget');
    }
}
