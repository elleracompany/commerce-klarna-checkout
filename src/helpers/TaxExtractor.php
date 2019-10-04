<?php


namespace ellera\commerce\klarna\helpers;


use craft\commerce\models\LineItem;

class TaxExtractor
{
	/**
	 * Order Line from Constructor
	 *
	 * @var LineItem
	 */
	private $line;

	/**
	 * Tax included in the base price in
	 * fractional denomination
	 *
	 * @var float|int
	 */
	private $included;

	/**
	 * Tax not included in the base price in
	 * fractional denomination
	 *
	 * @var float|int
	 */
	private $excluded;

	public function __construct(LineItem $line)
	{
		$this->line = $line;
		$this->included = $line->getAdjustmentsTotalByType('tax', true);
		$this->excluded = $line->getAdjustmentsTotalByType('tax', false);
	}

	/**
	 * Returns the total tax for the order line in
	 * fractional denomination
	 *
	 * @return int
	 */
	public function getTaxTotal() : int
	{
		return (int)round(($this->included + $this->excluded)*100);
	}

	/**
	 * Returns the tax rate in percent,
	 * with two implicit decimals
	 *
	 * @return int
	 */
	public function getTaxRate() : int
	{
		return (int)round(($this->getTaxTotal()/$this->getTotalNet())*10000);
	}

	/**
	 * Returns the tax for one unit from the
	 * order line in fractional denomination
	 *
	 * @return int
	 */
	public function getTaxUnit()
	{
		return (int)($this->getTaxTotal()/$this->line->qty);
	}

	/**
	 * Returns the gross price for the
	 * order line
	 *
	 * @return int
	 */
	public function getTotalGross() : int
	{
		return (int)($this->getTotalNet()+$this->getTaxTotal());
	}

	/**
	 * Returns the gross price for one
	 * unit from the order line
	 *
	 * @return int
	 */
	public function getUnitGross() : int
	{
		return $this->getTotalGross()/$this->line->qty;
	}

	/**
	 * Returns the net price for the
	 * order line
	 *
	 * @return int
	 */
	public function getTotalNet() : int
	{
		return (int)round(($this->line->getTotal()*100)-$this->getTaxTotal());
	}

	/**
	 * Returns the net price for one
	 * unit from the order line
	 *
	 * @return int
	 */
	public function getUnitNet() : int
	{
		return (int)$this->getTotalNet()/$this->line->qty;
	}

	public function debug() : array
	{
		return [
			'tax_included' => $this->included,
			'tax_excluded' => $this->excluded,
			'tax_rate' => $this->getTaxRate(),
			'line_total' => $this->line->getTotal(),
			'line_tax' => $this->getTaxUnit(),
			'line_net' => $this->getTotalNet(),
			'line_gross' => $this->getTotalGross()
		];
	}
}