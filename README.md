# SLWN Product Filters - WooCommerce Product Filters Plugin

Profesjonalna wtyczka WordPress/WooCommerce do zaawansowanego filtrowania produktów po atrybutach i kategoriach z obsługą AJAX, widgetów, shortcode i nowoczesnego interfejsu użytkownika.

## 🚀 Funkcjonalności

-   **Filtry atrybutów produktów** - filtrowanie po dowolnych atrybutach WooCommerce
-   **Filtry kategorii** - filtrowanie po kategoriach i podkategoriach
-   **Różne style wyświetlania**:
    -   Lista rozwijana (select)
    -   Przyciski (radio buttons)
    -   **Checkboxy (wielokrotny wybór)** - zaznaczanie wielu wartości jednocześnie
    -   Suwak zakresu (range slider) dla wartości numerycznych
-   **AJAX bez przeładowywania** - płynne filtrowanie produktów
-   **Obsługa localStorage** - zapamiętywanie filtrów między stronami
-   **Widgety WordPress** - łatwe dodawanie przez panel administracyjny
-   **Shortcode** - elastyczne umieszczanie w treści
-   **Responsywny design** - pełna obsługa urządzeń mobilnych
-   **Kompatybilność z HPOS** - obsługa nowych funkcji WooCommerce

## 📦 Instalacja

1. Pobierz wtyczkę i skopiuj do `/wp-content/plugins/slwn-product-filters/`
2. Aktywuj wtyczkę w panelu administracyjnym WordPress
3. Upewnij się, że WooCommerce jest aktywne
4. Przejdź do `Wygląd > Widgety` aby skonfigurować filtry

## ⚙️ Konfiguracja

### Widgety

Wtyczka dodaje 2 nowe widgety:

1. **"Filtr produktów"** - główny widget do filtrowania
2. **"Przyciski filtra"** - widget z przyciskami "Filtruj" i "Resetuj"

### Dodawanie filtrów

1. `Wygląd > Widgety`
2. Dodaj widget "Filtr produktów" do sidebar sklepu
3. Skonfiguruj widget:
    - **Typ filtra**: Atrybut lub Kategoria
    - **Atrybut/Kategoria**: Wybierz konkretny element
    - **Sposób wyświetlania**: Select, Radio, **Checkboxy** lub Range slider
    - **Opcje**: Tytuł, tekst domyślny, jednostki itp.
    - **Pokaż opcje bez produktów**: Możesz wybrać, czy pokazywać wszystkie opcje atrybutu/kategorii (w tym te bez przypisanych produktów), czy tylko te, które mają produkty. Opcja dostępna w ustawieniach widgetu jako checkbox.

### Shortcode

```php
[slwn_product_filters title="Filtry produktów" show_categories="true" show_attributes="true"]
```

**Parametry:**

-   `title` - tytuł sekcji filtrów
-   `show_categories` - wyświetl filtry kategorii (true/false)
-   `show_attributes` - wyświetl filtry atrybutów (true/false)
-   `show_price` - wyświetl filtr ceny (true/false)
-   `style` - dodatkowe style CSS

## 🎨 Klasy CSS

### Główne kontenery

```css
.slwn-product-filters-wrapper    /* Główny kontener */
/* Główny kontener */
/* Główny kontener */
/* Główny kontener */
.slwn-product-filters-form       /* Formularz filtrów */
.slwn-product-filter; /* Pojedynczy filtr */
```

### Typy filtrów

```css
.slwn-product-filter--attribute   /* Filtr atrybutu */
/* Filtr atrybutu */
/* Filtr atrybutu */
/* Filtr atrybutu */
.slwn-product-filter--category    /* Filtr kategorii */
.slwn-product-filter--range       /* Filtr zakresu */
.slwn-product-filter--checkbox; /* Filtr checkboxów */
```

### Elementy interfejsu

