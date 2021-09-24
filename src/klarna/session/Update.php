<?php


namespace ellera\commerce\klarna\klarna\session;


use ellera\commerce\klarna\gateways\Base;
use ellera\commerce\klarna\klarna\KlarnaResponse;
use ellera\commerce\klarna\models\forms\HostedForm;

class Update extends KlarnaResponse
{
    private $redirect_url;

    public function getRedirectUrl(): string
    {
        return $this->redirect_url;
    }

    public function isSuccessful(): bool
    {
        return 200 <= $this->raw_response->getStatusCode() && $this->raw_response->getStatusCode() < 300;
    }

    public function isProcessing(): bool
    {
        return false;
    }

    public function isRedirect(): bool
    {
        return true;
    }

    public function __construct(Base $gateway, HostedForm $form)
    {
        parent::__construct($gateway);
        try {

            $last_response = json_decode($form->transaction->order->lastTransaction->response);
            $this->endpoint = '/checkout/v3/orders/' . $form->transaction->order->lastTransaction->reference;

            $this->body = $form->generateCreateOrderRequestBody();

            $this->post();

            if(isset($this->response->order_id)) {
                $this->setTransactionReference($this->response->order_id);
            }
            else $this->setTransactionReference('!No Ref');

            $this->redirect_url = $last_response->redirect_url;

            $this->get();

            $this->setTransactionReference($form->transaction->order->lastTransaction->reference);

        } catch (\Exception $e) {
            // Silent catch. Empty
        }
    }
}