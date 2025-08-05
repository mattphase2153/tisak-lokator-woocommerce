<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Tisak_AJAX {
    public function __construct() {
        add_action( 'wp_ajax_get_tisak_lokacije', array( $this, 'get_tisak_lokacije' ) );
        add_action( 'wp_ajax_nopriv_get_tisak_lokacije', array( $this, 'get_tisak_lokacije' ) );
        add_action( 'wp_ajax_get_tisak_store_info', array( $this, 'get_tisak_store_info' ) );
        add_action( 'wp_ajax_nopriv_get_tisak_store_info', array( $this, 'get_tisak_store_info' ) );
        add_action( 'wp_ajax_get_tisak_template', array( $this, 'get_tisak_template' ) );
        add_action( 'wp_ajax_nopriv_get_tisak_template', array( $this, 'get_tisak_template' ) );
    }
    // AJAX handler za dohvaćanje template-a
    public function get_tisak_template() {
        ob_start();
        include TISAK_LOKATOR_ULTIMATE_PATH . 'templates/checkout-tisak-locator.php';
        $html = ob_get_clean();
       
        echo $html;
        wp_die();
    }
    public function get_tisak_lokacije() {
        // Sigurnosna provjera - popravljena
        $nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : (isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '');
       
        if ( ! $nonce || ! wp_verify_nonce($nonce, 'tisak_lokator_nonce') ) {
            // Za development - dopusti bez nonce provjere
            if ( ! defined('WP_DEBUG') || ! WP_DEBUG ) {
                wp_send_json_error( array( 'message' => 'Neautorizirani zahtjev.' ) );
                return;
            }
        }
        $all_stores_cache = Tisak_Cache::get( 'all_stores' );
        if ( $all_stores_cache !== null && count($all_stores_cache) > 0 ) {
            error_log( 'Tisak Lokator: Koristi cache (' . count($all_stores_cache) . ' lokacija)' );
            wp_send_json_success( $all_stores_cache );
            return;
        }
        error_log( 'Tisak Lokator: Dohvaćam lokacije iz API-ja...' );
        $url = 'https://lokator.tisak.hr/Services/Stores.json?searchObject=' . urlencode('{"authToken":"tisak","services":[],"storeTypes":["1","2"]}');
       
        $response = wp_remote_get( $url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'TisakLokatorUltimate/2.0.15 (WordPress Plugin)'
            )
        ));
        if ( is_wp_error( $response ) ) {
            error_log( 'Tisak Lokator: API greška za lokacije: ' . $response->get_error_message() );
            wp_send_json_error( array( 'message' => 'Greška pri dohvaćanju lokacija iz API-ja. Molimo pokušajte kasnije.' ) );
            return;
        }
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( empty( $data ) || !is_array( $data ) ) {
            error_log( 'Tisak Lokator: Nevažeći API odgovor' );
            wp_send_json_error( array( 'message' => 'Nema dostupnih podataka sa API-ja.' ) );
            return;
        }
        $formatted = array();
        $cities_map = array(); // Za lakše debugiranje
        foreach ($data as $store) {
            // Ekstraktuj grad iz adrese
            $city = '';
            if (isset($store['address'])) {
                $address_parts = explode('<br/>', $store['address']);
                if (count($address_parts) >= 2) {
                    // Drugi dio sadrži poštanski broj i grad
                    $postal_city = trim($address_parts[1]);
                    // Regex za hrvatski format: 5 brojeva + razmak + naziv grada
                    if (preg_match('/^\d{5}\s+(.+)$/i', $postal_city, $matches)) {
                        $city = trim($matches[1]);
                    }
                }
            }
            // Ako nemamo grad, preskoči
            if (empty($city)) {
                error_log('Tisak Lokator: Preskačem lokaciju bez grada - ' . $store['name']);
                continue;
            }
            // Provjeri ostale podatke
            if (empty($store['code']) || !isset($store['lat']) || !isset($store['lng']) ||
                empty($store['lat']) || empty($store['lng'])) {
                continue;
            }
            // Brojač po gradovima za debug
            if (!isset($cities_map[$city])) {
                $cities_map[$city] = 0;
            }
            $cities_map[$city]++;
            $formatted[] = array(
                'code' => $store['code'],
                'name' => $store['name'] ?? 'Nepoznato',
                'address' => str_replace('<br/>', ', ', $store['address'] ?? ''),
                'full_address' => str_replace('<br/>', ', ', $store['address'] ?? ''),
                'city' => $city,
                'lat' => (string)$store['lat'],
                'lng' => (string)$store['lng'],
                'type' => intval($store['type'] ?? 1)
            );
        }
        // Debug ispis gradova
        error_log( 'Tisak Lokator: Pronađeni gradovi:' );
        arsort($cities_map);
        $counter = 0;
        foreach ($cities_map as $city => $count) {
            error_log( sprintf(' %s: %d lokacija', $city, $count) );
            if (++$counter >= 10) break;
        }
        if ( count($formatted) > 0 ) {
            Tisak_Cache::set( 'all_stores', $formatted, 2 * HOUR_IN_SECONDS );
            error_log( 'Tisak Lokator: API podaci učitani i cache-ani (' . count($formatted) . ' lokacija u ' . count($cities_map) . ' gradova)' );
        } else {
            error_log( 'Tisak Lokator: Nema lokacija za cache' );
        }
        wp_send_json_success( $formatted );
    }
    public function get_tisak_store_info() {
        // Sigurnosna provjera - popravljena
        $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
       
        if ( ! $nonce || ! wp_verify_nonce($nonce, 'tisak_lokator_nonce') ) {
            // Za development - dopusti bez nonce provjere
            if ( ! defined('WP_DEBUG') || ! WP_DEBUG ) {
                wp_send_json_error( array( 'message' => 'Neautorizirani zahtjev.' ) );
                return;
            }
        }
        $code = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
        if ( empty( $code ) ) {
            wp_send_json_error( array( 'message' => 'Nedostaje kod lokacije.' ) );
            return;
        }
        $cached_details = Tisak_Cache::get_store_details( $code );
        if ( $cached_details !== null ) {
            error_log( 'Tisak Lokator: Koristi cache za store ' . $code );
            wp_send_json_success( $cached_details );
            return;
        }
        $url = 'https://lokator.tisak.hr/Services/StoreInfo.json?searchObject=' . urlencode(json_encode([
            'authToken' => 'tisak',
            'code' => $code
        ]));
        $response = wp_remote_get( $url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'TisakLokatorUltimate/2.0.15 (WordPress Plugin)'
            )
        ));
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
            return;
        }
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( empty( $data ) ) {
            wp_send_json_error( array( 'message' => 'Nevažeći odgovor sa servera.' ) );
            return;
        }
        // Cache rezultat
        Tisak_Cache::set_store_details( $code, $data, 4 * HOUR_IN_SECONDS );
        wp_send_json_success( $data );
    }
}