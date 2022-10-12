<?php

namespace ChristopherBolt\BoltShopTools\Checkout\Component;






#use SilverShop\Discounts\Checkout\CouponCheckoutComponent;
use SilverStripe\Control\Email\Email;
use SilverShop\Model\Order;
use SilverShop\Checkout\Component\CustomerDetails;
use SilverShop\Checkout\Component\AddressBookShipping;
use SilverShop\Checkout\Component\AddressBookBilling;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\CompositeField;
use SilverShop\Discounts\Model\Discount;
use SilverStripe\View\Requirements;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Security;
use SilverShop\Model\Address;
use SilverStripe\ORM\ValidationException;
use SilverStripe\SiteConfig\SiteConfig;
use SilverShop\Checkout\Component\CheckoutComponent;
use SilverStripe\Core\Config\Configurable;



class CombinedDetails extends CheckoutComponent{
    
    use Configurable;
	
	private static $show_coupon = true;

	protected $requiredfields = array(
		'FirstName','Surname','Email'
	);
	
	public function getComponent($componentClass) {
		if (class_exists($componentClass)) {
			return new $componentClass;
		} else {
			return null;
		}
	}

	public function getFormFields(Order $order) {
		$detailsComponet = new CustomerDetails();
		$shippingAddressComponent = new AddressBookShipping();
		$billingAddressComponent = new AddressBookBilling();
		if ($this->config()->get('show_coupon')) {
			$couponComponent = $this->getComponent('\SilverShop\Discounts\Checkout\CouponCheckoutComponent');
		}
		$shippingFields = $this->renameAddressFields($shippingAddressComponent->getFormFields($order), 'Shipping');
		
		$shippingFields->push(new CheckboxField(
			"SeparateBillingAddress", "Bill to a different address"
		));
		
		$fields = new FieldList();
		
		if ($this->config()->get('show_coupon') && $couponComponent) {
			$fields->push(CompositeField::create($couponComponent->getFormFields($order))->setTag('fieldset')->setLegend('Discount'));
		}
		
		$fields->push(CompositeField::create($detailsComponet->getFormFields($order))->setTag('fieldset')->setLegend('Contact Details'));
		$fields->push(CompositeField::create($shippingFields)->setTag('fieldset')->setLegend(_t("Address.ShippingAddress", "Shipping Address")));
		$fields->push(CompositeField::create($this->renameAddressFields($billingAddressComponent->getFormFields($order), 'Billing'))->setTag('fieldset')->setLegend(_t("Address.BillingAddress", "Billing Address")));
		
		//ensure country is restricted if there is only one allowed country
		//if($country = SiteConfig::current_site_config()->getSingleCountry()){
			//$fields->push(HiddenField::create('Shipping[Country]', 'Country', $country));
			//$fields->fieldByName('Shipping[Country_readonly]')->setValue($country);
			//$fields->push(HiddenField::create('Billing[Country]', 'Country', $country));
			//$fields->fieldByName('Billing[Country_readonly]')->setValue($country);F
		//}
		
		Requirements::clear('shop/javascript/CheckoutPage.js');
		return $fields;
	}
	function renameAddressFields($fields, $prefix) {
		foreach ($fields as $field) {
			$field->setName($prefix.'['.$field->getName().']');
			if (is_a($field, CompositeField::class)) {
				$field->setChildren($this->renameAddressFields($field->getChildren(), $prefix));
			}
		}
		return $fields;
	}
	function renameAddressData($data, $prefix) {
		$newData = array();
		foreach ($data as $k => $v) {
			$newData[$prefix.'['.$k.']'] = $v;
		}
		return $newData;
	}
	function getAddressData($data, $prefix) {
		$newData = array();
		foreach ($data as $k => $v) {
			if (preg_match('/^'.$prefix.'\[[a-z0-9_\-]+\]$/i', $k)) {
				$newData[preg_replace('/^'.$prefix.'\[([a-z0-9_\-]+)\]$/i', "$1", $k)] = $v;
			}
		}
		return $newData;
	}
	
	public function getRequiredFields(Order $order) {
		return $this->requiredfields;
	}
	public function validateData(Order $order, array $data) {
		
		if (self::config()->get('show_coupon') && !empty($data['Code'])) {
			$couponComponent = $this->getComponent('\SilverShop\Discounts\Checkout\CouponCheckoutComponent\CouponCheckoutComponent');
			if ($couponComponent) $couponComponent->validateData($order, $data);
		}
		
		$detailsComponet = new CustomerDetails();
		$detailsComponet->validateData($order, $data);
				
		$result = new ValidationResult();
		$this->ValidateAddress($order, $data, $result, 'Shipping');
		if ($data['SeparateBillingAddress']) {
			$this->ValidateAddress($order, $data, $result, 'Billing');
		}
		
	}
	
