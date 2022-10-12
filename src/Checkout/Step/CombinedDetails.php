<?php

namespace ChristopherBolt\BoltShopTools\Checkout\Step;









use SilverShop\Cart\ShoppingCart;
use SilverShop\Checkout\CheckoutComponentConfig;
use ChristopherBolt\BoltShopTools\Checkout\Component\CombinedDetails as CombinedDetailsCheckoutComponent;
use SilverShop\Forms\CheckoutForm;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FieldList;
use ChristopherBolt\BoltShopTools\Helpers\Session;
use SilverShop\Checkout\Step\CheckoutStep;



// Combined details and addresses into one checkout step

class CombinedDetails extends CheckoutStep{

	private static $allowed_actions = array(
		'customerdetails',
		'CombinedDetailsForm'
	);

	public function customerdetails() {
		$form = $this->CombinedDetailsForm();
		return array(
			'OrderForm' => $form
		);
	}

	public function CombinedDetailsForm() {
		$cart = ShoppingCart::curr();
		if(!$cart){
			return false;
		}
		$config = new CheckoutComponentConfig(ShoppingCart::curr());
		$config->addComponent(new CombinedDetailsCheckoutComponent());
		$form = new CheckoutForm($this->owner, 'CombinedDetailsForm', $config);
		$form->setRedirectLink($this->NextStepLink());
		$form->setActions(new FieldList(
			new FormAction("checkoutSubmit", "Continue")
		));
		$this->owner->extend('updateCombinedDetailsForm', $form);

		return $form;
	}
	
	function CouponCode() {
		return Session::get("cart.couponcode");
	}
}
