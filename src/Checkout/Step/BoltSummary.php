<?php

namespace ChristopherBolt\BoltShopTools\Checkout\Step;


use SilverStripe\Omnipay\GatewayInfo;




use SilverShop\Cart\ShoppingCart;
use SilverShop\Checkout\CheckoutComponentConfig;
use SilverShop\Checkout\Checkout;
use SilverShop\Checkout\Step\Summary;



// Exdends the existing summary, provides rewriting of the Proceed button depending on the action that will be taken

class BoltSummary extends Summary{
	
	private static $allowed_actions = array(
		'summary',
		'ConfirmationForm',
	);

	public function ConfirmationForm() {
		$form = parent::ConfirmationForm();
		
		// get the order
		$config = new CheckoutComponentConfig(ShoppingCart::curr());
		$order = $config->getOrder();
		
		// If order is zero we need to set the success url
		$form->setSuccessLink($order->Link());
		
		// if order is 0 or if the gateway is Manual then we change the button name
		//$order->calculate();
		$gateway = Checkout::get($order)->getSelectedPaymentMethod(false);
		if($order->GrandTotal() == 0 || /*GatewayInfo::isOffsite($gateway) || */GatewayInfo::isManual($gateway)){
			$actions = $form->Actions( )	;
			foreach ($actions as $action) {				
				if ($action->actionName() == 'checkoutSubmit' && $action->Title() == _t('CheckoutForm.PROCEED', 'Proceed to payment')) {
					$action->setTitle(_t('CheckoutForm.PLACE_ORDER', 'Place Order'));
				}
			}
			
		}
		return $form;
	}

}
