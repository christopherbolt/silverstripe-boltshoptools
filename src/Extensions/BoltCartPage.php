<?php

namespace ChristopherBolt\BoltShopTools\Extensions;



use SilverShop\Page\CartPage;
use SilverStripe\ORM\DataExtension;



class BoltCartPage extends DataExtension {
	public function canCreate($member = null, $context = array())
    {
		return !CartPage::get()->exists();
    }
}