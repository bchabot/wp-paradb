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

		// Smooth scroll for internal links
		$('a[href^="#"]').on('click', function(e) {
			var target = $(this).attr('href');
			if (target !== '#' && $(target).length > 0) {
				e.preventDefault();
				$('html, body').animate({
					scrollTop: $(target).offset().top - 50
				}, 500);
			}
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
		var $errorContainer = $('.form-errors');
		if ($errorContainer.length === 0) {
			$errorContainer = $('<div class="form-errors paradb-notice error"></div>');
			$('.witness-submission-form').prepend($errorContainer);
		}
		
		var errorHtml = '<ul style="margin: 10px 0; padding-left: 20px;">';
		messages.forEach(function(message) {
			errorHtml += '<li>' + message + '</li>';
		});
		errorHtml += '</ul>';
		
		$errorContainer.html('<p><strong>Please correct the following errors:</strong></p>' + errorHtml);
		$errorContainer.show();
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

})( jQuery );