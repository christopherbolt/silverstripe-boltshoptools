<?php

namespace ChristopherBolt\BoltShopTools\Checkout\Component;













use SilverShop\Model\Order;
use SilverShop\Checkout\Component\Payment;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\ORM\ValidationException;
use SilverShop\Cart\ShoppingCart;
use SilverShop\Shipping\Model\ShippingMethod;
use SilverShop\Checkout\Component\CheckoutComponent;
use SilverShop\Checkout\Checkout;


class CombinedShippingPayment extends CheckoutComponent{

	function getShippingEstimates(Order $order) {
		if (!$order->hasMethod('getShippingEstimates')) return false;
		$estimates = $order->getShippingEstimates();
		// Chris Bolt, filter out estimates by additional constraints
		$allowedIds = array();
		foreach ($estimates as $estimate) {
			if (	isset($estimate->HideAllOthers) && $estimate->HideAllOthers) {
				$exceptions = $estimate->HideExceptions();
				$allowedIds[] = $estimate->ID;
				if ($exceptions->count()){
					$exceptionIds = $exceptions->getIdList();
					$allowedIds = array_merge($allowedIds, $exceptionIds);
				}
			}
		}
		if (count($allowedIds)) {
			$estimates = $estimates->filter(array(
				'ID' => $allowedIds
			));
		}
		$excludeIds = array();
		foreach ($estimates as $estimate) {
			if (	isset($estimate->OnlyShowIfNoOther) && $estimate->OnlyShowIfNoOther) {
				if ($estimates->count() > 1) {
					// its not the only one, check the OnlyShowIfMethods
					$onlys = $estimate->OnlyShowIfMethods();
					if (!$onlys->count()){
						$excludeIds[] = $estimate->ID;
					} else {
						// check if the OnlyShowIfMethods are the only methods
						$onlyIds = $onlys->getIDList();
						$onlyIds[] = $estimate->ID;
						if ($estimates->exclude(array('ID'=>$onlyIds))->count()) {
							$excludeIds[] = $estimate->ID;
						}
					}
				}
			}
		}
		if (count($excludeIds)) {
			$estimates = $estimates->exclude(array(
				'ID' => $excludeIds
			));
		}
        // Sort esitmates by price
        $estimates = $estimates->Sort('Rate ASC');

		// Note the form can be modified by extending the CombinedShippingPayment Step

		return $estimates;
	}

	public function noShippingRequired(Order $order) {
		// If shipping module not installed then free shipping
		if (!$order->hasMethod('createShippingPackage')) return true;
		// Chris Bolt added free shipping hack.
		$package = $order->createShippingPackage();
		// if package weight or volume is low then return no estimates
		if ($package->weight() <= 0 && $package->volume() <= 0) {
			return true;
		}
		// End Chris Bolt
		return false;
	}

	public function getFormFields(Order $order) {
		$paymentComponet = new Payment();
		$paymentFields = $paymentComponet->getFormFields($order);
		// Note the form can be modified by extending the CombinedShippingPayment Step

		$shippingFields = new FieldList();

		if (!$this->noShippingRequired($order)) {
			// There is no shipping component so we need to do this manually.
			// Chris Bolt, filter out estimates by additional constraints
			$estimates = $this->getShippingEstimates($order);
			// End Chris Bolt

			if($estimates && $estimates->exists()){

				$estimate_array = $estimates->map()->toArray();
				foreach($estimate_array as $id => $value) {
					$estimate_array[$id] = preg_replace('/^([0-9.]+)/', \SilverStripe\ORM\FieldType\DBCurrency::config()->currency_symbol."$1", $value);
				}

				$shippingFields->push(
					$options = OptionsetField::create(
						"ShippingMethodID",
						"Shipping Options",
						$estimate_array,
						$estimates->First()->ID
					)
				);

				if (count($estimate_array) == 1) {
					$options->setValue(key($estimate_array));
					$order->setShippingMethod($estimates->First());
				}

			}else{
				$shippingFields->push(
					LiteralField::create(
						"NoShippingMethods",
						"<p class=\"message warning\">
							"._t("Shipping.NoShippingOptions", "Shipping could not be automatically calculated for this order. We will contact you with pricing. Your order will not ship until shipping costs have been paid.")."
						</p>"
					)
				);
			}
		} else {
			$shippingFields->push(new LiteralField('NoShippingRequired', _t('CheckoutForm.NO_SHIPPING_REQUIRED', 'No shipping required.')));
			if (!$order->GrandTotal()) { // Nothing to pay for and no shipping either
				//Config::inst()->update('Payment', 'allowed_gateways', 'Manual');
				$paymentFields = new FieldList(HiddenField::create('PaymentMethod', 'PaymentMethod', 'Manual'));
				$paymentFields->push(new LiteralField('NoPaymentRequired', _t('CheckoutForm.NO_PAYMENT_REQUIRED', 'No payment required.')));
				
				// Enforce manual payment if no total??
				Checkout::get($order)->setPaymentMethod('Manual');
			}
		}
		// End shipping fields

		$fields = new FieldList(
			CompositeField::create($shippingFields)->setTag('fieldset')->setName('ShippingFields')/*->setLegend('Shipping Method')*/,
			CompositeField::create($paymentFields)->setTag('fieldset')->setName('PaymentFields')/*->setLegend('Payment Method')*/
		);

		return $fields;
	}

	public function getRequiredFields(Order $order) {
		$paymentComponet = new Payment();
		$required = $paymentComponet->getRequiredFields($order);
		if (!$this->noShippingRequired($order)) {
			if (!Order::config()->allow_no_shipping_methods || (($estimates = $this->getShippingEstimates($order)) && $estimates->count())) array_push($required, 'ShippingMethodID');
		}
		return $required;
	}

	public function validateData(Order $order, array $data) {
		// Validate shipping method
		if (!$this->noShippingRequired($order)) {
			$result = new ValidationResult();
			if(!isset($data['ShippingMethodID']) && (!Order::config()->allow_no_shipping_methods || (($estimates = $this->getShippingEstimates($order)) && $estimates->count()))){
				$result->error("Shipping method is required", "ShippingMethodID");
				throw new ValidationException($result);
			}
			$estimates = $order->getShippingEstimates();
			if(($estimates && $estimates->exists()) || !Order::config()->allow_no_shipping_methods){
				$estimateMap = $estimates->map();
				if(!isset($estimateMap[$data['ShippingMethodID']])){
					$result->error("Shipping method not supported", "ShippingMethodID");
					throw new ValidationException($result);
				}
			}
		}

		// Validate payment method
		$paymentComponet = new Payment();
		$paymentComponet->validateData($order, $data) ;
	}

	public function getData(Order $order) {
		$paymentComponet = new Payment();
		$data = $paymentComponet->getData($order);

		$cart = ShoppingCart::singleton()->current();
		if ($cart->ShippingMethodID) {
			$data['ShippingMethodID'] = $cart->ShippingMethodID;
		}
		return $data;
	}

	public function setData(Order $order, array $data) {
		// Set shipping
		$option = null;
		if(isset($data['ShippingMethodID'])){
			$option = ShippingMethod::get()
						->byID((int)$data['ShippingMethodID']);
		}
		//assign option to order / modifier
		if($option){
			$order->setShippingMethod($option);
		} else {
			$order->ShippingTotal = 0;
			$order->ShippingMethodID = 0;
			$order->write();
		}

		// Set payment
		$paymentComponet = new Payment();
		$paymentComponet->setData($order, $data);
	}

}
