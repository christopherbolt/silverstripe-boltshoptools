<?php

namespace ChristopherBolt\BoltShopTools\Extensions;









use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataExtension;
use SilverShop\Checkout\OrderEmailNotifier;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Email\Email;
use SilverStripe\SiteConfig\SiteConfig;
use ChristopherBolt\BoltShopTools\Helpers\Session;


// Makes sure that emails are sent when an order is placed, even if the order has not been fully paid. -- REMOVED, use confirmation email instead
// Add order status log

class BoltOrder extends DataExtension{
	
	/*private static $searchable_fields = array(
        'Reference' => array(),
        'FirstName' => array(
            'title' => 'Customer First Name',
        ),
		'Surname' => array(
            'title' => 'Customer Surname',
        ),
       'Email'     => array(
           'title' => 'Customer Email',
        ),
        'Status'    => array(
            'filter' => 'ExactMatchFilter',
            'field'  => 'CheckboxSetField',
        ),
    );*/
	
	function FullName() {
		return $this->owner->FirstName.' '.$this->owner->Surname;	
	}
	
	/*function onPayment() {
		$this->owner->_bolt_onPaymentCalled = true;
	}
	function onPlaceOrder() {
		$inPaidOrder = isset($this->owner->_bolt_onPaymentCalled) ? $this->owner->_bolt_onPaymentCalled : false;
		if (!$inPaidOrder && !$this->owner->ReceiptSent) {
			$processor = OrderProcessor::create($this->owner);
			$processor->sendReceipt();
		}
	}*/
	
	// Hide Authorized Bank Transferss
	function VisiblePayments() {
		return $this->owner->Payments()->exclude(array(
			//'Gateway' => 'Manual',
			'Status' => array('Authorized','Created')
		));
	}
	
	// Complete payment from admin when an order is marked as paid
	/*function onBeforeWrite() {
		if ($this->TotalOutstanding() > 0 && empty($this->Paid) && in_array($this->owner->Status, $this->owner->config()->payable_status)) {
		//if ($this->owner->canPay()) 
			$processor = OrderProcessor::create($this->owner);
			$processor->completePayment();
		}
	}*/
	
	// Automatically mark bank transfer as paid if this order is paid
	/*function onAfterWrite() {
		parent::onAfterWrite();
		if ($this->owner->Status == 'Paid') {
			$payments = $this->owner->Payments()->filter(array(
				'Gateway' => 'Manual',
				'Status' => 'Authorized'
				));
			if ($payments && $payments->count()) {
				foreach ($payments as $payment) {
					$payment->Status = 'Captured';	
					$payment->write();
				}
			}
		}
	}*/
	
	public function updateCMSFields(FieldList $fields) {
		
		// Remove modifiers tab
		$fields->removeByName('Modifiers');
		
		// Let user know to mark paid using payment log
		//if ($this->owner->canPay()) $fields->dataFieldByName('Status')->setDescription('To mark this order as paid you must first add the payment below.');
		
		// Add payment log
		$payments = $fields->dataFieldByName('Payments');
		if ($payments) { // Omnipay UI is required here!
			//$payments->setConfig(new GridFieldConfig_RecordEditor());
			$config = $payments->getConfig();
			// add back config items removed by PayableUIExtension
			$config->addComponent(new GridFieldAddNewButton('buttons-before-left'));
			$config->addComponent($filter = new GridFieldFilterHeader());
			$config->addComponent(new GridFieldDeleteAction());
			$config->addComponent(new GridFieldPageCount('toolbar-header-right'));

			$payments->setList($this->owner->VisiblePayments());
			
			$payments->setTitle('Completed Payments');
		}
		$GLOBALS['CURRENT_ORDER_ID'] = $this->owner->ID;
		
		// Add order status log
		/*if ($this->owner->config()->get('show_order_status_log')) {
			$fields->addFieldToTab("Root.Main", new GridField('OrderStatusLogs', 'Order Status Log', $this->owner->OrderStatusLogs(), GridFieldConfig_RecordEditor::create()
				//->addComponent(new GridFieldOrderableRows('SortOrder'))
			));
		}*/
		
	}
	/*
	// This function seems to be missing?
	public function sendStatusChange($title, $note) {
		//SSViewer::set_theme('mytheme');
		$processor = OrderProcessor::create($this->owner);
		$processor->sendStatusChange($title, $note);
		
        $notifier = OrderEmailNotifier::create($this->owner);
	}
	*/
    
    
    // Called when order is placed (but not necisarily paid for)
    function onPlaceOrder() {
        // Update stock level (stock will be returned when order cancelled)
        foreach ($this->owner->Items() as $item) {
            if ($item->hasMethod('subtractStockLevel')) $item->subtractStockLevel();
        }
        
        // Send additional order notifications
        $notifier = OrderEmailNotifier::create($this->owner);
        $emails = explode(',', SiteConfig::current_site_config()->AdditionalOrderEmailTo);
        $oldAdmin = Email::config()->admin_email;
        foreach ($emails as $email) {
            $email = trim($email);
            if ($email) {
                Config::inst()->update(Email::class, 'admin_email', $email);
                $notifier->sendAdminNotification();
            }
        }
        Config::inst()->update(Email::class, 'admin_email', $oldAdmin);
        
        // Clear coupon codes
        Session::clear('cart.couponcode');
    }
    
    // Called when order status changes
    function onStatusChange($fromStatus, $toStatus) {
        // Return stock when cancelled
        if (($fromStatus != 'MemberCancelled' && $fromStatus != 'AdminCancelled') && ($toStatus == 'MemberCancelled' || $toStatus == 'AdminCancelled')) {
            foreach ($this->owner->Items() as $item) {
                if ($item->hasMethod('returnStockLevel')) $item->returnStockLevel();
            }
        }
    }
}
