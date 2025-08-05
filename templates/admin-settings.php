<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings_obj = new Tisak_Settings();
$settings = $settings_obj->init_settings();
$option_data = get_option('tisak_lokator_settings');

function tisak_render_section( $settings, $section_id, $option_data ) {
    $section_fields = array();
    $section_title = '';
    $in_section = false;

    foreach ( $settings as $setting ) {
        if ( isset( $setting['type'] ) && $setting['type'] === 'title' && isset( $setting['id'] ) ) {
            if ( $setting['id'] === $section_id ) {
                $section_title = $setting['title'];
                $in_section = true;
            } else {
                $in_section = false;
            }
        } elseif ( isset( $setting['type'] ) && $setting['type'] === 'sectionend' ) {
            if ( $in_section ) {
                break;
            }
        } elseif ( $in_section && isset( $setting['id'] ) ) {
            $section_fields[] = $setting;
        }
    }

    if ( empty( $section_fields ) ) {
        return '';
    }

    ob_start();
    ?>
    <div class="tisak-accordion-item">
        <h3 class="tisak-accordion-header" onclick="toggleAccordion(this)">
            <span class="tisak-accordion-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </span>
            <?php echo esc_html( $section_title ); ?>
        </h3>
        <div class="tisak-accordion-content">
            <?php foreach ( $section_fields as $setting ) : ?>
                <?php
                $field_id = $setting['id'];
                $field_name = esc_attr( $field_id );
                $label = isset( $setting['title'] ) ? esc_html( $setting['title'] ) : '';
                $desc = isset( $setting['desc'] ) ? '<small>' . esc_html( $setting['desc'] ) . '</small>' : '';
                $type = isset( $setting['type'] ) ? $setting['type'] : 'text';

                preg_match('/tisak_lokator_settings\\[([^\\]]+)\\]/', $field_id, $matches);
                $key = $matches[1] ?? '';
                $value = $option_data[$key] ?? $setting['default'] ?? '';
                ?>

                <div class="tisak-setting-row">
                    <label for="<?php echo $field_id; ?>"><?php echo $label; ?></label>

                    <?php if ( $type === 'textarea' ) : ?>
                        <textarea name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" rows="10" cols="50"><?php echo esc_textarea( $value ); ?></textarea>
                    <?php elseif ( $type === 'color' ) : ?>
                        <div class="tisak-color-picker-wrapper">
                            <input type="color" name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" value="<?php echo esc_attr( $value ); ?>" class="tisak-color-picker">
                            <input type="text" value="<?php echo esc_attr( $value ); ?>" class="tisak-color-value" readonly>
                        </div>
                    <?php elseif ( $type === 'number' ) : ?>
                        <?php 
                        $min = isset($setting['min']) ? 'min="' . $setting['min'] . '"' : '';
                        $max = isset($setting['max']) ? 'max="' . $setting['max'] . '"' : '';
                        ?>
                        <input type="number" name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo $min . ' ' . $max; ?>>
                    <?php elseif ( $type === 'text' ) : ?>
                        <input type="text" name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>" value="<?php echo esc_attr( $value ); ?>">
                    <?php elseif ( $type === 'select' && isset( $setting['options'] ) ) : ?>
                        <select name="<?php echo $field_name; ?>" id="<?php echo $field_id; ?>">
                            <?php foreach ( $setting['options'] as $option_key => $option_label ) : ?>
                                <option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $value, $option_key ); ?>>
                                    <?php echo esc_html( $option_label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ( $type === 'radio' && isset( $setting['options'] ) ) : ?>
                        <div class="tisak-radio-group">
                            <?php foreach ( $setting['options'] as $radio_value => $radio_label ) : ?>
                                <label class="tisak-radio-label">
                                    <input type="radio" name="<?php echo $field_name; ?>" value="<?php echo esc_attr( $radio_value ); ?>" <?php checked( $value, $radio_value ); ?>>
                                    <span><?php echo esc_html( $radio_label ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $desc ) : ?>
                        <p class="description"><?php echo $desc; ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Cache sekcija
function tisak_render_cache_section() {
    $cache_info = Tisak_Cache::get_cache_info();
    
    ob_start();
    ?>
    <div class="tisak-accordion-item">
        <h3 class="tisak-accordion-header" onclick="toggleAccordion(this)">
            <span class="tisak-accordion-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </span>
            Predmemorija
        </h3>
        <div class="tisak-accordion-content">
            <div class="tisak-cache-status">
                <div class="tisak-cache-grid">
                    <div class="tisak-cache-card">
                        <h4>Status</h4>
                        <p><strong>Stavke u predmemoriji:</strong> <?php echo $cache_info['cached_items']; ?></p>
                        
                        <?php if ( $cache_info['cached_items'] > 0 ) : ?>
                            <p class="tisak-status-active">Predmemorija je aktivna</p>
                            <p>Stranica se učitava brže!</p>
                        <?php else : ?>
                            <p class="tisak-status-empty">Predmemorija je prazna</p>
                            <p>Prvi put učitavanje može biti sporije.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tisak-cache-card">
                        <h4>Akcije</h4>
                        <a href="<?php echo admin_url( 'admin.php?page=tisak-lokator&clear_cache=1' ); ?>" 
                           class="button button-secondary" 
                           onclick="return confirm('Sigurno želite očistiti predmemoriju?')">
                            Očisti Predmemoriju
                        </a>
                        <p class="description">Koristite ako imate problema s prikazom lokacija.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Preview komponenta - KORISTI ISTI TEMPLATE KAO CHECKOUT
function tisak_render_preview() {
    // Postavi flag da je admin preview
    $GLOBALS['tisak_is_admin_preview'] = true;
    
    ob_start();
    ?>
    <div class="tisak-preview-section">
        <h3>Pregled u stvarnom vremenu</h3>
        <div class="tisak-preview-container">
            <div class="tisak-preview-mockup">
                <?php
                // Uključi isti template koji se koristi na checkout stranici
                include plugin_dir_path(__FILE__) . '../templates/checkout-tisak-locator.php';
                ?>
            </div>
        </div>
    </div>
    <?php
    
    // Ukloni flag
    unset($GLOBALS['tisak_is_admin_preview']);
    
    return ob_get_clean();
}  
?>

<div class="wrap tisak-admin-wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <div class="tisak-admin-container">
        <div class="tisak-admin-sidebar">
            <div class="tisak-settings-panel">
                <form method="post" action="options.php" class="tisak-settings-form">
                    <?php settings_fields( 'tisak_lokator_options' ); ?>
                    
                    <div class="tisak-accordion">
                        <?php
                        // Renderuj sve sekcije
                        echo tisak_render_section( $settings, 'tisak_dostava_options', $option_data );
                        echo tisak_render_section( $settings, 'tisak_layout_options', $option_data );
                        echo tisak_render_section( $settings, 'tisak_section_style', $option_data );
                        echo tisak_render_section( $settings, 'tisak_typography', $option_data );
                        echo tisak_render_section( $settings, 'tisak_dropdown_style', $option_data );
                        echo tisak_render_section( $settings, 'tisak_button_style', $option_data );
                        echo tisak_render_section( $settings, 'tisak_info_style', $option_data );
                        echo tisak_render_section( $settings, 'tisak_map_style', $option_data );
                        echo tisak_render_section( $settings, 'tisak_marker_style', $option_data );
                        // UKLONJENA linija za popup sekciju
                        echo tisak_render_section( $settings, 'tisak_dodatno_options', $option_data );
                        echo tisak_render_cache_section();
                        ?>
                    </div>
                    
                    <div class="tisak-form-actions">
                        <?php submit_button( __( 'Spremi promjene', 'tisak-lokator-ultimate' ), 'primary', 'submit', false ); ?>
                        <button type="button" class="button button-secondary" id="reset-settings-btn" onclick="resetTisakSettings()">
                            <?php _e( 'Resetiraj postavke', 'tisak-lokator-ultimate' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="tisak-admin-main">
            <?php echo tisak_render_preview(); ?>
        </div>
    </div>

    <!-- Skriveni form za resetiranje -->
    <form method="post" id="reset-form" style="display: none;">
        <?php wp_nonce_field( 'tisak_reset_settings', 'tisak_reset_nonce' ); ?>
        <input type="hidden" name="tisak_reset_settings" value="1">
    </form>
</div>

<style>
/* Admin panel stilovi */
.tisak-admin-wrap { 
    max-width: 1400px; 
    margin: 20px 20px 20px 0; 
}

.tisak-admin-container { 
    display: flex; 
    gap: 30px; 
    margin-top: 20px; 
}

.tisak-admin-sidebar { 
    width: 400px; 
    flex-shrink: 0;
}

.tisak-admin-main { 
    flex: 1; 
}

.tisak-settings-panel {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.tisak-settings-form { 
    padding: 0; 
}

/* Accordion stilovi */
.tisak-accordion {
    border-top: 1px solid #e0e0e0;
}

.tisak-accordion-item {
    border-bottom: 1px solid #e0e0e0;
}

.tisak-accordion-header {
    margin: 0;
    padding: 15px 20px;
    background: #fafafa;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    color: #23282d;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.tisak-accordion-header:hover {
    background: #f5f5f5;
}

.tisak-accordion-header.active {
    background: #f0f0f1;
    color: #007cba;
}

.tisak-accordion-icon {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s ease;
}

.tisak-accordion-header.active .tisak-accordion-icon {
    transform: rotate(90deg);
}

.tisak-accordion-content {
    display: none;
    padding: 20px;
    background: #fff;
}

.tisak-accordion-content.active {
    display: block;
}

/* Postavke redovi */
.tisak-setting-row { 
    margin-bottom: 20px; 
}

.tisak-setting-row:last-child {
    margin-bottom: 0;
}

.tisak-setting-row label { 
    display: block; 
    margin-bottom: 8px; 
    font-weight: 600; 
    color: #23282d; 
    font-size: 13px;
}

.tisak-setting-row input[type="text"],
.tisak-setting-row input[type="number"],
.tisak-setting-row select,
.tisak-setting-row textarea { 
    width: 100%; 
}

.tisak-setting-row textarea { 
    font-family: monospace; 
    font-size: 13px;
}

.tisak-setting-row .description { 
    margin-top: 5px; 
    color: #666; 
    font-size: 12px; 
}

/* Color picker */
.tisak-color-picker-wrapper { 
    display: flex; 
    align-items: center; 
    gap: 10px; 
}

.tisak-color-picker { 
    width: 50px; 
    height: 35px; 
    padding: 0; 
    border: 1px solid #ddd; 
    border-radius: 4px; 
    cursor: pointer; 
}

.tisak-color-value { 
    width: 100px !important; 
    font-family: monospace; 
    font-size: 12px;
}

/* Radio grupa */
.tisak-radio-group { 
    display: flex; 
    gap: 20px; 
}

.tisak-radio-label { 
    display: flex; 
    align-items: center; 
    gap: 5px; 
    cursor: pointer; 
    font-size: 13px;
}

.tisak-radio-label input { 
    margin-top: 0; 
}

/* Cache sekcija */
.tisak-cache-status { 
    padding: 0; 
}

.tisak-cache-grid { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 20px; 
}

.tisak-cache-card { 
    background: #f8f9fa; 
    padding: 15px; 
    border-radius: 6px; 
    border: 1px solid #dee2e6; 
}

.tisak-cache-card h4 { 
    margin-top: 0; 
    margin-bottom: 10px; 
    font-size: 13px;
}

.tisak-status-active { 
    color: #28a745; 
    font-weight: bold; 
    font-size: 12px;
}

.tisak-status-empty { 
    color: #ffc107; 
    font-weight: bold; 
    font-size: 12px;
}

/* Preview */
.tisak-preview-section { 
    background: #fff; 
    border: 1px solid #ccd0d4; 
    box-shadow: 0 1px 1px rgba(0,0,0,.04); 
    padding: 20px; 
    position: sticky;
    top: 32px;
}

.tisak-preview-section h3 { 
    margin-top: 0; 
    margin-bottom: 20px; 
}

.tisak-preview-container { 
    background: #f5f5f5; 
    padding: 20px; 
    border-radius: 8px; 
}

.tisak-preview-mockup { 
    max-width: 100%; 
}

/* Preview specifični stilovi koji override-aju inline stilove */
.tisak-preview-mockup .tisak-lokator-section {
    transition: all 0.3s ease;
}

.tisak-preview-mockup .tisak-lokator-section * {
    transition: all 0.3s ease;
}

/* Override za border stil */
.tisak-preview-mockup .tisak-lokator-section {
    border-style: solid !important;
}

.tisak-preview-mockup #tisak-map {
    border-style: solid !important;
}

.tisak-preview-mockup #tisak-info {
    border-style: solid !important;
    background-color: #ffffff !important; /* Info box uvijek bijela */
}

.tisak-preview-mockup select {
    border-style: solid !important;
}

/* Ensure button styles apply */
.tisak-preview-mockup .tisak-gps-btn {
    border: none !important;
    cursor: pointer !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
}

/* Ensure labels are visible */
.tisak-preview-mockup .tisak-label-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.tisak-preview-mockup .tisak-label-row label {
    margin-bottom: 0 !important;
    display: inline-block;
}

.tisak-preview-mockup label[for="tisak_lokacija"] {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.tisak-preview-mockup .required {
    color: #e2401c !important;
}

/* Form actions */
.tisak-form-actions { 
    display: flex; 
    gap: 10px; 
    align-items: center; 
    margin: 0;
    padding: 20px; 
    background: #f8f9fa; 
    border-top: 1px solid #e0e0e0; 
}

/* Responsive */
@media (max-width: 1400px) {
    .tisak-admin-sidebar {
        width: 450px;
    }
}

@media (max-width: 1200px) {
    .tisak-admin-container { 
        flex-direction: column; 
    }
    
    .tisak-admin-sidebar { 
        width: 100%; 
    }
    
    .tisak-preview-section {
        position: static;
    }
}

@media (max-width: 782px) {
    .tisak-admin-wrap {
        margin: 10px 0;
    }
    
    .tisak-cache-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Accordion toggle
function toggleAccordion(header) {
    const content = header.nextElementSibling;
    const allHeaders = document.querySelectorAll('.tisak-accordion-header');
    const allContents = document.querySelectorAll('.tisak-accordion-content');
    
    // Zatvori sve ostale
    allHeaders.forEach(h => {
        if (h !== header) {
            h.classList.remove('active');
        }
    });
    
    allContents.forEach(c => {
        if (c !== content) {
            c.classList.remove('active');
        }
    });
    
    // Toggle trenutni
    header.classList.toggle('active');
    content.classList.toggle('active');
    
    // Spremi stanje
    if (header.classList.contains('active')) {
        localStorage.setItem('tisakActiveAccordion', header.textContent.trim());
    }
}

// Reset settings
function resetTisakSettings() {
    if (confirm('<?php _e( "Jeste li sigurni da želite resetirati sve postavke na zadane vrijednosti? Ova akcija se ne može poništiti.", "tisak-lokator-ultimate" ); ?>')) {
        document.getElementById('reset-form').submit();
    }
}

// Real-time preview update
document.addEventListener('DOMContentLoaded', function() {
    // Otvori zadnji aktivni accordion
    const lastActive = localStorage.getItem('tisakActiveAccordion');
    if (lastActive) {
        const headers = document.querySelectorAll('.tisak-accordion-header');
        headers.forEach(header => {
            if (header.textContent.trim() === lastActive) {
                toggleAccordion(header);
            }
        });
    } else {
        const firstHeader = document.querySelector('.tisak-accordion-header');
        if (firstHeader) {
            toggleAccordion(firstHeader);
        }
    }

    // Color picker sync
    document.querySelectorAll('.tisak-color-picker').forEach(picker => {
        const valueInput = picker.nextElementSibling;
        picker.addEventListener('input', e => {
            valueInput.value = e.target.value;
            updatePreview();
        });
    });

    // GLAVNA FUNKCIJA ZA AŽURIRANJE PREVIEW-a
    const updatePreview = () => {
        // Kreiraj ili ažuriraj style tag za CSS varijable
        let styleTag = document.getElementById('tisak-preview-styles');
        if (!styleTag) {
            styleTag = document.createElement('style');
            styleTag.id = 'tisak-preview-styles';
            document.head.appendChild(styleTag);
        }

        // Dohvati sve vrijednosti
        const settings = {};
        document.querySelectorAll('input[name*="tisak_lokator_settings"], select[name*="tisak_lokator_settings"], textarea[name*="tisak_lokator_settings"]').forEach(input => {
            const match = input.name.match(/tisak_lokator_settings\[([^\]]+)\]/);
            if (match) {
                const key = match[1];
                if (input.type === 'radio') {
                    if (input.checked) {
                        settings[key] = input.value;
                    }
                } else {
                    settings[key] = input.value;
                }
            }
        });

        // Generiraj CSS sa novim vrijednostima
        let css = '.tisak-preview-mockup {\n';
        
        // Sve numeričke vrijednosti koje trebaju px
        const pxValues = [
            'section_margin', 'section_padding', 'section_border_width', 'section_border_radius',
            'title_size', 'title_margin_bottom', 'font_size_pc', 'font_size_mobile',
            'dropdown_padding', 'dropdown_border_radius', 'btn_border_radius', 'btn_padding_x', 
            'btn_padding_y', 'btn_font_size', 'info_padding', 'info_border_radius', 
            'map_height_pc', 'map_border_width', 'map_border_radius', 'marker_size', 
            'marker_active_size'
        ];

        // Postavi CSS varijable
        for (const [key, value] of Object.entries(settings)) {
            if (value) {
                const cssVarName = '--tisak-' + key.replace(/_/g, '-');
                const cssValue = pxValues.includes(key) ? value + 'px' : value;
                css += `    ${cssVarName}: ${cssValue} !important;\n`;
            }
        }
        
        css += '}\n\n';

        // NEMA VIŠE HOVER STILOVA - UKLONILI SMO dropdown_hover_bg i btn_hover_bg

        // Postavi novi CSS
        styleTag.innerHTML = css;

        // Dodatno ažuriraj info box boje direktno
        const infoBox = document.querySelector('.tisak-preview-mockup #tisak-info');
        if (infoBox && infoBox.querySelector('.tisak-info-card')) {
            updateInfoBoxColors(settings);
        }

        // GPS button visibility
        const gpsBtn = document.querySelector('.tisak-preview-mockup .tisak-gps-btn');
        if (gpsBtn && settings.enable_gps) {
            gpsBtn.style.display = settings.enable_gps === 'on' ? 'inline-flex' : 'none';
        }

        // Invalidate map ako se mijenja visina
        if (window.tisakPreviewMap && settings.map_height_pc) {
            setTimeout(() => {
                window.tisakPreviewMap.invalidateSize();
            }, 100);
        }
    };

    // Funkcija za ažuriranje boja u info boxu
    const updateInfoBoxColors = (settings) => {
        const infoCard = document.querySelector('.tisak-preview-mockup .tisak-info-card');
        if (!infoCard) return;

        // Generiraj novi HTML sa ispravnim bojama
        const name = infoCard.querySelector('p:first-child strong')?.textContent || 'Demo lokacija';
        const address = 'Demo adresa, 10000 Zagreb';
        
        const html = `
            <p style="margin: 0 0 10px 0;">
                <strong style="font-size: 16px; color: ${settings.info_title_color || '#212529'};">${name}</strong>
            </p>
            <p style="margin: 0 0 10px 0; color: ${settings.info_text_color || '#6c757d'};">
                <strong style="color: ${settings.info_label_color || '#495057'};">Adresa:</strong> ${address}
            </p>
            <p style="margin: 10px 0 5px 0; color: ${settings.info_label_color || '#495057'};">
                <strong>Radno vrijeme:</strong>
            </p>
            <p style="margin: 0 0 3px 20px; color: ${settings.info_text_color || '#6c757d'};">Pon-Pet: 08:00 - 20:00</p>
            <p style="margin: 0 0 3px 20px; color: ${settings.info_text_color || '#6c757d'};">Subota: 09:00 - 15:00</p>
            <p style="margin: 0 20px; color: ${settings.info_text_color || '#6c757d'};">Nedjelja: Zatvoreno</p>
        `;
        
        infoCard.innerHTML = html;
    };

    // Bind event listeners na sve inpute
    document.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('input', updatePreview);
        input.addEventListener('change', updatePreview);
    });

    // Initial update
    updatePreview();
    
    // Postavi demo podatke nakon delay-a
    setTimeout(() => {
        updatePreview();
        
        // Automatski odaberi prvu lokaciju
        const dropdown = document.querySelector('.tisak-preview-mockup #tisak_lokacija');
        if (dropdown && dropdown.options.length > 1) {
            dropdown.selectedIndex = 1;
            dropdown.dispatchEvent(new Event('change'));
        }
    }, 500);

    // Handler za dropdown u preview-u
    document.addEventListener('change', function(e) {
        if (e.target.matches('.tisak-preview-mockup #tisak_lokacija')) {
            const option = e.target.options[e.target.selectedIndex];
            if (option.value) {
                const settings = {};
                document.querySelectorAll('input[name*="tisak_lokator_settings"]').forEach(input => {
                    const match = input.name.match(/tisak_lokator_settings\[([^\]]+)\]/);
                    if (match) {
                        settings[match[1]] = input.value;
                    }
                });
                updateInfoBoxColors(settings);
            }
        }
    });
});
</script>