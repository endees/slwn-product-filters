<?php
/**
 * Admin page template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('slwn_product_filters_settings');
        do_settings_sections('slwn_product_filters_settings');
        submit_button(__('Zapisz ustawienia', 'slwn-product-filters'));
        ?>
    </form>
    
    <div class="slwn-product-filters-info">
        <h2><?php _e('Informacje o wtyczce', 'slwn-product-filters'); ?></h2>
        <p><?php _e('Wersja:', 'slwn-product-filters'); ?> <?php echo SLWN_PRODUCT_FILTERS_VERSION; ?></p>
        <p><?php _e('Autor:', 'slwn-product-filters'); ?> <a href="https://slawinsky.pl" target="_blank">Maciej Sławiński</a></p>
        <p><?php _e('Ta wtyczka umożliwia filtrowanie produktów WooCommerce po atrybutach i kategoriach.', 'slwn-product-filters'); ?></p>
        
        <h3><?php _e('Shortcodes', 'slwn-product-filters'); ?></h3>
        <p><?php _e('Główny shortcode filtrów:', 'slwn-product-filters'); ?> <code>[slwn_product_filters]</code></p>
        <p><?php _e('Z parametrami:', 'slwn-product-filters'); ?> <code>[slwn_product_filters type="sidebar" style="modern" show_categories="yes" show_attributes="yes" show_price="yes"]</code></p>
        <p><?php _e('Widget filtra:', 'slwn-product-filters'); ?> <code>[slwn_filter_widget filter_type="category" style="list" title="Kategorie"]</code></p>
        
        <h3><?php _e('Dostępne style', 'slwn-product-filters'); ?></h3>
        <ul>
            <li><strong>default</strong> - <?php _e('Standardowy styl', 'slwn-product-filters'); ?></li>
            <li><strong>modern</strong> - <?php _e('Nowoczesny styl', 'slwn-product-filters'); ?></li>
            <li><strong>minimal</strong> - <?php _e('Minimalistyczny styl', 'slwn-product-filters'); ?></li>
            <li><strong>compact</strong> - <?php _e('Kompaktowy styl', 'slwn-product-filters'); ?></li>
        </ul>
        
        <h3><?php _e('Dostępne hooki', 'slwn-product-filters'); ?></h3>
        <ul>
            <li><code>slwn_product_filters_before_init</code> - <?php _e('Przed inicjalizacją wtyczki', 'slwn-product-filters'); ?></li>
            <li><code>slwn_product_filters_after_init</code> - <?php _e('Po inicjalizacji wtyczki', 'slwn-product-filters'); ?></li>
            <li><code>slwn_product_filters_settings_saved</code> - <?php _e('Po zapisie ustawień', 'slwn-product-filters'); ?></li>
            <li><code>slwn_product_filters_before_filter</code> - <?php _e('Przed filtrowaniem produktów', 'slwn-product-filters'); ?></li>
            <li><code>slwn_product_filters_after_filter</code> - <?php _e('Po filtrowaniu produktów', 'slwn-product-filters'); ?></li>
        </ul>
    </div>
</div>

<style>
.slwn-product-filters-info {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-top: 20px;
}

.slwn-product-filters-info h2,
.slwn-product-filters-info h3 {
    margin-top: 0;
}

.slwn-product-filters-info code {
    background: #f1f1f1;
    padding: 2px 4px;
    border-radius: 3px;
}

.slwn-product-filters-info ul {
    padding-left: 20px;
}

.slwn-product-filters-info a {
    color: #0073aa;
    text-decoration: none;
}

.slwn-product-filters-info a:hover {
    text-decoration: underline;
}
</style>
