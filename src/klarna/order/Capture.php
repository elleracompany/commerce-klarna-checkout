<?php


namespace ellera\commerce\klarna\klarna;

use ellera\commerce\klarna\gateways\Base;
use craft\commerce\models\Transaction;

class Capture extends KlarnaResponse
{
    /**
     * Capture constructor.
     * @param Base $gateway
     * @param Transaction $transaction
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct(Base $gateway, Transaction $transaction)
    {
        parent::__construct($gateway);

        $this->endpoint = "/ordermanagement/v1/orders/{$transaction->reference}/captures";

        $this->body = [
            'captured_amount' => (int)$transaction->paymentAmount * 100,
            'description' => $transaction->hash
        ];

        $this->post();
    }
}