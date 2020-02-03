<?php


namespace ellera\commerce\klarna\klarna\order;

use Craft;
use craft\commerce\Plugin as Commerce;
use ellera\commerce\klarna\gateways\Base;
use craft\commerce\models\Transaction;
use ellera\commerce\klarna\klarna\KlarnaResponse;

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

        if(Craft::$app->plugins->getPlugin('commerce')->is(Commerce::EDITION_LITE)) $order_lines = $gateway->getOrderLinesLite($transaction->order, $gateway);
        else $order_lines = $gateway->getOrderLines($transaction->order, $gateway);

        $this->body = [
            'captured_amount' => (int)$transaction->paymentAmount * 100,
            'description' => $transaction->hash,
            'order_lines' => $order_lines[1]
        ];

        $this->post();
    }
}