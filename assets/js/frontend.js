/**
 * Frontend JavaScript for SLWN Product Filters
 * Obsługa filtrów produktów WooCommerce - przeniesiona z product-filters.js
 */

(function($) {
    'use strict';

    // Flagi zapobiegające nadpisywaniu podczas przywracania filtrów
    let isRestoringFilters = false;
    let userChangedFilters = false; // Flaga śledząca zmiany użytkownika
    window.isRestoringFilters = isRestoringFilters; // Udostępnij globalnie
    
    // Udostępnij funkcję aktualizacji URL globalnie
    window.slwnUpdateURLWithFilters = null;

    $(document).ready(function() {
        /**
         * Pobierz wszystkie aktywne filtry z formularza i URL
         * @return {Object} Obiekt z filtrami i flagą czy są jakiekolwiek filtry
         */
        function getAllProductFilters() {
            // 1. Pobierz filtry z URL - mają najwyższy priorytet
            const urlParams = new URLSearchParams(window.location.search);
            const allFilters = {};
            let hasFilters = false;
            
            // Zbierz filtry z URL
            urlParams.forEach(function(value, key) {
                if (key.indexOf('filter_') === 0 && value !== '') {
                    allFilters[key] = value;
                    hasFilters = true;
                }
            });
            
            // 2. Pobierz filtry z wszystkich widgetów na stronie
            $('.slwn-product-filter-widget-form').each(function() {
                const $form = $(this);
                
                // Specjalna obsługa checkboxów - zbierz wartości z ukrytych pól
                $form.find('.checkbox-values-holder').each(function() {
                    const $hidden = $(this);
                    const name = $hidden.attr('name');
                    const value = $hidden.val();
                    
                    if (name && name.indexOf('filter_') === 0 && value !== '' && !allFilters[name]) {
                        allFilters[name] = value;
                        hasFilters = true;
                    }
                });
                
                // Normalna obsługa innych kontrolek
                $form.find('select[name^="filter_"], input[name^="filter_"]').each(function() {
                    const $input = $(this);
                    const name = $input.attr('name');
                    let value = $input.val();
                    
                    // Pomiń ukryte pola checkboxów (już obsłużone powyżej)
                    if ($input.hasClass('checkbox-values-holder')) {
                        return;
                    }
                    
                    // Pomiń checkboxy z nazwami z [] (używamy ukrytych pól)
                    if ($input.is('[type="checkbox"]') && name && name.endsWith('[]')) {
                        return;
                    }
                    
                    // Dla radio/checkbox sprawdź czy jest zaznaczony
                    if ($input.is('[type="radio"]') || $input.is('[type="checkbox"]')) {
                        if (!$input.is(':checked')) {
                            return; // Pomiń niezaznaczone
                        }
                    }
                    
                    // Jeśli to filtr atrybutu i ma wartość, i nie ma go już z URL
                    if (name && name.indexOf('filter_') === 0 && value !== '' && !allFilters[name]) {
                        allFilters[name] = value;
                        hasFilters = true;
                    }
                });
            });
            
            // 3. Sprawdź ukryte pola range sliderów (jeśli nie zostały już pobrane z URL)
            $('input[type="hidden"][name*="filter_"]').each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                const value = $input.val();
                
                if (name && value !== '' && !allFilters[name]) {
                    allFilters[name] = value;
                    hasFilters = true;
                }
            });
            
            // 4. localStorage jako ostatnia opcja (tylko jeśli nie mamy żadnych filtrów)
            if (!hasFilters) {
                try {
                    const savedFilters = localStorage.getItem('product_filters');
                    if (savedFilters) {
                        const parsedFilters = JSON.parse(savedFilters);
                        for (const key in parsedFilters) {
                            if (parsedFilters.hasOwnProperty(key) && 
                                key.indexOf('filter_') === 0 && 
                                parsedFilters[key] !== '') {
                                allFilters[key] = parsedFilters[key];
                                hasFilters = true;
                            }
                        }
                    }
                } catch (e) {
                    // Błąd podczas odczytu zapisanych filtrów
                }
            }
            
            return { filters: allFilters, hasFilters: hasFilters };
        }
        
        /**
         * Zapisz filtry do localStorage
         * @param {Object} filters Obiekt z filtrami do zapisania
         */
        function saveProductFilters(filters) {
            try {
                localStorage.setItem('product_filters', JSON.stringify(filters));
            } catch (e) {
                // Nie można zapisać filtrów
            }
        }
        
        /**
         * Aktualizuj URL z aktualnymi filtrami
         * @param {Object} filters Obiekt z filtrami
         */
        function updateURLWithFilters(filters) {
            try {
                let url = window.location.pathname;
                const params = new URLSearchParams();
                
                // Dodaj filtry do parametrów
                for (const key in filters) {
                    if (filters.hasOwnProperty(key) && filters[key] !== '' && filters[key] !== null) {
                        params.append(key, filters[key]);
                    }
                }
                
                // Zachowaj parametr kategorii jeśli istnieje
                const currentParams = new URLSearchParams(window.location.search);
                if (currentParams.has('product_cat')) {
                    params.append('product_cat', currentParams.get('product_cat'));
                }
                
                // Buduj nowy URL
                if (params.toString()) {
                    url += '?' + params.toString();
                }
                
                // Aktualizuj URL bez przeładowania strony
                window.history.replaceState({}, '', url);
                
                
            } catch (e) {
            }
        }
        
        /**
         * Obsługa zmiany kategorii z zachowaniem filtrów
         */
        function initCategoryChangeHandlers() {
            // Obsługa selecta kategorii
            $('select[name="product_cat"]').on('change', function() {
                const $select = $(this);
                const $selectedOption = $select.find('option:selected');
                let url;
                
                // Jeśli wybrano "Wybierz opcję" (pusta wartość)
                if ($selectedOption.val() === '') {
                    url = $selectedOption.data('url') || $select.data('shop-url') || '/sklep/';
                } else {
                    url = $selectedOption.data('url');
                }
                
                if (url) {
                    // Zbierz wszystkie aktywne filtry i dodaj do URL
                    const filterData = getAllProductFilters();
                    saveProductFilters(filterData.filters);
                    
                    if (filterData.hasFilters) {
                        const params = $.param(filterData.filters);
                        url += (url.indexOf('?') === -1 ? '?' : '&') + params;
                    }
                    
                    window.location.href = url;
                }
                
                return false;
            });
            
            // Obsługa przycisków kategorii
            $('.slwn-product-filter--category .slwn-product-filter__radio').on('change', function() {
                const $radio = $(this);
                const url = $radio.data('url');
                
                if (url) {
                    // Zbierz wszystkie aktywne filtry i dodaj do URL
                    const filterData = getAllProductFilters();
                    saveProductFilters(filterData.filters);
                    
                    if (filterData.hasFilters) {
                        const params = $.param(filterData.filters);
                        url += (url.indexOf('?') === -1 ? '?' : '&') + params;
                    }
                    
                    window.location.href = url;
                }
                
                return false;
            });
        }
        
        /**
         * Przywróć zapisane filtry do formularza
         */
        function restoreProductFilters() {
            try {
                // Jeśli użytkownik już zmienił filtry, nie nadpisuj jego zmian
                if (userChangedFilters) {
                    return;
                }
                
                // Sprawdź czy obecne wartości widgetów różnią się od URL
                // Jeśli tak, to znaczy że użytkownik już je zmienił
                const currentUrlParams = new URLSearchParams(window.location.search);
                let urlHasFilters = false;
                let widgetsDifferFromURL = false;
                
                // Sprawdź każdy widget czy jego wartość różni się od URL
                $('.slwn-product-filter-widget-form select[name^="filter_"], .slwn-product-filter-widget-form input[name^="filter_"]:checked').each(function() {
                    const $input = $(this);
                    const name = $input.attr('name');
                    const currentValue = $input.val();
                    const urlValue = currentUrlParams.get(name);
                    
                    if (name.indexOf('filter_') === 0) {
                        if (urlValue) {
                            urlHasFilters = true;
                            if (currentValue !== urlValue) {
                                widgetsDifferFromURL = true;
                            }
                        }
                    }
                });
                
                // Jeśli widgety różnią się od URL, to użytkownik je już zmienił
                if (urlHasFilters && widgetsDifferFromURL) {
                    userChangedFilters = true;
                    return;
                }
                
                isRestoringFilters = true; // Ustaw flagę podczas przywracania
                window.isRestoringFilters = true; // Ustaw również globalnie
                
                const urlParams = new URLSearchParams(window.location.search);
                const savedFilters = localStorage.getItem('product_filters');
                let filtersToApply = {};
                let hasFiltersInURL = false;
                
                
                // 1. Sprawdź filtry w URL - mają priorytet
                urlParams.forEach(function(value, key) {
                    if (key.indexOf('filter_') === 0 && value !== '') {
                        filtersToApply[key] = value;
                        hasFiltersInURL = true;
                    }
                });
                
                
                // 2. Jeśli brak filtrów w URL, użyj localStorage jako backup
                // ALE tylko jeśli URL nie jest stroną sklepu bez filtrów
                if (!hasFiltersInURL && savedFilters) {
                    const currentPath = window.location.pathname;
                    const shopPath = '/sklep/';
                    const isShopPage = currentPath.endsWith(shopPath) || currentPath.endsWith(shopPath.replace('/', ''));
                    
                    // Jeśli jesteśmy na stronie sklepu bez parametrów, to znaczy że filtry zostały wyczyszczone
                    if (isShopPage && window.location.search === '') {
                        // Wyczyść localStorage
                        localStorage.removeItem('product_filters');
                        filtersToApply = {};
                    } else {
                        const parsedFilters = JSON.parse(savedFilters);
                        for (const key in parsedFilters) {
                            if (parsedFilters.hasOwnProperty(key) && key.indexOf('filter_') === 0) {
                                // Uwzględnij także puste filtry - są ważne dla czyszczenia checkboxów
                                filtersToApply[key] = parsedFilters[key];
                            }
                        }
                    }
                } else if (hasFiltersInURL) {
                    // Wyczyść localStorage jeśli mamy filtry w URL, żeby uniknąć konfliktów
                    localStorage.setItem('product_filters', JSON.stringify(filtersToApply));
                }
                
                // 3. Zastosuj filtry do wszystkich widgetów na stronie - także puste filtry
                if (Object.keys(filtersToApply).length > 0) {
                    
                    // Aktualizuj wszystkie widgety
                    $('.slwn-product-filter-widget-form').each(function() {
                        const $form = $(this);
                        
                        // Znajdź wszystkie filtry w tym formularzu
                        $form.find('select[name^="filter_"], input[name^="filter_"]').each(function() {
                            const $input = $(this);
                            const name = $input.attr('name');
                            
                            // Sprawdź czy to filtr do przywrócenia
                            let filterValue = null;
                            let filterKey = null;
                            
                            if (name && filtersToApply[name]) {
                                filterValue = filtersToApply[name];
                                filterKey = name;
                            } else if (name && name.endsWith('[]')) {
                                // Dla checkboxów sprawdź nazwę bez []
                                const baseName = name.replace('[]', '');
                                if (filtersToApply[baseName]) {
                                    filterValue = filtersToApply[baseName];
                                    filterKey = baseName;
                                }
                            }
                            
                            if (filterValue) {
                                 // Dla selectów
                                if ($input.is('select')) {
                                    const oldValue = $input.val();
                                    
                                    // Metoda 1: Standardowa
                                    $input.val(filterValue);
                                    let newValue = $input.val();
                                    
                                    // Metoda 2: Ręczne ustawienie selected
                                    if (newValue !== filterValue) {
                                        $input.find('option').each(function() {
                                            const $option = $(this);
                                            if ($option.val() === filterValue) {
                                                $option.prop('selected', true);
                                            } else {
                                                $option.prop('selected', false);
                                            }
                                        });
                                        newValue = $input.val();
                                    }
                                    
                                    // Metoda 3: Bezpośrednia zmiana DOM
                                    if (newValue !== filterValue) {
                                        $input.find('option').removeAttr('selected');
                                        const $targetOption = $input.find('option[value="' + filterValue + '"]');
                                        $targetOption.attr('selected', 'selected');
                                        
                                        // Wymuszenie re-render
                                        $input[0].selectedIndex = $targetOption.index();
                                        
                                        newValue = $input.val();
                                    }
                                    
                                    // Metoda 4: Trigger change event
                                    if (newValue !== filterValue) {
                                        // Tymczasowo wyłącz flag podczas wymuszania
                                        const wasRestoring = isRestoringFilters;
                                        isRestoringFilters = false;
                                        
                                        $input.val(filterValue).trigger('change');
                                        
                                        isRestoringFilters = wasRestoring;
                                        newValue = $input.val();
                                    }
                                    
                                }
                                // Dla radio i checkbox
                                else if ($input.is('[type="radio"]') || $input.is('[type="checkbox"]')) {
                                    // Specjalna obsługa dla checkboxów z wieloma wartościami
                                    if ($input.is('[type="checkbox"]')) {
                                        if (filterValue === '' || filterValue === null || filterValue === undefined) {
                                            // Pusty filtr - odznacz wszystkie checkboxy
                                            $input.prop('checked', false);
                                        } else if (filterValue.includes(',')) {
                                            const checkboxValues = filterValue.split(',').map(v => v.trim());
                                            const currentValue = $input.val();
                                            const shouldBeChecked = checkboxValues.includes(currentValue);
                                            $input.prop('checked', shouldBeChecked);
                                        } else {
                                            // Pojedyncza wartość
                                            const shouldBeChecked = $input.val() === filterValue;
                                            $input.prop('checked', shouldBeChecked);
                                        }
                                        
                                        // Aktualizuj ukryte pole po przywróceniu checkboxów
                                        const $form = $input.closest('.slwn-product-filter-widget-form');
                                        const baseName = filterKey; // używaj filterKey zamiast name
                                        const $hiddenField = $form.find('input[name="' + baseName + '"].checkbox-values-holder');
                                        if ($hiddenField.length) {
                                            $hiddenField.val(filterValue || ''); // Ustaw pustą wartość jeśli filtr pusty
                                        }
                                    } else if ($input.val() === filterValue) {
                                        $input.prop('checked', true);
                                    } else {
                                        $input.prop('checked', false);
                                    }
                                }
                                // Dla ukrytych pól (range sliders)
                                else if ($input.is('[type="hidden"]')) {
                                    $input.val(filterValue);
                                }
                                // Dla innych inputów
                                else {
                                    $input.val(filterValue);
                                }
                            } else if (name && name.indexOf('filter_') === 0) {
                                // Wyczyść pole jeśli nie ma go w filtrach do zastosowania
                                if ($input.is('select')) {
                                    $input.val('');
                                    $input.find('option').removeAttr('selected');
                                    $input.find('option[value=""]').attr('selected', 'selected');
                                } else if ($input.is('[type="radio"]') || $input.is('[type="checkbox"]')) {
                                    $input.prop('checked', false);
                                } else if (!$input.is('[type="hidden"]')) {
                                    $input.val('');
                                }
                            }
                        });
                    });
                    
                    // Synchronizuj range slidery z wartościami
                    for (const filterName in filtersToApply) {
                        if (filterName.includes('_min') || filterName.includes('_max')) {
                            const baseFilter = filterName.replace('_min', '').replace('_max', '');
                            const minValue = filtersToApply[baseFilter + '_min'];
                            const maxValue = filtersToApply[baseFilter + '_max'];
                            
                            if (minValue !== undefined && maxValue !== undefined) {
                                // Znajdź odpowiedni slider i ustaw jego wartości
                                $('.slwn-range-slider').each(function() {
                                    const $slider = $(this);
                                    const sliderId = $slider.attr('id');
                                    
                                    if (sliderId && (sliderId.includes(baseFilter.replace('filter_', '')) || 
                                                   sliderId.includes(baseFilter))) {
                                        const sliderElement = document.getElementById(sliderId);
                                        if (sliderElement && sliderElement.noUiSlider) {
                                            sliderElement.noUiSlider.set([minValue, maxValue]);
                                        }
                                    }
                                });
                            }
                        }
                    }
                    
                } else {
                    
                    // Wyczyść wszystkie widgety
                    $('.slwn-product-filter-widget-form').each(function() {
                        const $form = $(this);
                        $form.find('select[name^="filter_"]').each(function() {
                            const $select = $(this);
                            $select.val('');
                            $select.find('option').removeAttr('selected');
                            $select.find('option[value=""]').attr('selected', 'selected');
                        });
                        $form.find('input[name^="filter_"][type="radio"], input[name^="filter_"][type="checkbox"]').prop('checked', false);
                        
                        // Wyczyść także ukryte pola checkboxów
                        $form.find('.checkbox-values-holder').val('');
                    });
                }
                
                // Zdejmij flagę po zakończeniu przywracania
                setTimeout(function() {
                    isRestoringFilters = false;
                    window.isRestoringFilters = false;
                    // Pozwól użytkownikowi na nowe zmiany po zakończeniu przywracania
                    userChangedFilters = false;
                }, 500);
                
            } catch (e) {
                isRestoringFilters = false;
                window.isRestoringFilters = false;
            }
        }
        
        /**
         * Obsługa przycisku reset filtrów
         */
        function initResetFiltersButton() {
            $('.slwn-filters-buttons__reset, .wc-filter-reset').on('click', function(e) {
                // Wyczyść localStorage przed przekierowaniem
                localStorage.removeItem('product_filters');
                
            });
        }
        
        /**
         * Obsługa przycisku "Filtruj"
         */
        function initApplyFiltersButton() {
            $(document).on('click', '.slwn-apply-filters-btn', function(e) {
                e.preventDefault();
                
                try {
                    // Sprawdź aktualny stan przed przeładowaniem
                    const currentURL = window.location.href;
                    const currentFilters = localStorage.getItem('product_filters');
                    
                    
                    // Sprawdź aktualne wartości widgetów
                    const widgetFilters = {};
                    $('.slwn-product-filter-widget-form').each(function() {
                        const $form = $(this);
                        $form.find('select[name^="filter_"], input[name^="filter_"]').each(function() {
                            const $input = $(this);
                            const name = $input.attr('name');
                            let value = $input.val();
                            
                            // Dla radio/checkbox sprawdź czy jest zaznaczony
                            if ($input.is('[type="radio"]') || $input.is('[type="checkbox"]')) {
                                if (!$input.is(':checked')) {
                                    return; // Pomiń niezaznaczone
                                }
                            }
                            
                            if (name && name.indexOf('filter_') === 0 && value !== '') {
                                widgetFilters[name] = value;
                            }
                        });
                    });
                    
                    
                    // Sprawdź czy URL i widgety są zsynchronizowane
                    const urlParams = new URLSearchParams(window.location.search);
                    const urlFilters = {};
                    urlParams.forEach(function(value, key) {
                        if (key.indexOf('filter_') === 0) {
                            urlFilters[key] = value;
                        }
                    });
                    
                    
                    // Sprawdź czy wszystko się zgadza
                    let isSync = true;
                    for (const key in widgetFilters) {
                        if (urlFilters[key] !== widgetFilters[key]) {
                            isSync = false;
                        }
                    }
                    
                    if (isSync) {
                    } else {
                        
                        // Jeśli nie są zsynchronizowane, napraw URL
                        updateURLWithFilters(widgetFilters);
                    }
                    
                    // Przeładuj stronę
                    window.location.reload();
                    
                } catch (e) {
                    console.error('Błąd podczas stosowania filtrów:', e);
                }
            });
        }
        
        // Inicjalizacja wszystkich funkcji związanych z filtrami
        function init() {
            initCategoryChangeHandlers();
            initWidgetFormHandlers();
            initResetFiltersButton();
            initApplyFiltersButton(); // Nowa funkcja dla przycisku "Filtruj"
            
            // Udostępnij funkcję globalnie dla range sliderów
            window.slwnUpdateURLWithFilters = updateURLWithFilters;
            
            // Po przesłaniu formularza zapisz filtry
            $('#product-filters-form').on('submit', function() {
                const filterData = getAllProductFilters();
                saveProductFilters(filterData.filters);
                return true;
            });
            
            // Przywróć filtry z większym opóźnieniem, żeby inne skrypty się załadowały
            setTimeout(function() {
                restoreProductFilters();
                
                // Dodatkowe sprawdzenie po przywróceniu
                setTimeout(function() {
                    $('.slwn-product-filter-widget-form select[name^="filter_"]').each(function() {
                        const $select = $(this);
                        const name = $select.attr('name');
                        const currentValue = $select.val();
                        const selectedOption = $select.find('option:selected');
                        
                    });
                }, 200);
                
            }, 500); // Zwiększone opóźnienie
        }
        
        /**
         * Obsługa formularzy widgetów
         */
        function initWidgetFormHandlers() {
            // Obsługa zmian w formularzach widgetów - aktualizuj URL natychmiast
            $(document).on('change', '.slwn-product-filter-widget-form select[name^="filter_"], .slwn-product-filter-widget-form input[name^="filter_"]', function() {
                // Pomiń jeśli jesteśmy w trakcie przywracania filtrów
                if (isRestoringFilters) {
                    return;
                }

                // Ustaw flagę, że użytkownik zmienił filtry
                userChangedFilters = true;

                const $input = $(this);
                const name = $input.attr('name');
                let value = $input.val();


                // Specjalna obsługa dla checkboxów
                if ($input.is('[type="checkbox"]') && name.endsWith('[]')) {
                    // Pobierz bazową nazwę filtru (bez [])
                    const baseName = name.replace('[]', '');
                    const $checkboxes = $input.closest('.slwn-product-filter-widget-form').find('input[name="' + name + '"]');
                    const checkedValues = [];
                    
                    $checkboxes.each(function() {
                        const $checkbox = $(this);
                        if ($checkbox.is(':checked')) {
                            checkedValues.push($checkbox.val());
                        }
                    });
                    
                    // Złącz wartości przecinkami
                    const combinedValue = checkedValues.join(',');
                    
                    // Aktualizuj ukryte pole
                    const $hiddenField = $input.closest('.slwn-product-filter-widget-form').find('input[name="' + baseName + '"].checkbox-values-holder');
                    $hiddenField.val(combinedValue);
                    
                    
                    // Pobierz aktualne filtry z localStorage i zaktualizuj bezpośrednio
                    let currentFilters = {};
                    try {
                        const savedFilters = localStorage.getItem('product_filters');
                        if (savedFilters) {
                            currentFilters = JSON.parse(savedFilters);
                        }
                    } catch (e) {
                    }
                    
                    // Zaktualizuj wartość filtru
                    if (combinedValue === '') {
                        delete currentFilters[baseName]; // Usuń filtr całkowicie jeśli pusty
                    } else {
                        currentFilters[baseName] = combinedValue;
                    }
                    
                    // Zapisz zaktualizowane filtry do localStorage
                    saveProductFilters(currentFilters);
                    
                    // NATYCHMIAST aktualizuj URL - usuń puste filtry z URL
                    const filtersForURL = Object.assign({}, currentFilters);
                    Object.keys(filtersForURL).forEach(key => {
                        if (filtersForURL[key] === '' || filtersForURL[key] === null || filtersForURL[key] === undefined) {
                            delete filtersForURL[key];
                        }
                    });
                    updateURLWithFilters(filtersForURL);
                    
                    return;
                }

                // Dla radio/checkbox sprawdź czy jest zaznaczony
                if ($input.is('[type="radio"]') || $input.is('[type="checkbox"]')) {
                    if (!$input.is(':checked')) {
                        value = ''; // Jeśli nie zaznaczony, ustaw pustą wartość
                    }
                }

                if (name && name.indexOf('filter_') === 0) {
                    // Pobierz wszystkie aktualne filtry
                    const filterData = getAllProductFilters();
                    
                    // Zaktualizuj wartość
                    if (value === '') {
                        delete filterData.filters[name];
                    } else {
                        filterData.filters[name] = value;
                    }
                    
                    // Zapisz do localStorage
                    saveProductFilters(filterData.filters);
                    
                    // NATYCHMIAST aktualizuj URL
                    updateURLWithFilters(filterData.filters);
                    
                }
            });
            
            // Obsługa zmian w ukrytych polach range sliderów
            $(document).on('change', '.slwn-product-filter-widget-form input[type="hidden"][name*="filter_"]', function() {
                // Pomiń jeśli jesteśmy w trakcie przywracania filtrów
                if (isRestoringFilters) {
                    return;
                }
                
                const $input = $(this);
                const name = $input.attr('name');
                const value = $input.val();
                
                if (name && value) {
                    const filterData = getAllProductFilters();
                    filterData.filters[name] = value;
                    saveProductFilters(filterData.filters);
                    
                    // NATYCHMIAST aktualizuj URL
                    updateURLWithFilters(filterData.filters);
                }
            });
            
            // Obsługa selecta kategorii - nadal automatyczne bo to nawigacja
            $(document).on('change', '.slwn-product-filter-widget-form select[name="product_cat"]', function() {
                const $form = $(this).closest('.slwn-product-filter-widget-form');
                
                // Wyczyść filtry przed przejściem do nowej kategorii
                localStorage.removeItem('product_filters');
                
                setTimeout(function() {
                    $form.submit();
                }, 100);
            });
        }
        
        // Inicjalizacja przy załadowaniu strony
        init();
        
    });

})(jQuery);
