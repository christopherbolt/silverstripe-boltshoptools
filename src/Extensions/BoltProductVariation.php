<?php

namespace ChristopherBolt\BoltShopTools\Extensions;






use UncleCheese\DisplayLogic\Forms\Wrapper as DisplayLogicWrapper;


use SilverStripe\Forms\FieldList;
use SilverStripe\Assets\Image;
use SilverShop\Page\Product;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataExtension;



// Adds stock management
// Sets default folder for image

class BoltProductVariation extends DataExtension{
	
	private static $db = array(
		'ManageStock' => 'Boolean',
		'StockLevel' => 'Int',
	);
	
	// Summary fields
  	//private static $summary_fields = array(
	//	'Image.CMSThumbnail',
	//	'InternalItemID' => 'Product Code',
		//'Product.Title' => 'Product',
	//	'Title' => 'Variation',
	//	'Price' => 'Price'
  	//);
	
	//private static $field_labels = array(
	//	'Image.CMSThumbnail' => 'Image',
	//);

	function updateCMSFields(FieldList $fields) {
		if ($fields->dataFieldByName('Image')) $fields->dataFieldByName('Image')->setFolderName('Products');	
		
		// Add product attribute AddNewDropDown
		$attributes = $this->owner->Product()->VariationAttributeTypes();
		$d = true;
		if(!$attributes->exists() && defined('IN_PRODUCT_ID')){
			$d = false;
			$attributes = Product::get()->byId(IN_PRODUCT_ID)->VariationAttributeTypes();
		}
		if($attributes->exists()){
            
			foreach($attributes as $attribute){
				
				$remove = false;
				if($attribute->getDropDownField() && $d){
					$method = 'replace';
					$replace = 'ProductAttributes['.$attribute->ID.']';
				} elseif (!$d) {
					$method = 'insert';
					$replace = 'savefirst';
					$remove = false;
				}else{
					$method = 'insert';
					$replace = 'novalues'.$attribute->Name;
					$remove = true;
				}
				
				$field = $attribute->getAdminDropDownField()->setShowEditButton(true);
				if($value = $this->owner->AttributeValues()->find('TypeID', $attribute->ID)){
					$field->setValue($value->ID);
				}
				if ($method == 'replace') {
					$fields->replaceField($replace, $field);
				} else {
					$fields->insertBefore($field, $replace);
					if ($remove) $fields->removeByName($replace);
				}
				
			}
			$fields->removeByName('savefirst');
		}
		
		// Add stock management options
		if (defined('IN_PRODUCT_ID')) {
			$product = Product::get()->byId(IN_PRODUCT_ID);
		} else if ($this->owner->exists()) {
			$product = $this->owner->Product();
		}
        if ($product::config()->show_stock_options) {
            if ($product->ManageStock) {
                $fields->push(LiteralField::create('variationspriceinstructinos','<div class="message">
                        Stock - Stock levels for this product are currently controlled as a single stock level accross all variations. To specify individual stock levels for each variation untick the Manage Stock option for this product.</div>'
                ));
            } else {
                $fields->push(CheckboxField::create('ManageStock', 'Manage stock'));
                $fields->push(DisplayLogicWrapper::create(
                    NumericField::create('StockLevel')
                   ->setDescription('When the stock level reaches 0 this item will no-longer be available for purchase.')
                )->displayIf('ManageStock')->isChecked()->end());
            }
        }
        
        # Shipping??
		if ($product::config()->hide_shipping) {
			$fields->removebyName('Weight');
			$fields->removebyName('Height');
			$fields->removebyName('Width');
			$fields->removebyName('Depth');
		} else if ($product::config()->show_free_shipping_instructions) {
        	$fields->insertBefore("Weight", new HeaderField('freeshippingheader','Shipping', 2), 'Weight');
			$fields->insertBefore("Weight", new LiteralField('freeshippingmessage','<div class="message">Enter all zero values for free shipping. A package with no weight or volume ships for free.</div>'), 'Weight');
		}
	}
	
	function canPurchase($member = null, $quantity = 1) {
		// Stock management
		if ($this->owner->ManageStock) {
			//if ($this->owner->StockLevel-$quantity < 0) {
			if ($this->owner->StockLevel < 1) { // Quantities greater than one now handled by shopping cart
				return false; // no stock!
			}
		}
		return null;
	}
	
	function onAfterWrite() {
		$product = $this->owner->Product();
		$check = $product->IsSoldOut();
		if ($check != $product->SoldOut) {
			DB::query('UPDATE SilverShop_Product SET SoldOut='.($check?'1':'0').' WHERE ID='.$product->ID);
        	DB::query('UPDATE SilverShop_Product_Live SET SoldOut='.($check?'1':'0').' WHERE ID='.$product->ID);
			//$product->SoldOut = $check;
			//$product->write();
		}
	}
	
	// Duplicate 
	function onAfterDuplicate($page) {
		if ($this->owner->ID < 1) {
			// Can only add relations if this exists.
			return;
		}
		// Has many relations to duplicate...
		$relations = array('SpecificPrices');
		$this->owner->_inDuplication = true;
		foreach ($relations as $relation) {
			foreach($page->$relation() as $item) {
				$new = $item->duplicate();
				$this->owner->$relation()->add($new);
			}
		}
		unset($this->owner->_inDuplication);
	}
	
}