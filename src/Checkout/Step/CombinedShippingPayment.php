<?php

namespace ChristopherBolt\BoltShopTools\Checkout\Step;

use SilverStripe\Omnipay\GatewayInfo;








use SilverShop\Cart\ShoppingCart;
use SilverShop\Checkout\CheckoutComponentConfig;
use ChristopherBolt\BoltShopTools\Checkout\Component\CombinedShippingPayment as CombinedShippingPaymentCheckoutComponent;
use SilverShop\Forms\CheckoutForm;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FieldList;
use SilverShop\Checkout\Checkout;
use SilverShop\Checkout\Step\CheckoutStep;


/**
 * Gives methods to ship by, based on previously given address and order items.
 * 
 */
class CombinedShippingPayment extends CheckoutStep{
	
	private static $allowed_actions = array(
		'shippingandpayment',
		'ShippingPaymentForm',
	);
	
	protected function checkoutconfig() {
		$config = new CheckoutComponentConfig(ShoppingCart::curr(), false);
		$config->addComponent(new CombinedShippingPaymentCheckoutComponent());

		return $config;
	}

	public function shippingandpayment() {
		// If only one payment gateway and one or less shipping methods then skip to the next step
		$component = new CombinedShippingPaymentCheckoutComponent();
		$gateways = GatewayInfo::getSupportedGateways();
		if ($component->noShippingRequired($this->owner->Cart()) && count($gateways) == 1) {
			return $this->owner->redirect($this->NextStepLink());
		}
		
		// Else display the order form
		return array(
			'OrderForm' => $this->ShippingPaymentForm()
		);
	}

	public function ShippingPaymentForm() {
		$form = new CheckoutForm($this->owner, "ShippingPaymentForm", $this->checkoutconfig());
		$form->setActions(new FieldList(
			FormAction::create("setshippingpaymentmethod", "Continue")
		));
		$this->owner->extend('updateShippingPaymentMethodForm', $form);

		return $form;
	}

	public function setshippingpaymentmethod($data, $form) {
		$this->checkoutconfig()->setData($form->getData());
		return $this->owner->redirect($this->NextStepLink());
	}

	public function SelectedPaymentMethod() {
		return Checkout::get($this->owner->Cart())->getSelectedPaymentMethod(true);
	}
	
	public function PaymentRequired()
    {
        $order = $this->owner->Cart();
		if ($order && !$order->GrandTotal()) {
			return false;	
		}
		return true;
    }
	
}