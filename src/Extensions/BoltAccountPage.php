<?php

namespace ChristopherBolt\BoltShopTools\Extensions;



use ChristopherBolt\BoltShopTools\Helpers\Session;
use SilverStripe\Core\Extension;



class BoltAccountPage extends Extension {
	private static $allowed_actions = array(
        'login', // redirects to editprofile
    );
	
	 public function login()
    {
        Session::set(
			"FormInfo.ShopAccountForm_EditAccountForm.formError.message",
			'Your password has been changed.'
		);
		Session::set("FormInfo.ShopAccountForm_EditAccountForm.formError.type", 'good');
		
		Session::set(
			"FormInfo.ChangePasswordForm_ChangePasswordForm.formError.message",
			'Your password has been changed.'
		);
		Session::set("FormInfo.ChangePasswordForm_ChangePasswordForm.formError.type", 'good');
		
		$this->owner->redirect($this->owner->Link('editprofile'));
    }
}