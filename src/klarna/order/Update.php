<?php


namespace ellera\commerce\klarna\klarna;

use ellera\commerce\klarna\gateways\Base;
use craft\commerce\elements\Order;

class Update extends KlarnaResponse
{
    /**
     * Update constructor.
     * @param Base $gateway
     * @param Order $order
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct(Base $gateway, Order $order)
    {
        parent::__construct($gateway);

        $this->endpoint = '/ordermanagement/v1/orders/' . $order->getLastTransaction()->reference;

        $this->get();

        if(isset($this->response->order_id)) $this->setTransactionReference($this->response->order_id);
    }
}