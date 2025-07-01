<?php
/**
 * SLWN Product Filters Widgets
 * Przeniesiona logika z wc_filters.php z motywu
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widget filtra atrybutów produktów (z wc_filters.php)
 */
class SLWN_Product_Filter_Attributes_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'slwn_product_filter_attributes',
            'SLWN Filtr Atrybutów',
            array(
                'description' => 'Wyświetla filtry atrybutów produktów',
                'customize_selective_refresh' => true,
            )
        );
    }

    public function widget($args, $instance) {
        if (!is_shop() && !is_product_category() && !is_product_tag() && !is_search()) {
            return;
        }

        $title = !empty($instance['title']) ? $instance['title'] : '';
        $filter_type = !empty($instance['filter_type']) ? $instance['filter_type'] : 'attribute';
        $attribute = !empty($instance['attribute']) ? $instance['attribute'] : '';
        $parent_category = !empty($instance['parent_category']) ? (int)$instance['parent_category'] : 0;
        $placeholder = !empty($instance['placeholder']) ? $instance['placeholder'] : 'Wszystko';
        $display_type = !empty($instance['display_type']) ? $instance['display_type'] : 'select';
        $min_label = !empty($instance['min_label']) ? $instance['min_label'] : 'Min';
        $max_label = !empty($instance['max_label']) ? $instance['max_label'] : 'Max';
        $unit = !empty($instance['unit']) ? $instance['unit'] : '';

        if ($filter_type === 'attribute' && empty($attribute)) {
            return;
        }

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }

        // Dodaj formularz wrapper dla widgetu
        $form_action = $this->get_current_page_url();
        echo '<form method="GET" action="' . esc_url($form_action) . '" class="slwn-product-filter-widget-form">';
        
        // Zachowaj aktualne parametry
        $this->render_hidden_fields();

        if ($filter_type === 'attribute') {
            $this->render_attribute_filter($attribute, $display_type, $placeholder, $min_label, $max_label, $unit);
        } elseif ($filter_type === 'category') {
            $this->render_category_filter($parent_category, $display_type, $placeholder);
        }
        
        echo '</form>';

        echo $args['after_widget'];
    }

    private function render_attribute_filter($attribute, $display_type, $placeholder, $min_label, $max_label, $unit) {
        $attribute_taxonomy = wc_attribute_taxonomy_name($attribute);
        $current_filter = isset($_GET['filter_' . $attribute]) ? wc_clean(wp_unslash($_GET['filter_' . $attribute])) : '';
        $min_value = isset($_GET['filter_' . $attribute . '_min']) ? wc_clean(wp_unslash($_GET['filter_' . $attribute . '_min'])) : '';
        $max_value = isset($_GET['filter_' . $attribute . '_max']) ? wc_clean(wp_unslash($_GET['filter_' . $attribute . '_max'])) : '';

        if ($display_type === 'range') {
            $this->render_range_filter($attribute, $attribute_taxonomy, $min_value, $max_value, $min_label, $max_label, $unit);
        } else {
            $this->render_select_or_buttons_filter($attribute, $attribute_taxonomy, $display_type, $placeholder, $current_filter);
        }
    }

    private function render_range_filter($attribute, $attribute_taxonomy, $min_value, $max_value, $min_label, $max_label, $unit) {
        $range_values = $this->get_attribute_range_values($attribute_taxonomy);
        $min = $range_values['min'];
        $max = $range_values['max'];
        
        if ($min === false || $max === false) {
            echo '<p>Brak dostępnych wartości numerycznych dla tego atrybutu.</p>';
            return;
        }
        
        $current_min = !empty($min_value) ? $min_value : $min;
        $current_max = !empty($max_value) ? $max_value : $max;
        $slider_id = 'range-slider-' . $attribute . '-' . mt_rand(1000, 9999);
        ?>
        <div class="slwn-product-filter slwn-product-filter--range">
            <div class="slwn-range-slider-container">
                <div class="slwn-range-values">
                    <span class="min-value"><?php echo esc_html($min_label); ?>: <span class="value"><?php echo esc_html($current_min . $unit); ?></span></span>
                    <span class="max-value"><?php echo esc_html($max_label); ?>: <span class="value"><?php echo esc_html($current_max . $unit); ?></span></span>
                </div>
                <div id="<?php echo esc_attr($slider_id); ?>" class="slwn-range-slider" 
                     data-min="<?php echo esc_attr($min); ?>" 
                     data-max="<?php echo esc_attr($max); ?>"
                     data-current-min="<?php echo esc_attr($current_min); ?>"
                     data-current-max="<?php echo esc_attr($current_max); ?>"
                     data-unit="<?php echo esc_attr($unit); ?>">
                </div>
                <input type="hidden" name="filter_<?php echo esc_attr($attribute); ?>_min" id="<?php echo esc_attr($slider_id); ?>-min" value="<?php echo esc_attr($current_min); ?>">
                <input type="hidden" name="filter_<?php echo esc_attr($attribute); ?>_max" id="<?php echo esc_attr($slider_id); ?>-max" value="<?php echo esc_attr($current_max); ?>">
            </div>
        </div>
        <?php
        $this->add_range_slider_script($slider_id, $min, $max, $current_min, $current_max, $unit, $attribute);
    }

    private function render_select_or_buttons_filter($attribute, $attribute_taxonomy, $display_type, $placeholder, $current_filter) {
        $terms = get_terms([
            'taxonomy' => $attribute_taxonomy,
            'hide_empty' => true,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            echo '<p>Brak dostępnych wartości dla tego atrybutu.</p>';
            return;
        }

        // Dla checkboxów, current_filter może być tablicą wartości oddzielonych przecinkami
        $current_values = [];
        if ($display_type === 'checkbox' && !empty($current_filter)) {
            $current_values = explode(',', $current_filter);
            $current_values = array_map('trim', $current_values);
        }
        ?>
        <div class="slwn-product-filter slwn-product-filter--<?php echo esc_attr($attribute); ?> slwn-product-filter--<?php echo esc_attr($display_type); ?>">
            <?php if ($display_type === 'select') : ?>
                <select name="filter_<?php echo esc_attr($attribute); ?>" class="slwn-product-filter__select filter-control">
                    <option value=""><?php echo esc_html($placeholder); ?></option>
                    <?php foreach ($terms as $term) : ?>
                        <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($current_filter, $term->slug); ?>>
                            <?php echo esc_html($term->name); ?> (<?php echo $term->count; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($display_type === 'checkbox') : ?>
                <div class="slwn-product-filter__checkboxes">
                    <?php foreach ($terms as $term) : ?>
                        <label class="slwn-filter-option slwn-filter-option--checkbox">
                            <input type="checkbox" name="filter_<?php echo esc_attr($attribute); ?>[]" value="<?php echo esc_attr($term->slug); ?>" 
                                   <?php checked(in_array($term->slug, $current_values), true); ?> 
                                   class="slwn-product-filter__checkbox filter-control"
                                   data-attribute="<?php echo esc_attr($attribute); ?>">
                            <span class="slwn-filter-checkbox-label"><?php echo esc_html($term->name); ?> (<?php echo $term->count; ?>)</span>
                        </label>
                    <?php endforeach; ?>
                    <!-- Hidden field do przechowywania wartości dla URL -->
                    <input type="hidden" name="filter_<?php echo esc_attr($attribute); ?>" value="<?php echo esc_attr($current_filter); ?>" class="checkbox-values-holder">
                </div>
            <?php else : // buttons ?>
                <div class="slwn-product-filter__buttons">
                    <?php foreach ($terms as $term) : ?>
                        <label class="slwn-filter-option">
                            <input type="radio" name="filter_<?php echo esc_attr($attribute); ?>" value="<?php echo esc_attr($term->slug); ?>" 
                                   <?php checked($current_filter, $term->slug); ?> 
                                   class="slwn-product-filter__radio">
                            <span class="slwn-filter-button"><?php echo esc_html($term->name); ?> (<?php echo $term->count; ?>)</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_category_filter($parent_category, $display_type, $placeholder) {
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => $parent_category,
        ]);

        if (is_wp_error($categories) || empty($categories)) {
            return;
        }

        $current_category = get_queried_object();
        $current_cat_id = is_product_category() ? $current_category->term_id : 0;
        $current_cat_slug = is_product_category() ? $current_category->slug : '';
        
        // Obsługa wielu kategorii dla checkboxów
        $current_cat_filter = isset($_GET['product_cat']) ? wc_clean(wp_unslash($_GET['product_cat'])) : '';
        $current_cat_values = [];
        if ($display_type === 'checkbox' && !empty($current_cat_filter)) {
            $current_cat_values = explode(',', $current_cat_filter);
            $current_cat_values = array_map('trim', $current_cat_values);
        }
        ?>
        <div class="slwn-product-filter slwn-product-filter--category slwn-product-filter--<?php echo esc_attr($display_type); ?>">
            <?php if ($display_type === 'select') : ?>
                <select name="product_cat" class="slwn-product-filter__select" data-shop-url="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">
                    <option value="" data-url="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php echo esc_html($placeholder); ?></option>
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?php echo esc_attr($category->slug); ?>" 
                                data-url="<?php echo esc_url(get_term_link($category)); ?>"
                                <?php selected($current_cat_id, $category->term_id); ?>>
                            <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($display_type === 'checkbox') : ?>
                <div class="slwn-product-filter__checkboxes">
                    <?php foreach ($categories as $category) : ?>
                        <label class="slwn-filter-option slwn-filter-option--checkbox">
                            <input type="checkbox" name="product_cat[]" value="<?php echo esc_attr($category->slug); ?>"
                                   data-url="<?php echo esc_url(get_term_link($category)); ?>"
                                   <?php checked(in_array($category->slug, $current_cat_values) || $current_cat_id === $category->term_id, true); ?>
                                   class="slwn-product-filter__checkbox filter-control">
                            <span class="slwn-filter-checkbox-label"><?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)</span>
                        </label>
                    <?php endforeach; ?>
                    <!-- Hidden field do przechowywania wartości dla URL -->
                    <input type="hidden" name="product_cat" value="<?php echo esc_attr($current_cat_filter ?: $current_cat_slug); ?>" class="checkbox-values-holder">
                </div>
            <?php else : // buttons ?>
                <div class="slwn-product-filter__buttons">
                    <?php foreach ($categories as $category) : ?>
                        <label class="slwn-filter-option">
                            <input type="radio" name="product_cat" value="<?php echo esc_attr($category->slug); ?>"
                                   data-url="<?php echo esc_url(get_term_link($category)); ?>"
                                   <?php checked($current_cat_id, $category->term_id); ?>
                                   class="slwn-product-filter__radio">
                            <span class="slwn-filter-button"><?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function get_attribute_range_values($attribute_taxonomy) {
        // Sprawdź czy taksonomia istnieje
        if (!taxonomy_exists($attribute_taxonomy)) {
            return ['min' => false, 'max' => false];
        }
        
        // Pobierz wszystkie terminy
        $all_terms = get_terms([
            'taxonomy' => $attribute_taxonomy,
            'hide_empty' => false,
            'fields' => 'names'
        ]);
        
        if (empty($all_terms) || is_wp_error($all_terms)) {
            return ['min' => false, 'max' => false];
        }
        
        // Filtruj numeryczne wartości
        $numeric_values = [];
        foreach ($all_terms as $term_name) {
            if (is_numeric($term_name)) {
                $numeric_values[] = floatval($term_name);
            }
        }
        
        if (empty($numeric_values)) {
            return ['min' => false, 'max' => false];
        }
        
        return [
            'min' => min($numeric_values),
            'max' => max($numeric_values)
        ];
    }

    private function add_range_slider_script($slider_id, $min, $max, $current_min, $current_max, $unit, $attribute) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            if (typeof noUiSlider !== 'undefined') {
                var slider = document.getElementById('<?php echo esc_js($slider_id); ?>');
                if (slider) {
                    noUiSlider.create(slider, {
                        start: [<?php echo esc_js($current_min); ?>, <?php echo esc_js($current_max); ?>],
                        connect: true,
                        range: {
                            'min': <?php echo esc_js($min); ?>,
                            'max': <?php echo esc_js($max); ?>
                        },
                        step: 1,
                        format: {
                            to: function(value) { return Math.round(value); },
                            from: function(value) { return Math.round(value); }
                        }
                    });

                    slider.noUiSlider.on('update', function(values) {
                        var minValue = values[0];
                        var maxValue = values[1];
                        var $minInput = $('#<?php echo esc_js($slider_id); ?>-min');
                        var $maxInput = $('#<?php echo esc_js($slider_id); ?>-max');
                        
                        $minInput.val(minValue);
                        $maxInput.val(maxValue);
                        
                        // Trigger change event dla localStorage
                        $minInput.trigger('change');
                        $maxInput.trigger('change');
                        
                        $('#<?php echo esc_js($slider_id); ?>').closest('.slwn-range-slider-container').find('.min-value .value').text(minValue + '<?php echo esc_js($unit); ?>');
                        $('#<?php echo esc_js($slider_id); ?>').closest('.slwn-range-slider-container').find('.max-value .value').text(maxValue + '<?php echo esc_js($unit); ?>');
                        
                        // Zapisz w localStorage ale NIE submituj automatycznie
                        try {
                            // Sprawdź czy nie jesteśmy w trakcie przywracania filtrów
                            if (typeof window.isRestoringFilters !== 'undefined' && window.isRestoringFilters) {
                                return;
                            }
                            
                            var filters = JSON.parse(localStorage.getItem('product_filters') || '{}');
                            filters['filter_<?php echo esc_js($attribute); ?>_min'] = minValue;
                            filters['filter_<?php echo esc_js($attribute); ?>_max'] = maxValue;
                            localStorage.setItem('product_filters', JSON.stringify(filters));
                            
                            // NATYCHMIAST aktualizuj URL
                            if (typeof window.slwnUpdateURLWithFilters === 'function') {
                                window.slwnUpdateURLWithFilters(filters);
                            }
                        } catch (e) {
                            // Błąd podczas zapisywania filtrów
                        }
                    });
                }
            }
        });
        </script>
        <?php
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $filter_type = !empty($instance['filter_type']) ? $instance['filter_type'] : 'attribute';
        $attribute = !empty($instance['attribute']) ? $instance['attribute'] : '';
        $parent_category = !empty($instance['parent_category']) ? (int)$instance['parent_category'] : 0;
        $placeholder = !empty($instance['placeholder']) ? $instance['placeholder'] : 'Wszystko';
        $display_type = !empty($instance['display_type']) ? $instance['display_type'] : 'select';
        $min_label = !empty($instance['min_label']) ? $instance['min_label'] : 'Min';
        $max_label = !empty($instance['max_label']) ? $instance['max_label'] : 'Max';
        $unit = !empty($instance['unit']) ? $instance['unit'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Tytuł:'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('filter_type')); ?>">Typ filtra:</label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('filter_type')); ?>" name="<?php echo esc_attr($this->get_field_name('filter_type')); ?>">
                <option value="attribute" <?php selected($filter_type, 'attribute'); ?>>Atrybut</option>
                <option value="category" <?php selected($filter_type, 'category'); ?>>Kategoria</option>
            </select>
        </p>
        <p class="attribute-field" <?php echo $filter_type !== 'attribute' ? 'style="display:none;"' : ''; ?>>
            <label for="<?php echo esc_attr($this->get_field_id('attribute')); ?>">Atrybut:</label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('attribute')); ?>" name="<?php echo esc_attr($this->get_field_name('attribute')); ?>">
                <option value="">Wybierz atrybut</option>
                <?php
                $attributes = wc_get_attribute_taxonomies();
                foreach ($attributes as $attr) {
                    echo '<option value="' . esc_attr($attr->attribute_name) . '"' . selected($attribute, $attr->attribute_name, false) . '>' . esc_html($attr->attribute_label) . '</option>';
                }
                ?>
            </select>
        </p>
        <p class="category-field" <?php echo $filter_type !== 'category' ? 'style="display:none;"' : ''; ?>>
            <label for="<?php echo esc_attr($this->get_field_id('parent_category')); ?>">Kategoria nadrzędna:</label>
            <?php
            wp_dropdown_categories(array(
                'show_option_all' => 'Wszystkie główne kategorie',
                'option_none_value' => 0,
                'taxonomy' => 'product_cat',
                'name' => $this->get_field_name('parent_category'),
                'id' => $this->get_field_id('parent_category'),
                'class' => 'widefat',
                'selected' => $parent_category,
                'hide_empty' => false,
            ));
            ?>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('display_type')); ?>">Sposób wyświetlania:</label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('display_type')); ?>" name="<?php echo esc_attr($this->get_field_name('display_type')); ?>">
                <option value="select" <?php selected($display_type, 'select'); ?>>Lista rozwijana</option>
                <option value="buttons" <?php selected($display_type, 'buttons'); ?>>Przyciski</option>
                <option value="checkbox" <?php selected($display_type, 'checkbox'); ?>>Checkboxy (wielokrotny wybór)</option>
                <option value="range" <?php selected($display_type, 'range'); ?>>Suwak zakresu</option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('placeholder')); ?>">Tekst domyślny:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('placeholder')); ?>" name="<?php echo esc_attr($this->get_field_name('placeholder')); ?>" type="text" value="<?php echo esc_attr($placeholder); ?>">
        </p>
        <div class="range-fields" <?php echo $display_type !== 'range' ? 'style="display:none;"' : ''; ?>>
            <h4>⚙️ Ustawienia suwaka zakresu</h4>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('min_label')); ?>">Etykieta minimum:</label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('min_label')); ?>" name="<?php echo esc_attr($this->get_field_name('min_label')); ?>" type="text" value="<?php echo esc_attr($min_label); ?>">
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('max_label')); ?>">Etykieta maksimum:</label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('max_label')); ?>" name="<?php echo esc_attr($this->get_field_name('max_label')); ?>" type="text" value="<?php echo esc_attr($max_label); ?>">
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('unit')); ?>">Jednostka:</label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('unit')); ?>" name="<?php echo esc_attr($this->get_field_name('unit')); ?>" type="text" value="<?php echo esc_attr($unit); ?>" placeholder="np. kg, m, szt">
            </p>
        </div>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['filter_type'] = (!empty($new_instance['filter_type'])) ? sanitize_text_field($new_instance['filter_type']) : 'attribute';
        $instance['attribute'] = (!empty($new_instance['attribute'])) ? sanitize_text_field($new_instance['attribute']) : '';
        $instance['parent_category'] = (!empty($new_instance['parent_category'])) ? (int)$new_instance['parent_category'] : 0;
        $instance['placeholder'] = (!empty($new_instance['placeholder'])) ? sanitize_text_field($new_instance['placeholder']) : 'Wszystko';
        $instance['display_type'] = (!empty($new_instance['display_type'])) ? sanitize_text_field($new_instance['display_type']) : 'select';
        $instance['min_label'] = (!empty($new_instance['min_label'])) ? sanitize_text_field($new_instance['min_label']) : 'Min';
        $instance['max_label'] = (!empty($new_instance['max_label'])) ? sanitize_text_field($new_instance['max_label']) : 'Max';
        $instance['unit'] = (!empty($new_instance['unit'])) ? sanitize_text_field($new_instance['unit']) : '';

        return $instance;
    }

    /**
     * Get current page URL for form action
     */
    private function get_current_page_url() {
        if (is_shop()) {
            return wc_get_page_permalink('shop');
        } elseif (is_product_category()) {
            return get_term_link(get_queried_object());
        } elseif (is_product_tag()) {
            return get_term_link(get_queried_object());
        } elseif (is_search()) {
            return home_url('/');
        }
        return get_permalink();
    }

    /**
     * Render hidden fields to preserve current state
     */
    private function render_hidden_fields() {
        // Zachowaj tag produktu
        if (is_product_tag()) {
            $current_tag = get_queried_object();
            if ($current_tag) {
                echo '<input type="hidden" name="product_tag" value="' . esc_attr($current_tag->slug) . '">';
            }
        }
        
        // Zachowaj zapytanie wyszukiwania
        if (is_search() && get_search_query()) {
            echo '<input type="hidden" name="s" value="' . esc_attr(get_search_query()) . '">';
        }
        
        // Zachowaj inne filtry (nie modyfikuj kategorii)
        if (!empty($_GET)) {
            foreach ($_GET as $key => $value) {
                if (!in_array($key, ['product_cat', 'product_tag', 's']) && 
                    !empty($value)) {
                    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                }
            }
        }
    }
}

