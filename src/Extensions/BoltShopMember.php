<?php

namespace ChristopherBolt\BoltShopTools\Extensions;








use SilverShop\Model\Order;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Omnipay\Model\Payment;
use SilverStripe\ORM\DataExtension;



// Adds order and payment history to a member

class BoltShopMember extends DataExtension {
	private static $has_many = array(
		'ShopOrders' => Order::class,
		//'Payments' => 'Payment'
	);
	
	function updateCMSFields(FieldList $fields) {
		if ($this->owner->exists()) {
			$ordersGrid = $fields->dataFieldByName('ShopOrders');
			$ordersGrid->setList($ordersGrid->getList()->exclude(array('Status' => Config::inst()->get(Order::class, 'hidden_status'))));
			$config = $ordersGrid->getConfig();
			$config->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
			$config->removeComponentsByType(GridFieldDeleteAction::class);
			
            if ($payments = $this->owner->Payments()) {
                $fields->insertAfter(new Tab('Payments', 'Payments'), 'ShopOrders');
                $paymentsGrid = new GridField('Payments', 'Payments', $payments, GridFieldConfig_RecordEditor::create());
                $fields->addFieldToTab('Root.Payments', $paymentsGrid);
            }
		}
	}
	
	function Payments() {
		$orders = $this->owner->ShopOrders()->map('ID', 'ID')->toArray();
        if (count($orders)) {
            return Payment::get()->filter(array('OrderID' => $orders))->exclude(array('Status' => array('Authorized','Created')));
        }
	}
}