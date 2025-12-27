(function( $ ) {
	'use strict';

	/**
	 * Admin JavaScript for ParaDB
	 *
	 * @package    WP_ParaDB
	 * @subpackage WP_ParaDB/admin/js
	 */

	$(function() {
		// Auto-save form data to localStorage to prevent data loss
		var $forms = $('form[method="post"]');
		
		if ($forms.length > 0) {
			// Load saved form data on page load
			$forms.each(function() {
				var formId = $(this).attr('id') || 'paradb-form';
				loadFormData(formId);
			});

			// Save form data on input change
			$forms.on('input change', 'input, textarea, select', function() {
				var $form = $(this).closest('form');
				var formId = $form.attr('id') || 'paradb-form';
				saveFormData(formId);
			});

			// Clear saved data on successful submit
			$forms.on('submit', function() {
				var formId = $(this).attr('id') || 'paradb-form';
				clearFormData(formId);
			});
		}

		// Character counter for textareas
		$('textarea[maxlength]').each(function() {
			var $textarea = $(this);
			var maxLength = $textarea.attr('maxlength');
			var $counter = $('<div class="char-counter"></div>');
			$textarea.after($counter);
			
			$textarea.on('input', function() {
				var remaining = maxLength - $(this).val().length;
				$counter.text(remaining + ' characters remaining');
			}).trigger('input');
		});

		// Confirm before deleting
		$(document).on('click', 'a[href*="action=delete"]', function(e) {
			if (!$(this).data('confirmed')) {
				e.preventDefault();
				if (confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
					$(this).data('confirmed', true).get(0).click();
				}
			}
		});

		// Geolocation Button Logic
		$('.get-current-location').on('click', function() {
			var $btn = $(this);
			var target = $btn.data('target');
			var $input = $(target);
			
			if (!navigator.geolocation) {
				alert('Geolocation is not supported by your browser.');
				return;
			}

			$btn.text('⌛');
			navigator.geolocation.getCurrentPosition(function(pos) {
				var lat = pos.coords.latitude.toFixed(6);
				var lng = pos.coords.longitude.toFixed(6);
				
				if ($input.is('textarea') || $input.attr('type') === 'text') {
					$input.val(lat + ', ' + lng);
				} else {
					// Assume we are in a Lat/Lng pair field
					$('#latitude').val(lat).trigger('change');
					$('#longitude').val(lng).trigger('change');
				}
				$btn.text('📍');
			}, function(err) {
				alert('Error: ' + err.message);
				$btn.text('📍');
			});
		});

		// Location Name Autocomplete
		$('input[name="location_name"]').autocomplete({
			source: function(request, response) {
				$.ajax({
					url: paradb_admin.ajax_url,
					dataType: 'json',
					data: {
						action: 'paradb_search_locations',
						term: request.term,
						nonce: paradb_admin.nonce
					},
					success: function(data) {
						if (data.success) {
							response(data.data);
						} else {
							response([]);
						}
					}
				});
			},
			minLength: 2,
			select: function(event, ui) {
				var loc = ui.item.data;
				if (loc) {
					$('input[name="location_address"], input[name="address"]').val(loc.address || '');
					$('input[name="location_city"], input[name="city"]').val(loc.city || '');
					$('input[name="location_state"], input[name="state"]').val(loc.state || '');
					$('input[name="location_zip"], input[name="zip"]').val(loc.zip || '');
					$('input[name="location_country"], input[name="country"]').val(loc.country || '');
					$('input[name="latitude"]').val(loc.latitude || '');
					$('input[name="longitude"]').val(loc.longitude || '');
					
					// Trigger change to update map if initialized
					$('input[name="latitude"]').trigger('change');
				}
			}
		});

		// Enhanced file upload preview
		$('input[type="file"]').on('change', function() {
			var file = this.files[0];
			if (file) {
				var $preview = $(this).siblings('.file-preview');
				if ($preview.length === 0) {
					$preview = $('<div class="file-preview"></div>');
					$(this).after($preview);
				}
				
				var fileSize = (file.size / 1024).toFixed(2);
				var fileInfo = file.name + ' (' + fileSize + ' KB)';
				$preview.html('<strong>Selected file:</strong> ' + fileInfo);

				// Show image preview if it's an image
				if (file.type.match('image.*')) {
					var reader = new FileReader();
					reader.onload = function(e) {
						$preview.prepend('<img src="' + e.target.result + '" style="max-width: 200px; display: block; margin: 10px 0;">');
					};
					reader.readAsDataURL(file);
				}
			}
		});

		// Auto-suggest for location fields
		if (typeof google !== 'undefined' && google.maps && paradb_maps.provider === 'google') {
			$('input[name="location_address"], input[name="address"]').each(function() {
				$(this).attr('autocomplete', 'off'); // Prevent browser interference
				var autocomplete = new google.maps.places.Autocomplete(this);
				
				autocomplete.addListener('place_changed', function() {
					var place = autocomplete.getPlace();
					if (!place.geometry) return;
					
					var lat = place.geometry.location.lat();
					var lng = place.geometry.location.lng();
					
					$('#latitude').val(lat.toFixed(6)).trigger('change');
					$('#longitude').val(lng.toFixed(6)).trigger('change');
				});
			});

			// Location Map handling
			$('.location-map, #location-map').each(function() {
				initGoogleMap(this);
			});
		} else if (typeof L !== 'undefined' && paradb_maps.provider === 'osm') {
			$('input[name="location_address"], input[name="address"]').attr('autocomplete', 'off');
			
			// Location Map handling for Leaflet
			$('.location-map, #location-map').each(function() {
				initLeafletMap(this);
			});
		}

		// Environmental data fetching
		$('#fetch-environmental-data').on('click', function() {
			var $btn = $(this);
			var lat = $('#latitude').val() || 0;
			var lng = $('#longitude').val() || 0;
			var datetime = $('#activity_date').val() || $('#report_date').val() || '';

			if (!lat || !lng || !datetime) {
				alert('Please ensure location coordinates and date are set first.');
				return;
			}

			$btn.prop('disabled', true).text('Fetching...');

			$.ajax({
				url: paradb_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'paradb_fetch_environmental_data',
					nonce: paradb_admin.nonce,
					lat: lat,
					lng: lng,
					case_id: $('#case_id').val() || 0,
					datetime: datetime
				},
				                                				success: function(response) {
				                                					if (response.success) {
				                                						var data = response.data;
				                                						var resultsHtml = '<div class="fetch-results-list" style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin-top: 10px;">';
				                                						var appliedCount = 0;
				                                
				                                						if (data.weather) {
				                                							var tempStr = data.weather.temp ? data.weather.temp + (data.weather.temp_unit || '°C') : '';
				                                							if (tempStr) {
				                                								$('#temperature').val(tempStr);
				                                								resultsHtml += '<p><strong>Applied Temperature:</strong> ' + tempStr + '</p>';
				                                								appliedCount++;
				                                							}
				                                							
				                                							var cond = data.weather.weather_desc || ('Code: ' + data.weather.weather_code);
				                                							if (data.weather.humidity) cond += ', Humidity: ' + data.weather.humidity + '%';
				                                							if (data.weather.wind_speed) cond += ', Wind: ' + data.weather.wind_speed + (paradb_maps.units === 'imperial' ? ' mph' : ' km/h');
				                                							
				                                							$('#weather_conditions').val(cond);
				                                							resultsHtml += '<p><strong>Applied Weather:</strong> ' + cond + '</p>';
				                                							appliedCount++;
				                                						}
				                                
				                                						if (data.astro && data.astro.moon_phase) {
				                                							var phase = data.astro.moon_phase.toLowerCase().replace(/ /g, '_');
				                                							$('#moon_phase').val(phase);
				                                							resultsHtml += '<p><strong>Applied Moon Phase:</strong> ' + data.astro.moon_phase + '</p>';
				                                							appliedCount++;
				                                						}
				                                
				                                						if (data.astrology) {
				                                							var astroText = '';
				                                							for (var planet in data.astrology) {
				                                								astroText += planet + ': ' + data.astrology[planet].sign + ' (' + data.astrology[planet].full_degree.toFixed(2) + '°); ';
				                                							}
				                                							$('#astrological_data').val(astroText);
				                                							resultsHtml += '<p><strong>Applied Astrological Data:</strong> ' + astroText.substring(0, 100) + '...</p>';
				                                							appliedCount++;
				                                						}
				                                
				                                						if (data.geomagnetic) {
				                                							var geoText = 'Kp-Index: ' + data.geomagnetic.kp_index + ' (' + data.geomagnetic.source + ')';
				                                							$('#geomagnetic_data').val(geoText);
				                                							resultsHtml += '<p><strong>Applied Geomagnetic Data:</strong> ' + geoText + '</p>';
				                                							appliedCount++;
				                                						}
				                                
				                                						if (appliedCount === 0) {
				                                							resultsHtml += '<p>No data found for this location/time.</p>';
				                                						} else {
				                                							resultsHtml += '<p style="color: green; font-weight: bold;">All data automatically applied to fields.</p>';
				                                						}
				                                						
				                                						resultsHtml += '</div>';
				                                
				                                						$('#fetch-results-container').html(resultsHtml);
				                                						$('#fetch-results-row').show();
				                                					} else {
				                                						alert('Error: ' + response.data.message);
				                                					}
				                                				},				error: function() {
					alert('An unexpected error occurred during fetching.');
				},
				complete: function() {
					$btn.prop('disabled', false).text('Auto-fetch Environmental Data');
				}
			});
		});

		function initGoogleMap(element) {
			var lat = parseFloat($('#latitude').val()) || 0;
			var lng = parseFloat($('#longitude').val()) || 0;
			var center = {lat: lat, lng: lng};
			
			var map = new google.maps.Map(element, {
				zoom: (lat !== 0) ? 15 : 2,
				center: center
			});

			// If coordinates are 0, try to center on account_address if it exists
			if (lat === 0 && lng === 0) {
				var fallbackAddress = $('#account_address').val() || $('#address').val() || '';
				if (fallbackAddress) {
					var geocoder = new google.maps.Geocoder();
					geocoder.geocode({'address': fallbackAddress}, function(results, status) {
						if (status === 'OK') {
							map.setCenter(results[0].geometry.location);
							map.setZoom(12);
						}
					});
				}
			}

			// Fix for maps in tabs/collapsed containers
			google.maps.event.addListenerOnce(map, 'idle', function(){
				google.maps.event.trigger(map, 'resize');
				map.setCenter(center);
			});

			var marker = new google.maps.Marker({
				position: center,
				map: map,
				draggable: true
			});

			// Update coordinates when marker is dragged
			marker.addListener('dragend', function() {
				var pos = marker.getPosition();
				$('#latitude').val(pos.lat().toFixed(6));
				$('#longitude').val(pos.lng().toFixed(6));
				
				// Reverse Geocode
				var geocoder = new google.maps.Geocoder();
				geocoder.geocode({ 'location': pos }, function(results, status) {
					if (status === 'OK' && results[0]) {
						if (confirm('Update address details from map location?')) {
							var addr = results[0];
							$('input[name="location_address"], input[name="address"]').val(addr.formatted_address);
							
							// Parse address components
							var city = '', state = '', zip = '', country = '';
							for (var i = 0; i < addr.address_components.length; i++) {
								var comp = addr.address_components[i];
								if (comp.types.indexOf('locality') !== -1) city = comp.long_name;
								if (comp.types.indexOf('administrative_area_level_1') !== -1) state = comp.short_name;
								if (comp.types.indexOf('country') !== -1) country = comp.long_name;
								if (comp.types.indexOf('postal_code') !== -1) zip = comp.long_name;
							}
							
							$('input[name="location_city"], input[name="city"]').val(city);
							$('input[name="location_state"], input[name="state"]').val(state);
							$('input[name="location_zip"], input[name="zip"]').val(zip);
							$('input[name="location_country"], input[name="country"]').val(country);
						}
					}
				});
			});

			// Update marker when coordinates change manually
			$('#latitude, #longitude').on('change', function() {
				var newPos = {
					lat: parseFloat($('#latitude').val()) || 0,
					lng: parseFloat($('#longitude').val()) || 0
				};
				marker.setPosition(newPos);
				map.setCenter(newPos);
			});

			// Geocode address
			$('#geocode-address').on('click', function() {
				var address = $('#incident_location').val() || $('#address').val() || $('#location_address').val() || $('#account_address').val() || '';
				if (!address) {
					alert('Please enter an address first.');
					return;
				}

				var geocoder = new google.maps.Geocoder();
				geocoder.geocode({'address': address}, function(results, status) {
					if (status === 'OK') {
						var pos = results[0].geometry.location;
						map.setCenter(pos);
						marker.setPosition(pos);
						$('#latitude').val(pos.lat().toFixed(6));
						$('#longitude').val(pos.lng().toFixed(6));
					} else {
						var msg = 'Geocode was not successful for the following reason: ' + status;
						if (status === 'REQUEST_DENIED') {
							msg += '\n\nThis usually means the "Geocoding API" is not enabled for your API key in the Google Cloud Console, or there are restriction issues.';
						}
						alert(msg);
					}
				});
			});
		}

		function initLeafletMap(element) {
			var lat = parseFloat($('#latitude').val()) || 0;
			var lng = parseFloat($('#longitude').val()) || 0;
			
			var map = L.map(element).setView([lat, lng], (lat !== 0) ? 15 : 2);

			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
			}).addTo(map);

			// If coordinates are 0, try to center on account_address if it exists
			if (lat === 0 && lng === 0) {
				var fallbackAddress = $('#account_address').val() || $('#address').val() || '';
				if (fallbackAddress) {
					var url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(fallbackAddress);
					if (paradb_maps.locationiq_key) {
						url = 'https://us1.locationiq.com/v1/search.php?key=' + paradb_maps.locationiq_key + '&q=' + encodeURIComponent(fallbackAddress) + '&format=json';
					}
					$.getJSON(url, function(data) {
						if (data && data.length > 0) {
							var pos = [parseFloat(data[0].lat), parseFloat(data[0].lon)];
							map.setView(pos, 12);
						}
					});
				}
			}

			// Fix for maps in hidden containers
			setTimeout(function() {
				map.invalidateSize();
			}, 500);

			var marker = L.marker([lat, lng], {draggable: true}).addTo(map);

			marker.on('dragend', function(e) {
				var pos = marker.getLatLng();
				$('#latitude').val(pos.lat.toFixed(6));
				$('#longitude').val(pos.lng.toFixed(6));
				
				// Reverse Geocode (Nominatim)
				var url = 'https://nominatim.openstreetmap.org/reverse?format=json&lat=' + pos.lat + '&lon=' + pos.lng;
				if (paradb_maps.locationiq_key) {
					url = 'https://us1.locationiq.com/v1/reverse.php?key=' + paradb_maps.locationiq_key + '&lat=' + pos.lat + '&lon=' + pos.lng + '&format=json';
				}

				$.getJSON(url, function(data) {
					if (data && data.address) {
						if (confirm('Update address details from map location?')) {
							var addr = data.address;
							var fullAddr = data.display_name;
							
							$('input[name="location_address"], input[name="address"]').val(fullAddr);
							$('input[name="location_city"], input[name="city"]').val(addr.city || addr.town || addr.village || '');
							$('input[name="location_state"], input[name="state"]').val(addr.state || '');
							$('input[name="location_zip"], input[name="zip"]').val(addr.postcode || '');
							$('input[name="location_country"], input[name="country"]').val(addr.country || '');
						}
					}
				});
			});

			$('#latitude, #longitude').on('change', function() {
				var newLat = parseFloat($('#latitude').val()) || 0;
				var newLng = parseFloat($('#longitude').val()) || 0;
				marker.setLatLng([newLat, newLng]);
				map.setView([newLat, newLng]);
			});

			$('#geocode-address').on('click', function() {
				var address = $('#incident_location').val() || $('#address').val() || $('#location_address').val() || $('#account_address').val() || '';
				if (!address) {
					alert('Please enter an address first.');
					return;
				}

				var url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(address);
				if (paradb_maps.locationiq_key) {
					url = 'https://us1.locationiq.com/v1/search.php?key=' + paradb_maps.locationiq_key + '&q=' + encodeURIComponent(address) + '&format=json';
				}

				$.getJSON(url, function(data) {
					if (data && data.length > 0) {
						var pos = [parseFloat(data[0].lat), parseFloat(data[0].lon)];
						map.setView(pos, 15);
						marker.setLatLng(pos);
						$('#latitude').val(pos[0].toFixed(6));
						$('#longitude').val(pos[1].toFixed(6));
					} else {
						alert('Location not found.');
					}
				});
			});
		}

		// Evidence type auto-selection based on file extension
		$('input[name="evidence_file"]').on('change', function() {
			var fileName = $(this).val().split('\\').pop();
			var extension = fileName.split('.').pop().toLowerCase();
			var $typeSelect = $('select[name="evidence_type"]');
			
			if ($typeSelect.length > 0) {
				var imageTypes = ['jpg', 'jpeg', 'png', 'gif'];
				var audioTypes = ['mp3', 'wav', 'ogg'];
				var videoTypes = ['mp4', 'avi', 'mov'];
				var docTypes = ['pdf', 'doc', 'docx'];
				
				if (imageTypes.indexOf(extension) !== -1) {
					$typeSelect.val('photo');
				} else if (audioTypes.indexOf(extension) !== -1) {
					$typeSelect.val('audio');
				} else if (videoTypes.indexOf(extension) !== -1) {
					$typeSelect.val('video');
				} else if (docTypes.indexOf(extension) !== -1) {
					$typeSelect.val('document');
				}
			}
		});

		// Location ID Dropdown Change
		$('#location_id').on('change', function() {
			var $selected = $(this).find('option:selected');
			if ($(this).val()) {
				var lat = $selected.data('lat') || 0;
				var lng = $selected.data('lng') || 0;
				$('#latitude').val(lat).trigger('change');
				$('#longitude').val(lng).trigger('change');
			}
		});

		// Table row highlighting
		$('.wp-list-table tbody tr').on('mouseenter', function() {
			$(this).addClass('hover-highlight');
		}).on('mouseleave', function() {
			$(this).removeClass('hover-highlight');
		});

		// Dashboard widget refresh
		$('.paradb-widget-refresh').on('click', function(e) {
			e.preventDefault();
			var $widget = $(this).closest('.paradb-widget');
			$widget.addClass('loading');
			
			// Simulate AJAX refresh
			setTimeout(function() {
				$widget.removeClass('loading');
			}, 1000);
		});

		// Relationship Target Object loading
		$('#rel_target_type').on('change', function() {
			var type = $(this).val();
			var $select = $('#rel_target_id');
			var $loading = $('#rel_target_loading');

			if (!type || $select.length === 0) {
				return;
			}

			$select.prop('disabled', true);
			$loading.show();

			$.ajax({
				url: paradb_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'paradb_get_linkable_objects',
					nonce: paradb_admin.nonce,
					type: type
				},
				success: function(response) {
					if (response.success) {
						var objects = response.data.objects;
						var options = '<option value="">' + (objects.length > 0 ? 'Select Object' : 'No objects found') + '</option>';
						
						$.each(objects, function(i, obj) {
							options += '<option value="' + obj.id + '">' + obj.label + '</option>';
						});
						
						$select.html(options);
					} else {
						$select.html('<option value="">Error loading objects</option>');
					}
				},
				error: function() {
					$select.html('<option value="">Error loading objects</option>');
				},
				complete: function() {
					$select.prop('disabled', false);
					$loading.hide();
				}
			});
		});
		
		// Postbox toggle handler
		$(document).on('click', '.postbox .handlediv, .postbox .hndle', function() {
			var $postbox = $(this).closest('.postbox');
			$postbox.toggleClass('closed');
			
			// Update aria-expanded if the button was clicked
			var $btn = $postbox.find('.handlediv');
			if ($btn.length > 0) {
				$btn.attr('aria-expanded', !$postbox.hasClass('closed'));
			}
		});
		
		// Trigger initial load if element exists
		if ($('#rel_target_type').length > 0) {
			$('#rel_target_type').trigger('change');
		}

	});

	/**
	 * Save form data to localStorage
	 */
	function saveFormData(formId) {
		var $form = $('#' + formId);
		if ($form.length === 0) {
			$form = $('form').first();
		}
		
		var formData = {};
		$form.find('input, textarea, select').each(function() {
			var $field = $(this);
			var name = $field.attr('name');
			
			if (name && $field.attr('type') !== 'password') {
				if ($field.attr('type') === 'checkbox') {
					formData[name] = $field.prop('checked');
				} else if ($field.attr('type') === 'radio') {
					if ($field.prop('checked')) {
						formData[name] = $field.val();
					}
				} else {
					formData[name] = $field.val();
				}
			}
		});
		
		try {
			localStorage.setItem('paradb_form_' + formId, JSON.stringify(formData));
		} catch (e) {
			// localStorage not available or quota exceeded
			console.log('Could not save form data');
		}
	}

	/**
	 * Load form data from localStorage
	 */
	function loadFormData(formId) {
		try {
			var savedData = localStorage.getItem('paradb_form_' + formId);
			if (savedData) {
				var formData = JSON.parse(savedData);
				var $form = $('#' + formId);
				if ($form.length === 0) {
					$form = $('form').first();
				}
				
				$.each(formData, function(name, value) {
					var $field = $form.find('[name="' + name + '"]');
					if ($field.length > 0) {
						if ($field.attr('type') === 'checkbox') {
							$field.prop('checked', value);
						} else if ($field.attr('type') === 'radio') {
							$field.filter('[value="' + value + '"]').prop('checked', true);
						} else {
							$field.val(value);
						}
					}
				});
			}
		} catch (e) {
			// localStorage not available
			console.log('Could not load form data');
		}
	}

	/**
	 * Clear saved form data from localStorage
	 */
	function clearFormData(formId) {
		try {
			localStorage.removeItem('paradb_form_' + formId);
		} catch (e) {
			// localStorage not available
			console.log('Could not clear form data');
		}
	}

})( jQuery );