/**
 * Widget przycisków filtra
 */
class SLWN_Product_Filter_Buttons_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'slwn_product_filter_buttons',
            'SLWN Przyciski Filtra',
            array(
                'description' => 'Wyświetla przyciski filtruj i resetuj dla filtrów produktów',
                'customize_selective_refresh' => true,
            )
        );
    }

    public function widget($args, $instance) {
        if (!is_shop() && !is_product_category() && !is_product_tag() && !is_search()) {
            return;
        }

        $title = !empty($instance['title']) ? $instance['title'] : '';
        $filter_text = !empty($instance['filter_text']) ? $instance['filter_text'] : 'Filtruj';
        $reset_text = !empty($instance['reset_text']) ? $instance['reset_text'] : 'Resetuj';
        $show_filter = !empty($instance['show_filter']) ? true : false;
        $show_reset = !empty($instance['show_reset']) ? true : false;

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }

        $reset_url = is_shop() ? wc_get_page_permalink('shop') : 
                     (is_product_category() ? wc_get_page_permalink('shop') : 
                     get_permalink());
        ?>
        <div class="slwn-filters-buttons">
            <?php if ($show_filter) : ?>
                <button type="button" class="slwn-filters-buttons__filter" onclick="slwnSubmitAllFilters()"><?php echo esc_html($filter_text); ?></button>
            <?php endif; ?>
            
            <?php if ($show_reset) : ?>
                <button type="button" class="slwn-filters-buttons__reset" onclick="slwnResetAllFilters('<?php echo esc_url($reset_url); ?>')"><?php echo esc_html($reset_text); ?></button>
            <?php endif; ?>
        </div>

        <script>
        // Funkcja submit filtrów - zbiera filtry z formularzy i odświeża stronę
        window.slwnSubmitAllFilters = function() {
            // KROK 1: Najpierw zaktualizuj wszystkie ukryte pola checkboxów na podstawie aktualnego stanu
            document.querySelectorAll('.checkbox-values-holder').forEach(function(hiddenField) {
                const fieldName = hiddenField.name;
                const checkboxName = fieldName + '[]';
                const checkedBoxes = document.querySelectorAll('input[name="' + checkboxName + '"]:checked');
                
                if (checkedBoxes.length > 0) {
                    const values = Array.from(checkedBoxes).map(cb => cb.value);
                    hiddenField.value = values.join(',');
                } else {
                    hiddenField.value = ''; // Wyczyść jeśli nic nie zaznaczone
                }
            });
            
            // KROK 2: Stwórz nowy formularz do submitu
            const submitForm = document.createElement('form');
            submitForm.method = 'GET';
            submitForm.action = '<?php echo esc_url(wc_get_page_permalink('shop')); ?>';
            
            // KROK 3: Zbierz WSZYSTKIE możliwe filtry (nawet puste) aby wyczyścić URL
            const allFilterNames = new Set();
            
            // Zbierz nazwy wszystkich filtrów z widgetów
            document.querySelectorAll('.slwn-product-filter-widget-form input, .slwn-product-filter-widget-form select').forEach(function(input) {
                if (!input.name) return;
                
                let filterName = input.name;
                if (filterName.indexOf('filter_') === 0) {
                    // Dla checkboxów usuń [] z nazwy
                    if (filterName.endsWith('[]')) {
                        filterName = filterName.replace('[]', '');
                    }
                    allFilterNames.add(filterName);
                }
                
                // Dodaj też product_cat jeśli istnieje
                if (filterName === 'product_cat' || filterName === 'product_cat[]') {
                    allFilterNames.add('product_cat');
                }
            });
            
            // KROK 4: Dla każdego filtru, sprawdź czy ma wartość, jeśli nie - dodaj pustą wartość
            allFilterNames.forEach(function(filterName) {
                let hasValue = false;
                let filterValue = '';
                
                // Sprawdź checkboxy
                if (document.querySelector('input[name="' + filterName + '[]"]')) {
                    const checkedBoxes = document.querySelectorAll('input[name="' + filterName + '[]"]:checked');
                    if (checkedBoxes.length > 0) {
                        const values = Array.from(checkedBoxes).map(cb => cb.value);
                        filterValue = values.join(',');
                        hasValue = true;
                    }
                }
                // Sprawdź radio
                else if (document.querySelector('input[name="' + filterName + '"][type="radio"]')) {
                    const checkedRadio = document.querySelector('input[name="' + filterName + '"][type="radio"]:checked');
                    if (checkedRadio && checkedRadio.value) {
                        filterValue = checkedRadio.value;
                        hasValue = true;
                    }
                }
                // Sprawdź select
                else if (document.querySelector('select[name="' + filterName + '"]')) {
                    const selectElement = document.querySelector('select[name="' + filterName + '"]');
                    if (selectElement && selectElement.value) {
                        filterValue = selectElement.value;
                        hasValue = true;
                    }
                }
                // Sprawdź hidden (range sliders)
                else if (document.querySelector('input[name="' + filterName + '"][type="hidden"]')) {
                    const hiddenElement = document.querySelector('input[name="' + filterName + '"][type="hidden"]');
                    if (hiddenElement && hiddenElement.value && !hiddenElement.classList.contains('checkbox-values-holder')) {
                        filterValue = hiddenElement.value;
                        hasValue = true;
                    }
                }
                
                // Dodaj filtr do formularza (nawet jeśli pusty)
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = filterName;
                hiddenInput.value = filterValue;
                submitForm.appendChild(hiddenInput);
            });
            
            // KROK 5: Dodaj formularz do DOM i wyślij
            document.body.appendChild(submitForm);
            submitForm.submit();
        };
        
        window.slwnResetAllFilters = function(resetUrl) {
            localStorage.removeItem('product_filters');
            window.location.href = resetUrl;
        };
        </script>
        <?php

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $filter_text = !empty($instance['filter_text']) ? $instance['filter_text'] : 'Filtruj';
        $reset_text = !empty($instance['reset_text']) ? $instance['reset_text'] : 'Resetuj';
        $show_filter = !empty($instance['show_filter']) ? true : false;
        $show_reset = !empty($instance['show_reset']) ? true : false;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Tytuł:'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_filter')); ?>" name="<?php echo esc_attr($this->get_field_name('show_filter')); ?>" <?php checked($show_filter); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_filter')); ?>">Pokaż przycisk filtruj</label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('filter_text')); ?>">Tekst przycisku filtruj:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('filter_text')); ?>" name="<?php echo esc_attr($this->get_field_name('filter_text')); ?>" type="text" value="<?php echo esc_attr($filter_text); ?>">
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_reset')); ?>" name="<?php echo esc_attr($this->get_field_name('show_reset')); ?>" <?php checked($show_reset); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_reset')); ?>">Pokaż przycisk resetuj</label>
        </p>   
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('reset_text')); ?>">Tekst przycisku resetuj:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('reset_text')); ?>" name="<?php echo esc_attr($this->get_field_name('reset_text')); ?>" type="text" value="<?php echo esc_attr($reset_text); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['filter_text'] = (!empty($new_instance['filter_text'])) ? sanitize_text_field($new_instance['filter_text']) : 'Filtruj';
        $instance['reset_text'] = (!empty($new_instance['reset_text'])) ? sanitize_text_field($new_instance['reset_text']) : 'Resetuj';
        $instance['show_filter'] = (!empty($new_instance['show_filter'])) ? true : false;
        $instance['show_reset'] = (!empty($new_instance['show_reset'])) ? true : false;

        return $instance;
    }
}