```css
.slwn-product-filter__select      /* Lista rozwijana */
/* Lista rozwijana */
/* Lista rozwijana */
/* Lista rozwijana */
.slwn-product-filter__buttons     /* Przyciski radio */
.slwn-product-filter__checkboxes  /* Kontener checkboxów */
.slwn-filter-option--checkbox     /* Opcja checkbox */
.slwn-filters-buttons__filter     /* Przycisk "Filtruj" */
.slwn-filters-buttons__reset; /* Przycisk "Resetuj" */
```

## 🔧 Jak działa

### Frontend (JavaScript)

-   **Synchronizacja URL ↔ Filtry** - automatyczne zachowanie stanu filtrów
-   **localStorage** - pamiętanie wyborów między sesjami
-   **Obsługa checkboxów** - wielokrotny wybór z łączeniem wartości przecinkami
-   **Submit tylko po kliknięciu** - filtry nie stosują się automatycznie
-   **Range sliders** - interaktywne suwaki dla wartości numerycznych

### Backend (PHP)

-   **Widgety WordPress** - pełna integracja z systemem widgetów
-   **WooCommerce Query** - modyfikacja zapytań produktów
-   **AJAX endpoints** - szybkie filtrowanie bez przeładowywania
-   **HPOS compatibility** - zgodność z najnowszymi standardami WooCommerce

### Obsługa checkboxów (wielokrotny wybór)

Wtyczka obsługuje zaawansowane checkboxy umożliwiające zaznaczenie wielu wartości:

-   **Format URL**: `?pa_color=czerwony,niebieski,zielony` (dla atrybutów)
-   **Format kategorii**: `?product_cat=kategoria1,kategoria2`
-   **Format range**: `?min_pa_rozmiar=10&max_pa_rozmiar=50`
-   **Automatyczne łączenie** wartości przecinkami
-   **Synchronizacja** między URL, localStorage i formularzem
-   **Proper reset** - odznaczenie wszystkich checkboxów czyści filtr

**📋 Format parametrów URL:**

-   **Atrybuty produktów**: `pa_nazwa_atrybutu` (np. `pa_color`, `pa_size`)
-   **Kategorie**: `product_cat`
-   **Range slidery**: `min_pa_nazwa` i `max_pa_nazwa` (np. `min_pa_rozmiar`, `max_pa_rozmiar`)

## 🛠️ Wymagania systemowe

-   **WordPress**: 5.0+
-   **WooCommerce**: 5.0+
-   **PHP**: 7.4+
-   **jQuery**: (załączone w WordPress)

## 🐛 Troubleshooting

### Filtry nie działają

1. Sprawdź czy WooCommerce jest aktywne
2. Upewnij się że atrybuty produktów są "Used for filtering"
3. Sprawdź konsolę przeglądarki pod kątem błędów JS

### Checkboxy nie zaznaczają się

1. Sprawdź format URL - wartości powinny być oddzielone przecinkami
2. Upewnij się że atrybuty mają poprawne slug'i
3. Wyczyść cache przeglądarki i localStorage

### Stylowanie nie działa

1. Sprawdź czy motyv ładuje style wtyczki
2. Użyj inspektora przeglądarki do debugowania CSS
3. Dodaj custom styles w motyyy jeśli potrzeba

## 📝 Changelog

### v1.2.0 (Obecna wersja) - Finalna wersja produkcyjna

**🎯 Główne ulepszenia:**

-   ✅ **Przepisano logikę submitu filtrów** - uproszczenie i zwiększenie niezawodności
-   ✅ **Naprawiono problem z checkboxami** - poprawne czyszczenie po odznaczeniu wszystkich opcji
-   ✅ **Usunięto automatyczne submitowanie** - filtry stosują się dopiero po kliknięciu "Filtruj"
-   ✅ **Wyczyszczono kod z logów debugowych** - kod gotowy do produkcji
-   ✅ **Poprawiona synchronizacja URL ↔ Formularz** - pełna obsługa wszystkich typów filtrów
-   ✅ **Zmieniono klasy CSS z `ntc-` na `slwn-`** - ujednolicenie nazewnictwa
-   ✅ **Naprawiono dynamiczne przełączanie pól w adminie** - pola widgetów przełączają się bez przeładowywania
-   ✅ **Dodano opcję "Pokaż opcje bez produktów"** - możliwość wyświetlania wszystkich opcji atrybutu/kategorii
-   ✅ **Poprawiono format URL zgodnie ze standardem WooCommerce** - używa `pa_` dla atrybutów zamiast `filter_`

