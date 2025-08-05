<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Tisak_Settings {
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'handle_reset_settings' ) );
    }

    public function add_settings_page() {
        // Ova metoda se ne koristi jer admin stranica se dodaje kroz Tisak_Admin klasu
    }

    public function settings_page() {
        // Ova metoda se ne koristi jer se koristi admin-settings.php template
    }

    public function register_settings() {
        register_setting( 'tisak_lokator_options', 'tisak_lokator_settings', array( $this, 'sanitize_settings' ) );
    }

    public function handle_reset_settings() {
        if ( isset( $_POST['tisak_reset_settings'] ) && 
             wp_verify_nonce( $_POST['tisak_reset_nonce'], 'tisak_reset_settings' ) && 
             current_user_can( 'manage_options' ) ) {
            
            $default_settings = $this->get_default_settings();
            
            update_option( 'tisak_lokator_settings', $default_settings );
            
            // Očisti cache nakon resetiranja
            if ( class_exists( 'Tisak_Cache' ) ) {
                Tisak_Cache::clear_all();
            }
            
            wp_redirect( add_query_arg( 'settings-reset', 'success', admin_url( 'admin.php?page=tisak-lokator' ) ) );
            exit;
        }
    }

    public function get_default_settings() {
        return array(
            // Dimenzije
            'map_height_pc' => 300,
            'map_width_pc' => 440,
            'font_size_pc' => 16,
            'font_size_mobile' => 14,
            
            // Osnovne boje
            'section_bg_color' => '#ffffff',
            'section_border_color' => '#e0e0e0',
            'section_border_width' => 1,
            'section_border_radius' => 8,
            'section_padding' => 20,
            'section_margin' => 20,
            
            // Naslovi
            'title_color' => '#333333',
            'title_size' => 18,
            'title_weight' => 'bold',
            'title_margin_bottom' => 15,
            'label_color' => '#333333',
            
            // Dropdown
            'dropdown_bg_color' => '#ffffff',
            'dropdown_border_color' => '#dddddd',
            'dropdown_text_color' => '#333333',
            'dropdown_padding' => 10,
            'dropdown_border_radius' => 4,
            
            // Gumbovi
            'btn_bg_color' => '#ffffff',
            'btn_text_color' => '#2a2a2a',
            'btn_border_radius' => 6,
            'btn_padding_x' => 20,
            'btn_padding_y' => 10,
            'btn_font_size' => 14,
            'btn_font_weight' => '600',
            
            // Info kartice
            'info_border_color' => '#dee2e6',
            'info_title_color' => '#212529',
            'info_label_color' => '#495057',
            'info_text_color' => '#6c757d',
            'info_padding' => 15,
            'info_border_radius' => 6,
            
            // Mapa
            'map_border_color' => '#dddddd',
            'map_border_width' => 1,
            'map_border_radius' => 4,
            
            // Markeri
            'marker_color' => '#dc3545',
            'marker_active_color' => '#007cba',
            'marker_user_color' => '#007bff',
            'marker_size' => 12,
            'marker_active_size' => 32,
                        
            // Ostalo
            'enable_gps' => 'on',
            'enable_log' => 'off',
            'custom_css' => '',
            'shipping_method_instance' => ''
        );
    }

    public function sanitize_settings( $input ) {
        $sanitized = array();
        $defaults = $this->get_default_settings();
        
        // Shipping method
        if ( isset( $input['shipping_method_instance'] ) ) {
            $sanitized['shipping_method_instance'] = sanitize_text_field( $input['shipping_method_instance'] );
        }
        
        // Map dimensions
        if ( isset( $input['map_height_pc'] ) ) {
            $sanitized['map_height_pc'] = absint( $input['map_height_pc'] );
            if ( $sanitized['map_height_pc'] < 100 ) $sanitized['map_height_pc'] = 100;
            if ( $sanitized['map_height_pc'] > 800 ) $sanitized['map_height_pc'] = 800;
        }
        
        if ( isset( $input['map_width_pc'] ) ) {
            $sanitized['map_width_pc'] = absint( $input['map_width_pc'] );
            if ( $sanitized['map_width_pc'] < 200 ) $sanitized['map_width_pc'] = 200;
            if ( $sanitized['map_width_pc'] > 1200 ) $sanitized['map_width_pc'] = 1200;
        }
        
        // Font sizes
        if ( isset( $input['font_size_pc'] ) ) {
            $sanitized['font_size_pc'] = absint( $input['font_size_pc'] );
            if ( $sanitized['font_size_pc'] < 10 ) $sanitized['font_size_pc'] = 10;
            if ( $sanitized['font_size_pc'] > 24 ) $sanitized['font_size_pc'] = 24;
        }
        
        if ( isset( $input['font_size_mobile'] ) ) {
            $sanitized['font_size_mobile'] = absint( $input['font_size_mobile'] );
            if ( $sanitized['font_size_mobile'] < 10 ) $sanitized['font_size_mobile'] = 10;
            if ( $sanitized['font_size_mobile'] > 20 ) $sanitized['font_size_mobile'] = 20;
        }
        
        // Title settings
        if ( isset( $input['title_size'] ) ) {
            $sanitized['title_size'] = absint( $input['title_size'] );
            if ( $sanitized['title_size'] < 12 ) $sanitized['title_size'] = 12;
            if ( $sanitized['title_size'] > 32 ) $sanitized['title_size'] = 32;
        }
        
        if ( isset( $input['title_weight'] ) ) {
            $allowed_weights = array('normal', 'bold', '600', '700');
            $sanitized['title_weight'] = in_array($input['title_weight'], $allowed_weights) 
                ? $input['title_weight'] : 'bold';
        }
        
        if ( isset( $input['title_margin_bottom'] ) ) {
            $sanitized['title_margin_bottom'] = absint( $input['title_margin_bottom'] );
        }
        
        // Section settings
        if ( isset( $input['section_border_width'] ) ) {
            $sanitized['section_border_width'] = absint( $input['section_border_width'] );
            if ( $sanitized['section_border_width'] > 10 ) $sanitized['section_border_width'] = 10;
        }
        
        if ( isset( $input['section_border_radius'] ) ) {
            $sanitized['section_border_radius'] = absint( $input['section_border_radius'] );
            if ( $sanitized['section_border_radius'] > 50 ) $sanitized['section_border_radius'] = 50;
        }
        
        if ( isset( $input['section_padding'] ) ) {
            $sanitized['section_padding'] = absint( $input['section_padding'] );
            if ( $sanitized['section_padding'] > 50 ) $sanitized['section_padding'] = 50;
        }
        
        if ( isset( $input['section_margin'] ) ) {
            $sanitized['section_margin'] = absint( $input['section_margin'] );
            if ( $sanitized['section_margin'] > 50 ) $sanitized['section_margin'] = 50;
        }
        
        // Colors - validate all color inputs
        $color_fields = array(
            'section_bg_color', 'section_border_color', 'title_color',
            'dropdown_bg_color', 'dropdown_border_color', 'dropdown_text_color', 'dropdown_hover_bg',
            'btn_bg_color', 'btn_text_color', 'btn_hover_bg',
            'info_bg_color', 'info_border_color', 'info_text_color', 'info_title_color', 'info_label_color',
            'map_border_color', 'marker_color', 'marker_active_color', 'marker_user_color',
            'popup_bg_color', 'popup_text_color'
        );
        
        foreach ( $color_fields as $field ) {
            if ( isset( $input[$field] ) ) {
                $color = sanitize_hex_color( $input[$field] );
                $sanitized[$field] = $color ? $color : (isset($defaults[$field]) ? $defaults[$field] : '#ffffff');
            }
        }
        
        // Numeric fields
        $numeric_fields = array(
            'dropdown_padding', 'dropdown_border_radius',
            'btn_border_radius', 'btn_padding_x', 'btn_padding_y', 'btn_font_size',
            'info_padding', 'info_border_radius',
            'map_border_width', 'map_border_radius',
            'marker_size', 'marker_active_size',
            'popup_border_radius'
        );
        
        foreach ( $numeric_fields as $field ) {
            if ( isset( $input[$field] ) ) {
                $sanitized[$field] = absint( $input[$field] );
            }
        }
        
        // Button font weight
        if ( isset( $input['btn_font_weight'] ) ) {
            $allowed_weights = array('normal', '600', '700', 'bold');
            $sanitized['btn_font_weight'] = in_array($input['btn_font_weight'], $allowed_weights) 
                ? $input['btn_font_weight'] : '600';
        }
        
        // Enable GPS/Log
        if ( isset( $input['enable_gps'] ) ) {
            $sanitized['enable_gps'] = $input['enable_gps'] === 'on' ? 'on' : 'off';
        }
        
        if ( isset( $input['enable_log'] ) ) {
            $sanitized['enable_log'] = $input['enable_log'] === 'on' ? 'on' : 'off';
        }
        
        // Custom CSS
        if ( isset( $input['custom_css'] ) ) {
            $sanitized['custom_css'] = wp_strip_all_tags( $input['custom_css'] );
        }
        
        // Clear cache when settings change
        if ( class_exists( 'Tisak_Cache' ) ) {
            Tisak_Cache::clear_all();
        }
        
        return $sanitized;
    }

    /**
     * Dohvati sve shipping instance
     */
