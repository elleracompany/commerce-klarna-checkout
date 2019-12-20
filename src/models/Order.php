<?php


namespace ellera\commerce\klarna\models;

use ellera\commerce\klarna\klarna\KlarnaResponse;

class Order
{
	/**
	 * API Response
	 *
	 * @var KlarnaResponse
	 */
	public $response;

	/**
	 * KlarnaOrder constructor.
	 *
	 * @param KlarnaResponse $response
	 */
	public function __construct(KlarnaResponse $response)
	{
		$this->response = $response;
	}

    /**
     * Returns iFrame HTML markup from Klarna
     *
     * @return string
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
	public function getHtmlSnippet() : string
	{
		return $this->response->getDecodedResponse()->html_snippet;
	}

    /**
     * Returns the Klarna Internal Order ID
     *
     * @return string
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
	public function getOrderId() : string
	{
		return isset($this->response->getDecodedResponse()->order_id) ? $this->response->getDecodedResponse()->order_id : false;
	}

    /**
     * Returns the Klarna order Currency
     *
     * @return string
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
	public function getCurrency() : string
	{
		return $this->response->getDecodedResponse()->purchase_currency;
	}

    /**
     * Returns the e-mail from billing address
     *
     * @return string
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
	public function getEmail() : string
	{
		return $this->response->getDecodedResponse()->billing_address->email;
	}

    /**
     * Returns the Order Total in fractional denomination
     *
     * @return float
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
	public function getOrderAmount() : float
	{
		return $this->response->getDecodedResponse()->order_amount/100;
	}
}