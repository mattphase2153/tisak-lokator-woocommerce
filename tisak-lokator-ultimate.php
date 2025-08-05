<?php
/**
 * Plugin Name: Tisak Lokator Ultimate za WooCommerce
 * Description: Napredni odabir lokacija Tisak s mapama, prilagodljivim postavkama i poboljšanim značajkama za WooCommerce checkout.
 * Version: 2.0.17
 * Author: Advantage Digital LLC / Matej Poznić
 * License: GPL-2.0+
 * Text Domain: tisak-lokator-ultimate
 */

// Zaštita od direktnog pristupa
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Osnovne konstante
if ( ! defined( 'TISAK_LOKATOR_ULTIMATE_VERSION' ) ) {
    define( 'TISAK_LOKATOR_ULTIMATE_VERSION', '2.0.17' );
}

if ( ! defined( 'TISAK_LOKATOR_ULTIMATE_PATH' ) ) {
    define( 'TISAK_LOKATOR_ULTIMATE_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'TISAK_LOKATOR_ULTIMATE_URL' ) ) {
    define( 'TISAK_LOKATOR_ULTIMATE_URL', plugin_dir_url( __FILE__ ) );
}

// Provjera PHP verzije
if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
    add_action( 'admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p>Tisak Lokator Ultimate zahtijeva PHP 5.6 ili noviji.</p>
        </div>
        <?php
    });
    return;
}

// Učitaj potrebne datoteke - s provjerom postojanja
function tisak_lokator_load_files() {
    $files = array(
        'includes/class-tisak-checkout.php',
        'includes/class-tisak-settings.php',
        'includes/class-tisak-ajax.php',
        'includes/class-tisak-order.php',
        'includes/class-tisak-admin.php',
        'includes/class-tisak-cache.php'
        // UKLONJENA class-tisak-blocks.php
    );
    
    foreach ( $files as $file ) {
        $filepath = TISAK_LOKATOR_ULTIMATE_PATH . $file;
        if ( file_exists( $filepath ) ) {
            require_once $filepath;
        }
    }
}

// Glavna inicijalizacija
function tisak_lokator_ultimate_init() {
    // Provjeri WooCommerce
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p>Tisak Lokator Ultimate zahtijeva WooCommerce plugin. Molimo instalirajte i aktivirajte WooCommerce.</p>
            </div>
            <?php
        });
        return;
    }
    
    // Učitaj text domain
    load_plugin_textdomain( 'tisak-lokator-ultimate', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    
    // Učitaj files
    tisak_lokator_load_files();
    
    // Inicijaliziraj klase ako postoje
    $classes = array(
        'Tisak_Checkout',
        'Tisak_Settings',
        'Tisak_AJAX',
        'Tisak_Order',
        'Tisak_Admin'
        // UKLONJENA Tisak_Blocks
    );
    
    foreach ( $classes as $class ) {
        if ( class_exists( $class ) ) {
            new $class();
        }
    }
}

// Hook za plugins_loaded
add_action( 'plugins_loaded', 'tisak_lokator_ultimate_init', 20 );

// Enqueue scripts
function tisak_lokator_ultimate_enqueue_scripts() {
    // Provjeri da li je checkout stranica
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
        return;
    }
    
    $settings = get_option( 'tisak_lokator_settings', array() );
    
    // Leaflet
    wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
    wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
    
    // Plugin scripts
    wp_enqueue_script( 
        'tisak-lokator', 
        TISAK_LOKATOR_ULTIMATE_URL . 'assets/js/tisak-lokator.js', 
        array( 'jquery', 'leaflet' ), 
        TISAK_LOKATOR_ULTIMATE_VERSION, 
        true 
    );
    
    // UKLONJEN tisak-blocks script
    
    // Styles
    wp_enqueue_style( 
        'tisak-lokator-style', 
        TISAK_LOKATOR_ULTIMATE_URL . 'assets/css/tisak-lokator.css', 
        array(), 
        TISAK_LOKATOR_ULTIMATE_VERSION 
    );
    
    wp_enqueue_style( 
        'tisak-lokator-mobile', 
        TISAK_LOKATOR_ULTIMATE_URL . 'assets/css/tisak-mobile.css', 
        array( 'tisak-lokator-style' ), 
        TISAK_LOKATOR_ULTIMATE_VERSION 
    );
    
    // CSS varijable
    tisak_lokator_add_inline_styles( $settings );
    
    // Localize data
    $localize_data = array(
        'ajax_url'          => admin_url( 'admin-ajax.php' ),
        'plugin_url'        => TISAK_LOKATOR_ULTIMATE_URL,
        'nonce'             => wp_create_nonce( 'tisak_lokator_nonce' ),
        'map_width'         => isset( $settings['map_width_pc'] ) ? $settings['map_width_pc'] . 'px' : '500px',
        'map_height'        => isset( $settings['map_height_pc'] ) ? $settings['map_height_pc'] . 'px' : '300px',
        'font_size'         => isset( $settings['font_size_pc'] ) ? $settings['font_size_pc'] . 'px' : '16px',
        'enable_log'        => isset( $settings['enable_log'] ) ? $settings['enable_log'] : 'off',
        'enable_gps'        => isset( $settings['enable_gps'] ) ? $settings['enable_gps'] : 'on',
        'shipping_instance' => isset( $settings['shipping_method_instance'] ) ? $settings['shipping_method_instance'] : '',
        'marker_color'      => isset( $settings['marker_color'] ) ? $settings['marker_color'] : '#dc3545',
        'marker_active_color' => isset( $settings['marker_active_color'] ) ? $settings['marker_active_color'] : '#007cba',
        'marker_user_color' => isset( $settings['marker_user_color'] ) ? $settings['marker_user_color'] : '#007bff',
        'marker_size'       => isset( $settings['marker_size'] ) ? $settings['marker_size'] : 12,
        'marker_active_size' => isset( $settings['marker_active_size'] ) ? $settings['marker_active_size'] : 32,
    );
    
    wp_localize_script( 'tisak-lokator', 'tisakSettings', $localize_data );
}
add_action( 'wp_enqueue_scripts', 'tisak_lokator_ultimate_enqueue_scripts' );

