<?php


namespace ellera\commerce\klarna\klarna\session;

use ellera\commerce\klarna\gateways\Base;
use ellera\commerce\klarna\klarna\KlarnaResponse;
use ellera\commerce\klarna\models\forms\BasePaymentForm;

class Create extends KlarnaResponse
{
    /**
     * Create constructor.
     * @param Base $gateway
     * @param BasePaymentForm $form
     * @param string $payment_session_url
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct(Base $gateway, BasePaymentForm $form, string $payment_session_url)
    {
        parent::__construct($gateway);

        $this->endpoint = '/hpp/v1/sessions';

        // TODO: Make this function
        $this->body = $form->generateCreateSessionRequestBody();

        $this->post();

        if(isset($this->response->session_id)) $this->setTransactionReference($this->response->session_id);
        else $this->setTransactionReference('!No Ref');
    }
}