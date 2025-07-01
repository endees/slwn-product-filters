<?php
/**
 * AJAX class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SLWN_Product_Filters_Ajax {
    
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
        // AJAX actions for logged in users
        add_action('wp_ajax_slwn_filter_products', array($this, 'filter_products_handler'));
        add_action('wp_ajax_slwn_load_more_products', array($this, 'load_more_products_handler'));
        
        // AJAX actions for non-logged in users
        add_action('wp_ajax_nopriv_slwn_filter_products', array($this, 'filter_products_handler'));
        add_action('wp_ajax_nopriv_slwn_load_more_products', array($this, 'load_more_products_handler'));
        
        // Additional AJAX actions
        add_action('wp_ajax_slwn_get_filter_options', array($this, 'get_filter_options_handler'));
        
        // Hooki do obsługi filtrowania produktów WooCommerce (z wc_filters.php)
        add_action('woocommerce_product_query', array($this, 'filter_products_by_attributes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_filter_scripts'));
    }
    
    /**
     * Filter products handler
     */
    public function filter_products_handler() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'slwn_product_filters_nonce')) {
            wp_die(__('Błąd bezpieczeństwa', 'slwn-product-filters'));
        }
        
        // Process filter request
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        
        // Apply filters to query
        $products = $this->get_filtered_products($filters, $page);
        
        $response = array(
            'success' => true,
            'message' => __('Produkty zostały przefiltrowane', 'slwn-product-filters'),
            'data' => array(
                'products_html' => $this->get_filtered_products_html($products),
                'total_products' => $this->get_filtered_products_count($filters),
                'current_page' => $page
            )
        );
        
        wp_send_json($response);
    }
    
    /**
     * Load more products handler
     */
    public function load_more_products_handler() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'slwn_product_filters_nonce')) {
            wp_die(__('Błąd bezpieczeństwa', 'slwn-product-filters'));
        }
        
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        
        $response = array(
            'success' => true,
            'message' => __('Więcej produktów zostało załadowanych', 'slwn-product-filters'),
            'data' => array(
                'products_html' => $this->get_filtered_products_html($filters, $page),
                'has_more' => $this->has_more_products($filters, $page)
            )
        );
        
        wp_send_json($response);
    }
    
    /**
     * Get filter options handler
     */
    public function get_filter_options_handler() {
        // Check user capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Brak uprawnień', 'slwn-product-filters'));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'slwn_product_filters_admin_nonce')) {
            wp_die(__('Błąd bezpieczeństwa', 'slwn-product-filters'));
        }
        
        $filter_type = isset($_POST['filter_type']) ? sanitize_text_field($_POST['filter_type']) : '';
        
        $response = array(
            'success' => true,
            'message' => __('Opcje filtra zostały pobrane', 'slwn-product-filters'),
            'data' => $this->get_filter_options($filter_type)
        );
        
        wp_send_json($response);
    }
    
    /**
     * Get filtered products
     */
    private function get_filtered_products($filters, $page = 1) {
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => get_option('posts_per_page', 12),
            'paged' => $page,
            'meta_query' => array(),
            'tax_query' => array('relation' => 'AND')
        );
        
        // Apply filters based on your logic
        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue;
            }
            
            // Category filter
            if ($key === 'product_cat' && !empty($value)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($value),
                );
            }
            
            // Attribute filters - obsługa nowego formatu pa_
            if (preg_match('/^pa_(.+)$/', $key)) {
                $attribute_name = sanitize_text_field(str_replace('pa_', '', $key));
                $attribute_taxonomy = wc_attribute_taxonomy_name($attribute_name);
                
                if (taxonomy_exists($attribute_taxonomy)) {
                    // Obsługa wielu wartości (checkboxy)
                    $terms = is_array($value) ? array_map('sanitize_text_field', $value) : explode(',', sanitize_text_field($value));
                    $terms = array_filter($terms); // Usuń puste wartości
                    
                    if (!empty($terms)) {
                        $args['tax_query'][] = array(
                            'taxonomy' => $attribute_taxonomy,
                            'field' => 'slug',
                            'terms' => $terms,
                            'operator' => count($terms) > 1 ? 'IN' : 'IN'
                        );
                    }
                }
            }
            
            // Range filters (min/max) - obsługa nowego formatu min_pa_ i max_pa_
            if (preg_match('/^(min|max)_pa_(.+)$/', $key, $matches)) {
                $range_type = $matches[1]; // 'min' or 'max'
                $attribute_name = sanitize_text_field($matches[2]);
                $attribute_taxonomy = wc_attribute_taxonomy_name($attribute_name);
                
                if (taxonomy_exists($attribute_taxonomy)) {
                    // This would need more complex meta query logic for ranges
                    // Implementation depends on how you store numeric values
                }
            }
        }
        
        return new WP_Query($args);
    }
    
    /**
     * Get filtered products HTML
     */
    private function get_filtered_products_html($products_query) {
        if (is_a($products_query, 'WP_Query')) {
            $products = $products_query->posts;
        } else {
            $products = $products_query;
        }
        
        ob_start();
        
        if (!empty($products)) {
            woocommerce_product_loop_start();
            
            foreach ($products as $product_post) {
                $GLOBALS['post'] = $product_post;
                setup_postdata($product_post);
                wc_get_template_part('content', 'product');
            }
            
            woocommerce_product_loop_end();
            wp_reset_postdata();
        } else {
            echo '<p class="woocommerce-info">' . __('Nie znaleziono produktów spełniających kryteria.', 'slwn-product-filters') . '</p>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Get filtered products count
     */
    private function get_filtered_products_count($filters) {
        $products_query = $this->get_filtered_products($filters, 1);
        return $products_query->found_posts;
    }
    
    /**
     * Check if has more products
     */
    private function has_more_products($filters, $page) {
        // Implementation for checking if there are more products
        return false;
    }
    
    /**
     * Get filter options
     */
    private function get_filter_options($filter_type) {
        // Implementation for getting filter options
        return array();
    }
    
    /**
     * Dodanie filtrowania produktów po atrybutach do zapytania WooCommerce
     * Przeniesiona funkcja z wc_filters.php
     */
    public function filter_products_by_attributes($query) {
        // Tylko dla głównego zapytania WooCommerce na front-endzie
        if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy())) {
            
            // Sprawdź wszystkie parametry GET
            foreach ($_GET as $key => $value) {
                if (empty($value)) {
                    continue;
                }
                
                // Filtry atrybutów WooCommerce (pa_)
                if (preg_match('/^pa_(.+)$/', $key)) {
                    $attribute = str_replace('pa_', '', $key);
                    $attribute_taxonomy = wc_attribute_taxonomy_name($attribute);
                    
                    if (taxonomy_exists($attribute_taxonomy)) {
                        $meta_query = $query->get('meta_query', array());
                        $tax_query = $query->get('tax_query', array());
                        
                        // Obsługa wielu wartości oddzielonych przecinkami (checkboxy)
                        $filter_values = array();
                        if (strpos($value, ',') !== false) {
                            $filter_values = array_map('trim', explode(',', $value));
                        } else {
                            $filter_values = array($value);
                        }
                        
                        $tax_query[] = array(
                            'taxonomy' => $attribute_taxonomy,
                            'field'    => 'slug', 
                            'terms'    => $filter_values,
                            'operator' => 'IN'
                        );
                        
                        $query->set('tax_query', $tax_query);
                    }
                }
                
                // Filtr kategorii (sprawdzamy czy parametr product_cat istnieje i nie jest pusty)
                if ($key === 'product_cat' && !empty($value)) {
                    // Obsługa wielu kategorii oddzielonych przecinkami (checkboxy)
                    if (strpos($value, ',') !== false) {
                        $category_values = array_map('trim', explode(',', $value));
                        $tax_query = $query->get('tax_query', array());
                        
                        $tax_query[] = array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'slug',
                            'terms'    => $category_values,
                            'operator' => 'IN'
                        );
                        
                        $query->set('tax_query', $tax_query);
                    }
                    // Dla pojedynczej kategorii WooCommerce automatycznie obsługuje parametr
                }
                
                // Filtry zakresu (min/max) - poprawiona logika dla atrybutów w formacie min_pa_ i max_pa_
                if (preg_match('/^(min|max)_pa_(.+)$/', $key, $matches)) {
                    $range_type = $matches[1]; // 'min' or 'max'
                    $attribute = $matches[2];
                    $attribute_taxonomy = wc_attribute_taxonomy_name($attribute);
                    
                    if (taxonomy_exists($attribute_taxonomy)) {
                        // Pobierz wartość min i max w nowym formacie
                        $min_key = 'min_pa_' . $attribute;
                        $max_key = 'max_pa_' . $attribute;
                        $min_value = isset($_GET[$min_key]) ? floatval($_GET[$min_key]) : null;
                        $max_value = isset($_GET[$max_key]) ? floatval($_GET[$max_key]) : null;
                        
                        if ($range_type === 'min' && $min_value !== null && $max_value !== null && 
                            (!isset($processed_ranges) || !in_array($attribute, $processed_ranges))) {
                            
                            // Znajdź wszystkie terminy w zakresie
                            $terms_in_range = $this->get_terms_in_range($attribute_taxonomy, $min_value, $max_value);
                            
                            if (!empty($terms_in_range)) {
                                $tax_query = $query->get('tax_query', array());
                                
                                $tax_query[] = array(
                                    'taxonomy' => $attribute_taxonomy,
                                    'field'    => 'slug',
                                    'terms'    => $terms_in_range,
                                    'operator' => 'IN'
                                );
                                
                                $query->set('tax_query', $tax_query);
                            } else {
                                // Jeśli brak terminów w zakresie, ustaw niemożliwe zapytanie
                                $query->set('post__in', array(0));
                            }
                            
                            // Zaznacz atrybut jako przetworzony
                            if (!isset($processed_ranges)) {
                                $processed_ranges = array();
                            }
                            $processed_ranges[] = $attribute;
                        }
                    }
                }
            }
        }
        
        return $query;
    }
    
    /**
     * Dodaj wymagane skrypty
     * Przeniesiona funkcja z wc_filters.php
     */
    public function enqueue_filter_scripts() {
        if (is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() || is_search()) {
            // jQuery UI dla suwaków zakresu
            wp_enqueue_script('jquery-ui-slider');
            wp_enqueue_style('jquery-ui-style', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
            
            // Dodanie zmiennej globalnej z URL sklepu
            wp_add_inline_script('jquery', '
                var woocommerceShopPageUrl = "' . esc_url(wc_get_page_permalink('shop')) . '";
            ');
            
            // noUiSlider dla zaawansowanych suwaków
            wp_enqueue_script('nouislider', 'https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.js', array('jquery'), '15.7.0', true);
            wp_enqueue_style('nouislider', 'https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.css', array(), '15.7.0');
        }
    }
    
    /**
     * Znajdź terminy w podanym zakresie numerycznym
     */
    private function get_terms_in_range($taxonomy, $min_value, $max_value) {
        $all_terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'fields' => 'all'
        ]);
        
        if (empty($all_terms) || is_wp_error($all_terms)) {
            return array();
        }
        
        $terms_in_range = array();
        foreach ($all_terms as $term) {
            $term_value = $term->name;
            
            // Sprawdź czy nazwa terminu jest numeryczna
            if (is_numeric($term_value)) {
                $numeric_value = floatval($term_value);
                
                // Sprawdź czy wartość mieści się w zakresie
                if ($numeric_value >= $min_value && $numeric_value <= $max_value) {
                    $terms_in_range[] = $term->slug;
                }
            } else {
                // Dla nienu merycznych terminów, sprawdź czy zawierają liczby
                preg_match_all('/\d+(?:\.\d+)?/', $term_value, $matches);
                if (!empty($matches[0])) {
                    foreach ($matches[0] as $match) {
                        $numeric_value = floatval($match);
                        if ($numeric_value >= $min_value && $numeric_value <= $max_value) {
                            $terms_in_range[] = $term->slug;
                            break; // Jeden match wystarczy
                        }
                    }
                }
            }
        }
        
        return $terms_in_range;
    }
}