/**
 * Widget kategorii produktów
 */
class SLWN_Product_Filter_Categories_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'slwn_product_filter_categories',
            'SLWN Filtr Kategorii',
            array(
                'description' => 'Wyświetla filtr kategorii produktów',
                'customize_selective_refresh' => true,
            )
        );
    }

    public function widget($args, $instance) {
        if (!is_shop() && !is_product_category() && !is_product_tag() && !is_search()) {
            return;
        }

        $title = !empty($instance['title']) ? $instance['title'] : '';
        $parent_category = !empty($instance['parent_category']) ? (int)$instance['parent_category'] : 0;
        $placeholder = !empty($instance['placeholder']) ? $instance['placeholder'] : 'Wszystkie kategorie';

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }

        // Dodaj formularz wrapper dla widgetu
        $form_action = $this->get_current_page_url();
        echo '<form method="GET" action="' . esc_url($form_action) . '" class="slwn-product-filter-widget-form">';
        
        // Zachowaj aktualne parametry (bez product_cat)
        $this->render_hidden_fields();

        $this->render_category_filter($parent_category, $placeholder);
        
        echo '</form>';

        echo $args['after_widget'];
    }

    private function render_category_filter($parent_category, $placeholder) {
        $current_category = get_query_var('product_cat');
        
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => $parent_category
        ));

        if (!empty($categories) && !is_wp_error($categories)) {
            echo '<div class="wc-filter-item">';
            echo '<select name="product_cat" class="wc-filter-select" data-filter="category">';
            echo '<option value="">' . esc_html($placeholder) . '</option>';
            
            foreach ($categories as $category) {
                $selected = ($current_category === $category->slug) ? 'selected' : '';
                echo '<option value="' . esc_attr($category->slug) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
            }
            
            echo '</select>';
            echo '</div>';
        }
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $parent_category = !empty($instance['parent_category']) ? $instance['parent_category'] : 0;
        $placeholder = !empty($instance['placeholder']) ? $instance['placeholder'] : 'Wszystkie kategorie';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Tytuł:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('parent_category')); ?>">Kategoria nadrzędna:</label>
            <?php
            wp_dropdown_categories(array(
                'taxonomy' => 'product_cat',
                'name' => $this->get_field_name('parent_category'),
                'id' => $this->get_field_id('parent_category'),
                'selected' => $parent_category,
                'show_option_all' => 'Wszystkie kategorie',
                'hierarchical' => true,
                'class' => 'widefat'
            ));
            ?>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('placeholder')); ?>">Tekst placeholder:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('placeholder')); ?>" name="<?php echo esc_attr($this->get_field_name('placeholder')); ?>" type="text" value="<?php echo esc_attr($placeholder); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['parent_category'] = (!empty($new_instance['parent_category'])) ? (int)$new_instance['parent_category'] : 0;
        $instance['placeholder'] = (!empty($new_instance['placeholder'])) ? sanitize_text_field($new_instance['placeholder']) : 'Wszystkie kategorie';

        return $instance;
    }

    /**
     * Get current page URL for form action
     */
    private function get_current_page_url() {
        if (is_shop()) {
            return wc_get_page_permalink('shop');
        } elseif (is_product_category()) {
            return wc_get_page_permalink('shop'); // Redirect to shop for category filter
        } elseif (is_product_tag()) {
            return get_term_link(get_queried_object());
        } elseif (is_search()) {
            return home_url('/');
        }
        return get_permalink();
    }

    /**
     * Render hidden fields to preserve current state
     */
    private function render_hidden_fields() {
        // Zachowaj tag produktu
        if (is_product_tag()) {
            $current_tag = get_queried_object();
            if ($current_tag) {
                echo '<input type="hidden" name="product_tag" value="' . esc_attr($current_tag->slug) . '">';
            }
        }
        
        // Zachowaj zapytanie wyszukiwania
        if (is_search() && get_search_query()) {
            echo '<input type="hidden" name="s" value="' . esc_attr(get_search_query()) . '">';
        }
        
        // Zachowaj filtry atrybutów
        if (!empty($_GET)) {
            foreach ($_GET as $key => $value) {
                if (strpos($key, 'filter_') === 0 && !empty($value)) {
                    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                }
            }
        }
    }
}

