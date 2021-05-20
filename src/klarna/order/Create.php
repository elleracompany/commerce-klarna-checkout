<?php


namespace ellera\commerce\klarna\klarna\order;

use ellera\commerce\klarna\gateways\Base;
use ellera\commerce\klarna\klarna\KlarnaResponse;
use ellera\commerce\klarna\models\forms\BasePaymentForm;

class Create extends KlarnaResponse
{
    public function isSuccessful(): bool
    {
        return false;
    }

    public function isProcessing(): bool
    {
        return true;
    }

    public function isRedirect(): bool
    {
        return false;
    }

    /**
     * Create constructor.
     * @param Base $gateway
     * @param BasePaymentForm $form
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct(Base $gateway, BasePaymentForm $form)
    {
        parent::__construct($gateway);

        $this->endpoint = '/checkout/v3/orders';

        $this->body = $form->generateCreateOrderRequestBody();

        $this->post();

        if(isset($this->response->order_id)) {
            $this->setTransactionReference($this->response->order_id);
        }
        else $this->setTransactionReference('!No Ref');
    }
}