// Admin scripts
function tisak_lokator_admin_assets( $hook ) {
    if ( $hook === 'toplevel_page_tisak-lokator' || $hook === 'tisak-lokator_page_tisak-lokator-wizard' ) {
        wp_enqueue_style(
            'tisak-lokator-admin-style',
            TISAK_LOKATOR_ULTIMATE_URL . 'assets/css/admin-settings.css',
            array(),
            TISAK_LOKATOR_ULTIMATE_VERSION
        );
    }
}
add_action( 'admin_enqueue_scripts', 'tisak_lokator_admin_assets' );

// CSS varijable
function tisak_lokator_add_inline_styles( $settings ) {
    $css = tisak_lokator_generate_css( $settings );
    wp_add_inline_style( 'tisak-lokator-style', $css );
}

// Generiraj CSS
function tisak_lokator_generate_css( $settings ) {
    $defaults = array(
        'section_margin' => 20,
        'section_padding' => 20,
        'section_bg_color' => '#ffffff',
        'section_border_color' => '#e0e0e0',
        'section_border_width' => 1,
        'section_border_radius' => 8,
        'title_color' => '#333333',
        'title_size' => 18,
        'title_weight' => 'bold',
        'title_margin_bottom' => 15,
        'font_size_pc' => 16,
        'font_size_mobile' => 14,
        'dropdown_bg_color' => '#ffffff',
        'dropdown_border_color' => '#dddddd',
        'dropdown_text_color' => '#333333',
        'dropdown_padding' => 10,
        'dropdown_border_radius' => 4,
        'btn_bg_color' => '#007cba',
        'btn_text_color' => '#ffffff',
        'btn_border_radius' => 6,
        'btn_padding_x' => 20,
        'btn_padding_y' => 10,
        'btn_font_size' => 14,
        'btn_font_weight' => '600',
        'info_border_color' => '#dee2e6',
        'info_title_color' => '#212529',
        'info_label_color' => '#495057',
        'info_text_color' => '#495057',
        'info_padding' => 15,
        'info_border_radius' => 6,
        'map_height_pc' => 300,
        'map_border_color' => '#dddddd',
        'map_border_width' => 1,
        'map_border_radius' => 4,
        'marker_color' => '#dc3545',
        'marker_active_color' => '#007cba',
        'marker_user_color' => '#007bff',
        'marker_size' => 12,
        'marker_active_size' => 32,
    );
    
    $css = ':root {' . "\n";
    
    // Lista numeričkih vrijednosti koje trebaju px
    $px_values = array(
        'section_margin', 'section_padding', 'section_border_width', 'section_border_radius',
        'title_size', 'title_margin_bottom', 'font_size_pc', 'font_size_mobile',
        'dropdown_padding', 'dropdown_border_radius', 'btn_border_radius', 'btn_padding_x', 
        'btn_padding_y', 'btn_font_size', 'info_padding', 'info_border_radius', 
        'map_height_pc', 'map_border_width', 'map_border_radius', 'marker_size', 
        'marker_active_size', 'popup_border_radius'
    );
    
    foreach ( $defaults as $key => $default_value ) {
        $value = isset( $settings[$key] ) ? $settings[$key] : $default_value;
        
        // Dodaj px ako treba
        if ( in_array( $key, $px_values ) ) {
            $value = $value . 'px';
        }
        
        $css_var_name = '--tisak-' . str_replace( '_', '-', $key );
        $css .= '    ' . $css_var_name . ': ' . $value . ';' . "\n";
    }
    
    $css .= '}' . "\n";
    
    // Custom CSS
    if ( ! empty( $settings['custom_css'] ) ) {
        $css .= "\n" . $settings['custom_css'];
    }
    
    return $css;
}