**🔧 Zmiany techniczne:**

-   Przepisano funkcję `slwnSubmitAllFilters()` - zawsze wysyła wszystkie filtry, nawet puste
-   Usunięto wszystkie `console.log()` z kodu produkcyjnego
-   Uproszczono logikę obsługi localStorage i URL
-   Poprawiono obsługę range sliderów z tax_query
-   Zmieniono wszystkie klasy CSS z prefiksu `ntc-` na `slwn-`
-   Dodano centralny system JavaScript w adminie dla obsługi widgetów
-   Ulepszona obsługa enqueue'owania skryptów w panelu administracyjnym
-   **Zmieniono format parametrów URL**: atrybuty używają teraz `pa_nazwa_atrybutu`, range slidery `min_pa_nazwa` i `max_pa_nazwa`

**🗑️ Oczyszczanie:**

-   Usunięto wszystkie pliki testowe HTML
-   Usunięto tymczasowe pliki markdown z dokumentacją developera
-   Usunięto niepotrzebne logi debugowe z PHP
-   Usunięto inline JavaScript z widgetów na rzecz centralnego admin.js

### v1.1.0 - Dodanie checkboxów wielokrotnego wyboru

**🆕 Nowe funkcjonalności:**

-   Dodano styl filtrów: **Checkboxy (wielokrotny wybór)**
-   Możliwość zaznaczenia wielu wartości jednocześnie
-   Obsługa formatu `?filter_attr=value1,value2,value3` w URL
-   Automatyczne łączenie wartości przecinkami

**🎨 Ulepszenia UI/UX:**

-   Nowe style CSS dla checkboxów
-   Responsive design dla checkboxów
-   Ukryte pola przechowujące połączone wartości
-   Poprawiona obsługa fokusa i accessibility

**🔧 Zmiany backend:**

-   Rozszerzono `class-widgets.php` o obsługę checkboxów
-   Zaktualizowano `class-ajax.php` dla wielu wartości
-   Dodano walidację i sanityzację dla arrays
-   Ulepszona obsługa tax_query dla multiple values

### v1.0.0 - Pierwsza wersja

**🎉 Inicjalna wersja wtyczki:**

-   Przeniesienie kodu z motywu `slawinsky-boilerplate`
-   Pełna funkcjonalność filtrów atrybutów i kategorii
-   Obsługa widgetów WordPress i shortcode
-   Responsywny design i AJAX
-   Kompatybilność z WooCommerce HPOS

**📁 Struktura projektu:**

-   `slwn-product-filters.php` - główny plik wtyczki
-   `includes/` - klasy PHP (widgets, AJAX, frontend, admin)
-   `assets/` - pliki JS i CSS
-   `templates/` - szablony HTML
-   `languages/` - tłumaczenia

## 🤝 Wsparcie

Jeśli napotkasz problemy:

1. **Sprawdź wymagania systemowe** - szczególnie wersje WordPress/WooCommerce
2. **Włącz tryb debug** - dodaj `define('WP_DEBUG', true);` do wp-config.php
3. **Sprawdź logi błędów** - przejrzyj error_log serwera
4. **Wyczyść cache** - zarówno wtyczek jak i przeglądarki
5. **Konflikt z innymi wtyczkami** - tymczasowo wyłącz inne wtyczki filtrów

## 📄 Licencja

GPL v2 lub nowsza. Zobacz `LICENSE` file.

---

**Autor**: Maciej Sławiński  
**Wersja**: 1.2.0  
**Testowano do**: WordPress 6.4, WooCommerce 8.4  
**Wymaga**: PHP 7.4+, WordPress 5.0+, WooCommerce 5.0+
