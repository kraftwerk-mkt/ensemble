/**
 * Ensemble Maps Pro - Frontend Scripts
 * 
 * Supports both OpenStreetMap (Leaflet) and Google Maps
 * 
 * @package Ensemble
 * @subpackage Addons/Maps Pro
 * @version 2.3.5
 * 
 * Fixes in 2.3.5:
 * - Fixed style not applying in locations map shortcode
 * - Shortcode now uses correct style based on provider (google_map_style vs map_style)
 * - Added debug logging for style issues
 * 
 * Fixes in 2.3.4:
 * - Fixed getBounds error for L.layerGroup (calculate bounds manually)
 * - Fixed version number causing old JS to be cached
 * - Added debug logging for style issues
 * 
 * Fixes in 2.3.3:
 * - Map styles now work correctly (all 7 OSM styles + 5 Google styles)
 * - Added voyager, watercolor, terrain, satellite styles for Leaflet
 * - Added retro and night styles for Google Maps
 * 
 * Fixes in 2.3.2:
 * - Settings toggles now work correctly (hidden fields fix)
 * 
 * Fixes in 2.3.1:
 * - Added close button to popups
 * - Click on map closes popup
 * - Improved popup padding
 * 
 * Fixes in 2.3.0:
 * - Thumbnail markers now work in Google Maps (using Custom Overlay)
 * - Thumbnail markers work in Leaflet with CSS
 * - Route button opens Google Maps directly
 * - Single event maps have no popup
 */

