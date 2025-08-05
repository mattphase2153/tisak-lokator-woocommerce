<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php 
$settings = get_option( 'tisak_lokator_settings', array() );
?>

<div class="wrap">
    <div class="tisak-wizard-wrap">
        <div class="tisak-wizard-header">
            <h1>Tisak Lokator Ultimate - Čarobnjak za postavljanje</h1>
            <div class="tisak-wizard-steps">
                <div class="step-line"></div>
                <div class="step-line-progress"></div>
                <div class="step active" data-step="1">
                    <span class="step-number">1</span>
                    <span class="step-label">Dobrodošli</span>
                </div>
                <div class="step" data-step="2">
                    <span class="step-number">2</span>
                    <span class="step-label">Metoda dostave</span>
                </div>
                <div class="step" data-step="3">
                    <span class="step-number">3</span>
                    <span class="step-label">Završetak</span>
                </div>
            </div>
        </div>

        <div class="wizard-content">
            <!-- Step 1: Welcome -->
            <div class="wizard-panel step-1">
                <div class="wizard-icon">
                    <img src="<?php echo TISAK_LOKATOR_ULTIMATE_URL; ?>assets/images/tisakLokator-logo-og.png" 
                         alt="Tisak Lokator" 
                         width="100" 
                         height="100">
                </div>
                <h2>DOBRODOŠLI U TISAK LOKATOR ULTIMATE</h2>
                <p class="wizard-lead">Dodatak za WooCommerce koji omogućuje vašim kupcima jednostavan odabir Tisak lokacija za dostavu.</p>
                
                <div class="wizard-features">
                    <div class="wizard-feature">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Interaktivna mapa s svim lokacijama Tisak poslovnica</span>
                    </div>
                    <div class="wizard-feature">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>GPS lociranje najbližih poslovnica</span>
                    </div>
                    <div class="wizard-feature">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Potpuno prilagodljiv dizajn</span>
                    </div>
                    <div class="wizard-feature">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Podrška za klasični WooCommerce checkout</span>
                    </div>
                </div>
                
                <div class="wizard-buttons">
                    <button class="wizard-button wizard-button-primary wizard-next" data-next="2">
                        Započni postavljanje
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Step 2: Shipping Method -->
            <div class="wizard-panel step-2" style="display:none;">
                <div class="wizard-icon">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#0073aa" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                </div>
                <h2>Odaberite metodu dostave</h2>
                <p class="wizard-lead">Odaberite WooCommerce metodu dostave koja će aktivirati Tisak lokator pri naplati.</p>
                
                <div class="wizard-form-group">
                    <label for="tisak-shipping-instance">Dostupne metode dostave:</label>
                    <div class="wizard-select-wrapper">
                        <select id="tisak-shipping-instance" class="wizard-select">
                            <option value="">-- Odaberite metodu --</option>
                            <?php 
                            // Get shipping instances
                            $shipping_instances = array();
                            $zones = WC_Shipping_Zones::get_zones();
                            $zones[0] = WC_Shipping_Zones::get_zone(0); // Add "Rest of the World" zone
                            
                            foreach ($zones as $zone_data) {
                                $zone = is_array($zone_data) ? WC_Shipping_Zones::get_zone($zone_data['zone_id']) : $zone_data;
                                $methods = $zone->get_shipping_methods();
                                
                                foreach ($methods as $instance_id => $method) {
                                    if ($method->is_enabled()) {
                                        $zone_name = is_array($zone_data) ? $zone_data['zone_name'] : __('Rest of the World', 'tisak-lokator-ultimate');
                                        $full_id = $method->id . ':' . $method->instance_id;
                                        $shipping_instances[$full_id] = sprintf('%s - %s (%s)', $method->get_title(), $method->get_method_title(), $zone_name);
                                    }
                                }
                            }
                            
                            $saved_instance = isset($settings['shipping_method_instance']) ? $settings['shipping_method_instance'] : '';
                            
                            foreach ( $shipping_instances as $id => $name ) : ?>
                                <option value="<?php echo esc_attr( $id ); ?>" <?php selected( $saved_instance, $id ); ?>>
                                    <?php echo esc_html( $name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="wizard-help">
                        <span class="dashicons dashicons-info-outline" style="color: #0073aa; font-size: 18px; vertical-align: middle; margin-right: 5px;"></span>
                        <small>Tisak lokator će se prikazati samo kada kupac odabere ovu metodu dostave.</small>
                    </div>
                </div>
                
                <div class="wizard-buttons">
                    <button class="wizard-button wizard-button-secondary wizard-prev" data-prev="1">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                        Nazad
                    </button>
                    <button class="wizard-button wizard-button-primary wizard-next" data-next="3">
                        Dalje
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Step 3: Complete -->
            <div class="wizard-panel step-3" style="display:none;">
                <div class="wizard-icon success">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h2>Sve je spremno!</h2>
                <p class="wizard-lead">Tisak Lokator Ultimate je uspješno konfiguriran i spreman za korištenje.</p>
                
                <div class="wizard-next-steps">
                    <h3>Sljedeći koraci:</h3>
                    <ol>
                        <li>Prilagodite dizajn u postavkama plugina</li>
                        <li>Testirajte checkout proces kao kupac</li>
                        <li>Provjerite da li se lokacije ispravno prikazuju</li>
                    </ol>
                </div>
                
                <div class="wizard-buttons">
                    <button class="wizard-button wizard-button-secondary wizard-prev" data-prev="2">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                        Nazad
                    </button>
                    <button class="wizard-button wizard-button-primary wizard-finish">
                        Završi postavljanje
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Navigation
    $('.wizard-next').on('click', function() {
        const nextStep = $(this).data('next');
        showStep(nextStep);
    });

    $('.wizard-prev').on('click', function() {
        const prevStep = $(this).data('prev');
        showStep(prevStep);
    });

    function showStep(stepNumber) {
        // Hide all panels
        $('.wizard-panel').fadeOut(200, function () {
            $('.wizard-panel.step-' + stepNumber).fadeIn(200);
        });

        // Update step indicators
        $('.step').removeClass('active');
        
        // Mark completed steps
        for (let i = 1; i < stepNumber; i++) {
            $('.step[data-step="' + i + '"]').addClass('completed');
        }

        // Mark current step as active
        $('.step[data-step="' + stepNumber + '"]').addClass('active');

        // Remove completed class from future steps
        for (let i = stepNumber + 1; i <= 3; i++) {
            $('.step[data-step="' + i + '"]').removeClass('completed');
        }

        // Update progress line
        updateProgressLine(stepNumber);
    }

    function updateProgressLine(currentStep) {
        const totalSteps = 3;
        const stepWidth = 100 / (totalSteps - 1);
        const progressPercentage = (currentStep - 1) * stepWidth;
        
        $('.step-line-progress').css('width', progressPercentage + '%');
    }

    // Finish button
    $('.wizard-finish').on('click', function () {
        const shippingInstance = $('#tisak-shipping-instance').val();

        if (!shippingInstance) {
            alert('Molimo odaberite metodu dostave!');
            showStep(2);
            return;
        }
        
        console.log('Spremam shipping instance:', shippingInstance);

        $.post(tisakWizard.ajax_url, {
            action: 'tisak_wizard_save',
            shipping_method_instance: shippingInstance,
            nonce: tisakWizard.nonce
        }, function (response) {
            console.log('Response:', response);
            if (response.success) {
                window.location.href = response.data.redirect;
            } else {
                alert('Greška pri spremanju postavki: ' + (response.data || 'Nepoznata greška'));
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            alert('Greška pri komunikaciji sa serverom.');
        });
    });

    // Initialize progress line on load
    updateProgressLine(1);
});
</script>