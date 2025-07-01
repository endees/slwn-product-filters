/**
 * Admin JavaScript for SLWN Product Filters
 */

(function ($) {
    "use strict";

    $(document).ready(function () {
        // Initialize admin functionality
        SLWNProductFiltersAdmin.init();
    });

    /**
     * Admin object
     */
    window.SLWNProductFiltersAdmin = {
        /**
         * Initialize
         */
        init: function () {
            this.bindEvents();
            this.initWidgetControls();
        },

        /**
         * Bind events
         */
        bindEvents: function () {
            // Settings form submission
            $('form[action="options.php"]').on("submit", this.handleFormSubmit);

            // Test button click
            $(document).on(
                "click",
                ".slwn-product-filters-test-button",
                this.handleTestButton
            );

            // Enable/disable filters toggle
            $(document).on(
                "change",
                "#enable_filters",
                this.handleFiltersToggle
            );
        },

        /**
         * Handle form submit
         */
        handleFormSubmit: function (e) {
            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"]');

            // Show loading state
            $submitButton.prop("disabled", true);
            $submitButton.after(
                '<span class="slwn-product-filters-spinner"></span>'
            );
        },

        /**
         * Handle test button
         */
        handleTestButton: function (e) {
            e.preventDefault();

            var $button = $(this);
            $button.prop("disabled", true);
            $button.text("Testowanie...");

            // AJAX request
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "slwn_get_filter_options",
                    nonce: $("#slwn_product_filters_admin_nonce").val(),
                },
                success: function (response) {
                    if (response.success) {
                        SLWNProductFiltersAdmin.showNotice(
                            response.message,
                            "success"
                        );
                    } else {
                        SLWNProductFiltersAdmin.showNotice(
                            response.message || "Wystąpił błąd",
                            "error"
                        );
                    }
                },
                error: function () {
                    SLWNProductFiltersAdmin.showNotice(
                        "Wystąpił błąd połączenia",
                        "error"
                    );
                },
                complete: function () {
                    $button.prop("disabled", false);
                    $button.text("Test");
                },
            });
        },

        /**
         * Handle filters toggle
         */
        handleFiltersToggle: function () {
            var $checkbox = $(this);
            var isEnabled = $checkbox.prop("checked");

            // Show/hide related options
            $(".filters-dependent").toggle(isEnabled);

            if (isEnabled) {
                SLWNProductFiltersAdmin.showNotice(
                    "Filtry produktów zostały włączone",
                    "info"
                );
            } else {
                SLWNProductFiltersAdmin.showNotice(
                    "Filtry produktów zostały wyłączone",
                    "info"
                );
            }
        },

        /**
         * Show admin notice
         */
        showNotice: function (message, type) {
            type = type || "info";

            var $notice = $(
                '<div class="notice notice-' +
                    type +
                    ' is-dismissible"><p>' +
                    message +
                    "</p></div>"
            );
            $(".wrap h1").after($notice);

            // Auto dismiss after 5 seconds
            setTimeout(function () {
                $notice.fadeOut(function () {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Validate form
         */
        validateForm: function ($form) {
            var isValid = true;

            // Add validation logic here

            return isValid;
        },

        /**
         * Initialize widget controls
         */
        initWidgetControls: function () {
            console.log("SLWN Admin: Initializing widget controls");

            // Initialize existing widgets immediately
            this.initAllWidgets();

            // Also try with a slight delay for widgets that load asynchronously
            setTimeout(function () {
                SLWNProductFiltersAdmin.initAllWidgets();
            }, 500);

            // Listen for widget events
            $(document).on("widget-added", function (event, widget) {
                console.log("SLWN Admin: Widget added", widget);
                SLWNProductFiltersAdmin.initAllWidgets();
            });

            $(document).on("widget-updated", function (event, widget) {
                console.log("SLWN Admin: Widget updated", widget);
                SLWNProductFiltersAdmin.initAllWidgets();
            });

            // Fallback: Check periodically for new widgets
            setInterval(function () {
                SLWNProductFiltersAdmin.initAllWidgets();
            }, 2000);
        },

        /**
         * Initialize all product filter widgets
         */
        initAllWidgets: function () {
            var self = this;

            // Find all widgets with our specific class or containing our selects
            $("div.widget").each(function () {
                var $widget = $(this);
                var $filterTypeSelect = $widget.find(
                    'select[id*="filter_type"]'
                );
                var $displayTypeSelect = $widget.find(
                    'select[id*="display_type"]'
                );

                // Also check for our widget base ID patterns
                var widgetId = $widget.attr("id") || "";
                var isSlwnWidget =
                    widgetId.indexOf("slwn_product_filter") !== -1 ||
                    $filterTypeSelect.length > 0 ||
                    $displayTypeSelect.length > 0;

                // Only process if this widget has our selects or is our widget
                if (
                    isSlwnWidget &&
                    $filterTypeSelect.length &&
                    $displayTypeSelect.length
                ) {
                    console.log("SLWN Admin: Found product filter widget", {
                        widget: $widget,
                        widgetId: widgetId,
                        filterSelect: $filterTypeSelect.attr("id"),
                        displaySelect: $displayTypeSelect.attr("id"),
                    });
                    self.initSingleWidget(
                        $widget,
                        $filterTypeSelect,
                        $displayTypeSelect
                    );
                }
            });
        },

        /**
         * Initialize single widget controls
         */
        initSingleWidget: function (
            $widget,
            $filterTypeSelect,
            $displayTypeSelect
        ) {
            var self = this;

            console.log("SLWN Admin: Initializing single widget", {
                widget: $widget,
                filterTypeSelect: $filterTypeSelect,
                displayTypeSelect: $displayTypeSelect,
            });

            // Toggle filter type fields
            function toggleFilterTypeFields() {
                var filterType = $filterTypeSelect.val();
                var $attributeField = $widget.find(".attribute-field");
                var $categoryField = $widget.find(".category-field");

                console.log("SLWN Admin: Toggling filter type fields", {
                    filterType: filterType,
                    attributeField: $attributeField.length,
                    categoryField: $categoryField.length,
                });

                if (filterType === "attribute") {
                    $attributeField.show();
                    $categoryField.hide();
                } else if (filterType === "category") {
                    $attributeField.hide();
                    $categoryField.show();
                }
            }

            // Toggle display type fields
            function toggleDisplayTypeFields() {
                var displayType = $displayTypeSelect.val();
                var $rangeFields = $widget.find(".range-fields");

                console.log("SLWN Admin: Toggling display type fields", {
                    displayType: displayType,
                    rangeFields: $rangeFields.length,
                });

                if (displayType === "range") {
                    $rangeFields.show();
                } else {
                    $rangeFields.hide();
                }
            }

            // Remove any existing event handlers to prevent duplicates
            $filterTypeSelect.off("change.slwn-admin");
            $displayTypeSelect.off("change.slwn-admin");

            // Bind change events
            $filterTypeSelect.on("change.slwn-admin", function () {
                console.log(
                    "SLWN Admin: Filter type changed to:",
                    $(this).val()
                );
                toggleFilterTypeFields();
            });

            $displayTypeSelect.on("change.slwn-admin", function () {
                console.log(
                    "SLWN Admin: Display type changed to:",
                    $(this).val()
                );
                toggleDisplayTypeFields();
            });

            // Initialize on load
            toggleFilterTypeFields();
            toggleDisplayTypeFields();

            console.log("SLWN Admin: Widget initialization complete");
        },
    };
})(jQuery);
