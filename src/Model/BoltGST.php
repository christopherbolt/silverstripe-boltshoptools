<?php

namespace ChristopherBolt\BoltShopTools\Model;



use SilverShop\Model\Order;
use SilverShop\Model\Modifiers\Tax\Base;



/**
 * Base class for creating tax modifiers with.
 */
class BoltGST extends Base {

	private static $db = array(
		'Country' => 'Varchar'
	);

	private static $name = "GST";
	private static $table_title = null;
	private static $rate = 0.15;
	private static $exclusive = false;
	private static $includedmessage = "%.1f%% %s (inclusive)";
	private static $excludedmessage = "%.1f%% %s";
	private static $defaults = array(
		'Type' => 'Ignored'
	);

    private static $table_name = 'BoltGST';

	private static $applicable_countries = array('NZ');

	public function populateDefaults(){
		parent::populateDefaults();
		$this->Type = self::config()->exclusive ? 'Chargable' : 'Ignored';
	}

	/**
	 * Get the tax amount to charge on the order.
	 */
	public function value($incoming) {
		if (is_array(self::config()->get('applicable_countries')) && !in_array($this->Country(), self::config()->get('applicable_countries'))) return 0; // No tax outside of NZ

		$this->Rate = self::config()->rate;
		$incoming -= $this->TaxExemptValue();
		//inclusive tax requires a different calculation
		return self::config()->exclusive ?
				$incoming * $this->Rate :
				$incoming - round($incoming/(1+$this->Rate), Order::config()->rounding_precision);
	}

	public function TableTitle(){
		return self::config()->get('table_title') ? self::config()->get('table_title') : ($this->Type == 'Chargable' ? self::config()->get('name') : "Includes ".self::config()->get('name'));
	}

	// loops through the order and calculates a value to subtract based upon tax exempt products.
	public function TaxExemptValue(){
		$tax_subtract = 0;
		$order = $this->Order();
		if($order && $orderItems = $order->Items()) {
			foreach($orderItems as $orderItem){
				if($product = $orderItem->Buyable()){
					if (isset($product->TaxExempt) && $product->TaxExempt) {
						$tax_subtract += $orderItem->Total();
					}
				}
			}
		}
		return $tax_subtract;
	}

	public function Country(){
		/*if($order = $this->Order()){
			return ($order->UseShippingAddress && $order->ShippingCountry) ?
				$order->ShippingCountry :
				$order->Country;
		}
		return null;*/
		if(($order = $this->Order()) && ($address = $order->ShippingAddress()) && ($country = $address->Country)) {
			return $country;
		} else if (is_array(self::config()->get('applicable_countries'))) {
			return self::config()->get('applicable_countries')[0];
		}
		return null;
	}

}
