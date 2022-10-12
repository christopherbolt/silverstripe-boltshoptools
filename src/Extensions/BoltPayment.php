<?php

namespace ChristopherBolt\BoltShopTools\Extensions;


use SilverStripe\Omnipay\GatewayInfo;
use SilverStripe\Omnipay\Service\ServiceResponse;




use SilverStripe\Forms\DateTimeField;






use SilverStripe\Forms\FieldList;
use SilverShop\Extension\ShopConfigExtension;
use SilverShop\Model\Order;
use SilverStripe\Forms\MoneyField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Control\Controller;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Core\Config\Config;
use SilverStripe\View\SSViewer;
use SilverStripe\ORM\DataExtension;



// Allows payments to be edited in the CMS

class BoltPayment extends DataExtension {
	
	private static $summary_fields = array(
		//'Order.FirstName' => 'FirstName',
		//'Order.Surname' => 'Surname',
		'OrderID' => 'Order ID'
	);
	
	/*private static $searchable_fields = array(
		'Money' => 'Money',
		'Gateway' => 'Gateway',
		'Status' => 'Status',
		'Order.FirstName' => array(
			'title' => 'Customer First Name'
		),
		'Order.Surname' => array(
			'title' => 'Customer Surname'
		),
		'Order.Email' => array(
			'title' => 'Customer Email'
		),
		//'Order.Surname' => 'Order Surname',
		//'Order.Reference' => 'Order Reference',
	);*/

	
	//private static $searchable_fields = array(
	//	'OrderID'
	//);
	
	public function updateCMSFields(FieldList $fields) {
		
		// Set defaults, done here, rather than using populateDefaults so they are only set from the admin.
		if (!$this->owner->exists()) {
			$this->owner->Created = date("Y-m-d H:i:s");  
			$this->owner->Status = 'Captured';
			
			$money = $this->owner->obj('Money');
			$money->setCurrency(ShopConfigExtension::config()->get('base_currency'));
			if (!$this->owner->exists() && isset($GLOBALS['CURRENT_ORDER_ID']) && ($order=Order::get()->byId($GLOBALS['CURRENT_ORDER_ID']))) {
				$money->setAmount($order->TotalOutstanding());
			}
			
			$info = GatewayInfo::getSupportedGateways();
			if (isset($info['Manual'])) {
				$this->owner->setField('Gateway', 'Manual');
			} elseif (isset($info['BankTransfer'])) {
				$this->owner->setField('Gateway', 'BankTransfer');
			}
		}
		
		//exit($this->owner->MoneyCurrency.' '.$this->owner->MoneyAmount);
				
		$fields->insertBefore($created=DateTimeField::create("Created", "Created"), 'MoneyValue');
		//$created->getDateField()->setConfig('showcalendar', 1);
		
		$fields->insertBefore(MoneyField::create("Money", _t("Payment.MONEY", "Money")), 'MoneyValue');
		$fields->insertBefore(DropdownField::create('Gateway', 'Gateway',
			\SilverStripe\Omnipay\GatewayInfo::getSupportedGateways()
		)->setHasEmptyDefault(true), 'GatewayTitle');
		$statusOptions = $this->owner->dbObject('Status')->enumValues();
		// Hide Authorized status
		if ($this->owner->Status != 'Authorized') unset($statusOptions['Authorized']);
		if ($this->owner->Status != 'Created') unset($statusOptions['Created']);
		$fields->insertBefore(DropdownField::create(
				'Status',
				'Status',
				$statusOptions
			)->setDescription('"Captured" is a completed payment.'), 'GatewayTitle');
			
		$fields->insertBefore(DropdownField::create(
				'OrderID',
				'Order',
				Order::get()->map('ID', 'ID')->toArray()
			)->setHasEmptyDefault(true), 'GatewayTitle');
		
		if ($this->owner->exists() && ($order = $this->owner->Order()) && $order->exists()) {
			$orderGrid = new GridField('OrderDetails', 'Order Details', Order::get()->filter('ID', $this->owner->OrderID), GridFieldConfig_RecordEditor::create());
			$fields->insertBefore($orderGrid, 'GatewayTitle');
		}
		
		$fields->removeByName('MoneyValue');
		$fields->removeByName('GatewayTitle');

	}
	
	function onBeforeWrite() {
		$inAdmin = (is_subclass_of(Controller::curr(), LeftAndMain::class)) ? true : false;
		if ($inAdmin && $gateway = Controller::curr()->request->postVar('Gateway')) $this->owner->setField('Gateway', $gateway);
		if (!$this->owner->exists()) $this->SendReceipt = true;	
	}
	
	// on payment if order is paid
	function onAfterWrite() {
		$inAdmin = (is_subclass_of(Controller::curr(), LeftAndMain::class)) ? true : false;
		$order = $this->owner->Order();
		if ($inAdmin && $order && $order->exists() && !$order->Paid && $this->owner->Status == "Captured" && isset($this->SendReceipt) && $this->SendReceipt) {
			$this->SendReceipt = false;
			Config::inst()->update(SSViewer::class, 'theme_enabled', true);
			$this->owner->onCaptured(new ServiceResponse($this->owner));
			// If order not paid then re-set receipt sent so it will be sent again....
			$order = Order::get()->byId($this->owner->OrderID);
			if (!$order->Paid) {
				$order->ReceiptSent = NULL;
				$order->write();
			}
			Config::inst()->update(SSViewer::class, 'theme_enabled', false);
		}
	}
	
	// Add CMS Permissions
	// Should improve this at some point!
	public function canView($member = null) {
		return true;
	}

	public function canEdit($member = null) {
		return true;
	}

	public function canDelete($member = null) {
		return true;
	}
	
	public function canCreate($member = null) {
		return true;
	}
	
}