<?php


namespace ellera\commerce\klarna\klarna;

use ellera\commerce\klarna\gateways\Base;

class Acknowledge extends KlarnaResponse
{
    /**
     * Acknowledge constructor.
     * @param Base $gateway
     * @param string $klarnaId
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
	public function __construct(Base $gateway, string $klarnaId)
	{
	    parent::__construct($gateway);

		$this->endpoint = "/ordermanagement/v1/orders/{$klarnaId}/acknowledge";

		$this->get();

		if(isset($this->response->order_id)) $this->setTransactionReference($this->response->order_id);
	}
}