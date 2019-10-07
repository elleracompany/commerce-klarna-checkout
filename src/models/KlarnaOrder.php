<?php


namespace ellera\commerce\klarna\models;

class KlarnaOrder
{
	/**
	 * API Response
	 *
	 * @var KlarnaBaseResponse
	 */
	public $response;

	/**
	 * KlarnaOrder constructor.
	 *
	 * @param KlarnaBaseResponse $response
	 */
	public function __construct(KlarnaBaseResponse $response)
	{
		$this->response = $response;
	}

	/**
	 * Returns iFrame HTML markup from Klarna
	 *
	 * @return string
	 */
	public function getHtmlSnippet() : string
	{
		return $this->response->get()->html_snippet;
	}

	/**
	 * Returns the Klarna Internal Order ID
	 *
	 * @return string
	 */
	public function getOrderId() : string
	{
		return isset($this->response->get()->order_id) ? $this->response->get()->order_id : false;
	}

	/**
	 * Returns the Klarna order Currency
	 *
	 * @return string
	 */
	public function getCurrency() : string
	{
		return $this->response->get()->purchase_currency;
	}

	/**
	 * Returns the e-mail from billing address
	 *
	 * @return string
	 */
	public function getEmail() : string
	{
		return $this->response->get()->billing_address->email;
	}

	/**
	 * Returns the Order Total in fractional denomination
	 *
	 * @return float
	 */
	public function getOrderAmount() : float
	{
		return $this->response->get()->order_amount/100;
	}
}