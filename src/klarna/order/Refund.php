<?php


namespace ellera\commerce\klarna\klarna\order;

use Craft;
use craft\commerce\Plugin as Commerce;
use ellera\commerce\klarna\gateways\Base;
use craft\commerce\models\Transaction;
use ellera\commerce\klarna\klarna\KlarnaResponse;

class Refund extends KlarnaResponse
{
    /**
     * Refund constructor.
     * @param Base $gateway
     * @param Transaction $transaction
     * @param int $amount
     * @param string $note
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct(Base $gateway, Transaction $transaction, int $amount = null, string $note = '')
    {
        parent::__construct($gateway);

        if($amount == '') $amount = $transaction->order->totalPaid;

        $this->endpoint = "/ordermanagement/v1/orders/{$transaction->reference}/refunds";

        $this->body = [
            'refunded_amount' => $amount,
            'description' => $note
        ];

        $this->post();
    }
}