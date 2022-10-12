<?php
namespace ChristopherBolt\BoltShopTools\Extensions;

use SilverStripe\ORM\DataExtension;
use ChristopherBolt\BoltShopTools\Helpers\Session;

class BoltShoppingCart extends DataExtension{
    
    public function beforeAdd(&$buyable, &$quantity, &$filter) {
        if ($buyable->ManageStock) {
            if ($buyable->StockLevel-$quantity < 0) {
                $error = '"'.$buyable->Title.'" is not available in this quantity. Only '.$buyable->StockLevel.' item'.($buyable->StockLevel>1?'s':'').' remain'.($buyable->StockLevel<1?'s':'').'. Your quantity has been adjusted.';
                $quantity = $buyable->StockLevel;
                Session::set("FormInfo.StockManagement.formError.message", $error);
                Session::set("FormInfo.StockManagement.formError.type", 'bad');
            }
        }
    }
    
}