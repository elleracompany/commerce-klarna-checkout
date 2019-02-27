<?php

namespace ellera\commerce\klarna\models;

use craft\base\Model;
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
		$this->quantity = $line->qty;
		$this->unit_price = $tax->getUnitGross();
		$this->tax_rate = $tax->getTaxRate();
		$this->total_amount = $tax->getTotalGross();
		$this->total_tax_amount = $tax->getTaxTotal();
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