/**
 * Widget przycisku reset
 */
class SLWN_Product_Filter_Reset_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'slwn_product_filter_reset',
            'SLWN Przycisk Reset',
            array(
                'description' => 'Wyświetla przycisk resetowania filtrów',
                'customize_selective_refresh' => true,
            )
        );
    }

    public function widget($args, $instance) {
        if (!is_shop() && !is_product_category() && !is_product_tag() && !is_search()) {
            return;
        }

        $title = !empty($instance['title']) ? $instance['title'] : '';
        $reset_text = !empty($instance['reset_text']) ? $instance['reset_text'] : 'Resetuj filtry';

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }

        $reset_url = is_shop() ? wc_get_page_permalink('shop') : 
                    (is_product_category() ? get_term_link(get_queried_object()) : get_permalink());

        echo '<div class="wc-filter-buttons">';
        echo '<button type="button" class="wc-filter-reset" data-url="' . esc_url($reset_url) . '">' . esc_html($reset_text) . '</button>';
        echo '</div>';

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $reset_text = !empty($instance['reset_text']) ? $instance['reset_text'] : 'Resetuj filtry';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Tytuł:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('reset_text')); ?>">Tekst przycisku:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('reset_text')); ?>" name="<?php echo esc_attr($this->get_field_name('reset_text')); ?>" type="text" value="<?php echo esc_attr($reset_text); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['reset_text'] = (!empty($new_instance['reset_text'])) ? sanitize_text_field($new_instance['reset_text']) : 'Resetuj filtry';

        return $instance;
    }
}

