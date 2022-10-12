<?php

namespace ChristopherBolt\BoltShopTools\Extensions;



use SilverShop\Page\CheckoutPage;
use SilverStripe\ORM\DataExtension;



class BoltCheckoutPage extends DataExtension {
	public function canCreate($member = null, $context = array())
    {
		return !CheckoutPage::get()->exists();
    }	
}