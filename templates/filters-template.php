<?php
/**
 * Template for product filters
 * Template zgodny z nową strukturą kodu przeniesionych z motywu
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$form_id = 'product-filters-form';
?>

<div class="slwn-product-filters-wrapper">
    <form id="<?php echo esc_attr($form_id); ?>" method="GET" action="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="slwn-product-filters-form">
        
        <?php
        // Wyświetl ukryte pola dla aktualnej strony
        if (is_product_category()) {
            $current_category = get_queried_object();
            if ($current_category) {
                echo '<input type="hidden" name="product_cat" value="' . esc_attr($current_category->slug) . '">';
            }
        }
        
        if (is_product_tag()) {
            $current_tag = get_queried_object();
            if ($current_tag) {
                echo '<input type="hidden" name="product_tag" value="' . esc_attr($current_tag->slug) . '">';
            }
        }
        
        // Pola wyszukiwania
        if (is_search() && get_search_query()) {
            echo '<input type="hidden" name="s" value="' . esc_attr(get_search_query()) . '">';
        }
        
        // Pozostałe parametry GET (oprócz filtrów)
        if (!empty($_GET)) {
            foreach ($_GET as $key => $value) {
                if (!in_array($key, ['filter_', 'product_cat', 'product_tag', 's']) && strpos($key, 'filter_') !== 0) {
                    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                }
            }
        }
        ?>
        
        <div class="slwn-filters-container">
            <?php
            // Miejsce na widgety filtrów
            if (is_active_sidebar('shop-filters')) {
                dynamic_sidebar('shop-filters');
            } else {
                // Domyślne filtry gdy brak widgetów
                ?>
                <div class="slwn-default-filters">
                    <p><?php _e('Aby wyświetlić filtry, dodaj widgety "Filtr produktów" do obszaru widgetów "Shop Filters".', 'slwn-product-filters'); ?></p>
                </div>
                <?php
            }
            ?>
        </div>
        
    </form>
</div>

<script>
// Dodatkowa obsługa formularza filtrów
jQuery(document).ready(function($) {
    // Obsługa zmiany filtrów
    $('#<?php echo esc_js($form_id); ?>').on('change', 'select, input[type="radio"], input[type="checkbox"]', function() {
        // Możemy tutaj dodać dodatkową logikę
    });
});
</script>