// Plugin action links
function tisak_lokator_plugin_action_links( $links ) {
    $settings_link = '<a href="admin.php?page=tisak-lokator">Settings</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'tisak_lokator_plugin_action_links' );

// Aktivacija
function tisak_lokator_ultimate_activate() {
    // Provjeri osnovne zahtjeve
    if ( ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( 'Tisak Lokator Ultimate zahtijeva WooCommerce plugin. Molimo instalirajte i aktivirajte WooCommerce prvo.' );
    }
    
    // Učitaj files za aktivaciju
    tisak_lokator_load_files();
    
    // Postavi default postavke
    if ( class_exists( 'Tisak_Settings' ) ) {
        $settings_obj = new Tisak_Settings();
        if ( method_exists( $settings_obj, 'get_default_settings' ) ) {
            $default_settings = $settings_obj->get_default_settings();
            if ( ! get_option( 'tisak_lokator_settings' ) ) {
                update_option( 'tisak_lokator_settings', $default_settings );
            }
        }
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'tisak_lokator_ultimate_activate' );

// Deaktivacija
function tisak_lokator_ultimate_deactivate() {
    // Učitaj files za deaktivaciju
    tisak_lokator_load_files();
    
    // Clear cache
    if ( class_exists( 'Tisak_Cache' ) ) {
        Tisak_Cache::clear_all();
    }
    
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'tisak_lokator_ultimate_deactivate' );

// Test API
function tisak_lokator_test_api() {
    if ( isset( $_GET['tisak_test_api'] ) && current_user_can( 'manage_options' ) ) {
        $url = 'https://lokator.tisak.hr/Services/Stores.json?searchObject=' . urlencode( '{"authToken":"tisak","services":[],"storeTypes":["1","2"]}' );
        $response = wp_remote_get( $url, array( 'timeout' => 15 ) );
        
        if ( is_wp_error( $response ) ) {
            wp_die( 'API greška: ' . $response->get_error_message() );
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        echo '<pre>';
        echo 'Response code: ' . wp_remote_retrieve_response_code( $response ) . "\n\n";
        echo 'Number of stores: ' . ( is_array( $data ) ? count( $data ) : 'Invalid data' ) . "\n\n";
        
        if ( is_array( $data ) && count( $data ) > 0 ) {
            echo "First store example:\n";
            print_r( $data[0] );
        }
        echo '</pre>';
        wp_die();
    }
}
add_action( 'init', 'tisak_lokator_test_api' );

// Admin preview scripts
function tisak_enqueue_checkout_preview_scripts($hook) {
    if ($hook !== 'toplevel_page_tisak-lokator') return;

    $settings = get_option('tisak_lokator_settings', array());

    // Leaflet
    wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);
    wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');

    // Isti JS i CSS kao na frontendu
    wp_enqueue_script(
        'tisak-lokator', 
        TISAK_LOKATOR_ULTIMATE_URL . 'assets/js/tisak-lokator.js', 
        array('jquery', 'leaflet'), 
        TISAK_LOKATOR_ULTIMATE_VERSION, 
        true
    );

    wp_enqueue_style(
        'tisak-lokator-style', 
        TISAK_LOKATOR_ULTIMATE_URL . 'assets/css/tisak-lokator.css', 
        array(), 
        TISAK_LOKATOR_ULTIMATE_VERSION
    );

    wp_enqueue_style(
        'tisak-lokator-mobile', 
        TISAK_LOKATOR_ULTIMATE_URL . 'assets/css/tisak-mobile.css', 
        array('tisak-lokator-style'), 
        TISAK_LOKATOR_ULTIMATE_VERSION
    );

    // Localize data
    $localize_data = array(
        'ajax_url'          => admin_url('admin-ajax.php'),
        'plugin_url'        => TISAK_LOKATOR_ULTIMATE_URL,
        'nonce'             => wp_create_nonce('tisak_lokator_nonce'),
        'map_width'         => isset($settings['map_width_pc']) ? $settings['map_width_pc'] . 'px' : '500px',
        'map_height'        => isset($settings['map_height_pc']) ? $settings['map_height_pc'] . 'px' : '300px',
        'font_size'         => isset($settings['font_size_pc']) ? $settings['font_size_pc'] . 'px' : '16px',
        'enable_log'        => isset($settings['enable_log']) ? $settings['enable_log'] : 'off',
        'enable_gps'        => isset($settings['enable_gps']) ? $settings['enable_gps'] : 'on',
        'shipping_instance' => '', // Prazno za admin preview
        'marker_color'      => isset($settings['marker_color']) ? $settings['marker_color'] : '#dc3545',
        'marker_active_color' => isset($settings['marker_active_color']) ? $settings['marker_active_color'] : '#007cba',
        'marker_user_color' => isset($settings['marker_user_color']) ? $settings['marker_user_color'] : '#007bff',
        'marker_size'       => isset($settings['marker_size']) ? $settings['marker_size'] : 12,
        'marker_active_size' => isset($settings['marker_active_size']) ? $settings['marker_active_size'] : 32,
    );

    wp_localize_script('tisak-lokator', 'tisakSettings', $localize_data);

    // CSS varijable
    tisak_lokator_add_inline_styles($settings);
}
add_action('admin_enqueue_scripts', 'tisak_enqueue_checkout_preview_scripts');