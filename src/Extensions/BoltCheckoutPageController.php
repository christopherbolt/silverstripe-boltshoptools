<?php

namespace ChristopherBolt\BoltShopTools\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\HiddenField;
use SilverShop\Page\CheckoutPage;

class BoltCheckoutPageController extends Extension {
	
	function updateLoginForm($form) {
		$fields = $form->Fields();
		if ($back = $fields->dataFieldByName('BackURL')) {
			$back->setValue($this->owner->Link());
		} else {
			$fields->push(HiddenField::create('BackURL')->setValue($this->owner->Link()));
		}
		$this->owner->getRequest()->getSession()->set("BackURL", $this->owner->Link());
	}
	
	function updateCreateAccountForm($form) {
		$fields = $form->Fields();
		if ($back = $fields->dataFieldByName('BackURL')) {
			$back->setValue($this->owner->Link());
		} else {
			$fields->push(HiddenField::create('BackURL')->setValue($this->owner->Link()));
		}
		$this->owner->getRequest()->getSession()->set("BackURL", $this->owner->Link());
	}
}