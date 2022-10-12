<?php
namespace ChristopherBolt\BoltShopTools\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverShop\Discounts\Model\Discount;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DB;
use SilverShop\Model\Variation\OrderItem;
use SilverStripe\ORM\ValidationResult;
use ChristopherBolt\BoltShopTools\Helpers\Session;

// Product_OrderItem
class BoltProductOrderItem extends DataExtension{
	/*
	private static $searchable_fields = array(
		
		'ProductID' => array(
			'title' => 'Product',
			//'field' => 'TextField'
		),
		
		//"UnitPrice",
		//"Quantity",
		
		'Order.Reference' => array(
			'title' => 'Order Reference',
			'field' => 'TextField'
		),
		'Order.FirstName' => array(
			'title' => 'Order Customer First Name',
		),
		'Order.Surname' => array(
			'title' => 'Order Customer Surname',
		),
		'Order.Email' => array(
			'title' => 'Order Customer Email',
		),
		'Order.Status' => array(
			'title' => 'Order Status',
			'filter' => 'ExactMatchFilter',
			'field' => 'DropdownField'
		),
		
		
		//"Title" => "PartialMatchFilter",// Chris Bolt, functions cannot be searched
		//"TableTitle" => "PartialMatchFilter",// Chris Bolt, commented these out functions cannot be searched
		//"CartTitle" => "PartialMatchFilter",// Chris Bolt, commented these out functions cannot be searched
		
		//"Total"// Chris Bolt, functions cannot be searched
	);*/
	
	private static $summary_fields = array(
		"Order.Reference" => "Order",
		"Order.Placed" => "Placed",
		"CustomerName" => "Customer",
		"Order.Email" => "Email",
		"TitleAndSubTitle" => "Product",
		//"SubTitle" => "Attributes",
		"UnitPrice" => "Unit Price" ,
		"Quantity" => "Quantity" ,
		"Total" => "Total" ,
		"Order.Status" => "Status"
	);
	
	public function updateCMSFields(FieldList $fields) {
		/*
		//exit($this->owner->OrderID);
		if ($this->owner->exists() && $this->owner->OrderID) {
			//$orderGrid = new GridField('OrderDetails', 'Order Details', Order::get()->filter('ID', $this->owner->OrderID), GridFieldConfig_RecordEditor::create());
			//$fields->insertAfter($orderGrid, 'OrderID');
			$fields->insertAfter(LiteralField::create('OrderDetails', '<p><a href="/admin/orders/Order/EditForm/field/Order/item/'.$this->owner->OrderID.'/edit">View Full Order</a>'), 'OrderID');
		}
		*/
		$fields->removeByName('ProductVersion');
		$fields->removeByName('ProductVariationVersion');
		$fields->removeByName(Discount::class);
	}
	
	// Add CMS Permissions
	// Should improve this at some point!
	/*public function canView($member = null) {
		return true;
	}

	public function canEdit($member = null) {
		return false;
	}

	public function canDelete($member = null) {
		return false;
	}
	
	public function canCreate($member = null) {
		return false;
	}*/
	
