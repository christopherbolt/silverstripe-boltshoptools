<?php

namespace ChristopherBolt\BoltShopTools\Extensions;





use SilverShop\Page\ProductCategory;
use ChristopherBolt\BoltShopTools\Helpers\Session;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\DataExtension;



// Adds various generic functionality to Page 

class BoltShopPage extends DataExtension {
	
	/**
	 * Loop down each level of children to get all ids.
	 */
	public function AllChildCategoryIDs(){
		$ids = array($this->owner->ID);
		$allids = array();
		do{
			$ids = ProductCategory::get()
				->filter('ParentID', $ids)
				->getIDList();
			$allids += $ids;
		}while(!empty($ids));

		return $allids;
	}

	/**
	 * Return children ProductCategory pages of this category.
	 * @param bool $recursive
	 * @return DataList
	 */
	public function ChildCategories($recursive = false) {
		$ids = array($this->owner->ID);
		if($recursive){
			$ids += $this->owner->AllChildCategoryIDs();
		}

		return ProductCategory::get()->filter("ParentID", $ids);
	}
	
	function AddToCartSessionMessage() {
		$FormName = '';
		
		$message = Session::get("FormInfo.VariationForm_Form.formError.message") ? Session::get("FormInfo.VariationForm_Form.formError.message") : Session::get("FormInfo.AddProductForm_Form.formError.message");
		$type = Session::get("FormInfo.VariationForm_Form.formError.type") ? Session::get("FormInfo.VariationForm_Form.formError.type") : Session::get("FormInfo.AddProductForm_Form.formError.type");

		// Clear the messages
		Session::clear("FormInfo.VariationForm_Form.errors");
		Session::clear("FormInfo.VariationForm_Form.formError");
		Session::clear("FormInfo.AddProductForm_Form.errors");
		Session::clear("FormInfo.AddProductForm_Form.formError");
		
		if ($message) {
			return '<p class="message '.$type.'">'.$message.'</p>';	
		}
	}
	
	function StockManagementMessage() {
		$return = new ArrayData(array(
			'Message' => Session::get("FormInfo.StockManagement.formError.message"),
			'MessageType'=>Session::get("FormInfo.StockManagement.formError.type")
		));
		//exit(Session::get("FormInfo.CartForm_CartForm.formError.message"));
		// Clear
		//Session::clear("FormInfo.CartForm_CartForm.errors");
		Session::clear("FormInfo.StockManagement.formError");
		//Session::clear("FormInfo.CartForm.data");
		return $return;
	}
	
	public function CartFormMessage() {
		$return = new ArrayData(array(
			'Message' => Session::get("FormInfo.CartForm_CartForm.formError.message"),
			'MessageType'=>Session::get("FormInfo.CartForm_CartForm.formError.type")
		));
		//exit(Session::get("FormInfo.CartForm_CartForm.formError.message"));
		// Clear
		//Session::clear("FormInfo.CartForm_CartForm.errors");
		Session::clear("FormInfo.CartForm_CartForm.formError");
		//Session::clear("FormInfo.CartForm.data");
		return $return;
	}
	
	function InAdminLogin () {
		$back = isset($_GET['BackURL']) ? $_GET['BackURL'] : '';
		$matches = array('/admin/', '/invoice/', '/order/', '/dev/');
		foreach ($matches as $match) {
			if (strstr($back, $match)) return true;
			if (strstr($_SERVER['REQUEST_URI'], $match)) return true;
		}
	}
}