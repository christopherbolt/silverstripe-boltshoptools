// Default JavaScript to power the shop, if needed copy into themes folder and modify for each project
(function( $ ) {
	
	$(document).ready(function() {
		////////////////
		// Product Page
		////////////////
		if ($('#Product').length) {
			
			var ProductID = $('#Product').attr('data-product-id');
			
			var hasEnlarge = $('.productImages a.enlarge img.mainImage').length ? false : true;
			
			if (typeof(ProductID) !== 'undefined') {
				
				$('#UnitPrice input').change(function() {
					var val = $(this).val();
					val = val.replace(/[^0-9.]/gi, '');
					if ((val = parseFloat(val)) && val > 0) {
						$('.price').html('$'+val.toFixed(2));
					}
				});
				
				$('#VariationForm_Form select').change(function() {
					// check that all options have been selected
					var exit = false;
					$('#VariationForm_Form select').each(function() {
						if (!$(this).val()) exit = true;
					});
					if (exit) return;
					
					var width = $('.productImages img.mainImage').attr('width');
					var height = $('.productImages img.mainImage').attr('height');
					if ($('body').hasClass('retina')) {
						width = parseInt(width)*2;
						height = parseInt(height)*2;
					}					
					$('.attributeForm .priceWrapper').addClass('loading').html('&nbsp;');
					$.get(
						'JsonPriceUpdater', 
						$(this.form).serialize()+'&ProductID='+ProductID+'&imgWidth='+width+'&imgHeight='+height, 						
						function(data) {
							$('.priceWrapper').removeClass('loading');
							if (data.success) {
								if (data.imgURL) {
									$('.productImages img.mainImage').attr('src', data.imgURL);
									$('.productImages a.enlarge').attr('href', data.imgURLForPopup);
									if (hasEnlarge) setupProductImagePopup();
									updateCurrentImage();
								}
								if (data.priceHTML) {
									$('.attributeForm .priceWrapper').html(data.priceHTML);
								}
								if (data.canPurchase) {
									$('#VariationForm_Form_action_addtocart').prop('disabled', false);
								} else {
									$('#VariationForm_Form_action_addtocart').prop('disabled', true);
								}
							} else {
								$('.priceWrapper').html('<p class="message">This product is not available with the options you have chosen.</p>');
							}
						},
						'json'
					)
				});
			}
			
			if (hasEnlarge) {
				$('.productImages .grid.additionalImages a').click(function() {
					if ($('body').hasClass('retina')) {
						$('.productImages img.mainImage').attr('src', $(this).attr('data-retina-src'));
					} else {
						$('.productImages img.mainImage').attr('src', $(this).attr('href'));
					}
					$('.productImages a.enlarge').attr('href', $(this).attr('data-popupimage'));
					updateCurrentImage();
					setupProductImagePopup();
					return false;
				});
				setupProductImagePopup();
			} else {
				$('.productImages').magnificPopup({
					delegate: 'a', // the selector for gallery item
					type: 'image',
					gallery: {
					  enabled:true
					}
				});
			}
			
		}
		/////////////////
		// Addresses etc
		/////////////////
		// Default country drop down to new zealand
		if ($('select[name=Country]').length) {
			$('select[name=Country]').each(function () {
				if ($(this).val() == '') {
					$(this).val('NZ')
				}
			});
		}
		
	});
	
	function updateCurrentImage() {
		var url = $('.productImages img.mainImage').attr('src');
		$('.productImages .grid.additionalImages a').each(function() {
			if ($(this).attr('href') == url) {
				$(this).addClass('current');
			} else {
				$(this).removeClass('current');
			}
		});
	}
	function setupProductImagePopup() {
		if (hasEnlarge) {
			var popupitems = [{src:$('.productImages a.enlarge').attr('href'),type:'image'}]
			$('.productImages .grid.additionalImages a').each(function() {
				//alert($(this).attr('data-popupimage'));
				popupitems.push({src:$(this).attr('data-popupimage'),type:'image'});
			});
			//alert(popupitems);
			$('.productImages a.enlarge').magnificPopup({
				items: popupitems,
				gallery: {
				  enabled: true
				},
				type: 'image' // this is default type
			});
			//$('.productImages a.enlarge').click(function() {
			//	return false;
			//});
		}
	}
	
})(jQuery);