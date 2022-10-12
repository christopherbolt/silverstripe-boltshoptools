<?php

namespace ChristopherBolt\BoltShopTools\Extensions;

use SilverStripe\ORM\DataExtension;

class BoltShopGiftVoucherOrderItem extends DataExtension
{	
    function updateCreateCupon(&$coupon) {
		$this->updateCreateCoupon($coupon);
	}
	function updateCreateCoupon(&$coupon) {
        // Fix defaults which wrongly apply the voucher to each item in the cart rather than the order total
		$coupon->For = 'Order';
		$coupon->MinOrderValue = null;
	}
}
