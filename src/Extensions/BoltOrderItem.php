<?php

namespace ChristopherBolt\BoltShopTools\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Omnipay\PaymentGatewayController;

class BoltOrderItem extends DataExtension
{
	function updateUnitPrice(&$unitprice) {
		/* Fixes a bug where order items values are re-calculated during off-site payment so if price is affected on Member or session data then wrong price is saved to DB */
		$controller = Controller::curr();
		if (is_a($controller, PaymentGatewayController::class)) {
			$unitprice = $this->owner->UnitPrice;
		}
	}
}