(function($) {
    'use strict';
    
    var EnsembleMapsPro = {
        
        config: window.ensembleMaps || {},
        maps: {},
        markerLayers: {},
        mapOptions: {},
        userPosition: null,
        provider: 'osm',
        googleMaps: {},
        
        /**
         * Initialize
         */
        init: function() {
            var self = this;
            
            this.provider = this.config.provider || 'osm';
            
            console.log('Ensemble Maps: Init, provider:', this.provider, 'config:', this.config);
            
            // Timeout for loading overlays
            setTimeout(function() {
                $('.es-map-loading:not(.hidden)').each(function() {
                    $(this).addClass('hidden');
                    var $embed = $(this).siblings('.es-map-embed, .es-map-overview-embed');
                    if ($embed.children().length === 0) {
                        $embed.html('<p class="es-map-error">Map could not be loaded</p>');
                    }
                });
            }, 10000);
            
            this.initSingleMaps();
            this.initOverviewMaps();
        },
        
        /**
         * Initialize single event maps (NO popup)
         */
        initSingleMaps: function() {
            var self = this;
            
            $('.es-map-container.es-single-map').each(function() {
                var $container = $(this);
                var $embed = $container.find('.es-map-embed');
                var $loading = $container.find('.es-map-loading');
                var mapId = $embed.attr('id');
                
                if (!mapId || self.maps[mapId]) {
                    $loading.addClass('hidden');
                    return;
                }
                
                var lat = parseFloat($container.data('lat'));
                var lng = parseFloat($container.data('lng'));
                var locationName = $container.data('location-name') || '';
                
                if (!lat || !lng) {
                    $loading.addClass('hidden');
                    $embed.html('<p class="es-map-error">' + (self.config.strings?.noCoordinates || 'No coordinates available') + '</p>');
                    return;
                }
                
                var enableFullscreen = self.config.fullscreen === true || self.config.fullscreen === 'true' || self.config.fullscreen === '1';
                var enableGeolocation = self.config.geolocation === true || self.config.geolocation === 'true' || self.config.geolocation === '1';
                
                try {
                    self.initMap(mapId, {
                        center: [lat, lng],
                        zoom: parseInt(self.config.defaultZoom, 10) || 15,
                        style: self.config.mapStyle,
                        fullscreen: enableFullscreen,
                        geolocation: enableGeolocation,
                        clustering: false,
                        disablePopup: true,
                        markers: [{
                            lat: lat,
                            lng: lng,
                            name: locationName,
                            type: 'default'
                        }]
                    });
                    
                    $loading.addClass('hidden');
                } catch (e) {
                    console.error('Map init error:', e);
                    $loading.addClass('hidden');
                    $embed.html('<p class="es-map-error">Map could not be loaded</p>');
                }
            });
        },
        
        /**
         * Initialize overview maps
         */
        initOverviewMaps: function() {
            var self = this;
            
            $('.es-map-overview').each(function() {
                var $overview = $(this);
                var $embed = $overview.find('.es-map-overview-embed');
                var mapId = $embed.attr('id');
                
                if (!mapId) return;
                
                var locations = $overview.data('locations') || [];
                
                if (locations.length === 0) {
                    $embed.html('<p class="es-map-error">' + (self.config.strings?.noCoordinates || 'No locations') + '</p>');
                    return;
                }
                
                var markers = locations.map(function(loc) {
                    return {
                        id: loc.id,
                        lat: parseFloat(loc.latitude),
                        lng: parseFloat(loc.longitude),
                        name: loc.name,
                        address: loc.address,
                        type: loc.type || 'default',
                        url: loc.url,
                        thumbnail: loc.thumbnail,
                        upcoming_events: loc.upcoming_events
                    };
                });
                
                self.initMap(mapId, {
                    center: [self.config.defaultLat, self.config.defaultLng],
                    zoom: self.config.overviewZoom,
                    markers: markers,
                    fitBounds: true,
                    clustering: self.config.clustering
                });
            });
        },
        
        /**
         * Initialize Locations Overview Map (from shortcode)
         */
        initLocationsMap: function(mapId, locations, options) {
            var self = this;
            var $wrapper = $('#' + mapId);
            var canvasId = mapId + '-canvas';
            
            console.log('Ensemble Maps: initLocationsMap', locations.length, 'locations');
            
            if (!$wrapper.length) {
                console.error('Map wrapper not found:', mapId);
                return;
            }
            
            var markers = locations.map(function(loc) {
                return {
                    id: loc.id,
                    lat: parseFloat(loc.latitude) || 0,
                    lng: parseFloat(loc.longitude) || 0,
                    name: loc.name,
                    address: loc.address,
                    city: loc.city,
                    type: loc.type || 'default',
                    categories: loc.categories || [],
                    url: loc.url,
                    thumbnail: loc.thumbnail,
                    upcoming_events: loc.upcoming_events,
                    marker_icon: loc.marker_icon
                };
            }).filter(function(m) {
                return m.lat !== 0 && m.lng !== 0;
            });
            
            if (markers.length === 0) {
                $wrapper.find('.es-map-loading').addClass('hidden');
                $('#' + canvasId).html('<p class="es-map-error">No valid locations found</p>');
                return;
            }
            
            $wrapper.data('all-markers', markers);
            
            var showMarkerThumbnails = options.markerThumbnails !== false && self.config.markerThumbnails !== false;
            
            var map = self.initMap(canvasId, {
                center: [parseFloat(self.config.defaultLat) || 51.1657, parseFloat(self.config.defaultLng) || 10.4515],
                zoom: parseInt(self.config.overviewZoom, 10) || 6,
                markers: markers,
                fitBounds: true,
                clustering: options.clustering !== false,
                fullscreen: options.fullscreen !== false,
                geolocation: options.geolocation !== false,
                style: options.style || self.config.mapStyle,
                markerThumbnails: showMarkerThumbnails
            });
            
            $wrapper.find('.es-map-loading').addClass('hidden');
            
            self.bindFilterEvents($wrapper, canvasId);
            self.bindSearchEvents($wrapper, canvasId, markers);
            self.bindSidebarEvents($wrapper);
        },
        
        /**
         * Initialize a map (provider-agnostic)
         */
        initMap: function(mapId, options) {
            var provider = this.config.provider || 'osm';
            this.provider = provider;
            
            if (provider === 'google') {
                return this.initGoogleMap(mapId, options);
            } else {
                return this.initLeafletMap(mapId, options);
            }
        },
        
        // =====================================================
        // GOOGLE MAPS
        // =====================================================
        
        initGoogleMap: function(mapId, options) {
            var self = this;
            
            if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
                console.error('Google Maps API not loaded');
                return null;
            }
            
            var defaults = {
                center: [51.1657, 10.4515],
                zoom: 6,
                markers: [],
                fitBounds: false,
                clustering: false,
                fullscreen: false,
                geolocation: false,
                style: 'default',
                disablePopup: false,
                markerThumbnails: false
            };
            
            options = $.extend({}, defaults, options);
            self.mapOptions[mapId] = options;
            
            var zoom = parseInt(options.zoom, 10) || 15;
            var centerLat = parseFloat(options.center[0]) || 51.1657;
            var centerLng = parseFloat(options.center[1]) || 10.4515;
            
            var mapElement = document.getElementById(mapId);
            if (!mapElement) {
                console.error('Map element not found:', mapId);
                return null;
            }
            
            var styleToUse = options.style || self.config.mapStyle || 'default';
            console.log('Ensemble Maps Google: styleToUse =', styleToUse, 'options.style =', options.style, 'config.mapStyle =', self.config.mapStyle);
            var mapStyle = self.getGoogleMapStyle(styleToUse);
            
            var enableStreetView = self.config.streetView === true || self.config.streetView === 'true' || self.config.streetView === '1';
            var enableMapTypeControl = self.config.mapTypeControl === true || self.config.mapTypeControl === 'true' || self.config.mapTypeControl === '1';
            
            var map = new google.maps.Map(mapElement, {
                center: { lat: centerLat, lng: centerLng },
                zoom: zoom,
                styles: mapStyle,
                mapTypeControl: enableMapTypeControl,
                fullscreenControl: options.fullscreen === true,
                streetViewControl: enableStreetView,
                zoomControl: true
            });
            
            self.maps[mapId] = map;
            self.googleMaps[mapId] = {
                map: map,
                markers: [],
                infoWindow: new google.maps.InfoWindow(),
                clusterer: null,
                userMarker: null,
                disablePopup: options.disablePopup
            };
            
            // Close InfoWindow when clicking on map
            map.addListener('click', function() {
                self.googleMaps[mapId].infoWindow.close();
            });
            
            if (options.geolocation === true) {
                self.addGoogleGeolocationControl(map, mapId);
            }
            
            var markers = [];
            options.markers.forEach(function(markerData) {
                var marker = self.createGoogleMarker(markerData, map, mapId, options);
                if (marker) {
                    markers.push(marker);
                }
            });
            
            self.googleMaps[mapId].markers = markers;
            
            if (options.clustering && markers.length > 1 && typeof markerClusterer !== 'undefined') {
                self.googleMaps[mapId].clusterer = new markerClusterer.MarkerClusterer({
                    map: map,
                    markers: markers
                });
            }
            
            if (options.fitBounds && markers.length > 0) {
                var bounds = new google.maps.LatLngBounds();
                markers.forEach(function(marker) {
                    bounds.extend(marker.getPosition());
                });
                map.fitBounds(bounds);
                
                google.maps.event.addListenerOnce(map, 'bounds_changed', function() {
                    if (map.getZoom() > 15) {
                        map.setZoom(15);
                    }
                });
            }
            
            return map;
        },
        
        createGoogleMarker: function(data, map, mapId, options) {
            var self = this;
            
            if (!data.lat || !data.lng) return null;
            
            options = options || {};
            
            var iconConfig = data.marker_icon || self.config.markerIcons?.[data.type] || self.config.markerIcons?.['default'] || { icon: '📍', color: '#e74c3c' };
            var showThumbnails = options.markerThumbnails !== false && self.config.markerThumbnails !== false;
            
            var marker;
            
            // Use thumbnail marker if available and enabled
            if (showThumbnails && data.thumbnail) {
                marker = self.createGoogleThumbnailMarker(data, map, iconConfig);
            } else {
                // Fallback to simple SVG marker
                var markerIcon = {
                    url: self.createMarkerIcon(iconConfig.icon, iconConfig.color),
                    scaledSize: new google.maps.Size(40, 40),
                    anchor: new google.maps.Point(20, 40)
                };
                
                marker = new google.maps.Marker({
                    position: { lat: data.lat, lng: data.lng },
                    map: map,
                    icon: markerIcon,
                    title: data.name
                });
            }
            
            marker.locationData = data;
            
            if (!options.disablePopup && !self.googleMaps[mapId]?.disablePopup) {
                marker.addListener('click', function() {
                    var infoWindow = self.googleMaps[mapId].infoWindow;
                    var content = self.createPopupContent(data);
                    
                    infoWindow.setContent(content);
                    infoWindow.open(map, marker);
                    
                    google.maps.event.addListenerOnce(infoWindow, 'domready', function() {
                        if (data.id) {
                            self.loadLocationEvents(data.id);
                        }
                        self.bindPopupActions(data, mapId);
                    });
                });
            }
            
            return marker;
        },
        
        /**
         * Create Google Maps marker with thumbnail using OverlayView
         */
        createGoogleThumbnailMarker: function(data, map, iconConfig) {
            var self = this;
            
            // Create a custom overlay for thumbnail markers
            function ThumbnailMarker(position, thumbnail, color, title) {
                this.position = position;
                this.thumbnail = thumbnail;
                this.color = color;
                this.title = title;
                this.div = null;
            }
            
            ThumbnailMarker.prototype = new google.maps.OverlayView();
            
            ThumbnailMarker.prototype.onAdd = function() {
                var div = document.createElement('div');
                div.className = 'es-gmap-thumbnail-marker';
                div.style.cssText = 'position:absolute;cursor:pointer;';
                div.innerHTML = 
                    '<div class="es-marker-thumbnail" style="border-color:' + this.color + '">' +
                        '<img src="' + this.thumbnail + '" alt="" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'">' +
                        '<span class="es-marker-thumbnail-fallback" style="background-color:' + this.color + ';display:none">📍</span>' +
                    '</div>';
                div.title = this.title;
                this.div = div;
                
                var panes = this.getPanes();
                panes.overlayMouseTarget.appendChild(div);
            };
            
            ThumbnailMarker.prototype.draw = function() {
                if (!this.div) return;
                var overlayProjection = this.getProjection();
                var pos = overlayProjection.fromLatLngToDivPixel(this.position);
                this.div.style.left = (pos.x - 23) + 'px';
                this.div.style.top = (pos.y - 54) + 'px';
            };
            
            ThumbnailMarker.prototype.onRemove = function() {
                if (this.div) {
                    this.div.parentNode.removeChild(this.div);
                    this.div = null;
                }
            };
            
            ThumbnailMarker.prototype.getPosition = function() {
                return this.position;
            };
            
            var overlay = new ThumbnailMarker(
                new google.maps.LatLng(data.lat, data.lng),
                data.thumbnail,
                iconConfig.color,
                data.name
            );
            overlay.setMap(map);
            
            // Make it behave like a marker for click events
            overlay.addListener = function(event, callback) {
                if (overlay.div) {
                    overlay.div.addEventListener(event, callback);
                } else {
                    // Wait for onAdd
                    var checkDiv = setInterval(function() {
                        if (overlay.div) {
                            clearInterval(checkDiv);
                            overlay.div.addEventListener(event, callback);
                        }
                    }, 50);
                }
            };
            
            return overlay;
        },
        
        createMarkerIcon: function(emoji, color) {
            var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40">' +
                '<path d="M20 0C11.7 0 5 6.7 5 15c0 10 15 25 15 25s15-15 15-25C35 6.7 28.3 0 20 0z" fill="' + color + '"/>' +
                '<circle cx="20" cy="15" r="10" fill="#fff"/>' +
                '<text x="20" y="20" text-anchor="middle" font-size="14">' + emoji + '</text>' +
                '</svg>';
            
            return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
        },
        
        addGoogleGeolocationControl: function(map, mapId) {
            var self = this;
            
            var controlDiv = document.createElement('div');
            controlDiv.className = 'es-google-locate-control';
            controlDiv.innerHTML = '<button type="button" title="' + (self.config.strings?.myLocation || 'My Location') + '">' +
                '<span class="dashicons dashicons-location"></span></button>';
            
            controlDiv.querySelector('button').addEventListener('click', function() {
                self.googleGeolocate(map, mapId);
            });
            
            map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(controlDiv);
        },
        
        googleGeolocate: function(map, mapId) {
            var self = this;
            
            if (!navigator.geolocation) {
                alert(self.config.strings?.locationError || 'Geolocation not supported');
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    var pos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    self.userPosition = pos;
                    
                    if (self.googleMaps[mapId].userMarker) {
                        self.googleMaps[mapId].userMarker.setPosition(pos);
                    } else {
                        self.googleMaps[mapId].userMarker = new google.maps.Marker({
                            position: pos,
                            map: map,
                            icon: {
                                path: google.maps.SymbolPath.CIRCLE,
                                scale: 10,
                                fillColor: '#4285f4',
                                fillOpacity: 1,
                                strokeColor: '#fff',
                                strokeWeight: 3
                            },
                            title: self.config.strings?.myLocation || 'My Location'
                        });
                    }
                    
                    map.setCenter(pos);
                    map.setZoom(14);
                },
                function() {
                    alert(self.config.strings?.locationError || 'Could not get your location');
                }
            );
        },
        
        getGoogleMapStyle: function(styleName) {
            var styles = {
                'default': [],
                'dark': [
                    { elementType: 'geometry', stylers: [{ color: '#212121' }] },
                    { elementType: 'labels.text.fill', stylers: [{ color: '#757575' }] },
                    { elementType: 'labels.text.stroke', stylers: [{ color: '#212121' }] },
                    { featureType: 'administrative', elementType: 'geometry', stylers: [{ color: '#757575' }] },
                    { featureType: 'poi', elementType: 'labels.text.fill', stylers: [{ color: '#757575' }] },
                    { featureType: 'poi.park', elementType: 'geometry', stylers: [{ color: '#181818' }] },
                    { featureType: 'road', elementType: 'geometry.fill', stylers: [{ color: '#2c2c2c' }] },
                    { featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#8a8a8a' }] },
                    { featureType: 'road.arterial', elementType: 'geometry', stylers: [{ color: '#373737' }] },
                    { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#3c3c3c' }] },
                    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#000000' }] },
                    { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#3d3d3d' }] }
                ],
                'light': [
                    { elementType: 'geometry', stylers: [{ color: '#f5f5f5' }] },
                    { elementType: 'labels.text.fill', stylers: [{ color: '#616161' }] },
                    { elementType: 'labels.text.stroke', stylers: [{ color: '#f5f5f5' }] },
                    { featureType: 'poi', elementType: 'geometry', stylers: [{ color: '#eeeeee' }] },
                    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
                    { featureType: 'road.arterial', elementType: 'labels.text.fill', stylers: [{ color: '#757575' }] },
                    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#c9c9c9' }] }
                ],
                'retro': [
                    { elementType: 'geometry', stylers: [{ color: '#ebe3cd' }] },
                    { elementType: 'labels.text.fill', stylers: [{ color: '#523735' }] },
                    { elementType: 'labels.text.stroke', stylers: [{ color: '#f5f1e6' }] },
                    { featureType: 'administrative', elementType: 'geometry.stroke', stylers: [{ color: '#c9b2a6' }] },
                    { featureType: 'poi', elementType: 'geometry', stylers: [{ color: '#dfd2ae' }] },
                    { featureType: 'poi.park', elementType: 'geometry.fill', stylers: [{ color: '#a5b076' }] },
                    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#f5f1e6' }] },
                    { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#f8c967' }] },
                    { featureType: 'road.highway', elementType: 'geometry.stroke', stylers: [{ color: '#e9bc62' }] },
                    { featureType: 'water', elementType: 'geometry.fill', stylers: [{ color: '#b9d3c2' }] }
                ],
                'night': [
                    { elementType: 'geometry', stylers: [{ color: '#242f3e' }] },
                    { elementType: 'labels.text.fill', stylers: [{ color: '#746855' }] },
                    { elementType: 'labels.text.stroke', stylers: [{ color: '#242f3e' }] },
                    { featureType: 'administrative.locality', elementType: 'labels.text.fill', stylers: [{ color: '#d59563' }] },
                    { featureType: 'poi', elementType: 'labels.text.fill', stylers: [{ color: '#d59563' }] },
                    { featureType: 'poi.park', elementType: 'geometry', stylers: [{ color: '#263c3f' }] },
                    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#38414e' }] },
                    { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#212a37' }] },
                    { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#746855' }] },
                    { featureType: 'transit', elementType: 'geometry', stylers: [{ color: '#2f3948' }] },
                    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#17263c' }] },
                    { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#515c6d' }] }
                ]
            };
            
            console.log('Ensemble Maps: Using Google style:', styleName);
            
            return styles[styleName] || styles['default'];
        },
        
        // =====================================================
        // LEAFLET/OSM
        // =====================================================
        
        initLeafletMap: function(mapId, options) {
            var self = this;
            
            if (typeof L === 'undefined') {
                console.error('Leaflet not loaded');
                return null;
            }
            
            var defaults = {
                center: [51.1657, 10.4515],
                zoom: 6,
                markers: [],
                fitBounds: false,
                clustering: false,
                fullscreen: false,
                geolocation: false,
                style: 'default',
                disablePopup: false,
                markerThumbnails: false
            };
            
            options = $.extend({}, defaults, options);
            self.mapOptions[mapId] = options;
            
            var mapElement = document.getElementById(mapId);
            if (!mapElement) {
                console.error('Map element not found:', mapId);
                return null;
            }
            
            var map = L.map(mapId, {
                center: options.center,
                zoom: options.zoom,
                zoomControl: true,
                fullscreenControl: options.fullscreen === true
            });
            
            self.addLeafletTileLayer(map, options.style);
            
            if (options.geolocation === true && typeof L.control.locate !== 'undefined') {
                L.control.locate({
                    position: 'bottomright',
                    strings: {
                        title: self.config.strings?.myLocation || 'My Location'
                    }
                }).addTo(map);
                
                map.on('locationfound', function(e) {
                    self.userPosition = { lat: e.latlng.lat, lng: e.latlng.lng };
                });
            }
            
            var markerLayer;
            if (options.clustering && typeof L.markerClusterGroup !== 'undefined') {
                markerLayer = L.markerClusterGroup({
                    showCoverageOnHover: false,
                    maxClusterRadius: 50
                });
            } else {
                markerLayer = L.layerGroup();
            }
            
            options.markers.forEach(function(markerData) {
                var marker = self.createLeafletMarker(markerData, map, mapId, options);
                if (marker) {
                    markerLayer.addLayer(marker);
                }
            });
            
            markerLayer.addTo(map);
            
            self.maps[mapId] = map;
            self.markerLayers[mapId] = markerLayer;
            
            // Fit bounds to markers
            if (options.fitBounds && options.markers.length > 0) {
                // L.layerGroup doesn't have getBounds, so calculate manually
                var bounds = L.latLngBounds();
                options.markers.forEach(function(m) {
                    if (m.lat && m.lng) {
                        bounds.extend([m.lat, m.lng]);
                    }
                });
                
                if (bounds.isValid()) {
                    map.fitBounds(bounds, { padding: [30, 30] });
                }
            }
            
            return map;
        },
        
        addLeafletTileLayer: function(map, styleName) {
            var self = this;
            
            // All tile styles - matching PHP $map_styles
            var tileStyles = {
                'default': {
                    url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    attr: '© OpenStreetMap contributors'
                },
                'dark': {
                    url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
                    attr: '© OpenStreetMap contributors © CARTO'
                },
                'light': {
                    url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
                    attr: '© OpenStreetMap contributors © CARTO'
                },
                'voyager': {
                    url: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
                    attr: '© OpenStreetMap contributors © CARTO'
                },
                'watercolor': {
                    url: 'https://stamen-tiles.a.ssl.fastly.net/watercolor/{z}/{x}/{y}.jpg',
                    attr: 'Map tiles by Stamen Design'
                },
                'terrain': {
                    url: 'https://stamen-tiles.a.ssl.fastly.net/terrain/{z}/{x}/{y}{r}.png',
                    attr: 'Map tiles by Stamen Design'
                },
                'satellite': {
                    url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                    attr: 'Tiles © Esri'
                }
            };
            
            // Use passed style, fallback to config, then default
            var style = tileStyles[styleName] || tileStyles[self.config.mapStyle] || tileStyles['default'];
            
            console.log('Ensemble Maps: Using tile style:', styleName, style.url);
            
            L.tileLayer(style.url, {
                attribution: style.attr,
                maxZoom: 19
            }).addTo(map);
        },
        
        createLeafletMarker: function(data, map, mapId, options) {
            var self = this;
            
            if (!data.lat || !data.lng) return null;
            
            options = options || self.mapOptions[mapId] || {};
            
            var iconConfig = data.marker_icon || self.config.markerIcons?.[data.type] || self.config.markerIcons?.['default'] || { icon: '📍', color: '#e74c3c' };
            
            var icon;
            
            // Thumbnail markers work with CSS in Leaflet
            if (options.markerThumbnails && data.thumbnail) {
                icon = L.divIcon({
                    html: '<div class="es-marker-thumbnail" style="border-color: ' + iconConfig.color + '">' +
                          '<img src="' + data.thumbnail + '" alt="" onerror="this.style.display=\'none\'">' +
                          '<span class="es-marker-thumbnail-fallback" style="background-color: ' + iconConfig.color + '">' + iconConfig.icon + '</span>' +
                          '</div>',
                    className: 'es-marker-thumbnail-icon',
                    iconSize: [50, 60],
                    iconAnchor: [25, 60],
                    popupAnchor: [0, -60]
                });
            } else {
                icon = L.divIcon({
                    html: '<div class="es-marker" style="background-color: ' + iconConfig.color + '"><span class="es-marker-inner">' + iconConfig.icon + '</span></div>',
                    className: 'es-marker-icon',
                    iconSize: [40, 40],
                    iconAnchor: [20, 40],
                    popupAnchor: [0, -40]
                });
            }
            
            var marker = L.marker([data.lat, data.lng], { icon: icon });
            marker.locationData = data;
            
            if (!options.disablePopup) {
                var popupContent = self.createPopupContent(data);
                marker.bindPopup(popupContent, {
                    maxWidth: 320,
                    minWidth: 260
                });
                
                marker.on('popupopen', function() {
                    if (data.id) {
                        self.loadLocationEvents(data.id);
                    }
                    self.bindPopupActions(data, mapId);
                });
            }
            
            return marker;
        },
        
        // =====================================================
        // SHARED METHODS
        // =====================================================
        
        /**
         * Create popup content - COMPACT design
         */
        createPopupContent: function(data) {
            var self = this;
            var strings = self.config.strings || {};
            var html = '<div class="es-map-popup es-map-popup-compact">';
            
            // Close button
            html += '<button type="button" class="es-map-popup-close" aria-label="' + (strings.close || 'Schließen') + '">';
            html += '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>';
            html += '</button>';
            
            // Header with thumbnail and title
            html += '<div class="es-map-popup-header">';
            
            if (data.thumbnail) {
                html += '<div class="es-map-popup-thumb">';
                html += '<img src="' + data.thumbnail + '" alt="" onerror="this.parentElement.style.display=\'none\'">';
                html += '</div>';
            }
            
            html += '<div class="es-map-popup-header-content">';
            
            // Title with link
            html += '<h4 class="es-map-popup-title">';
            if (data.url) {
                html += '<a href="' + data.url + '">' + self.escapeHtml(data.name) + '</a>';
            } else {
                html += self.escapeHtml(data.name);
            }
            html += '</h4>';
            
            // Address
            if (data.address && data.address.trim()) {
                html += '<p class="es-map-popup-address">' + self.escapeHtml(data.address) + '</p>';
            }
            
            html += '</div>'; // header-content
            html += '</div>'; // header
            
            // Events placeholder
            html += '<div class="es-map-popup-events" data-location-id="' + (data.id || '') + '">';
            html += '<div class="es-map-popup-events-loading"><span class="es-spinner"></span></div>';
            html += '</div>';
            
            // Route button - ALWAYS visible, opens Google Maps
            html += '<a href="' + self.getRouteUrl(data) + '" target="_blank" rel="noopener" class="es-popup-route-btn">';
            html += '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>';
            html += ' ' + (strings.route || 'Route planen');
            html += '</a>';
            
            html += '</div>'; // popup
            
            return html;
        },
        
        /**
         * Get Google Maps route URL
         */
        getRouteUrl: function(data) {
            var destination;
            
            if (data.address && data.address.trim()) {
                destination = encodeURIComponent(data.address);
            } else if (data.lat && data.lng) {
                destination = data.lat + ',' + data.lng;
            } else {
                destination = encodeURIComponent(data.name || '');
            }
            
            return 'https://www.google.com/maps/dir/?api=1&destination=' + destination;
        },
        
        /**
         * Bind popup action buttons
         */
        bindPopupActions: function(data, mapId) {
            var self = this;
            
            // Bind close button
            $('.es-map-popup-close').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Close Google Maps InfoWindow
                if (self.googleMaps[mapId] && self.googleMaps[mapId].infoWindow) {
                    self.googleMaps[mapId].infoWindow.close();
                }
                
                // Close Leaflet Popup
                if (self.maps[mapId] && typeof self.maps[mapId].closePopup === 'function') {
                    self.maps[mapId].closePopup();
                }
            });
        },
        
        /**
         * Load location events via AJAX
         */
        loadLocationEvents: function(locationId) {
            var self = this;
            var $container = $('.es-map-popup-events[data-location-id="' + locationId + '"]');
            
            if (!$container.length) return;
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_get_location_events',
                    nonce: self.config.nonce,
                    location_id: locationId,
                    limit: 1
                },
                success: function(response) {
                    console.log('Events response:', response);
                    
                    if (response.success && response.data && response.data.events && response.data.events.length > 0) {
                        var event = response.data.events[0];
                        var html = '<div class="es-map-popup-next-event">';
                        html += '<span class="es-event-label">' + (self.config.strings?.nextEvent || 'Nächstes Event') + ':</span>';
                        html += '<a href="' + event.url + '" class="es-event-link">';
                        html += '<span class="es-event-title">' + self.escapeHtml(event.title) + '</span>';
                        if (event.formatted) {
                            html += '<span class="es-event-date">' + event.formatted + '</span>';
                        }
                        html += '</a>';
                        html += '</div>';
                        
                        $container.html(html);
                    } else {
                        $container.html('<p class="es-no-events">' + (self.config.strings?.noEvents || 'Keine kommenden Events') + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Events AJAX error:', status, error);
                    $container.empty();
                }
            });
        },
        
        /**
         * Bind filter events
         */
        bindFilterEvents: function($wrapper, mapId) {
            var self = this;
            
            $wrapper.find('.es-map-filter').on('change', function() {
                self.filterMarkers($wrapper, mapId);
            });
            
            $wrapper.find('.es-map-btn-reset').on('click', function() {
                $wrapper.find('.es-map-filter').val('');
                $wrapper.find('.es-map-search-input').val('');
                self.filterMarkers($wrapper, mapId);
            });
        },
        
        /**
         * Filter markers
         */
        filterMarkers: function($wrapper, mapId) {
            var self = this;
            var allMarkers = $wrapper.data('all-markers') || [];
            var cityFilter = $wrapper.find('.es-map-filter[data-filter="city"]').val() || '';
            var categoryFilter = $wrapper.find('.es-map-filter[data-filter="category"]').val() || '';
            
            var filteredMarkers = allMarkers.filter(function(m) {
                var cityMatch = !cityFilter || m.city === cityFilter;
                var categoryMatch = !categoryFilter || (m.categories && m.categories.indexOf(categoryFilter) !== -1);
                return cityMatch && categoryMatch;
            });
            
            var filteredCount = filteredMarkers.length;
            
            if (self.provider === 'google') {
                var mapData = self.googleMaps[mapId];
                if (!mapData) return;
                
                mapData.markers.forEach(function(marker) {
                    marker.setVisible(false);
                });
                
                var bounds = new google.maps.LatLngBounds();
                mapData.markers.forEach(function(marker) {
                    var data = marker.locationData;
                    var isFiltered = filteredMarkers.some(function(m) { return m.id === data.id; });
                    marker.setVisible(isFiltered);
                    if (isFiltered) {
                        bounds.extend(marker.getPosition());
                    }
                });
                
                if (filteredCount > 0 && !bounds.isEmpty()) {
                    mapData.map.fitBounds(bounds);
                }
            } else {
                var map = self.maps[mapId];
                var markerLayer = self.markerLayers[mapId];
                if (!map || !markerLayer) return;
                
                markerLayer.clearLayers();
                
                var bounds = L.latLngBounds();
                filteredMarkers.forEach(function(m) {
                    var marker = self.createLeafletMarker(m, map, mapId);
                    if (marker) {
                        markerLayer.addLayer(marker);
                        bounds.extend([m.lat, m.lng]);
                    }
                });
                
                if (filteredCount > 0 && bounds.isValid()) {
                    map.fitBounds(bounds, { padding: [30, 30] });
                }
            }
            
            self.updateFilterStats($wrapper, filteredCount, allMarkers.length);
        },
        
        updateFilterStats: function($wrapper, filtered, total) {
            var $stats = $wrapper.find('.es-map-stats-count');
            var $filtered = $wrapper.find('.es-map-stats-filtered');
            
            if (filtered < total) {
                $stats.text(filtered + ' / ' + total + ' Locations');
                $filtered.show();
            } else {
                $stats.text(total + ' Locations');
                $filtered.hide();
            }
        },
        
        bindSearchEvents: function($wrapper, mapId, markers) {
            var self = this;
            var $input = $wrapper.find('.es-map-search-input');
            var $results = $wrapper.find('.es-map-search-results');
            var $clear = $wrapper.find('.es-map-search-clear');
            
            var debounceTimer;
            
            $input.on('input', function() {
                var query = $(this).val().toLowerCase();
                
                clearTimeout(debounceTimer);
                
                if (query.length < 2) {
                    $results.removeClass('active').empty();
                    return;
                }
                
                debounceTimer = setTimeout(function() {
                    var matches = markers.filter(function(m) {
                        return m.name.toLowerCase().indexOf(query) !== -1 ||
                               (m.address && m.address.toLowerCase().indexOf(query) !== -1);
                    }).slice(0, 5);
                    
                    if (matches.length > 0) {
                        var html = matches.map(function(m) {
                            return '<div class="es-map-search-result" data-lat="' + m.lat + '" data-lng="' + m.lng + '" data-id="' + m.id + '">' +
                                   '<div class="es-map-search-result-name">' + self.escapeHtml(m.name) + '</div>' +
                                   (m.address ? '<div class="es-map-search-result-address">' + self.escapeHtml(m.address) + '</div>' : '') +
                                   '</div>';
                        }).join('');
                        
                        $results.html(html).addClass('active');
                    } else {
                        $results.html('<div class="es-map-search-result"><div class="es-map-search-result-name">' + (self.config.strings?.noResults || 'Keine Ergebnisse') + '</div></div>').addClass('active');
                    }
                }, 200);
            });
            
            $results.on('click', '.es-map-search-result', function() {
                var lat = parseFloat($(this).data('lat'));
                var lng = parseFloat($(this).data('lng'));
                var id = $(this).data('id');
                
                if (lat && lng) {
                    if (self.provider === 'google') {
                        var gmap = self.googleMaps[mapId].map;
                        gmap.setCenter({ lat: lat, lng: lng });
                        gmap.setZoom(15);
                        
                        self.googleMaps[mapId].markers.forEach(function(marker) {
                            if (marker.locationData && marker.locationData.id === id) {
                                google.maps.event.trigger(marker, 'click');
                            }
                        });
                    } else {
                        var map = self.maps[mapId];
                        map.setView([lat, lng], 15);
                        
                        self.markerLayers[mapId].eachLayer(function(layer) {
                            if (layer.locationData && layer.locationData.id === id) {
                                layer.openPopup();
                            }
                        });
                    }
                }
                
                $results.removeClass('active');
                $input.val($(this).find('.es-map-search-result-name').text());
            });
            
            $clear.on('click', function() {
                $input.val('');
                $results.removeClass('active').empty();
                self.filterMarkers($wrapper, mapId);
            });
            
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.es-map-search').length) {
                    $results.removeClass('active');
                }
            });
        },
        
        bindSidebarEvents: function($wrapper) {
            var $sidebar = $wrapper.find('.es-map-sidebar');
            
            $sidebar.find('.es-map-sidebar-close').on('click', function() {
                $sidebar.removeClass('active');
            });
        },
        
        escapeHtml: function(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    };
    
    // Legacy support
    var EnsembleMaps = {
        init: function() {
            EnsembleMapsPro.init();
        }
    };
    
    $(document).ready(function() {
        setTimeout(function() {
            EnsembleMapsPro.init();
        }, 100);
    });
    
    window.EnsembleMapsPro = EnsembleMapsPro;
    window.EnsembleMaps = EnsembleMaps;
    
})(jQuery);
