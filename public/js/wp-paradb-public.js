(function( $ ) {
	'use strict';

	/**
	 * Public-facing JavaScript for ParaDB
	 *
	 * @package    WP_ParaDB
	 * @subpackage WP_ParaDB/public/js
	 */

	$(function() {
		// Enhanced witness form validation
		$('.witness-submission-form').on('submit', function(e) {
			var $form = $(this);
			var isValid = true;
			var errorMessages = [];

			// Validate required fields
			$form.find('[required]').each(function() {
				var $field = $(this);
				if (!$field.val() || $field.val().trim() === '') {
					isValid = false;
					$field.addClass('field-error');
					var label = $field.siblings('label').text() || 'This field';
					errorMessages.push(label + ' is required.');
				} else {
					$field.removeClass('field-error');
				}
			});

			// Validate email format if provided
			var $emailField = $form.find('input[type="email"]');
			if ($emailField.val() && !isValidEmail($emailField.val())) {
				isValid = false;
				$emailField.addClass('field-error');
				errorMessages.push('Please enter a valid email address.');
			}

			// Validate description length
			var $description = $form.find('textarea[name="incident_description"]');
			if ($description.val() && $description.val().length < 50) {
				isValid = false;
				$description.addClass('field-error');
				errorMessages.push('Please provide a more detailed description (at least 50 characters).');
			}

			if (!isValid) {
				e.preventDefault();
				showFormErrors(errorMessages);
				
				// Scroll to first error
				var $firstError = $form.find('.field-error').first();
				if ($firstError.length > 0) {
					$('html, body').animate({
						scrollTop: $firstError.offset().top - 100
					}, 500);
				}
			}
		});

		// Clear error styling on input
		$('.witness-submission-form').on('input change', 'input, textarea, select', function() {
			$(this).removeClass('field-error');
		});

		// Character counter for witness description
		var $witnessDescription = $('textarea[name="incident_description"]');
		if ($witnessDescription.length > 0) {
			var $counter = $('<div class="char-counter" style="text-align: right; margin-top: 5px; font-size: 12px; color: #666;"></div>');
			$witnessDescription.after($counter);
			
			$witnessDescription.on('input', function() {
				var length = $(this).val().length;
				var minLength = 50;
				var remaining = minLength - length;
				
				if (remaining > 0) {
					$counter.text('Please write at least ' + remaining + ' more characters');
					$counter.css('color', '#dc3232');
				} else {
					$counter.text(length + ' characters');
					$counter.css('color', '#46b450');
				}
			}).trigger('input');
		}

		// Evidence lightbox for single case view
		$('.evidence-item a').on('click', function(e) {
			var href = $(this).attr('href');
			var isImage = /\.(jpg|jpeg|png|gif)$/i.test(href);
			
			if (isImage) {
				e.preventDefault();
				showLightbox(href);
			}
		});

		// Case search with live results
		var searchTimeout;
		$('.paradb-search-form input[type="search"]').on('input', function() {
			clearTimeout(searchTimeout);
			var $input = $(this);
			var query = $input.val();
			
			if (query.length >= 3) {
				searchTimeout = setTimeout(function() {
					performSearch(query);
				}, 500);
			}
		});

		// smooth scroll for internal links
		$('a[href^="#"]').on('click', function(e) {
			var target = $(this).attr('href');
			if (target !== '#' && $(target).length > 0) {
				e.preventDefault();
				$('html, body').animate({
					scrollTop: $(target).offset().top - 50
				}, 500);
			}
		});

		// Handle conditional field display
		$('#previous_experiences').on('change', function() {
			var $conditional = $('.paradb-conditional[data-depends-on="previous_experiences"]');
			if ($(this).is(':checked')) {
				$conditional.slideDown();
			} else {
				$conditional.slideUp();
				$('#previous_details').val('');
			}
		});

		// Witness Form AJAX Submission
		$('#paradb-witness-form').on('submit', function(e) {
			var isValid = true;
			var messages = [];

			// Check phenomena types
			if ($('input[name="phenomena_types[]"]:checked').length === 0) {
				isValid = false;
				messages.push('Please select at least one type of phenomenon.');
			}

			// Check description length
			var $description = $('#incident_description');
			var description = $description.val();
			var minLength = parseInt($description.data('min-length')) || 50;
			if (description.length < minLength) {
				isValid = false;
				messages.push('Description must be at least ' + minLength + ' characters.');
			}

			if (!isValid) {
				e.preventDefault();
				showFormErrors(messages);
				return false;
			}

			// Handle AJAX submission
			e.preventDefault();
			var $form = $(this);
			var $button = $form.find('.paradb-submit-button');
			var originalText = $button.text();
			
			$button.prop('disabled', true).text('Submitting...');
			$('.paradb-form-messages').empty();

			$.ajax({
				url: paradb_public.ajax_url,
				type: 'POST',
				data: $form.serialize() + '&action=paradb_submit_witness_form',
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						showNotice(response.data.message, 'success');
						$form[0].reset();
						$('.paradb-conditional').hide();
						
						if (response.data.redirect_url) {
							setTimeout(function() {
								window.location.href = response.data.redirect_url;
							}, 2000);
						}
					} else {
						showFormErrors([response.data.message || 'An error occurred. Please try again.']);
					}
				},
				error: function() {
					showFormErrors(['An error occurred. Please try again.']);
				},
				complete: function() {
					$button.prop('disabled', false).text(originalText);
				}
			});
		});

		// Read more/less toggle for long content
		$('.case-description').each(function() {
			var $desc = $(this);
			if ($desc.height() > 200) {
				$desc.css({
					'max-height': '200px',
					'overflow': 'hidden',
					'position': 'relative'
				});
				
				var $readMore = $('<a href="#" class="read-more-toggle" style="display: block; margin-top: 10px; color: #2271b1; text-decoration: none;">Read more &darr;</a>');
				$desc.after($readMore);
				
				$readMore.on('click', function(e) {
					e.preventDefault();
					if ($desc.css('max-height') === '200px') {
						$desc.css('max-height', 'none');
						$(this).text('Read less \u2191');
					} else {
						$desc.css('max-height', '200px');
						$(this).text('Read more \u2193');
					}
				});
			}
		});

		// Print functionality for case pages
		$('.print-case-button').on('click', function(e) {
			e.preventDefault();
			window.print();
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
					$('#latitude').val(lat).trigger('change');
					$('#longitude').val(lng).trigger('change');
				}
				$btn.text('📍');
			}, function(err) {
				alert('Error: ' + err.message);
				$btn.text('📍');
			});
		});

		// Auto-suggest for location fields
		if (typeof google !== 'undefined' && google.maps && paradb_maps.provider === 'google') {
			$('input[name="incident_location"], #incident_location').each(function() {
				$(this).attr('autocomplete', 'off');
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

			$('#location-map').each(function() {
				initGoogleMap(this);
			});
		} else if (typeof L !== 'undefined' && paradb_maps.provider === 'osm') {
			$('#location-map').each(function() {
				initLeafletMap(this);
			});
		}

		// Share functionality
		$('.share-case-button').on('click', function(e) {
			e.preventDefault();
			if (navigator.share) {
				navigator.share({
					title: document.title,
					url: window.location.href
				}).catch(function(error) {
					console.log('Error sharing:', error);
				});
			} else {
				// Fallback: copy to clipboard
				var dummy = document.createElement('input');
				document.body.appendChild(dummy);
				dummy.value = window.location.href;
				dummy.select();
				document.execCommand('copy');
				document.body.removeChild(dummy);
				
				showNotice('Link copied to clipboard!', 'success');
			}
		});
	});

	/**
	 * Validate email format
	 */
	function isValidEmail(email) {
		var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return re.test(email);
	}

	/**
	 * Show form validation errors
	 */
	function showFormErrors(messages) {
		var $container = $('.paradb-form-messages');
		if ($container.length === 0) {
			// Fallback if container is missing
			alert(messages.join('\n'));
			return;
		}
		
		var errorHtml = '<div class="paradb-notice error"><ul style="margin: 10px 0; padding-left: 20px;">';
		messages.forEach(function(message) {
			errorHtml += '<li>' + message + '</li>';
		});
		errorHtml += '</ul></div>';
		
		$container.html(errorHtml);
		$('html, body').animate({
			scrollTop: $container.offset().top - 100
		}, 500);
	}

	/**
	 * Show image in lightbox
	 */
	function showLightbox(imageUrl) {
		var $lightbox = $('<div class="paradb-lightbox"></div>');
		var $overlay = $('<div class="lightbox-overlay"></div>');
		var $content = $('<div class="lightbox-content"></div>');
		var $img = $('<img src="' + imageUrl + '" alt="">');
		var $close = $('<button class="lightbox-close">&times;</button>');
		
		$content.append($img, $close);
		$lightbox.append($overlay, $content);
		$('body').append($lightbox);
		
		// Add styles
		$lightbox.css({
			'position': 'fixed',
			'top': 0,
			'left': 0,
			'width': '100%',
			'height': '100%',
			'z-index': 999999,
			'display': 'flex',
			'align-items': 'center',
			'justify-content': 'center'
		});
		
		$overlay.css({
			'position': 'absolute',
			'top': 0,
			'left': 0,
			'width': '100%',
			'height': '100%',
			'background': 'rgba(0, 0, 0, 0.9)',
			'cursor': 'pointer'
		});
		
		$content.css({
			'position': 'relative',
			'max-width': '90%',
			'max-height': '90%',
			'z-index': 1
		});
		
		$img.css({
			'max-width': '100%',
			'max-height': '90vh',
			'display': 'block'
		});
		
		$close.css({
			'position': 'absolute',
			'top': '-40px',
			'right': '0',
			'background': 'none',
			'border': 'none',
			'color': '#fff',
			'font-size': '40px',
			'cursor': 'pointer',
			'line-height': '1'
		});
		
		// Close lightbox
		$overlay.on('click', function() {
			$lightbox.remove();
		});
		
		$close.on('click', function() {
			$lightbox.remove();
		});
		
		$(document).on('keyup.lightbox', function(e) {
			if (e.key === 'Escape') {
				$lightbox.remove();
				$(document).off('keyup.lightbox');
			}
		});
	}

	/**
	 * Show notice message
	 */
	function showNotice(message, type) {
		var $notice = $('<div class="paradb-notice ' + type + '"><p>' + message + '</p></div>');
		$notice.css({
			'position': 'fixed',
			'top': '20px',
			'right': '20px',
			'z-index': 9999,
			'padding': '15px 20px',
			'border-radius': '4px',
			'box-shadow': '0 2px 8px rgba(0,0,0,0.2)'
		});
		
		$('body').append($notice);
		
		setTimeout(function() {
			$notice.fadeOut(function() {
				$(this).remove();
			});
		}, 3000);
	}

	/**
	 * Perform case search (placeholder for AJAX implementation)
	 */
	function performSearch(query) {
		// This would be enhanced with AJAX in a production version
		console.log('Searching for: ' + query);
	}

	function initGoogleMap(element) {
		var lat = parseFloat($('#latitude').val()) || 0;
		var lng = parseFloat($('#longitude').val()) || 0;
		var center = {lat: lat, lng: lng};
		
		var map = new google.maps.Map(element, {
			zoom: (lat !== 0) ? 15 : 2,
			center: center
		});

		google.maps.event.addListenerOnce(map, 'idle', function(){
			google.maps.event.trigger(map, 'resize');
			map.setCenter(center);
		});

		var marker = new google.maps.Marker({
			position: center,
			map: map,
			draggable: true
		});

		marker.addListener('dragend', function() {
			var pos = marker.getPosition();
			$('#latitude').val(pos.lat().toFixed(6));
			$('#longitude').val(pos.lng().toFixed(6));
		});

		$('#latitude, #longitude').on('change', function() {
			var newPos = {
				lat: parseFloat($('#latitude').val()) || 0,
				lng: parseFloat($('#longitude').val()) || 0
			};
			marker.setPosition(newPos);
			map.setCenter(newPos);
		});

		$('#geocode-address').on('click', function() {
			var address = $('#incident_location').val() || $('#account_address').val() || '';
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

		setTimeout(function() {
			map.invalidateSize();
		}, 500);

		var marker = L.marker([lat, lng], {draggable: true}).addTo(map);

		marker.on('dragend', function(e) {
			var pos = marker.getLatLng();
			$('#latitude').val(pos.lat.toFixed(6));
			$('#longitude').val(pos.lng.toFixed(6));
		});

		$('#latitude, #longitude').on('change', function() {
			var newLat = parseFloat($('#latitude').val()) || 0;
			var newLng = parseFloat($('#longitude').val()) || 0;
			marker.setLatLng([newLat, newLng]);
			map.setView([newLat, newLng]);
		});

		$('#geocode-address').on('click', function() {
			var address = $('#incident_location').val() || $('#account_address').val() || '';
			if (!address) {
				alert('Please enter an address first.');
				return;
			}

			var url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(address);
			if (typeof paradb_maps !== 'undefined' && paradb_maps.locationiq_key) {
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

})( jQuery );