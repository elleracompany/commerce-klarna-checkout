<?php

namespace ellera\commerce\klarna\models;

use craft\base\Model;
use craft\commerce\base\ShippingMethodInterface;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use ellera\commerce\klarna\helpers\TaxExtractor;

class KlarnaOrderLine extends Model
{
	/**
	 * Product Name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Order Line Quantity
	 *
	 * @var integer
	 */
	public $quantity;

    /**
     * Order Line Product ID
     *
     * @var integer
     */
    public $product_id;

	/**
	 * Product Unit Price in fractional denomination
	 *
	 * @var integer
	 */
	public $unit_price;

	/**
	 * Unit Tax Rate in percentage*100
	 *
	 * @var integer
	 */
	public $tax_rate;

	/**
	 * Total Line Price in fractional denomination
	 *
	 * @var integer
	 */
	public $total_amount;

	/**
	 * Total Tax Amount in ProductProduct
	 *
	 * @var integer
	 */
	public $total_tax_amount;

	/**
	 * Product URL
	 *
	 * @var string
	 */
	public $product_url;

	/**
	 * Populate the model based on order line
	 *
	 * @param LineItem $line
	 */
	public function populate(LineItem $line)
	{
		$tax = new TaxExtractor($line);
		$this->name = $line->purchasable->title;
        $this->product_id = $line->purchasable->id;
		$this->quantity = $line->qty;
		$this->unit_price = $tax->getUnitGross();
		$this->tax_rate = $tax->getTaxRate();
		$this->total_amount = $tax->getTotalGross();
		$this->total_tax_amount = $tax->getTaxTotal();
	}

	/**
	 * Populate the model based on shipping method
	 *
	 * @param ShippingMethodInterface $method
	 * @param Order                   $order
	 */
	public function shipping(ShippingMethodInterface $method, Order $order)
	{
		$tax_excluded = 0;
		$tax_included = 0;
		$shipping_base_price = 0;
		foreach ($order->getAdjustments() as $adjustment) {
			if($adjustment->type == 'shipping' && $adjustment->lineItemId == null) {
				$shipping_base_price += $adjustment->amount;
			}
			if(isset($adjustment->sourceSnapshot['taxable']) && $adjustment->sourceSnapshot['taxable'] == 'order_total_shipping') {
				if($adjustment->included == "1") $tax_included+=$adjustment->amount;
				else $tax_excluded+=$adjustment->amount;
			}
		}
		$this->unit_price = (int) (($shipping_base_price+$tax_excluded)*100);
		$this->quantity = 1;
		$this->name = $method->getName();
		$this->total_amount = (int) (($shipping_base_price+$tax_excluded)*100*$this->quantity);
		$this->total_tax_amount = (int) (($tax_included+$tax_excluded)*100);
		$this->tax_rate = (int) round((($tax_excluded+$tax_included)/($shipping_base_price-$tax_included))*10000);
	}

	/**
	 * Returns total line tax in fractional denomination
	 *
	 * @return int
	 */
	public function getLineTax()
	{
		return $this->total_tax_amount;
	}
}