<?php

namespace ChristopherBolt\BoltShopTools\Extensions;









use ChristopherBolt\BoltShopTools\Helpers\Session;
use SilverShop\Model\Order;
use SilverShop\Checkout\CheckoutComponentConfig;
use SilverShop\Checkout\Component\OnsitePayment;
use SilverShop\Forms\PaymentForm;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Extension;



// Required to create an onsite payment form when paying an unpaid order
// Originally created for PSANZ, requies modifications to OrderActionsForm->dopayment

class BoltOrderManipulation extends Extension {
	
	 private static $allowed_actions = array(
		'payment',
		'PaymentForm'
    );
	
	// Chris Bolt, for onsite gateway payments
	/**
     * Action for making on-site payments
     */
    public function payment()
    {
        return array(
            'Title'     => 'Make Payment',
            'OrderForm' => $this->PaymentForm(),
        );
    }
	
	public function PaymentFormSessionOrder() {
		$gateway = Session::get('BoltOrderManipulation.Gateway');
		$orderID = Session::get('BoltOrderManipulation.OrderID');
		Session::set('Checkout.PaymentMethod', $gateway);
		
		if ($order = Order::get()->byId($orderID)) {
			return $order;
		}
	}

    public function PaymentForm()
    {
        
		$gateway = Session::get('BoltOrderManipulation.Gateway');
		$orderID = Session::get('BoltOrderManipulation.OrderID');
		Session::set('Checkout.PaymentMethod', $gateway);
		
		if ($order = Order::get()->byId($orderID)) {

			$config = new CheckoutComponentConfig($order, false);
			$config->addComponent(OnsitePayment::create());
	
			$form = PaymentForm::create($this->owner, PaymentForm::class, $config);
	
			$form->setActions(
				FieldList::create(
					FormAction::create("submitpayment", _t('CheckoutForm.SubmitPayment', "Submit Payment"))
				)
			);
			
			$form->setSuccessLink($this->owner->Link('order/'.$order->ID));
			$form->setFailureLink($this->owner->Link('payment'));
			$this->owner->extend('updatePaymentForm', $form);
	
			return $form;
		}
    }
	
	// End Chris Bolt
}