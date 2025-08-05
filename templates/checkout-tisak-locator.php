<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Provjeri je li admin preview
$is_admin_preview = isset($GLOBALS['tisak_is_admin_preview']) && $GLOBALS['tisak_is_admin_preview'] === true;

// Dohvati postavke
$settings = get_option( 'tisak_lokator_settings', array() );

// Helper funkcija
function tl_get($key, $settings, $default = '') {
    return isset($settings[$key]) ? $settings[$key] : $default;
}

// Sve postavke s defaultima
$section_margin = tl_get('section_margin', $settings, 20);
$section_padding = tl_get('section_padding', $settings, 20);
$section_bg_color = tl_get('section_bg_color', $settings, '#ffffff');
$section_border_color = tl_get('section_border_color', $settings, '#e0e0e0');
$section_border_width = tl_get('section_border_width', $settings, 1);
$section_border_radius = tl_get('section_border_radius', $settings, 8);
$title_color = tl_get('title_color', $settings, '#333333');
$title_size = tl_get('title_size', $settings, 18);
$title_weight = tl_get('title_weight', $settings, 'bold');
$title_margin_bottom = tl_get('title_margin_bottom', $settings, 15);
$font_size_pc = tl_get('font_size_pc', $settings, 16);
$font_size_mobile = tl_get('font_size_mobile', $settings, 14);
$enable_gps = tl_get('enable_gps', $settings, 'on');
$shipping_instance = tl_get('shipping_method_instance', $settings, '');

// Checkout helper
$checkout_value = function($key) use ($is_admin_preview) {
    if ($is_admin_preview) return '';
    return function_exists('WC') && WC()->checkout ? WC()->checkout->get_value($key) : '';
};
?>

<style>
/* Glavni container */
.tisak-lokator-section {
    margin-bottom: <?php echo intval($section_margin); ?>px !important;
    background: <?php echo esc_attr($section_bg_color); ?> !important;
    border: <?php echo intval($section_border_width); ?>px solid <?php echo esc_attr($section_border_color); ?> !important;
    border-radius: <?php echo intval($section_border_radius); ?>px !important;
    padding: <?php echo intval($section_padding); ?>px !important;
    font-size: <?php echo intval($font_size_pc); ?>px !important;
}

.tisak-lokator-section h3 {
    margin-top: 0 !important;
    margin-bottom: <?php echo intval($title_margin_bottom); ?>px !important;
    color: <?php echo esc_attr($title_color); ?> !important;
    font-size: <?php echo intval($title_size); ?>px !important;
    font-weight: <?php echo esc_attr($title_weight); ?> !important;
}

/* Label i GPS button row */
.tisak-label-row {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    margin-bottom: 10px !important;
}

.tisak-label-row label {
    margin-bottom: 0 !important;
    display: inline-block;
    color: <?php echo esc_attr($title_color); ?>;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .tisak-lokator-section {
        font-size: <?php echo intval($font_size_mobile); ?>px !important;
    }
    
    .tisak-label-row {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 10px !important;
    }
}
</style>

<div class="tisak-lokator-wrapper">
    <div class="tisak-lokator-section" id="tisak-lokator-section" style="display: none;">
        <h3><?php _e( 'Odaberite Tisak lokaciju za dostavu', 'tisak-lokator-ultimate' ); ?></h3>

        <div class="tisak-lokacija-wrapper">
            <!-- Label i GPS dugme -->
            <div class="tisak-label-row">
                <label for="tisak_lokacija">
                    <?php _e( 'Tisak lokacija', 'tisak-lokator-ultimate' ); ?> 
                    <abbr class="required" title="required">*</abbr>
                </label>
                
                <?php if ( $enable_gps === 'on' ): ?>
                    <button type="button" class="tisak-gps-btn" id="tisak-gps-btn">
                        <span class="gps-text"><?php _e('Pronađi najbliži', 'tisak-lokator-ultimate'); ?></span>
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- Dropdown -->
            <select name="tisak_lokacija" id="tisak_lokacija" class="tisak-dropdown">
                <option value=""><?php _e('Unesite grad u polju iznad za prikaz lokacija...', 'tisak-lokator-ultimate'); ?></option>
                <?php if ( $is_admin_preview ): ?>
                    <option value="ZAGREB-ILICA - Ilica 1, 10000 Zagreb" 
                            data-code="TK001" 
                            data-lat="45.8131" 
                            data-lng="15.9772"
                            data-name="ZAGREB-ILICA"
                            data-address="Ilica 1, 10000 Zagreb">
                        ZAGREB-ILICA - Ilica 1, 10000 Zagreb
                    </option>
                    <option value="SPLIT-RIVA - Obala HNP 12, 21000 Split" 
                            data-code="TK002" 
                            data-lat="43.5081" 
                            data-lng="16.4402"
                            data-name="SPLIT-RIVA"
                            data-address="Obala HNP 12, 21000 Split">
                        SPLIT-RIVA - Obala HNP 12, 21000 Split
                    </option>
                <?php endif; ?>
            </select>
        </div>

        <!-- Mapa -->
        <div id="tisak-map" class="tisak-map-container"></div>

        <!-- Info -->
        <div id="tisak-info" class="tisak-info-container"></div>

        <!-- Hidden field -->
        <input type="hidden" name="tisak_lokacija_code" id="tisak_lokacija_code" 
               value="<?php echo esc_attr( $checkout_value('tisak_lokacija_code') ); ?>" />
    </div>
</div>

<script type="text/javascript">
jQuery(function($) {
    const isAdminPreview = <?php echo $is_admin_preview ? 'true' : 'false'; ?>;
    const shippingInstance = '<?php echo esc_js($shipping_instance); ?>';
    
    // Toggle prikaza
    function toggleTisakDisplay() {
        const $section = $('#tisak-lokator-section');
        
        if (isAdminPreview) {
            $section.show();
            return;
        }
        
        if (!shippingInstance) {
            console.log('Tisak: Shipping instance not configured');
            $section.hide();
            return;
        }
        
        // Samo classic checkout
        const selectedShipping = $('input[name^="shipping_method"]:checked').val();
        
        console.log('Tisak: Selected shipping:', selectedShipping, 'Required:', shippingInstance);
        
        if (selectedShipping === shippingInstance) {
            $section.show();
            
            // Init mapa ako treba
            if (window.tisakMapNeedsInit && typeof window.initTisakMap === 'function') {
                setTimeout(window.initTisakMap, 200);
                window.tisakMapNeedsInit = false;
            }
        } else {
            $section.hide();
            $('#tisak_lokacija').val('');
            $('#tisak_lokacija_code').val('');
            $('#tisak-info').empty();
        }
    }
    
    // Initial check
    toggleTisakDisplay();
    
    // Classic checkout listeners
    $(document.body).on('change', 'input[name^="shipping_method"]', toggleTisakDisplay);
    $(document.body).on('updated_checkout', function() {
        setTimeout(toggleTisakDisplay, 200);
    });
});
</script>