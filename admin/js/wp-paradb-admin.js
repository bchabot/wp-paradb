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
		$('a[href*="action=delete"]').on('click', function(e) {
			if (!$(this).data('confirmed')) {
				e.preventDefault();
				if (confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
					$(this).data('confirmed', true).get(0).click();
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
		if (typeof google !== 'undefined' && google.maps) {
			$('input[name="location_address"]').each(function() {
				var autocomplete = new google.maps.places.Autocomplete(this);
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