<?php

namespace ChristopherBolt\BoltShopTools\Reports;

use SilverShop\Model\Order;
use SilverShop\Model\Modifiers\Tax\Base;
use SilverShop\Reports\ShopPeriodReport;

class TaxReport extends ShopPeriodReport
{
    protected $title = 'GST';

    protected $description = 'Report tax charged on orders. Only includes orders that have been paid.';

    protected $dataClass = Order::class;

    protected $periodfield = '"SilverShop_Order"."Paid"';

    protected $grouping = true;

    public function columns()
    {
        $period = isset($_GET['filters']['Grouping']) ? $_GET['filters']['Grouping'] : 'Month';
        return array(
            'FilterPeriod' => $period,
            'Count' => 'Order Count',
            'Sales' => 'Total Sales',
            'Tax' => 'Total Tax',
        );
    }

    public function query($params)
    {
		return parent::query($params)
            ->addInnerJoin(
                'SilverShop_OrderAttribute',
                '"SilverShop_OrderAttribute"."OrderID" = "SilverShop_Order"."ID" AND ("SilverShop_OrderAttribute"."ClassName" LIKE \'%Tax%\' OR "SilverShop_OrderAttribute"."ClassName" LIKE \'%GST%\')'
            )
            ->addInnerJoin('SilverShop_OrderModifier', '"SilverShop_OrderModifier"."ID" = "SilverShop_OrderAttribute"."ID"')
            ->selectField('COUNT("SilverShop_Order"."ID")', 'Count')
            ->selectField('SUM("SilverShop_OrderModifier"."Amount")', 'Tax')
            ->selectField('SUM("SilverShop_Order"."Total")', 'Sales');
    }
}
