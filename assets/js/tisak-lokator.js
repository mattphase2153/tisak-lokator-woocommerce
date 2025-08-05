jQuery(function ($) {
    let map = null;
    let allStores = [];
    let filteredStores = [];
    let allMarkers = [];
    let filteredMarkers = [];
    let currentAjaxRequest = null;
    let userLocation = null;
    let userMarker = null;
    let selectedMarker = null;
    // Provjera postojanja potrebnih objekata
    if (typeof tisakSettings === 'undefined') {
        console.error('tisakSettings objekt nije definiran');
        return;
    }
    // ===== ADMIN PREVIEW PODRŠKA =====
    // Provjeri je li admin preview
    const isAdminPreview = $('body').hasClass('wp-admin') && $('.tisak-preview-mockup').length > 0;
   
    // Ako je admin preview, modificiraj createGPSButton funkciju
    if (isAdminPreview) {
        // Override createGPSButton za admin
        window.originalCreateGPSButton = createGPSButton;
        createGPSButton = function() {
            const $tisakSection = $('.tisak-lokator-section');
           
            if (tisakSettings.enable_gps !== 'on') {
                return;
            }
           
            if ($tisakSection.find('.tisak-gps-btn').length > 0) {
                return;
            }
            const buttonHtml = `
                <div class="tisak-gps-container" style="margin: 8px 0; text-align: center;">
                    <button type="button" class="tisak-gps-btn">
                        <span class="gps-text">Pronađi najbliži</span>
                    </button>
                    <div class="tisak-gps-status" style="
                        margin-top: 10px;
                        font-size: 12px;
                        color: #666;
                        font-weight: 500;
                    "></div>
                </div>
            `;
            // Za admin preview, dodaj nakon h3
            const $h3 = $tisakSection.find('h3').first();
            if ($h3.length) {
                $h3.after(buttonHtml);
            }
            if (tisakSettings.enable_log === 'on') {
                console.log('GPS dugme kreirano (admin preview)');
            }
        };
    }
    // ===== KRAJ ADMIN PREVIEW PODRŠKE =====
    // Funkcija za normalizaciju teksta (uklanjanje dijakritika)
    const normalizeCity = (cityName) => {
        if (!cityName) return '';
        return cityName.toLowerCase()
            .trim()
            .replace(/[šđčćž]/g, c => ({ 'š': 's', 'đ': 'd', 'č': 'c', 'ć': 'c', 'ž': 'z' }[c] || c))
            .replace(/\s+/g, ' ');
    };
    // Debounce funkcija za optimizaciju
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    // GPS funkcionalnost sa try/catch
    function getUserLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('GPS nije podržan u ovom pregledniku'));
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const location = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    };
                   
                    if (tisakSettings.enable_log === 'on') {
                        console.log('GPS lokacija dobivena:', location);
                    }
                   
                    resolve(location);
                },
                (error) => {
                    let errorMessage = 'Greška pri GPS lokaciji';
                   
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'GPS pristup odbačen';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'GPS nedostupan';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'GPS timeout';
                            break;
                    }
                   
                    if (tisakSettings.enable_log === 'on') {
                        console.warn('Geolocation greška:', errorMessage);
                    }
                   
                    reject(new Error(errorMessage));
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000
                }
            );
        });
    }
    // Dodaj marker za korisnikovu lokaciju
    function addUserLocationMarker(lat, lng) {
        if (!map) return;
        if (userMarker) {
            map.removeLayer(userMarker);
        }
        // Kreiraj plavi marker za korisnika
        const userIcon = L.divIcon({
            html: '<div style="background: #007bff; width: 14px; height: 14px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,255,0.4); position: relative; z-index: 1000;"></div>',
            iconSize: [20, 20],
            iconAnchor: [10, 10],
            className: 'user-location-marker'
        });
        userMarker = L.marker([lat, lng], {
            icon: userIcon,
            zIndexOffset: 1000
        })
            .addTo(map)
            .bindPopup('Vaša lokacija')
            .openPopup();
        if (tisakSettings.enable_log === 'on') {
            console.log('User marker dodan na mapu:', lat, lng);
        }
    }
    // Kreiraj GPS dugme
    function createGPSButton() {
        const $tisakSection = $('.tisak-lokator-section');
       
        if (tisakSettings.enable_gps !== 'on') {
            return;
        }
       
        // Provjeri postoji li već dugme (jer ga sad kreiramo u PHP-u)
        if ($tisakSection.find('.tisak-gps-btn').length > 0 || $('#tisak-gps-btn').length > 0) {
            if (tisakSettings.enable_log === 'on') {
                console.log('GPS dugme već postoji');
            }
            return;
        }
        // Ova funkcija se više neće izvršavati jer dugme već postoji u HTML-u
        // Ali ostavljamo je zbog backward compatibility
       
        const buttonHtml = `
            <button type="button" class="tisak-gps-btn" id="tisak-gps-btn">
                <span class="gps-text">Pronađi najbliži</span>
            </button>
        `;
        // Pronađi label row
        const $labelRow = $tisakSection.find('.tisak-label-row');
        if ($labelRow.length) {
            $labelRow.append(buttonHtml);
        }
        if (tisakSettings.enable_log === 'on') {
            console.log('GPS dugme kreirano iz JS');
        }
    }
    // GPS handler sa boljim error handling-om
    function handleGPSLocation() {
        const $btn = $('.tisak-gps-btn, #tisak-gps-btn'); // Podrška za oba selektora
        const $status = $('.tisak-gps-status');
        const $icon = $btn.find('.gps-icon');
        const $text = $btn.find('.gps-text');
       
        // Ako nema status div-a, kreiraj ga
        if ($status.length === 0) {
            $btn.after('<div class="tisak-gps-status" style="margin-top: 10px; font-size: 12px; color: #666; font-weight: 500;"></div>');
        }
       
        // Loading state
        $btn.addClass('loading').prop('disabled', true);
        $icon.text('⏳');
        $text.text('Tražim...');
        $('.tisak-gps-status').text('Pristupam GPS-u...');
       
        getUserLocation()
            .then(location => {
                userLocation = location;
               
                $('.tisak-gps-status').text(`Lokacija pronađena (${location.accuracy.toFixed(0)}m preciznost)`);
               
                if (map) {
                    addUserLocationMarker(location.lat, location.lng);
                }
               
                const nearestStores = findNearestStores(allStores, userLocation.lat, userLocation.lng, 10);
               
                if (nearestStores.length === 0) {
                    throw new Error('Nema Tisak lokacija u blizini');
                }
               
                const nearest = nearestStores[0];
                $('.tisak-gps-status').text(`Najbliži: ${nearest.store.name} (${nearest.distance.toFixed(1)} km)`);
               
                displayNearestStores(nearestStores);
                selectNearestStore(nearest.store);
               
                // Zoom na korisnika i najbliže lokacije
                if (map) {
                    const bounds = L.latLngBounds([
                        [userLocation.lat, userLocation.lng],
                        ...nearestStores.slice(0, 5).map(item => [
                            parseFloat(item.store.lat),
                            parseFloat(item.store.lng)
                        ])
                    ]);
                    map.fitBounds(bounds, { padding: [50, 50] });
                }
               
                if (tisakSettings.enable_log === 'on') {
                    console.log('GPS rezultat:', nearestStores);
                }
            })
            .catch(error => {
                console.warn('GPS Error:', error.message);
                $('.tisak-gps-status').text('GPS nedostupan: ' + error.message);
            })
            .finally(() => {
                // Reset button
                $btn.removeClass('loading').prop('disabled', false);
                $text.text('Pronađi najbliži');
               
                // Hide status after 5 seconds
                setTimeout(() => {
                    $('.tisak-gps-status').fadeOut();
                }, 5000);
            });
    }
    // Dohvati sve Tisak lokacije kroz WordPress AJAX sa error handling-om
    function getAllTisakStores() {
        return new Promise((resolve, reject) => {
            if (allStores.length > 0) {
                resolve(allStores);
                return;
            }
            if (currentAjaxRequest) {
                currentAjaxRequest.abort();
            }
            currentAjaxRequest = $.ajax({
                url: tisakSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_tisak_lokacije',
                    _wpnonce: tisakSettings.nonce
                },
                success: function(response) {
                    if (response.success && Array.isArray(response.data)) {
                        allStores = response.data;
                       
                        if (tisakSettings.enable_log === 'on') {
                            console.log('Sve Tisak lokacije dohvaćene:', allStores.length);
                           
                            // Statistika po gradovima
                            const cityCounts = {};
                            allStores.forEach(store => {
                                const city = store.city || 'Nepoznato';
                                cityCounts[city] = (cityCounts[city] || 0) + 1;
                            });
                           
                            console.log('Lokacije po gradovima:', cityCounts);
                        }
                       
                        resolve(allStores);
                    } else {
                        reject(new Error('Greška pri dohvaćanju lokacija sa API-ja'));
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    if (textStatus !== 'abort') {
                        console.error('API fetch error:', errorThrown);
                        reject(new Error(`API greška: ${errorThrown}`));
                    }
                },
                complete: function() {
                    currentAjaxRequest = null;
                }
            });
        });
    }
    // Pronađi N najbližih lokacija
    function findNearestStores(stores, userLat, userLng, maxResults = 5) {
        const storesWithDistance = stores.map(store => {
            const lat = parseFloat(store.lat);
            const lng = parseFloat(store.lng);
           
            if (isNaN(lat) || isNaN(lng)) {
                console.warn('Invalid coordinates for store:', store.name);
                return null;
            }
            const distance = calculateDistance(userLat, userLng, lat, lng);
           
            return {
                store: store,
                distance: distance
            };
        }).filter(item => item !== null);
        return storesWithDistance
            .sort((a, b) => a.distance - b.distance)
            .slice(0, maxResults);
    }
    // Prikaži najbliže lokacije u dropdownu
    function displayNearestStores(nearestStores) {
        const $select = $('#tisak_lokacija');
        if (!$select.length) return;
       
        $select.empty().append('<option value="">Najbliže lokacije</option>');
        nearestStores.forEach((item, index) => {
            const store = item.store;
            const distance = item.distance;
            const ranking = index + 1;
           
            const name = store.name || 'Nepoznato';
            const address = store.full_address || store.address || '';
            const label = `${ranking}. ${name} (${distance.toFixed(1)} km)`;
            $select.append($('<option>', {
                value: `${name} - ${address}`,
                text: label,
                'data-code': store.code,
                'data-lat': store.lat,
                'data-lng': store.lng,
                'data-name': store.name,
                'data-address': store.full_address,
                'data-distance': distance.toFixed(1)
            }));
        });
    }
    // Automatski odaberi najbliži store
    function selectNearestStore(store) {
        const $select = $('#tisak_lokacija');
        if (!$select.length) return;
       
        const $option = $select.find(`option[data-code="${store.code}"]`);
       
        if ($option.length) {
            $select.val($option.val()).trigger('change');
            $('#tisak_lokacija_code').val(store.code);
           
            if (tisakSettings.enable_log === 'on') {
                console.log('Automatski odabran najbliži store:', store.name);
            }
           
            updateSelectedStore(store);
        }
    }
    // Filtriraj lokacije po gradu
    function filterStoresByCity(city) {
        if (!city || city.length < 2) {
            filteredStores = [];
            return filteredStores;
        }
        const normalizedInputCity = normalizeCity(city);
       
        filteredStores = allStores.filter(store => {
            if (!store.city) return false;
           
            const storeCity = normalizeCity(store.city);
           
            // Exact match grada
            return storeCity === normalizedInputCity ||
                   storeCity.includes(normalizedInputCity) ||
                   normalizedInputCity.includes(storeCity);
        });
        if (tisakSettings.enable_log === 'on') {
            console.log(`Filtrirane lokacije za "${city}":`, filteredStores.length);
        }
        return filteredStores;
    }
    // Dohvaćanje detalja lokacije iz API-ja sa error handling-om
    function fetchStoreDetails(code, callback) {
        if (!code) {
            callback({});
            return;
        }
        console.log('Dohvaćam detalje za kod:', code); // DEBUG
        $.ajax({
            url: tisakSettings.ajax_url,
            type: 'GET',
            data: {
                action: 'get_tisak_store_info',
                code: code,
                _wpnonce: tisakSettings.nonce
            },
            success: function(response) {
                console.log('API response:', response); // DEBUG
                if (response.success && response.data) {
                    console.log('Detalji dohvaćeni:', response.data); // DEBUG
                    callback(response.data);
                } else {
                    console.error('Greška pri dohvaćanju detalja lokacije:', response);
                    callback({});
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                console.error('AJAX greška pri dohvaćanju detalja lokacije:', textStatus, errorThrown);
                callback({});
            }
        });
    }
    // Izračunavanje udaljenosti između dvije točke
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) ** 2 +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) ** 2;
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }
    // Renderiranje opcija u dropdown
    function renderDropdownOptions(stores) {
        const $select = $('#tisak_lokacija');
        if (!$select.length) return;
       
        $select.empty();
        if (!stores.length) {
            $select.append('<option value="">Nema dostupnih lokacija u ovom gradu</option>');
            return;
        }
        $select.append('<option value="">Odaberite lokaciju...</option>');
        stores.forEach(store => {
            const name = store.name || 'Nepoznato';
            const address = store.full_address || store.address || '';
            const label = `${name} - ${address}`;
            $select.append($('<option>', {
                value: label,
                text: label,
                'data-code': store.code,
                'data-lat': store.lat,
                'data-lng': store.lng,
                'data-name': store.name,
                'data-address': store.full_address || store.address
            }));
        });
        if (tisakSettings.enable_log === 'on') {
            console.log('Dropdown popunjen sa', stores.length, 'lokacija');
        }
    }
    // Inicijalizacija i ažuriranje mape
    function initializeMap() {
        const $mapContainer = $('#tisak-map');
        if (!$mapContainer.length) return;
        // Provjeri da kontejner ima dimenzije
        if ($mapContainer.width() === 0 || $mapContainer.height() === 0) {
            $mapContainer.css({
                width: tisakSettings.map_width || '500px',
                height: tisakSettings.map_height || '300px'
            });
        }
        if (!map) {
            try {
                map = L.map('tisak-map', {
                    maxZoom: 18,
                    minZoom: 6,
                    preferCanvas: true // Bolje performanse za puno markera
                }).setView([45.8150, 15.9819], 7); // Centar Hrvatske
               
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
               
                // Force refresh nakon inicijalizacije
                setTimeout(() => {
                    map.invalidateSize();
                }, 100);
               
                if (tisakSettings.enable_log === 'on') {
                    console.log('Mapa inicijalizirana uspješno');
                }
            } catch (error) {
                console.error('Greška pri inicijalizaciji mape:', error);
            }
        }
    }
    // Dodavanje svih markera na mapu sa provjerom lat/lng
    function addAllMarkersToMap() {
        if (!map || !allStores.length) return;
        // Ukloni postojeće markere
        allMarkers.forEach(marker => map.removeLayer(marker));
        allMarkers = [];
        // Kreiraj marker cluster grupu ako ima puno markera
        const markers = L.featureGroup();
        allStores.forEach(store => {
            const lat = parseFloat(store.lat);
            const lng = parseFloat(store.lng);
           
            if (isNaN(lat) || isNaN(lng)) {
                console.warn('Invalid coordinates for store:', store.name);
                return;
            }
            // Različite boje za različite gradove ili sve crveno
            const customIcon = L.divIcon({
                html: `<div style="
                    background: rgba(220, 53, 69, 0.7);
                    width: 12px;
                    height: 12px;
                    border-radius: 50%;
                    border: 2px solid white;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                "></div>`,
                iconSize: [16, 16],
                iconAnchor: [8, 8],
                className: 'tisak-store-marker-small'
            });
            const marker = L.marker([lat, lng], {
                icon: customIcon,
                opacity: 0.7
            }).addTo(map);
                       
            // DODAJ CLICK EVENT HANDLER
            marker.on('click', function() {
                console.log('Marker clicked:', store.name);
               
                // Direktno pozovi updateSelectedStore da prikaže info
                updateSelectedStore(store);
               
                // Postavi dropdown ako je lokacija u njemu
                const $select = $('#tisak_lokacija');
                const optionValue = `${store.name} - ${store.address || store.full_address || ''}`;
               
                // Pokušaj postaviti vrijednost u dropdown
                $select.val(optionValue);
               
                // Ako nije u dropdownu (npr. drugi grad), dodaj privremeno
                if ($select.val() !== optionValue) {
                    // Dodaj opciju ako ne postoji
                    if ($select.find(`option[value="${optionValue}"]`).length === 0) {
                        $select.append($('<option>', {
                            value: optionValue,
                            text: optionValue,
                            'data-code': store.code,
                            'data-lat': store.lat,
                            'data-lng': store.lng,
                            'data-name': store.name,
                            'data-address': store.full_address || store.address
                        }));
                    }
                    $select.val(optionValue);
                }
               
                // Postavi kod
                $('#tisak_lokacija_code').val(store.code);
            });
           
            marker.storeData = store;
            allMarkers.push(marker);
            markers.addLayer(marker);
        });
        if (tisakSettings.enable_log === 'on') {
            console.log('Dodano', allMarkers.length, 'markera na mapu (sve lokacije)');
        }
    }
    // Označi filtrirane markere
    function highlightFilteredMarkers(city) {
        if (!map || !allMarkers.length) return;
        const normalizedCity = normalizeCity(city);
        allMarkers.forEach(marker => {
            const store = marker.storeData;
            const storeCity = normalizeCity(store.city);
            const isInCity = storeCity === normalizedCity ||
                           storeCity.includes(normalizedCity) ||
                           normalizedCity.includes(storeCity);
            if (isInCity) {
                // Povećaj i istakni markere u gradu
                marker.setIcon(L.icon({
                    iconUrl: tisakSettings.plugin_url + 'assets/images/TisakMarker.png',
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                    popupAnchor: [0, -32]
                }));
                marker.setOpacity(1);
                marker.setZIndexOffset(1000);
               
                // Ažuriraj popup za filtrirane markere - SAMO NAZIV
                marker.bindPopup(`<strong>${store.name}</strong>`);
               
                // DODAJ CLICK HANDLER I ZA FILTRIRANE MARKERE
                marker.off('click'); // Ukloni stari handler
                marker.on('click', function() {
                    console.log('Filtered marker clicked:', store.name);
                   
                    // Direktno pozovi updateSelectedStore
                    updateSelectedStore(store);
                   
                    const $select = $('#tisak_lokacija');
                    const optionValue = `${store.name} - ${store.address || store.full_address || ''}`;
                   
                    $select.val(optionValue);
                   
                    if ($select.val() !== optionValue) {
                        // Dodaj opciju ako ne postoji
                        if ($select.find(`option[value="${optionValue}"]`).length === 0) {
                            $select.append($('<option>', {
                                value: optionValue,
                                text: optionValue,
                                'data-code': store.code,
                                'data-lat': store.lat,
                                'data-lng': store.lng,
                                'data-name': store.name,
                                'data-address': store.full_address || store.address
                            }));
                        }
                        $select.val(optionValue);
                    }
                   
                    $('#tisak_lokacija_code').val(store.code);
                });
            } else {
                // Smanji ostale markere
                marker.setIcon(L.divIcon({
                    html: `<div style="
                        background: rgba(220, 53, 69, 0.3);
                        width: 8px;
                        height: 8px;
                        border-radius: 50%;
                        border: 1px solid rgba(255,255,255,0.5);
                    "></div>`,
                    iconSize: [10, 10],
                    iconAnchor: [5, 5],
                    className: 'tisak-store-marker-dimmed'
                }));
                marker.setOpacity(0.4);
                marker.setZIndexOffset(0);
               
                // DODAJ CLICK HANDLER I ZA SMANJENE MARKERE
                marker.off('click'); // Ukloni stari handler
                marker.on('click', function() {
                    console.log('Dimmed marker clicked:', store.name);
                   
                    // Direktno pozovi updateSelectedStore
                    updateSelectedStore(store);
                   
                    // Dodaj u dropdown ako ne postoji
                    const $select = $('#tisak_lokacija');
                    const optionValue = `${store.name} - ${store.address || store.full_address || ''}`;
                   
                    if ($select.find(`option[value="${optionValue}"]`).length === 0) {
                        $select.append($('<option>', {
                            value: optionValue,
                            text: optionValue,
                            'data-code': store.code,
                            'data-lat': store.lat,
                            'data-lak-lng': store.lng,
                            'data-name': store.name,
                            'data-address': store.full_address || store.address
                        }));
                    }
                    $select.val(optionValue);
                    $('#tisak_lokacija_code').val(store.code);
                   
                    // Centriraj mapu na taj marker
                    const lat = parseFloat(store.lat);
                    const lng = parseFloat(store.lng);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        map.setView([lat, lng], 14);
                    }
                });
            }
        });
        // Zoom na filtrirane lokacije
        if (filteredStores.length > 0) {
            const bounds = L.latLngBounds(filteredStores.map(store => [
                parseFloat(store.lat),
                parseFloat(store.lng)
            ]));
            map.fitBounds(bounds, { padding: [50, 50] });
        }
    }
    // NOVA FUNKCIJA - Spremi u cache
    let storeDetailsCache = {};
    // ISPRAVLJENA FUNKCIJA ZA PRIKAZ INFORMACIJA
    function displayStoreInfo(store, details = null) {
        console.log('displayStoreInfo called with:', store, details);
       
        const $infoDiv = $('#tisak-info');
       
        if (!$infoDiv.length) {
            console.error('Info div not found!');
            return;
        }
        // Kreiraj HTML
        let html = '<div class="tisak-info-card">';
       
        // Naziv
        html += `<p style="margin: 0 0 10px 0;"><strong style="font-size: 16px;">${store.name || 'Nepoznato'}</strong></p>`;
       
        // Adresa
        if (store.full_address || store.address) {
            html += `<p style="margin: 0 0 10px 0;"><strong>Adresa:</strong> ${store.full_address || store.address}</p>`;
        }
       
        // Radno vrijeme
        html += '<p style="margin: 10px 0 5px 0;"><strong>Radno vrijeme:</strong></p>';
       
        // Ako imamo detalje iz API-ja, koristi ih
        if (details && details.businessHours && details.businessHours.workday) {
            html += `<p style="margin: 0 0 3px 20px;">Pon-Pet: ${details.businessHours.workday}</p>`;
            html += `<p style="margin: 0 0 3px 20px;">Subota: ${details.businessHours.saturday || '08:00 - 14:00'}</p>`;
            html += `<p style="margin: 0 20px;">Nedjelja: ${details.businessHours.sunday || 'Zatvoreno'}</p>`;
        } else if (storeDetailsCache[store.code]) {
            // Ako imamo cache podatke
            const cached = storeDetailsCache[store.code];
            html += `<p style="margin: 0 0 3px 20px;">Pon-Pet: ${cached.businessHours.workday}</p>`;
            html += `<p style="margin: 0 0 3px 20px;">Subota: ${cached.businessHours.saturday || '08:00 - 14:00'}</p>`;
            html += `<p style="margin: 0 20px;">Nedjelja: ${cached.businessHours.sunday || 'Zatvoreno'}</p>`;
        } else {
            // Loading indikator dok čekamo API
            html += '<p style="margin: 0 0 3px 20px; color: #666; font-style: italic;">Učitavam radno vrijeme...</p>';
        }
       
        html += '</div>';
       
        // Postavi HTML i osiguraj vidljivost
        $infoDiv.html(html).show();
       
        // Postavi kod
        $('#tisak_lokacija_code').val(store.code || '');
        // Označi odabrani marker na mapi
        if (map) {
            allMarkers.forEach(marker => {
                if (marker.storeData && marker.storeData.code === store.code) {
                    marker.setIcon(L.icon({
                        iconUrl: tisakSettings.plugin_url + 'assets/images/TisakMarker.png',
                        iconSize: [40, 40],
                        iconAnchor: [20, 40],
                        popupAnchor: [0, -40]
                    }));
                    marker.setZIndexOffset(2000);
                    selectedMarker = marker;
                   
                    const lat = parseFloat(store.lat);
                    const lng = parseFloat(store.lng);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        map.setView([lat, lng], 14);
                    }
                } else if (marker.storeData) {
                    marker.setIcon(L.icon({
                        iconUrl: tisakSettings.plugin_url + 'assets/images/TisakMarker.png',
                        iconSize: [32, 32],
                        iconAnchor: [16, 32],
                        popupAnchor: [0, -32]
                    }));
                    marker.setZIndexOffset(1000);
                }
            });
        }
    }
    // ISPRAVLJENA FUNKCIJA ZA AŽURIRANJE LOKACIJE
    function updateSelectedStore(store) {
        console.log('updateSelectedStore called with:', store);
       
        if (!store || !store.code) {
            $('#tisak-info').html('<p style="color: #999; font-style: italic;">Nema odabrane lokacije.</p>');
            return;
        }
        // Prvo prikaži sa loading indikatorom
        displayStoreInfo(store, null);
       
        // Provjeri cache
        if (storeDetailsCache[store.code]) {
            // Ako imamo u cache-u, prikaži odmah
            displayStoreInfo(store, storeDetailsCache[store.code]);
            return;
        }
       
        // Dohvati iz API-ja
        fetchStoreDetails(store.code, function(details) {
            console.log('API returned details:', details);
           
            if (details && Object.keys(details).length > 0) {
                // Spremi u cache
                storeDetailsCache[store.code] = details;
                // Ažuriraj prikaz
                displayStoreInfo(store, details);
            } else {
                // Ako nema API podataka, prikaži default
                const defaultDetails = {
                    businessHours: {
                        workday: "08:00 - 20:00",
                        saturday: "08:00 - 14:00",
                        sunday: "Zatvoreno"
                    }
                };
                storeDetailsCache[store.code] = defaultDetails;
                displayStoreInfo(store, defaultDetails);
            }
        });
    }
    // Pronađi grad iz različitih input polja
    function getCityFromInputs() {
        // Lista selektora za grad
        const citySelectors = [
            '#billing_city',
            'input[name="billing_city"]',
            'input[id*="billing-city"]',
            'input[id*="city"]',
            '.wc-block-components-text-input input[type="text"]'
        ];
       
        for (let selector of citySelectors) {
            const $input = $(selector);
            if ($input.length && $input.val()) {
                return $input.val().trim();
            }
        }
       
        // Fallback - provjeri sve text inpute
        const $allInputs = $('input[type="text"]');
        for (let i = 0; i < $allInputs.length; i++) {
            const input = $allInputs[i];
            const placeholder = $(input).attr('placeholder') || '';
            const label = $(input).closest('label').text() || '';
            const parentText = $(input).parent().text() || '';
           
            if ((placeholder.toLowerCase().includes('city') ||
                 placeholder.toLowerCase().includes('grad') ||
                 label.toLowerCase().includes('city') ||
                 label.toLowerCase().includes('grad') ||
                 parentText.toLowerCase().includes('town') ||
                 parentText.toLowerCase().includes('city')) &&
                $(input).val()) {
                return $(input).val().trim();
            }
        }
       
        return '';
    }
    // Glavna funkcija za ažuriranje UI-ja
    function updateTisakUI() {
        // Ako je admin preview mode, ne ažuriraj
        if (window.tisakAdminPreviewMode) {
            console.log('Admin preview mode - skipping updateTisakUI');
            return;
        }
       
        const city = getCityFromInputs();
        const $tisakSelect = $('#tisak_lokacija');
        const $mapDiv = $('#tisak-map');
        const $infoDiv = $('#tisak-info');
        const $codeInput = $('#tisak_lokacija_code');
        // Provjera je li admin preview
        const isAdminPreview = $('body').hasClass('wp-admin') && $('.tisak-preview-mockup').length > 0;
       
        if (isAdminPreview) {
            console.log('Admin preview mode - skipping city check');
            // Za admin preview, samo inicijaliziraj mapu i izađi
            initializeMap();
            createGPSButton();
           
            // Postavi demo info
            if ($infoDiv.length && $infoDiv.is(':empty')) {
                $infoDiv.html(`
                    <p style="color: #999; font-style: italic;">
                        Odaberite lokaciju iz dropdown-a ili kliknite na marker na mapi za prikaz informacija
                    </p>
                `);
            }
           
            // Dodaj demo markere za admin
            if (allStores.length === 0 && map) {
                // Demo podaci za admin
                const demoStores = [
                    {code: 'DEMO1', name: 'ZAGREB-CENTAR', address: 'Ilica 1, 10000 Zagreb', full_address: 'Ilica 1, 10000 Zagreb', lat: '45.8131', lng: '15.9772', city: 'Zagreb'},
                    {code: 'DEMO2', name: 'SPLIT-RIVA', address: 'Riva 1, 21000 Split', full_address: 'Riva 1, 21000 Split', lat: '43.5081', lng: '16.4402', city: 'Split'},
                    {code: 'DEMO3', name: 'RIJEKA-KORZO', address: 'Korzo 1, 51000 Rijeka', full_address: 'Korzo 1, 51000 Rijeka', lat: '45.3271', lng: '14.4422', city: 'Rijeka'}
                ];
                allStores = demoStores;
                addAllMarkersToMap();
            }
           
            return; // Prekini izvršavanje za admin
        }
       
        // Ostatak koda nastavlja normalno...
        if (tisakSettings.enable_log === 'on') {
            console.log('Ažuriram Tisak UI za grad:', city);
        }
        if (!$tisakSelect.length || !$mapDiv.length) {
            if (tisakSettings.enable_log === 'on') {
                console.log('Tisak elementi nisu pronađeni na stranici');
            }
            return;
        }
        initializeMap();
        createGPSButton();
        // Dohvati sve lokacije i prikaži na mapi
        getAllTisakStores().then(() => {
            // Prikaži sve markere na mapi (samo jednom)
            if (allMarkers.length === 0) {
                addAllMarkersToMap();
            }
            if (!$infoDiv.find('.tisak-info-card').length) {
                $infoDiv.html('');
            }
            if (!city) {
                $tisakSelect.empty().append('<option value="">Unesite grad za prikaz lokacija</option>');
                // NE BRIŠI info ako je već odabrana lokacija
                if (!$('#tisak_lokacija_code').val()) {
                    $infoDiv.html('');
                }
                return;
            }
            // Filtriraj po gradu
            const filtered = filterStoresByCity(city);
            renderDropdownOptions(filtered);
           
            // Istakni filtrirane markere
            highlightFilteredMarkers(city);
            // ISPRAVLJENI DROPDOWN CHANGE HANDLER
            $tisakSelect.off('change.tisak').on('change.tisak', function () {
                const $selected = $(this).find('option:selected');
                const code = $selected.data('code');
                const lat = $selected.data('lat');
                const lng = $selected.data('lng');
                const name = $selected.data('name');
                const address = $selected.data('address');
               
                if (tisakSettings.enable_log === 'on') {
                    console.log('Dropdown promijenjen - odabrano:', { code, lat, lng, name });
                }
                if (code) {
                    $('#tisak_lokacija_code').val(code);
                   
                    // Kreiraj store objekt iz dropdown podataka
                    const store = {
                        code: code,
                        name: name || $selected.text().split(' - ')[0],
                        address: address || $selected.text().split(' - ')[1] || '',
                        full_address: address || $selected.text().split(' - ')[1] || '',
                        lat: lat,
                        lng: lng
                    };
                   
                    // Pozovi updateSelectedStore koji će prikazati podatke
                    updateSelectedStore(store);
                   
                } else {
                    $('#tisak_lokacija_code').val('');
                    $('#tisak-info').html('<p style="color: #999; font-style: italic;">Odaberite lokaciju za prikaz informacija.</p>');
                }
            });
            if (!filtered.length) {
                $infoDiv.html(`<p style="color: #999; font-style: italic;">Nema dostupnih Tisak lokacija u gradu "${city}".</p>`);
                return;
            }
        }).catch(error => {
            console.error('Error fetching stores:', error);
            $('#tisak-info').html('<p style="color: #dc3545; font-style: italic;">Greška pri dohvaćanju lokacija iz API-ja. Molimo pokušajte kasnije.</p>');
        });
    }
    // Debounced verzija updateTisakUI za bolje performanse
    const debouncedUpdateTisakUI = debounce(updateTisakUI, 300);
    // Event handleri
    $(document).ready(function() {
        if (!$('#tisak_lokacija').length || !$('#tisak-map').length) {
            if (tisakSettings.enable_log === 'on') {
                console.log('Tisak lokator elementi nisu pronađeni - preskačem inicijalizaciju');
            }
            return;
        }
        if (tisakSettings.enable_log === 'on') {
            console.log('Inicijalizacija Tisak lokatora...');
        }
       
        // Početna inicijalizacija s delay-om
        setTimeout(updateTisakUI, 500);
        // Bind na sve moguće city inpute
        $(document).on('input blur change', '#billing_city, input[name="billing_city"], input[id*="city"]', function() {
            if (tisakSettings.enable_log === 'on') {
                console.log('Grad promijenjen:', $(this).val());
            }
            debouncedUpdateTisakUI();
        });
        // Dodatni event za WooCommerce checkout update
        $(document.body).on('updated_checkout', function() {
            if (tisakSettings.enable_log === 'on') {
                console.log('Checkout ažuriran - osvježavam Tisak lokator');
            }
            setTimeout(updateTisakUI, 500);
        });
        // GPS dugme handler - podrška za oba selektora
        $(document).on('click', '.tisak-gps-btn, #tisak-gps-btn', function(e) {
            e.preventDefault();
            handleGPSLocation();
        });
        // Periodička provjera grada (fallback)
        let lastCity = '';
        setInterval(function() {
            const currentCity = getCityFromInputs();
           
            if (currentCity !== lastCity) {
                lastCity = currentCity;
                debouncedUpdateTisakUI();
            }
        }, 2000);
       
        // Poseban handler za admin preview
        if (isAdminPreview) {
            // Poseban dropdown handler za admin
            $(document).on('change', '#tisak_lokacija', function() {
                const $selected = $(this).find('option:selected');
                const code = $selected.data('code');
                const name = $selected.text().split(' - ')[0];
                const address = $selected.text().split(' - ')[1];
               
                if (code && name && name !== 'Odaberite lokaciju...') {
                    const store = {
                        code: code,
                        name: name,
                        address: address || 'Demo adresa',
                        full_address: address || 'Demo adresa'
                    };
                   
                    // Direktno prikaži info bez API poziva
                    displayStoreInfo(store, {
                        businessHours: {
                            workday: "08:00 - 20:00",
                            saturday: "09:00 - 15:00",
                            sunday: "Zatvoreno"
                        }
                    });
                }
            });
           
            // Klik na markere u admin preview
            $(document).on('click', '.leaflet-marker-icon', function() {
                console.log('Admin marker clicked');
            });
        }
    }); // Ova zagrada zatvara $(document).ready(function() {
   
    // Export funkcija za blocks checkout - MORA BITI OVDJE
    window.updateTisakUI = updateTisakUI;
    window.initializeMap = initializeMap;
    window.updateSelectedStore = updateSelectedStore;
    window.handleGPSLocation = handleGPSLocation;
   
});