public function get_shipping_instances() {
    $instances = array();
    
    // Provjeri da li WooCommerce postoji
    if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
        return $instances;
    }
    
    // Dohvati sve shipping zone
    $shipping_zones = WC_Shipping_Zones::get_zones();
    
    // Dodaj i "Locations not covered by your other zones"
    $zone_0 = WC_Shipping_Zones::get_zone( 0 );
    if ( $zone_0 ) {
        $shipping_zones[0] = array(
            'zone_name' => $zone_0->get_zone_name(),
            'zone_id' => 0
        );
    }
    
    foreach ( $shipping_zones as $zone_data ) {
        if ( isset( $zone_data['zone_id'] ) ) {
            $zone = WC_Shipping_Zones::get_zone( $zone_data['zone_id'] );
            $zone_name = $zone_data['zone_name'];
            
            foreach ( $zone->get_shipping_methods() as $method ) {
                if ( $method->is_enabled() ) {
                    // VAŽNO: Koristi isti format kao WooCommerce
                    $instance_id = $method->id . ':' . $method->instance_id;
                    $instances[ $instance_id ] = sprintf( 
                        __( '%s (%s - %s)', 'tisak-lokator-ultimate' ), 
                        $method->title,
                        ucfirst( $method->id ),
                        $zone_name
                    );
                }
            }
        }
    }
    
    return $instances;
}

    /**
     * Inicijalizacija postavki
     */
    public function init_settings() {
        $settings = array(
            // GENERAL TAB
            array(
                'title' => __( 'Metoda Dostave', 'tisak-lokator-ultimate' ),
                'type'  => 'title',
                'id'    => 'tisak_dostava_options',
            ),
            array(
                'title'   => __( 'Shipping metoda za Tisak', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[shipping_method_instance]',
                'type'    => 'select',
                'options' => $this->get_shipping_instances(),
                'default' => '',
                'desc'    => __( 'Odaberite metodu dostave koja će aktivirati Tisak lokator', 'tisak-lokator-ultimate' ),
            ),
            
            // DESIGN TAB - Layout
            array(
                'title' => __( 'Layout i dimenzije', 'tisak-lokator-ultimate' ),
                'type'  => 'title',
                'id'    => 'tisak_layout_options',
            ),
            array(
                'title'   => __( 'Visina Mape (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[map_height_pc]',
                'type'    => 'number',
                'default' => 300,
                'desc'    => __( 'Visina mape u pikselima (100-800)', 'tisak-lokator-ultimate' ),
            ),
            array(
                'title'   => __( 'Širina Mape (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[map_width_pc]',
                'type'    => 'number',
                'default' => 440,
                'desc'    => __( 'Širina mape u pikselima (200-1200)', 'tisak-lokator-ultimate' ),
            ),
            array(
                'title'   => __( 'Padding sekcije (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[section_padding]',
                'type'    => 'number',
                'default' => 20,
                'desc'    => __( 'Unutarnji razmak sekcije', 'tisak-lokator-ultimate' ),
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'tisak_layout_options',
            ),
            
            // DESIGN TAB - Section styling
            array(
                'title' => __( 'Stiliziranje sekcije', 'tisak-lokator-ultimate' ),
                'type'  => 'title',
                'id'    => 'tisak_section_style',
            ),
            array(
                'title'   => __( 'Pozadina sekcije', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[section_bg_color]',
                'type'    => 'color',
                'default' => '#ffffff',
            ),
            array(
                'title'   => __( 'Boja obruba sekcije', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[section_border_color]',
                'type'    => 'color',
                'default' => '#e0e0e0',
            ),
            array(
                'title'   => __( 'Debljina obruba (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[section_border_width]',
                'type'    => 'number',
                'default' => 1,
            ),
            array(
                'title'   => __( 'Zaobljenost obruba (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[section_border_radius]',
                'type'    => 'number',
                'default' => 8,
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'tisak_section_style',
            ),
            
            // DESIGN TAB - Typography
            array(
                'title' => __( 'Tipografija', 'tisak-lokator-ultimate' ),
                'type'  => 'title',
                'id'    => 'tisak_typography',
            ),
            array(
                'title'   => __( 'Boja naslova', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[title_color]',
                'type'    => 'color',
                'default' => '#333333',
            ),
            array(
                'title'   => __( 'Veličina naslova (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[title_size]',
                'type'    => 'number',
                'default' => 18,
            ),
            array(
                'title'   => __( 'Debljina naslova', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[title_weight]',
                'type'    => 'select',
                'options' => array(
                    'normal' => 'Normal',
                    '600' => 'Semi-bold',
                    '700' => 'Bold',
                    'bold' => 'Extra bold'
                ),
                'default' => 'bold',
            ),
            array(
                'title'   => __( 'Razmak ispod naslova (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[title_margin_bottom]',
                'type'    => 'number',
                'default' => 15,
            ),
            array(
                'title'   => __( 'Veličina fonta (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[font_size_pc]',
                'type'    => 'number',
                'default' => 16,
            ),
            array(
                'title'   => __( 'Veličina fonta mobile (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[font_size_mobile]',
                'type'    => 'number',
                'default' => 14,
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'tisak_typography',
            ),
            array(
                'title'   => __( 'Boja labela', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[label_color]',
                'type'    => 'color',
                'default' => '#333333',
                'desc'    => __( 'Boja za "Tisak lokacija" label', 'tisak-lokator-ultimate' ),
            ),

            // DESIGN TAB - Dropdown styling
            array(
                'title' => __( 'Dropdown stiliziranje', 'tisak-lokator-ultimate' ),
                'type'  => 'title',
                'id'    => 'tisak_dropdown_style',
            ),
            array(
                'title'   => __( 'Pozadina dropdown-a', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[dropdown_bg_color]',
                'type'    => 'color',
                'default' => '#ffffff',
            ),
            array(
                'title'   => __( 'Obrub dropdown-a', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[dropdown_border_color]',
                'type'    => 'color',
                'default' => '#dddddd',
            ),
            array(
                'title'   => __( 'Tekst dropdown-a', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[dropdown_text_color]',
                'type'    => 'color',
                'default' => '#333333',
            ),
            array(
                'title'   => __( 'Padding (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[dropdown_padding]',
                'type'    => 'number',
                'default' => 10,
            ),
            array(
                'title'   => __( 'Zaobljenost (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[dropdown_border_radius]',
                'type'    => 'number',
                'default' => 4,
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'tisak_dropdown_style',
            ),
            
            // DESIGN TAB - Button styling
            array(
                'title' => __( 'Gumbovi', 'tisak-lokator-ultimate' ),
                'type'  => 'title',
                'id'    => 'tisak_button_style',
            ),
            array(
                'title'   => __( 'Pozadina gumba', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[btn_bg_color]',
                'type'    => 'color',
                'default' => '#ffffff',
            ),
            array(
                'title'   => __( 'Tekst gumba', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[btn_text_color]',
                'type'    => 'color',
                'default' => '#2a2a2a',
            ),
            array(
                'title'   => __( 'Zaobljenost (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[btn_border_radius]',
                'type'    => 'number',
                'default' => 6,
            ),
            array(
                'title'   => __( 'Padding X (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[btn_padding_x]',
                'type'    => 'number',
                'default' => 20,
            ),
            array(
                'title'   => __( 'Padding Y (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[btn_padding_y]',
                'type'    => 'number',
                'default' => 10,
            ),
            array(
                'title'   => __( 'Veličina fonta (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[btn_font_size]',
                'type'    => 'number',
                'default' => 14,
            ),
            array(
                'title'   => __( 'Debljina fonta', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[btn_font_weight]',
                'type'    => 'select',
                'options' => array(
                    'normal' => 'Normal',
                    '600' => 'Semi-bold',
                    '700' => 'Bold',
                    'bold' => 'Extra bold'
                ),
                'default' => '600',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'tisak_button_style',
            ),
            
            // DESIGN TAB - Info cards
            array(
                'title' => __( 'Info kartice', 'tisak-lokator-ultimate' ),
                'type'  => 'title',
                'id'    => 'tisak_info_style',
            ),
            array(
                'title'   => __( 'Obrub info kartice', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[info_border_color]',
                'type'    => 'color',
                'default' => '#dee2e6',
            ),
            array(
                'title'   => __( 'Naslov info kartice', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[info_title_color]',
                'type'    => 'color',
                'default' => '#212529',
                'desc'    => __( 'Boja za naziv lokacije', 'tisak-lokator-ultimate' ),
            ),
            array(
                'title'   => __( 'Labele info kartice', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[info_label_color]',
                'type'    => 'color',
                'default' => '#495057',
                'desc'    => __( 'Boja za "Adresa:", "Radno vrijeme:"', 'tisak-lokator-ultimate' ),
            ),
            array(
                'title'   => __( 'Tekst info kartice', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[info_text_color]',
                'type'    => 'color',
                'default' => '#6c757d',
                'desc'    => __( 'Boja za običan tekst', 'tisak-lokator-ultimate' ),
            ),
            array(
                'title'   => __( 'Padding (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[info_padding]',
                'type'    => 'number',
                'default' => 15,
            ),
            array(
                'title'   => __( 'Zaobljenost (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[info_border_radius]',
                'type'    => 'number',
                'default' => 6,
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'tisak_info_style',
            ),
            
            // DESIGN TAB - Map styling
            array(
                'title' => __( 'Mapa', 'tisak-lokator-ultimate' ),
                'type'  => 'title',
                'id'    => 'tisak_map_style',
            ),
            array(
                'title'   => __( 'Obrub mape', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[map_border_color]',
                'type'    => 'color',
                'default' => '#dddddd',
            ),
            array(
                'title'   => __( 'Debljina obruba (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[map_border_width]',
                'type'    => 'number',
                'default' => 1,
            ),
            array(
                'title'   => __( 'Zaobljenost (px)', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[map_border_radius]',
                'type'    => 'number',
                'default' => 4,
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'tisak_map_style',
            ),
            
            // Custom CSS
            array(
                'title'   => __( 'Prilagođeni CSS', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[custom_css]',
                'type'    => 'textarea',
                'default' => '',
                'desc'    => __( 'Dodajte prilagođeni CSS kod za dodatno stiliziranje.', 'tisak-lokator-ultimate' ),
            ),
            
            // ADDITIONAL TAB
            array(
                'title' => __( 'Dodatne opcije', 'tisak-lokator-ultimate' ),
                'type'  => 'title',
                'id'    => 'tisak_dodatno_options',
            ),
            array(
                'title'   => __( 'GPS Funkcionalnost', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[enable_gps]',
                'type'    => 'radio',
                'options' => array(
                    'off' => __( 'Isključi GPS', 'tisak-lokator-ultimate' ),
                    'on'  => __( 'Uključi GPS', 'tisak-lokator-ultimate' ),
                ),
                'default' => 'on',
                'desc'    => __( 'GPS dugme omogućuje korisnicima da automatski pronađu najbliže Tisak lokacije.', 'tisak-lokator-ultimate' ),
            ),
            array(
                'title'   => __( 'Aktiviraj Debug Log', 'tisak-lokator-ultimate' ),
                'id'      => 'tisak_lokator_settings[enable_log]',
                'type'    => 'radio',
                'options' => array(
                    'off' => __( 'Isključi', 'tisak-lokator-ultimate' ),
                    'on'  => __( 'Uključi', 'tisak-lokator-ultimate' ),
                ),
                'default' => 'off',
                'desc'    => __( 'Omogući logiranje u konzoli preglednika (F12) za debug.', 'tisak-lokator-ultimate' ),
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'tisak_dodatno_options',
            ),
        );
        
        return apply_filters( 'tisak_lokator_settings', $settings );
    }
}