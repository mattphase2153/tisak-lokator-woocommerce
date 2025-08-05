<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Tisak_Admin {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'wp_ajax_tisak_wizard_save', array( $this, 'save_wizard_settings' ) );
        add_action( 'wp_ajax_tisak_clear_cache', array( $this, 'clear_cache' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }
    public function add_admin_menu() {
        add_menu_page(
            __( 'Tisak Lokator', 'tisak-lokator-ultimate' ),
            __( 'Tisak Lokator', 'tisak-lokator-ultimate' ),
            'manage_options',
            'tisak-lokator',
            array( $this, 'admin_page' ),
            'dashicons-location'
        );
        add_submenu_page(
            'tisak-lokator',
            __( 'Instalacijski čarobnjak', 'tisak-lokator-ultimate' ),
            __( 'Instalacijski čarobnjak', 'tisak-lokator-ultimate' ),
            'manage_options',
            'tisak-lokator-wizard',
            array( $this, 'wizard_page' )
        );
    }
    public function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'tisak-lokator' ) !== false ) {
            wp_enqueue_style( 'tisak-wizard-style', TISAK_LOKATOR_ULTIMATE_URL . 'assets/css/wizard.css', array(), TISAK_LOKATOR_ULTIMATE_VERSION );
            wp_enqueue_script( 'tisak-wizard-script', TISAK_LOKATOR_ULTIMATE_URL . 'assets/js/wizard.js', array( 'jquery' ), TISAK_LOKATOR_ULTIMATE_VERSION, true );
            wp_localize_script( 'tisak-wizard-script', 'tisakWizard', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'tisak_wizard_nonce' )
            ));
            error_log( 'Tisak Lokator: Učitane skripte za admin/wizard na hook=' . $hook );
        }
    }
    public function admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Nemate dopuštenje za pristup ovoj stranici.', 'tisak-lokator-ultimate' ) );
        }
       
        // Dodaj opciju za čišćenje cache
        if ( isset( $_GET['clear_cache'] ) && $_GET['clear_cache'] === '1' ) {
            Tisak_Cache::clear_all();
            wp_redirect( admin_url( 'admin.php?page=tisak-lokator&cache_cleared=1' ) );
            exit;
        }
       
        // Provjeri reset postavki
        if ( isset( $_POST['tisak_reset_settings'] ) &&
             isset( $_POST['tisak_reset_nonce'] ) &&
             wp_verify_nonce( $_POST['tisak_reset_nonce'], 'tisak_reset_settings' ) ) {
           
            $settings_obj = new Tisak_Settings();
            $default_settings = $settings_obj->get_default_settings();
            update_option( 'tisak_lokator_settings', $default_settings );
           
            // Očisti cache nakon resetiranja
            if ( class_exists( 'Tisak_Cache' ) ) {
                Tisak_Cache::clear_all();
            }
           
            wp_redirect( add_query_arg( 'settings-reset', 'success', admin_url( 'admin.php?page=tisak-lokator' ) ) );
            exit;
        }
       
        include TISAK_LOKATOR_ULTIMATE_PATH . 'templates/admin-settings.php';
    }
    public function wizard_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Nemate dopuštenje za pristup ovoj stranici.', 'tisak-lokator-ultimate' ) );
        }
        error_log( 'Tisak Lokator: Pristup čarobnjaku.' );
        $settings_obj = new Tisak_Settings();
        $shipping_instances = $settings_obj->get_shipping_instances();
        include TISAK_LOKATOR_ULTIMATE_PATH . 'templates/wizard.php';
    }
    public function save_wizard_settings() {
        check_ajax_referer( 'tisak_wizard_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Nemate dopuštenje.', 'tisak-lokator-ultimate' ) );
        }
        $settings = get_option( 'tisak_lokator_settings', array() );
        $settings['shipping_method_instance'] = sanitize_text_field( $_POST['shipping_method_instance'] );
        update_option( 'tisak_lokator_settings', $settings );
        update_option( 'tisak_lokator_installed', 'completed' );
        error_log( 'Tisak Lokator: Wizard spremljen, instanca=' . $settings['shipping_method_instance'] );
        wp_send_json_success( array( 'redirect' => admin_url( 'admin.php?page=tisak-lokator' ) ) );
    }
    public function clear_cache() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Nemate dozvolu.' );
        }
       
        Tisak_Cache::clear_all();
        wp_send_json_success( 'Cache očišćen.' );
    }
    public function admin_notices() {
        // Success poruke
        if ( isset( $_GET['cache_cleared'] ) && $_GET['cache_cleared'] === '1' ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong><?php _e( 'Tisak Lokator cache je uspješno očišćen!', 'tisak-lokator-ultimate' ); ?></strong></p>
            </div>
            <?php
        }
       
        if ( isset( $_GET['settings-reset'] ) && $_GET['settings-reset'] === 'success' ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong><?php _e( 'Tisak Lokator postavke su uspješno resetirane!', 'tisak-lokator-ultimate' ); ?></strong></p>
            </div>
            <?php
        }
       
        // Provjera za blocks checkout
        if ( $this->is_using_blocks_checkout() ) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong>Tisak Lokator Ultimate:</strong> <?php _e( 'Ovaj plugin podržava samo klasični WooCommerce checkout. Za korištenje ovog plugina, molimo prebacite se na klasični checkout.', 'tisak-lokator-ultimate' ); ?></p>
                <p><a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=advanced' ); ?>" class="button button-primary"><?php _e( 'Idi na WooCommerce postavke', 'tisak-lokator-ultimate' ); ?></a></p>
            </div>
            <?php
        }
    }
   
    private function is_using_blocks_checkout() {
        // Provjeri postoji li checkout stranica
        $checkout_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'checkout' ) : 0;
       
        if ( $checkout_page_id > 0 ) {
            $checkout_page = get_post( $checkout_page_id );
            if ( $checkout_page && function_exists( 'has_block' ) && has_block( 'woocommerce/checkout', $checkout_page ) ) {
                return true;
            }
        }
        return false;
    }
}