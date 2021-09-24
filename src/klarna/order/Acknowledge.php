<?php


namespace ellera\commerce\klarna\klarna\order;

use ellera\commerce\klarna\gateways\Base;
use ellera\commerce\klarna\klarna\KlarnaResponse;

class Acknowledge extends KlarnaResponse
{
    public function isSuccessful(): bool
    {
        return 200 <= $this->raw_response->getStatusCode() && $this->raw_response->getStatusCode() < 300;
    }

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

		$this->post();

		if(isset($this->response->order_id)) $this->setTransactionReference($this->response->order_id);
	}
}