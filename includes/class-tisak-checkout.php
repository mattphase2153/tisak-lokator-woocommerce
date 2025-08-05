<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Tisak_Checkout {
    public function __construct() {
        // Samo classic checkout
        add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'add_tisak_locator' ) );
        add_action( 'woocommerce_checkout_process', array( $this, 'validate_tisak_location' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_scripts' ) );
    }
    public function add_tisak_locator() {
        // Provjeri da li koristimo blocks checkout
        if ( function_exists( 'has_block' ) && has_block( 'woocommerce/checkout' ) ) {
            // Ne prikazuj na blocks checkout
            return;
        }
       
        $settings = get_option( 'tisak_lokator_settings', array() );
        $shipping_instance = isset( $settings['shipping_method_instance'] ) ? $settings['shipping_method_instance'] : '';
       
        // Dohvati odabrane shipping metode
        $chosen_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods', array() ) : array();
        $chosen_method = ! empty( $chosen_methods ) ? $chosen_methods[0] : '';
        error_log( 'Tisak Lokator: Provjera prikaza. Instanca=' . $shipping_instance . ', Odabrana metoda=' . $chosen_method );
        // Prikaži Tisak lokator samo ako je odabrana ispravna shipping metoda
        if ( $shipping_instance && $chosen_method === $shipping_instance ) {
            include TISAK_LOKATOR_ULTIMATE_PATH . 'templates/checkout-tisak-locator.php';
            error_log( 'Tisak Lokator: Locator prikazan na checkoutu.' );
        } else {
            error_log( 'Tisak Lokator: Lokator nije prikazan. Shipping instanca: ' . $shipping_instance . ', Odabrana metoda: ' . $chosen_method );
        }
    }
    public function validate_tisak_location() {
        $settings = get_option( 'tisak_lokator_settings', array() );
        $shipping_instance = isset( $settings['shipping_method_instance'] ) ? $settings['shipping_method_instance'] : '';
       
        $chosen_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods', array() ) : array();
        $chosen_method = ! empty( $chosen_methods ) ? $chosen_methods[0] : '';
        // Validiraj samo ako je odabrana Tisak shipping metoda
        if ( $chosen_method === $shipping_instance ) {
            $tisak_location = isset($_POST['tisak_lokacija']) ? sanitize_text_field($_POST['tisak_lokacija']) : '';
            $tisak_code = isset($_POST['tisak_lokacija_code']) ? sanitize_text_field($_POST['tisak_lokacija_code']) : '';
           
            if ( empty( $tisak_location ) || empty( $tisak_code ) ) {
                wc_add_notice( __( 'Molimo odaberite Tisak lokaciju za dostavu.', 'tisak-lokator-ultimate' ), 'error' );
                error_log( 'Tisak Lokator: Validacija neuspješna - nema odabrane lokacije ili koda.' );
            } else {
                error_log( 'Tisak Lokator: Validacija uspješna. Lokacija: ' . $tisak_location . ', Kod: ' . $tisak_code );
            }
        }
    }
    public function enqueue_checkout_scripts() {
        if ( is_checkout() ) {
            // Učitaj glavni CSS
            wp_enqueue_style(
                'tisak-lokator',
                TISAK_LOKATOR_ULTIMATE_URL . 'assets/css/tisak-lokator.css',
                array(),
                TISAK_LOKATOR_ULTIMATE_VERSION
            );
           
            // Dodaj dinamički CSS iz postavki
            $this->add_dynamic_styles();
        }
    }
    private function add_dynamic_styles() {
        $settings = get_option( 'tisak_lokator_settings', array() );
       
        // Generiraj CSS iz postavki
        $custom_css = "
        /* Dinamički generirani CSS iz Tisak Lokator postavki */
        .tisak-lokator-section {
            background-color: " . esc_attr( $settings['section_bg_color'] ?? '#ffffff' ) . ";
            border: " . esc_attr( $settings['section_border_width'] ?? '1' ) . "px solid " . esc_attr( $settings['section_border_color'] ?? '#e0e0e0' ) . ";
            border-radius: " . esc_attr( $settings['section_border_radius'] ?? '8' ) . "px;
            padding: " . esc_attr( $settings['section_padding'] ?? '20' ) . "px;
            margin: " . esc_attr( $settings['section_margin'] ?? '20' ) . "px 0;
        }
       
        .tisak-lokator-section h3 {
            color: " . esc_attr( $settings['title_color'] ?? '#333333' ) . ";
            font-size: " . esc_attr( $settings['title_size'] ?? '18' ) . "px;
            font-weight: " . esc_attr( $settings['title_weight'] ?? 'bold' ) . ";
            margin-bottom: " . esc_attr( $settings['title_margin_bottom'] ?? '15' ) . "px;
            margin-top: 0;
        }
       
        .tisak-lokator-section {
            font-size: " . esc_attr( $settings['font_size_pc'] ?? '16' ) . "px;
        }
       
        /* Dropdown stilovi */
        .tisak-lokator-section select,
        #tisak_lokacija {
            background-color: " . esc_attr( $settings['dropdown_bg_color'] ?? '#ffffff' ) . " !important;
            border: 1px solid " . esc_attr( $settings['dropdown_border_color'] ?? '#dddddd' ) . " !important;
            color: " . esc_attr( $settings['dropdown_text_color'] ?? '#333333' ) . " !important;
            padding: " . esc_attr( $settings['dropdown_padding'] ?? '10' ) . "px !important;
            border-radius: " . esc_attr( $settings['dropdown_border_radius'] ?? '4' ) . "px !important;
            width: 100%;
            font-size: inherit;
        }
       
        .tisak-lokator-section select:hover,
        #tisak_lokacija:hover {
            background-color: " . esc_attr( $settings['dropdown_hover_bg'] ?? '#f5f5f5' ) . " !important;
        }
       
        /* Gumbovi */
        .tisak-lokator-section button,
        .tisak-lokator-section .button,
        #tisak-gps-btn,
        .tisak-gps-btn {
            background-color: " . esc_attr( $settings['btn_bg_color'] ?? '#007cba' ) . " !important;
            color: " . esc_attr( $settings['btn_text_color'] ?? '#ffffff' ) . " !important;
            border-radius: " . esc_attr( $settings['btn_border_radius'] ?? '6' ) . "px !important;
            padding: " . esc_attr( $settings['btn_padding_y'] ?? '10' ) . "px " . esc_attr( $settings['btn_padding_x'] ?? '20' ) . "px !important;
            font-size: " . esc_attr( $settings['btn_font_size'] ?? '14' ) . "px !important;
            font-weight: " . esc_attr( $settings['btn_font_weight'] ?? '600' ) . " !important;
            border: none !important;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none !important;
        }
       
        .tisak-lokator-section button:hover,
        .tisak-lokator-section .button:hover,
        #tisak-gps-btn:hover {
            background-color: " . esc_attr( $settings['btn_hover_bg'] ?? '#005a87' ) . " !important;
            color: " . esc_attr( $settings['btn_text_color'] ?? '#ffffff' ) . " !important;
        }
       
        /* Info kartice */
        #tisak-info,
        .tisak-info-container,
        .tisak-info-card {
            background: " . esc_attr( $settings['info_bg_color'] ?? '#f8f9fa' ) . ";
            border-color: " . esc_attr( $settings['info_border_color'] ?? '#dee2e6' ) . ";
            color: " . esc_attr( $settings['info_text_color'] ?? '#6c757d' ) . ";
        }
           
        .tisak-info-card p {
            color: " . esc_attr( $settings['info_text_color'] ?? '#6c757d' ) . ";
        }
           
        .tisak-info-card strong {
            color: " . esc_attr( $settings['info_label_color'] ?? '#495057' ) . ";
        }
           
        /* Naslov info kartice */
        .tisak-info-card > p:first-child strong {
            color: " . esc_attr( $settings['info_title_color'] ?? '#212529' ) . " !important;
            font-size: 16px;
        }
       
        /* Mapa */
        #tisak-map {
            border: " . esc_attr( $settings['map_border_width'] ?? '1' ) . "px solid " . esc_attr( $settings['map_border_color'] ?? '#dddddd' ) . ";
            border-radius: " . esc_attr( $settings['map_border_radius'] ?? '4' ) . "px;
            height: " . esc_attr( $settings['map_height_pc'] ?? '300' ) . "px;
            width: 100%;
            margin: 15px 0;
        }
       
        /* Markeri - ovo će biti primijenjeno kroz JavaScript */
        .tisak-marker-color { color: " . esc_attr( $settings['marker_color'] ?? '#dc3545' ) . "; }
        .tisak-marker-active-color { color: " . esc_attr( $settings['marker_active_color'] ?? '#007cba' ) . "; }
        .tisak-marker-user-color { color: " . esc_attr( $settings['marker_user_color'] ?? '#007bff' ) . "; }
        .tisak-marker-size { font-size: " . esc_attr( $settings['marker_size'] ?? '12' ) . "px; }
        .tisak-marker-active-size { font-size: " . esc_attr( $settings['marker_active_size'] ?? '32' ) . "px; }
       
        /* Popup stilovi */
        .leaflet-popup-content-wrapper {
            background-color: " . esc_attr( $settings['popup_bg_color'] ?? '#ffffff' ) . " !important;
            color: " . esc_attr( $settings['popup_text_color'] ?? '#333333' ) . " !important;
            border-radius: " . esc_attr( $settings['popup_border_radius'] ?? '4' ) . "px !important;
        }
       
        .leaflet-popup-content {
            color: " . esc_attr( $settings['popup_text_color'] ?? '#333333' ) . " !important;
            margin: 13px 19px !important;
        }
       
        .leaflet-popup-content h4 {
            margin-top: 0 !important;
            color: " . esc_attr( $settings['popup_text_color'] ?? '#333333' ) . " !important;
        }
       
        .leaflet-popup-content p {
            margin: 5px 0 !important;
            color: " . esc_attr( $settings['popup_text_color'] ?? '#333333' ) . " !important;
        }
       
        .tisak-popup-btn {
            background-color: " . esc_attr( $settings['btn_bg_color'] ?? '#007cba' ) . " !important;
            color: " . esc_attr( $settings['btn_text_color'] ?? '#ffffff' ) . " !important;
            border-radius: " . esc_attr( $settings['btn_border_radius'] ?? '6' ) . "px !important;
            padding: " . esc_attr( $settings['btn_padding_y'] ?? '10' ) . "px " . esc_attr( $settings['btn_padding_x'] ?? '20' ) . "px !important;
            font-size: " . esc_attr( $settings['btn_font_size'] ?? '14' ) . "px !important;
            font-weight: " . esc_attr( $settings['btn_font_weight'] ?? '600' ) . " !important;
            border: none !important;
            cursor: pointer;
            text-decoration: none !important;
            display: inline-block;
            margin-top: 10px;
        }
       
        .tisak-popup-btn:hover {
            background-color: " . esc_attr( $settings['btn_hover_bg'] ?? '#005a87' ) . " !important;
            color: " . esc_attr( $settings['btn_text_color'] ?? '#ffffff' ) . " !important;
        }
       
        /* Mobile stilovi */
        @media (max-width: 768px) {
            .tisak-lokator-section {
                font-size: " . esc_attr( $settings['font_size_mobile'] ?? '14' ) . "px;
            }
           
            #tisak-map {
                height: 250px;
            }
        }
        ";
       
        // Dodaj custom CSS ako postoji
        if ( ! empty( $settings['custom_css'] ) ) {
            $custom_css .= "\n/* Custom CSS */\n" . $settings['custom_css'];
        }
       
        // Dodaj CSS na stranicu
        wp_add_inline_style( 'tisak-lokator', $custom_css );
    }
}