	function TitleAndSubTitle() {
		return DBField::create_field('Text', strip_tags(str_replace(array('<br>','<br/>','<br/>'), "\n", $this->owner->TableTitle().'
'.(method_exists($this->owner, 'SubTitle') ? $this->owner->SubTitle() : (property_exists($this->owner, 'SubTitle') ? $this->owner->SubTitle : '' )))));
		
	}
	
	//private static $field_labels = array(
	//	'OrderID' => 'Order Reference'
	//);
	
	/**
	 * Gets the name of the customer.
	 */
	public function getCustomerName() {
		$firstname = $this->owner->Order()->FirstName ? $this->owner->Order()->FirstName : $this->owner->Order()->Member()->FirstName;
		$surname = $this->owner->Order()->FirstName ? $this->owner->Order()->Surname : $this->owner->Order()->Member()->Surname;
		return implode(" ", array_filter(array($firstname, $surname)));
	}
	
	// this is called when the order has received payment
	function onPayment() {
		
	}
    
    // Removes the stock, called when order is placed
    function subtractStockLevel() {
        //parent::onPayment(); // is this required???????
		$product = $this->owner->Product();
		if ($product->ManageStock) {
			
			// For reasons unknown to me the stock level is often wrong so lets make sure its same as the DB
			//$table_suffix = (Versioned::current_stage() == 'Live') ? '_Live' : '';
			$product->StockLevel = DB::query('SELECT StockLevel FROM SilverShop_Product_Live WHERE ID='.$product->ID)->value();
			// End stock hack
			
			$stockLevel = $product->StockLevel;
			$stockLevel -= $this->owner->Quantity;			
			$product->StockLevel = $stockLevel;
			//$product->doRestoreToStage();
			DB::query('UPDATE SilverShop_Product SET StockLevel='.$stockLevel.' WHERE ID='.$product->ID);
        	DB::query('UPDATE SilverShop_Product_Live SET StockLevel='.$stockLevel.' WHERE ID='.$product->ID);
						
		} else if ($this->owner instanceof OrderItem) {
			$productVariation = $this->owner->ProductVariation();
			if ($productVariation->ManageStock) {
				$stockLevel = $productVariation->StockLevel;
				$stockLevel -= $this->owner->Quantity;
				$productVariation->StockLevel = $stockLevel;
				$productVariation->write();
				//$productVariation->doRestoreToStage();
			}
		}
		if ($product->IsSoldOut()) {
			$product->SoldOut = 1;
			DB::query('UPDATE SilverShop_Product SET SoldOut=1 WHERE ID='.$product->ID);
        	DB::query('UPDATE SilverShop_Product_Live SET SoldOut=1 WHERE ID='.$product->ID);
		}
    }
    
    // Returns the stock level back, todo: call when order is cancelled
    function returnStockLevel() {
        //parent::onPayment(); // is this required???????
		$product = $this->owner->Product();
		if ($product->ManageStock) {
			
			// For reasons unknown to me the stock level is often wrong so lets make sure its same as the DB
			//$table_suffix = (Versioned::current_stage() == 'Live') ? '_Live' : '';
			$product->StockLevel = DB::query('SELECT StockLevel FROM SilverShop_Product_Live WHERE ID='.$product->ID)->value();
			// End stock hack
			
			$stockLevel = $product->StockLevel;
			$stockLevel += $this->owner->Quantity;			
			$product->StockLevel = $stockLevel;
			//$product->doRestoreToStage();
			DB::query('UPDATE SilverShop_Product SET StockLevel='.$stockLevel.' WHERE ID='.$product->ID);
        	DB::query('UPDATE SilverShop_Product_Live SET StockLevel='.$stockLevel.' WHERE ID='.$product->ID);
						
		} else if ($this->owner instanceof OrderItem) {
			$productVariation = $this->owner->ProductVariation();
			if ($productVariation->ManageStock) {
				$stockLevel = $productVariation->StockLevel;
				$stockLevel += $this->owner->Quantity;
				$productVariation->StockLevel = $stockLevel;
				$productVariation->write();
				//$productVariation->doRestoreToStage();
			}
		}
		if (!$product->IsSoldOut()) {
			$product->SoldOut = 0;
			DB::query('UPDATE SilverShop_Product SET SoldOut=0 WHERE ID='.$product->ID);
        	DB::query('UPDATE SilverShop_Product_Live SET SoldOut=0 WHERE ID='.$product->ID);
		}
    }
	
	public function validate(ValidationResult $result) {
		//$result = $this->owner->validate();
		$quantity = $this->owner->Quantity;
		$product = $this->owner->Product();
		/*
		// Stock management
		if ($product->ManageStock) {
			if ($product->StockLevel-$quantity < 0) {
				$result->addError($product->Title.' is not available in this quantity. Only '.$product->StockLevel.' item(s) remain.');
			}
		} else if ($this->owner instanceof OrderItem) {
			$productVariation = $this->owner->ProductVariation();
			if ($productVariation->ManageStock) {
				if ($productVariation->StockLevel-$quantity < 0) {
					$result->addError($product->Title.': '.$this->owner->SubTitle().' is not available in this quantity. Only '.$productVariation->StockLevel.' item(s) remain.');
				}
			}
		}
		*/
		return $result;
	}
	
	
	function onBeforeWrite() {
		parent::onBeforeWrite();
		// Stock management
		$product = $this->owner->Product();
		if ($product && $product->ManageStock) {
			if ($product->StockLevel-$this->owner->Quantity < 0) {
				$error = '"'.$product->Title.'" is not available in this quantity. Only '.$product->StockLevel.' item'.($product->StockLevel>1?'s':'').' remain'.($product->StockLevel<1?'s':'').'. Your quantity has been adjusted.';
				$this->owner->Quantity = $product->StockLevel;
				Session::set("FormInfo.StockManagement.formError.message", $error);
				Session::set("FormInfo.StockManagement.formError.type", 'bad');
			}
		} else if ($this->owner instanceof OrderItem) {
			$productVariation = $this->owner->ProductVariation();
			if ($productVariation->ManageStock) {
				if ($productVariation->StockLevel-$this->owner->Quantity < 0) {
					$error = '"'.$product->Title.': '.$this->owner->SubTitle().'" is not available in this quantity. Only '.$productVariation->StockLevel.' item'.($productVariation->StockLevel>1?'s':'').' remain'.($productVariation->StockLevel<1?'s':'').'. Your quantity has been adjusted.';
					$this->owner->Quantity = $productVariation->StockLevel;
					Session::set("FormInfo.StockManagement.formError.message", $error);
					Session::set("FormInfo.StockManagement.formError.type", 'bad');
				}
			}
		}
	}
}