/**
 * Widget przycisku submit
 */
class SLWN_Product_Filter_Submit_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'slwn_product_filter_submit',
            'SLWN Przycisk Filtruj',
            array(
                'description' => 'Wyświetla przycisk uruchamiający filtrowanie',
                'customize_selective_refresh' => true,
            )
        );
    }

    public function widget($args, $instance) {
        if (!is_shop() && !is_product_category() && !is_product_tag() && !is_search()) {
            return;
        }

        $title = !empty($instance['title']) ? $instance['title'] : '';
        $submit_text = !empty($instance['submit_text']) ? $instance['submit_text'] : 'Filtruj produkty';

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }

        echo '<div class="wc-filter-buttons">';
        echo '<button type="button" class="wc-filter-submit slwn-apply-filters-btn" data-action="apply-filters">' . esc_html($submit_text) . '</button>';
        echo '</div>';

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $submit_text = !empty($instance['submit_text']) ? $instance['submit_text'] : 'Filtruj produkty';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Tytuł:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('submit_text')); ?>">Tekst przycisku:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('submit_text')); ?>" name="<?php echo esc_attr($this->get_field_name('submit_text')); ?>" type="text" value="<?php echo esc_attr($submit_text); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['submit_text'] = (!empty($new_instance['submit_text'])) ? sanitize_text_field($new_instance['submit_text']) : 'Filtruj produkty';

        return $instance;
    }
}
