<?php


namespace ellera\commerce\klarna\models;


use craft\commerce\models\Transaction;
use ellera\commerce\klarna\gateways\BaseGateway;
use ellera\commerce\klarna\gateways\KlarnaHPP;

class KlarnaHppPaymentForm extends KlarnaBasePaymentForm
{
    /**
     * @param Transaction $transaction
     * @param BaseGateway $gateway
     * @throws \craft\errors\SiteNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function populate(Transaction $transaction, BaseGateway $gateway): void
    {
        parent::populate($transaction, $gateway);

        /** @var $gateway KlarnaHPP */
        $this->merchant_urls = [
            'back' => $this->getStoreUrl().'back?hppId={{session_id}}',
            'cancel' => $this->getStoreUrl().'cancel?hppId={{session_id}}',
            'error' => $this->getStoreUrl().'error?hppId={{session_id}}',
            'failure' => $this->getStoreUrl().'failure?hppId={{session_id}}',
            'privacy_policy' => $this->getStoreUrl().'privacy_policy?hppId={{session_id}}',
            'status_update' => $this->getStoreUrl().'status_update?hppId={{session_id}}',
            'success' => $this->getStoreUrl().'success?hppId={{session_id}}&token={{authorization_token}}',
            'terms' => $this->getStoreUrl().$gateway->terms,
        ];
        $this->request_body = [
            'merchant_urls' => $this->hpp_merchant_urls,
            'options' => [
                'background_images' => [
                    [
                        'url' => 'https://ellera.no/images/mustbereplaced-main.jpg',
                        'width' => 1440
                    ]
                ],
                'logo_url' => 'https://ellera.no/images/ellera_black_transp.png',
                'page_title' => 'Complete your purchase',
                'payment_fallback' => true,
                'purchase_type' => 'buy'
            ],
            'payment_session_url' => null
        ];
        if(is_array($gateway->methods) && count($gateway->methods) == 1)
        {
            $this->request_body['options']['payment_method_category'] = $gateway->methods[0];
        }
        else {
            $methods = $gateway->methods;
            if(!$methods || empty($methods)) $methods = [];
            $this->request_body['options']['payment_method_categories'] = $methods;
        }
    }
}