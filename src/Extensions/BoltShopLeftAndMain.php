<?php

namespace ChristopherBolt\BoltShopTools\Extensions;


use SilverStripe\Admin\LeftAndMainExtension;


/**
 * Plug-ins for additional functionality in your LeftAndMain classes.
 * 
 * @package framework
 * @subpackage admin
 */
class BoltShopLeftAndMain extends LeftAndMainExtension {

	public function init() {
		parent::init();
		//Requirements::javascript(BOLTSHOPTOOLS_DIR.'/thirdparty/livequery/jquery.livequery.js');
		//Requirements::javascript(BOLTSHOPTOOLS_DIR.'/javascript/leftandmain.js');
	}

}