	function ValidateAddress($order, $data, $result, $prefix) {
		// Validate Shipping
		$existingID = !empty($data[$prefix.'['.$prefix.'AddressID]']) ? (int)$data[$prefix.'['.$prefix.'AddressID]'] : 0;
		
		$hasErrors = false;

		if ($existingID) {
			// If existing address selected, check that it exists in $member->AddressBook
			if (!($member = Security::getCurrentUser()) || !$member->AddressBook()->byID($existingID)) {
				$result->error("Invalid address supplied", $prefix.'['.$prefix.'AddressID]');
				$hasErrors = true;
			}
		} else {
			// Otherwise, require the normal address fields
			$required = $order->{$prefix.'Address'}()->getRequiredFields();
			foreach ($required as $fieldName) {
				if (empty($data[$prefix.'['.$fieldName.']'])) {
					$errorMessage = _t(
						'Form.FIELDISREQUIRED',
						'{name} is required',
						array('name' => $fieldName)
					);

					$result->addError($errorMessage, $prefix.'['.$fieldName.']');
					$hasErrors = true;
				}
			}
		}
		if ($hasErrors) throw new ValidationException($result);
	}

	public function getData(Order $order) {
		$detailsComponet = new CustomerDetails();
		$shippingAddressComponent = new AddressBookShipping();
		$billingAddressComponent = new AddressBookBilling();
		if (self::config()->get('show_coupon')) {
			$couponComponent = $this->getComponent('\SilverShop\Discounts\Checkout\CouponCheckoutComponent\CouponCheckoutComponent');
		}
		
		$data = array();
		$data += $detailsComponet->getData($order);
		//ensure country is restricted if there is only one allowed country
		if($country = SiteConfig::current_site_config()->getSingleCountry()){
			$data['Shipping[Country_readonly]'] = $country;
			$data['Shipping[Country]'] = $country;
			$data['Billing[Country_readonly]'] = $country;
			$data['Billing[Country]'] = $country;
		}
		// Only add shipping and billing data if no address book
		$member = Security::getCurrentUser();
		if(!($member && $member->AddressBook()->exists())){
			$data += $this->renameAddressData($shippingAddressComponent->getData($order), 'Shipping');
			$data += $this->renameAddressData($billingAddressComponent->getData($order), 'Billing');
		} else if ($member && !$order->ShippingAddressID && !$member->DefaultShippingAddressID && ($first = $member->AddressBook()->First())) {
			$order->ShippingAddressID = $first->ID;
		}
		$data["Shipping[ShippingAddressID]"] = $order->ShippingAddressID;
		$data["Billing[BillingAddressID]"] = $order->BillingAddressID;
		
		if (self::config()->get('show_coupon') && $couponComponent) {
			$data += $couponComponent->getData($order);
		}
		
		$data["SeparateBillingAddress"] = $order->SeparateBillingAddress;
		
		return $data;
	}

	public function setData(Order $order, array $data) {
		
		$shippingAddressComponent = new AddressBookShipping();
		$billingAddressComponent = new AddressBookBilling();
		if (self::config()->get('show_coupon')) {
			$couponComponent = $this->getComponent('\SilverShop\Discounts\Checkout\CouponCheckoutComponent\CouponCheckoutComponent');
		}
		$orderFields = array(
			'FirstName',
			'Surname',
			'Email'
		); 
		$orderData = array();
		foreach($orderFields as $field) {
			$orderData[$field] = isset($data[$field]) ? $data[$field] : '';
		}
		$orderData['SeparateBillingAddress'] = isset($data['SeparateBillingAddress']) ? $data['SeparateBillingAddress'] : 0;
		$order->update($orderData);
		$order->write();
		
		$shippingAddressComponent->setData($order, $this->getAddressData($data, 'Shipping'));
		if ($data['SeparateBillingAddress']) {
			$billingAddressComponent->setData($order, $this->getAddressData($data, 'Billing'));
		} else {
			//ensure billing address = shipping address, when appropriate
			$order->BillingAddressID = $order->ShippingAddressID;
			$order->write();	
		}
		if (self::config()->get('show_coupon') && $couponComponent) {
			$couponComponent->setData($order, $data);
		}
	}

}
