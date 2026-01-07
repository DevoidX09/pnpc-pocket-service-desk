/**
 * Attachment Viewer JavaScript for PNPC Pocket Service Desk
 *
 * Handles lightbox/modal functionality for images and PDFs,
 * including gallery navigation and keyboard controls.
 */
(function($) {
	'use strict';

	// Configuration constants
	var CONFIG = {
		SWIPE_THRESHOLD: 50,           // Minimum pixels for swipe detection
		PDF_LOAD_TIMEOUT: 1000,        // Timeout for PDF load detection (ms)
		ANIMATION_DURATION: 300        // Fade animation duration (ms)
	};

	// Store attachment data
	var attachmentGallery = [];
	var currentIndex = 0;
	var $lightbox = null;
	var touchStartX = 0;
	var touchEndX = 0;

	$(document).ready(function() {
		var initialized = initLightbox();
		if (!initialized) {
			// Lightbox not available, disable attachment viewer
			return;
		}
		
		bindEvents();
		buildAttachmentGallery();
	});

	/**
	 * Initialize lightbox HTML if not present
	 */
	function initLightbox() {
		$lightbox = $('#pnpc-psd-lightbox');
		
		// If lightbox doesn't exist in DOM, it will be rendered by PHP
		// This is just a safety check
		if (!$lightbox.length) {
			console.warn('PNPC PSD: Lightbox element not found in DOM');
			return false;
		}

		// Add touch/swipe support after lightbox is confirmed to exist
		$lightbox.on('touchstart', function(e) {
			touchStartX = e.changedTouches[0].screenX;
		});

		$lightbox.on('touchend', function(e) {
			touchEndX = e.changedTouches[0].screenX;
			handleSwipe();
		});

		return true;
	}

	/**
	 * Build gallery of all image attachments on page
	 */
	function buildAttachmentGallery() {
		attachmentGallery = [];
		
		$('.pnpc-psd-view-attachment').each(function() {
			var $btn = $(this);
			var type = $btn.data('type');
			var url = $btn.data('url');
			var filename = $btn.data('filename');
			
			if (type && url) {
				attachmentGallery.push({
					type: type,
					url: url,
					filename: filename || 'Attachment',
					$element: $btn
				});
			}
		});
	}

	/**
	 * Bind all event listeners
	 */
	function bindEvents() {
		// Open lightbox on button click
		$(document).on('click', '.pnpc-psd-view-attachment', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var type = $btn.data('type');
			var url = $btn.data('url');
			var filename = $btn.data('filename');
			
			// Find index in gallery
			var index = attachmentGallery.findIndex(function(item) {
				return item.url === url && item.type === type;
			});
			
			if (index === -1) {
				// Not in gallery, add it
				index = attachmentGallery.length;
				attachmentGallery.push({
					type: type,
					url: url,
					filename: filename || 'Attachment',
					$element: $btn
				});
			}
			
			openLightbox(index);
		});

		// Click on thumbnail images
		$(document).on('click', '.pnpc-psd-attachment-thumbnail', function(e) {
			e.preventDefault();
			var $img = $(this);
			var $btn = $img.closest('.pnpc-psd-attachment').find('.pnpc-psd-view-attachment');
			
			if ($btn.length) {
				$btn.trigger('click');
			}
		});

		// Close lightbox
		$(document).on('click', '.pnpc-psd-lightbox-close', function(e) {
			e.preventDefault();
			closeLightbox();
		});

		// Close on backdrop click
		$(document).on('click', '.pnpc-psd-lightbox-backdrop', function(e) {
			closeLightbox();
		});

		// Navigation buttons
		$(document).on('click', '.pnpc-psd-lightbox-prev', function(e) {
			e.preventDefault();
			navigatePrev();
		});

		$(document).on('click', '.pnpc-psd-lightbox-next', function(e) {
			e.preventDefault();
			navigateNext();
		});

		// Keyboard navigation
		$(document).on('keydown', function(e) {
			if (!$lightbox || !$lightbox.is(':visible')) {
				return;
			}

			switch(e.key) {
				case 'Escape':
					closeLightbox();
					break;
				case 'ArrowLeft':
					navigatePrev();
					break;
				case 'ArrowRight':
					navigateNext();
					break;
				case 'Home':
					navigateToIndex(0);
					break;
				case 'End':
					navigateToIndex(getImageCount() - 1);
					break;
			}
		});
	}

	/**
	 * Handle swipe gesture
	 */
	function handleSwipe() {
		var diff = touchStartX - touchEndX;

		if (Math.abs(diff) > CONFIG.SWIPE_THRESHOLD) {
			if (diff > 0) {
				// Swiped left - next
				navigateNext();
			} else {
				// Swiped right - prev
				navigatePrev();
			}
		}
	}

	/**
	 * Open lightbox at specific index
	 */
	function openLightbox(index) {
		if (!$lightbox || !$lightbox.length) {
			console.error('PNPC PSD: Lightbox not initialized');
			return;
		}

		if (index < 0 || index >= attachmentGallery.length) {
			console.error('PNPC PSD: Invalid attachment index');
			return;
		}

		currentIndex = index;
		var attachment = attachmentGallery[currentIndex];

		// Show lightbox
		$lightbox.fadeIn(CONFIG.ANIMATION_DURATION);
		$lightbox.attr('aria-hidden', 'false');
		$('body').addClass('pnpc-psd-lightbox-open');

		// Load content based on type
		if (attachment.type === 'image') {
			loadImage(attachment);
			updateNavigationButtons();
		} else if (attachment.type === 'pdf') {
			loadPDF(attachment);
			hideNavigationButtons();
		}

		// Update download link
		$lightbox.find('.pnpc-psd-lightbox-download').attr('href', attachment.url);
		
		// Set focus to close button for accessibility
		setTimeout(function() {
			$lightbox.find('.pnpc-psd-lightbox-close').focus();
		}, CONFIG.ANIMATION_DURATION + 50);
	}

	/**
	 * Close lightbox
	 */
	function closeLightbox() {
		if (!$lightbox || !$lightbox.length) {
			return;
		}

		$lightbox.fadeOut(CONFIG.ANIMATION_DURATION);
		$lightbox.attr('aria-hidden', 'true');
		$('body').removeClass('pnpc-psd-lightbox-open');

		// Clear content after animation
		setTimeout(function() {
			$lightbox.find('.pnpc-psd-lightbox-image').attr('src', 'about:blank');
			$lightbox.find('.pnpc-psd-lightbox-pdf').attr('src', 'about:blank');
		}, CONFIG.ANIMATION_DURATION);
	}

	/**
	 * Load image in lightbox
	 */
	function loadImage(attachment) {
		var $imageContainer = $lightbox.find('.pnpc-psd-lightbox-image-container');
		var $pdfContainer = $lightbox.find('.pnpc-psd-lightbox-pdf-container');
		var $image = $lightbox.find('.pnpc-psd-lightbox-image');
		var $filename = $lightbox.find('.pnpc-psd-lightbox-filename');
		var $counter = $lightbox.find('.pnpc-psd-lightbox-counter');

		// Show image container, hide PDF container
		$imageContainer.show();
		$pdfContainer.hide();

		// Load image
		$image.attr('src', attachment.url);
		$image.attr('alt', attachment.filename);

		// Update caption
		$filename.text(attachment.filename);

		// Update counter for images only
		var imageCount = getImageCount();
		var imageIndex = getImageIndex(currentIndex);
		
		if (imageCount > 1) {
			$counter.text((imageIndex + 1) + ' / ' + imageCount);
			$counter.show();
		} else {
			$counter.hide();
		}
	}

	/**
	 * Load PDF in lightbox
	 */
	function loadPDF(attachment) {
		var $imageContainer = $lightbox.find('.pnpc-psd-lightbox-image-container');
		var $pdfContainer = $lightbox.find('.pnpc-psd-lightbox-pdf-container');
		var $pdf = $lightbox.find('.pnpc-psd-lightbox-pdf');
		var $fallback = $lightbox.find('.pnpc-psd-pdf-fallback');

		// Show PDF container, hide image container
		$pdfContainer.show();
		$imageContainer.hide();

		// Try to load PDF in iframe
		$pdf.attr('src', attachment.url);

		// Check if PDF loaded successfully
		// Most modern browsers support inline PDFs, but we provide fallback
		var pdfLoadTimeout = setTimeout(function() {
			// Check iframe dimensions and load state
			var pdfHeight = $pdf.height();
			var pdfWidth = $pdf.width();
			
			// If iframe has no dimensions, assume load failed
			if (pdfHeight === 0 && pdfWidth === 0) {
				$pdf.hide();
				if ($fallback.length) {
					$fallback.show();
					$fallback.find('a').attr('href', attachment.url);
				}
			}
		}, CONFIG.PDF_LOAD_TIMEOUT);

		// Listen for successful iframe load
		$pdf.on('load', function() {
			clearTimeout(pdfLoadTimeout);
			$pdf.show();
			$fallback.hide();
		});

		// Handle load errors
		$pdf.on('error', function() {
			clearTimeout(pdfLoadTimeout);
			$pdf.hide();
			if ($fallback.length) {
				$fallback.show();
				$fallback.find('a').attr('href', attachment.url);
			}
		});
	}

	/**
	 * Navigate to previous item
	 */
	function navigatePrev() {
		var imageCount = getImageCount();
		if (imageCount <= 1) {
			return;
		}

		var imageIndex = getImageIndex(currentIndex);
		var prevImageIndex = imageIndex > 0 ? imageIndex - 1 : imageCount - 1;
		var prevIndex = getAttachmentIndexByImageIndex(prevImageIndex);

		if (prevIndex !== -1) {
			navigateToIndex(prevIndex);
		}
	}

	/**
	 * Navigate to next item
	 */
	function navigateNext() {
		var imageCount = getImageCount();
		if (imageCount <= 1) {
			return;
		}

		var imageIndex = getImageIndex(currentIndex);
		var nextImageIndex = imageIndex < imageCount - 1 ? imageIndex + 1 : 0;
		var nextIndex = getAttachmentIndexByImageIndex(nextImageIndex);

		if (nextIndex !== -1) {
			navigateToIndex(nextIndex);
		}
	}

	/**
	 * Navigate to specific index
	 */
	function navigateToIndex(index) {
		if (index < 0 || index >= attachmentGallery.length) {
			return;
		}

		var attachment = attachmentGallery[index];
		
		// Only navigate if it's an image (navigation only works for images)
		if (attachment.type === 'image') {
			currentIndex = index;
			loadImage(attachment);
			updateNavigationButtons();
			
			// Update download link
			$lightbox.find('.pnpc-psd-lightbox-download').attr('href', attachment.url);
		}
	}

	/**
	 * Update navigation button states
	 */
	function updateNavigationButtons() {
		var $prev = $lightbox.find('.pnpc-psd-lightbox-prev');
		var $next = $lightbox.find('.pnpc-psd-lightbox-next');
		var imageCount = getImageCount();

		if (imageCount > 1) {
			$prev.addClass('pnpc-psd-show');
			$next.addClass('pnpc-psd-show');
		} else {
			$prev.removeClass('pnpc-psd-show');
			$next.removeClass('pnpc-psd-show');
		}
	}

	/**
	 * Hide navigation buttons (for PDFs)
	 */
	function hideNavigationButtons() {
		$lightbox.find('.pnpc-psd-lightbox-prev').removeClass('pnpc-psd-show');
		$lightbox.find('.pnpc-psd-lightbox-next').removeClass('pnpc-psd-show');
	}

	/**
	 * Get total count of images in gallery
	 */
	function getImageCount() {
		return attachmentGallery.filter(function(item) {
			return item.type === 'image';
		}).length;
	}

	/**
	 * Get image index (0-based) within images only
	 */
	function getImageIndex(attachmentIndex) {
		var imageIndex = 0;
		for (var i = 0; i < attachmentIndex; i++) {
			if (attachmentGallery[i].type === 'image') {
				imageIndex++;
			}
		}
		return imageIndex;
	}

	/**
	 * Get attachment index by image index
	 */
	function getAttachmentIndexByImageIndex(imageIndex) {
		var count = 0;
		for (var i = 0; i < attachmentGallery.length; i++) {
			if (attachmentGallery[i].type === 'image') {
				if (count === imageIndex) {
					return i;
				}
				count++;
			}
		}
		return -1;
	}

})(jQuery);
