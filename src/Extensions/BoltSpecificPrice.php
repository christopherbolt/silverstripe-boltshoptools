<?php

namespace ChristopherBolt\BoltShopTools\Extensions;



use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;



// Used to hide the percentage price option since this is incompatible with other changes we have made, hopefully will get time to fix this later

class BoltSpecificPrice extends DataExtension {
	function updateCMSFields(FieldList $fields) {
		$fields->removeByName("DiscountPercent");
	}
}