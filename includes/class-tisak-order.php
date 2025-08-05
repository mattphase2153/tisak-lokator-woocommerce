<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Tisak_Order {
    public function __construct() {
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta' ) );
        add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'display_order_meta' ) );
    }
    public function update_order_meta( $order_id ) {
        if ( isset( $_POST['tisak_lokacija'] ) ) {
            update_post_meta( $order_id, '_tisak_lokacija', sanitize_text_field( $_POST['tisak_lokacija'] ) );
        }
        if ( isset( $_POST['tisak_lokacija_code'] ) ) {
            update_post_meta( $order_id, '_tisak_lokacija_code', sanitize_text_field( $_POST['tisak_lokacija_code'] ) );
        }
    }
    public function display_order_meta( $order ) {
        $tisak_lokacija = get_post_meta( $order->get_id(), '_tisak_lokacija', true );
        $tisak_lokacija_code = get_post_meta( $order->get_id(), '_tisak_lokacija_code', true );
        if ( $tisak_lokacija ) {
            echo '<p><strong>Tisak Lokacija:</strong> ' . esc_html( $tisak_lokacija ) . '</p>';
        }
        if ( $tisak_lokacija_code ) {
            echo '<p><strong>Šifra prodajnog mjesta:</strong> ' . esc_html( $tisak_lokacija_code ) . '</p>';
        } else {
            echo '<p><strong>Šifra prodajnog mjesta:</strong> N/A</p>';
        }
    }
}