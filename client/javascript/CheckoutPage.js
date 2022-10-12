// Default JavaScript to power the shop, if needed copy into themes folder and modify for each project

// Original checkoutpage.js follows ... with some mods
(function($) {
	$(document).ready(function() {
		
		// Chris Bolt, surrounded in a function so can call on Ajax load
		function originalCheckoutSetup() {
		// End Chris Bolt

			// Configuration defaults
			if (typeof(window.ShopConfig) != 'object') window.ShopConfig = {};
			if (typeof(window.ShopConfig.Checkout) != 'object') window.ShopConfig.Checkout = {};
			window.ShopConfig.Checkout = $.extend({
				showFieldAnimation: 'fadeIn',
				hideFieldAnimation: 'fadeOut',
			}, window.ShopConfig.Checkout);
	
	
			// Payment checkout component (selecting a payment method)
			var paymentInputs = $('#PaymentMethod input[type=radio]');
			var methodFields = $('div.paymentfields');
			
			methodFields.hide();
			
			paymentInputs.each(function(e) {
				if($(this).attr('checked') == true) {
					$('#MethodFields_' + $(this).attr('value')).show();
				}
			});
			
			paymentInputs.click(function(e) {
				methodFields.hide();
				$('#MethodFields_' + $(this).attr('value')).show();
			});
	
	
			// Addressbook checkout component
			// This handles a dropdown or radio buttons containing existing addresses or payment methods,
			// with one of the options being "create a new ____". When that last option is selected, the
			// other fields need to be shown, otherwise they need to be hidden.
			function onExistingValueChange(){
				$('.hasExistingValues').each(function(idx, container){
					// visible if the value is not an ID (numeric)
					var toggleState = isNaN(parseInt($('.existingValues select, .existingValues input:checked', container).val()));
					var toggleMethod = toggleState ? ShopConfig.Checkout.showFieldAnimation : ShopConfig.Checkout.hideFieldAnimation;
					var toggleFields = $(container).find('.field').not('.existingValues');
	
					// animate the fields
					if (toggleFields && toggleFields.length > 0) {
						if (typeof(toggleMethod) == 'object') {
							toggleFields.animate(toggleMethod, 'fast', 'swing');
						} else {
							toggleFields[toggleMethod]('fast', 'swing');
						}
					}
	
					// clear them out
					// Chris Bolt, comment this out
					//toggleFields.find('input, select, textarea').val('').prop('disabled', toggleState ? '' : 'disabled');
					// End Chris Bolt
				});
			}
	
			$('.existingValues select').on('change', onExistingValueChange);
			$('.existingValues input[type=radio]').on('click', onExistingValueChange);
	
			onExistingValueChange(); // handle initial state
		// Chris Bolt
		}
		originalCheckoutSetup()
		// End Chris Bolt
		
		// ------------------------------------
		// Chrid Bolt, new Ajax Checkout
		// AJAX Checkout Page 
		//Ajax checkout
		ajaxifyCheckoutForm();
		function ajaxifyCheckoutForm() {
			
			// Hide / show billing address
			var separateBilling = $('#CheckoutForm_CombinedDetailsForm_ChristopherBolt-BoltShopTools-Checkout-Component-CombinedDetails_SeparateBillingAddress');
			if (separateBilling.length) {
				var billingFields = separateBilling.parents('fieldset').next().first();
				if (separateBilling.prop('checked')) {
					billingFields.show();
				} else {
					billingFields.hide();
				}
				separateBilling.click(function() {
					if ($(this).prop('checked')) {
						billingFields.show();
					} else {
						billingFields.hide();
					}
				});
			}
			
			$( "#Checkout form" ).not('form.isAjaxified').submit(function( event ) {
				if (this.id == 'PaymentForm_ConfirmationForm') {
					if (!this.ReadTermsAndConditions.checked) {
						alert('You must agree to the terms and conditions.');
						event.preventDefault();
						return false;	
					}
					$(this).find('.Actions input[type=submit]').addClass('loading');
				} else {
					$(this).addClass('isAjaxified');
					$(this).find('.Actions input[type=submit]').addClass('loading');
					// Stop form from submitting normally
					event.preventDefault();
					// Send the data using post
					var posting = $.post( 
						$(this).attr("action"), 
						$(this).serialize(),
						function( data ) {
							//alert(data);
							if (/^\s*<form /gi.test(data)) {
								html = data.replace(/^[\s\S]+<form [^>]+>([\s\S]+)<\/form>[\s\S]+$/gi, "$1");
								//alert(html);
								$( "#Checkout form" ).html(html);
							} else {
								html = data.replace(/^[\s\S]+<body [^>]+>([\s\S]+)<\/body>[\s\S]+$/gi, "$1");
								$('#Checkout').html($(html).find('#Checkout'));
							}
							if (typeof(jQuery.fn.addPlaceholders) === "function") {
								jQuery('#Checkout form input.text, textarea').addPlaceholders({forceScripted:true});
							}
							originalCheckoutSetup();
							ajaxifyCheckoutForm();
							// Scroll to
							$('html, body').animate({
								scrollTop: $('.accordion-group.current').offset().top
							}, 500, 'swing')
						} 
					);
				}
			});	
		}
	});
})(jQuery);	